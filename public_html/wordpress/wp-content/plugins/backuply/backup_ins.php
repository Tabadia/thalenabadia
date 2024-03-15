<?php
/*
* BACKUPLY
* https://backuply.com
* (c) Backuply Team
*/

//PHP Options
if(!set_time_limit(300)){
	set_time_limit(60);
}
error_reporting(E_ALL);
ignore_user_abort(true);

//Constants
define('ARCHIVE_TAR_ATT_SEPARATOR', 90001);
define('ARCHIVE_TAR_END_BLOCK', pack('a512', ''));

include_once BACKUPLY_DIR . '/backuplytar.php'; // Including the TAR class
	
function backuply_can_create_file(){
	$file = BACKUPLY_BACKUP_DIR . '/soft.tmp';
	$fp = @fopen($file, 'wb');
	if($fp === FALSE){
		return false;
	}
	
	if(@fwrite($fp, 'backuply') === FALSE){
		return false;
	}
	
	@fclose($fp);
	
	// Check if the file exists
	
	if(file_exists($file)){
		@unlink($file);
		return true;
	}
	
	return false;	
}

function backuply_tar_archive($tarname, $file_list, $handle_remote = false){
	backuply_log('Archiving your WP INSTALL Now');
	$tar_archive = new backuply_tar($tarname, '', $handle_remote);
	
	$res = $tar_archive->createModify($file_list, '', '');
	
	if(!$res){
		return false;	
	}
	
	//backuply_log('Backup Process Completed !!');
	return true;
}

function backuply_resetfilelist(){
	global $directorylist;
	$directorylist = array();
}

// Back up the database !!!
function backuply_mysql_fn($shost, $suser, $spass, $sdb, $sdbfile){
	//echo $shost.' == '. $suser.' == '. $spass.' == '. $sdb.' == '. $sdbfile;
	
	global $data, $backuply;
	
	$link = backuply_mysql_connect($shost, $suser, $spass);
	
	backuply_mysql_query('SET CHARACTER SET utf8mb4', $link);
	
	// Open and create a file handle for sql.
	$handle = fopen($sdbfile,'a');
	
	$s_def = $alter_queries = $sresponse = '';
	$sql_alter = $tables = array();
	
	$ser_ver = backuply_PMA_sversion($link);
	$s_def = backuply_PMA_exportHeader($sdb, $ser_ver);
	if(empty($GLOBALS['backup_tables'])){
		fwrite($handle, $s_def);
	}
		
	// We did not create the database ! So just backup the tables required for this database
	if(!empty($data['exclude_db'])){
		
		$thisdb_tables = $data['exclude_db'];
		
		if(!is_array($data['exclude_db'])){
			$thisdb_tables = unserialize($data['exclude_db']);
		}
		
		// This is just to remove the ` since we are not getting it in $tables below
		foreach($thisdb_tables as $tk => $tv){
			// There was a bug since Softaculous 4.7.2 that did not save exclude_db for ins causing empty array. Fixed in Softaculous 4.7.7
			if(empty($tv)) continue;
			$_thisdb_tables[trim($tk, '`')] = trim($tv, '`');
		}
	}

	//List Views
	$squery = backuply_mysql_query('SHOW TABLE STATUS FROM `' . $sdb . '` WHERE COMMENT = \'VIEW\'', $link);
	
	$views = array();	
	if(backuply_mysql_num_rows($squery) > 0){
		while($row = backuply_mysql_fetch_row($squery)){
			$views[] = $row[0];
		}
	}
	
	// Sort the views
	usort($views, 'strnatcasecmp');
	
	// List the tables
	$squery = backuply_mysql_query('SHOW TABLES FROM `' . $sdb . '`', $link);
	
	while($row = backuply_mysql_fetch_row($squery)){
		
		// We do not need to backup this table
		if(!empty($_thisdb_tables) && is_array($_thisdb_tables) && in_array($row[0], $_thisdb_tables)){
			continue;
		}
		
		if(in_array($row[0], $views)){
			continue;
		}
		
		$tables[] = $row[0];
	}
	
	// Sort the tables
	usort($tables, 'strnatcasecmp');	
	
	foreach($tables as $table => $v){
		// We are resuming so we dont need to backup every table again
		if(!empty($GLOBALS['backup_tables']) && in_array($v, $GLOBALS['backup_tables'])){
			continue;
		}

		backuply_backup_stop_checkpoint();
		
		if(empty($GLOBALS['db_in_progress'])){	
			backuply_status_log('Adding (L'.$backuply['status']['loop'].') : '. $v .' table', 'working', 50);
			// Get the table structure(table definition)
			$stable_defn = backuply_PMA_getTableDef($sdb, $v, "\n", false, true, $link);
			
			$s_def = $stable_defn['structure']."\n";
			fwrite($handle, $s_def);
		} else {
			backuply_status_log('Resuming (L'.$backuply['status']['loop'].') : '. $GLOBALS['db_in_progress'] .' table', 'working', 52);
		}
		
		// Get the table data(table contents)
		// We have added $handle so that we can write the INSERT queries directly when we get it. 
		// Basically To avoid MEMORY EXHAUST FOR  BIG INSERTS
		backuply_PMA_exportData($sdb, $v, "\n", $handle, $link);
		$GLOBALS['backup_tables'][] = $v;
		
		// List of alter queries 
		// We have changed this because the OLD method was putting the ALTER queries after CREATE table query which was causing issues.
		if(!empty($stable_defn['alter'])){
			$alter_queries .= $stable_defn['alter'];
		}
	}
	
	//Save Views
	foreach($views as $view){
		
		$defn = backuply_PMA_getViews($sdb, $view, "\n", $link);
		
		$view_def = $defn['structure']."\n";
		fwrite($handle, $view_def);
	}
	
	fwrite($handle, $alter_queries);
	
	//List Triggers/Events/Procedures/Functions	
	//Triggers
	$triggers = backuply_PMA_getTriggers($sdb, $link);
	foreach($triggers as $trigger){
		fwrite($handle, "\n".$trigger['drop']."\nDELIMITER //\n");
		fwrite($handle, $trigger['create']."// \nDELIMITER ;\n\n");
	}
	
	//Events
	$events = backuply_PMA_getEvents($sdb, $link);
	foreach($events as $event){
		fwrite($handle, "\n".$event['drop']."\nDELIMITER $$ \n-- \n-- Events \n--\n");
		fwrite($handle, $event['create']);
		fwrite($handle, "\n$$ \nDELIMITER ;\n\n");
	}
	
	//Functions
	$functions = backuply_PMA_getProceduresOrFunctions($sdb, 'FUNCTION', $link);
	foreach($functions as $function){
		fwrite($handle, "\n".$function['drop']."\nDELIMITER $$ \n-- \n-- Functions \n--\n");
		fwrite($handle, $function['create']);
		fwrite($handle, "\n$$ \nDELIMITER ;\n\n");
	}
	
	//Procedures
	$procedures = backuply_PMA_getProceduresOrFunctions($sdb, 'PROCEDURE', $link);
	foreach($procedures as $procedure){
		fwrite($handle, "\n".$procedure['drop']."\nDELIMITER $$ \n-- \n-- Procedures \n--\n");
		fwrite($handle, $procedure['create']);
		fwrite($handle, "\n$$ \nDELIMITER ;\n\n");
	}	
	
	$sresponse = backuply_PMA_exportFooter(); // Just to add the finishing lines
	fwrite($handle, $sresponse);
	fclose($handle);
	
	backuply_backup_stop_checkpoint();
	// Just check that file is created or not ??
	if(file_exists($sdbfile)){
	
		return true;
	}
	
	return false;
	
} //End of database backup

function backuply_PMA_getViews($db, $view, $crlf, $link){
	
	$schema_create = $auto_increment = $dump = '';
	$new_crlf = $crlf;
	
	// This is for foreign language characters
	//To read the values from the old DB in UTF8 format
	//backuply_mysql_query('SET NAMES "utf8mb4"', $link);
	
	// Complete view dump,
	// Whether to quote view and fields names or not
	backuply_mysql_query('SET SQL_QUOTE_SHOW_CREATE = 1', $link);
	
	// Create view structure
	$result = backuply_mysql_query('SHOW CREATE VIEW `'.$db.'`.`'.$view.'`', $link);

	// Construct the dump for the view structure
	$dump .=  '--' . $crlf
			. '-- Structure for view ' . '`' . $view.'`' . $crlf
			. '--' . $crlf
			. 'DROP VIEW IF EXISTS `' . $view . '`;' . $crlf . $crlf;
	
	if ($row = backuply_mysql_fetch_assoc($result)) {
			
		$create_query = $row['Create View'];
		
		preg_match('/DEFINER=(.*?) SQL/is', $create_query, $matches);
		$create_query = str_replace($matches[1], 'CURRENT_USER', $create_query);

		$schema_create .= $new_crlf . $dump;

		// Convert end of line chars to one that we want (note that MySQL doesn't return query it will accept in all cases)
		if (strpos($create_query, "(\r\n ")) {
			$create_query = str_replace("\r\n", $crlf, $create_query);
		} elseif (strpos($create_query, "(\n ")) {
			$create_query = str_replace("\n", $crlf, $create_query);
		} elseif (strpos($create_query, "(\r ")) {
			$create_query = str_replace("\r", $crlf, $create_query);
		}
		
		$schema_create .= $create_query;
	}
		
	backuply_mysql_free_result($result);
		
	// Dump the structure !!!
	$return['structure'] = $schema_create . ';' . $crlf;
	
	return $return;
}

function backuply_PMA_getTriggers($db, $link){
	$query = backuply_mysql_query('SHOW TRIGGERS FROM `' . $db . '`', $link);
	$result = array(); //added as empty so don't give warning when data is empty..
	
	while($trigger = backuply_mysql_fetch_assoc($query)){
		
		$one_result = array();
		$one_result['name'] = $trigger['Trigger'];
		$one_result['table'] = $trigger['Table'];
		$one_result['action_timing'] = $trigger['Timing'];
		$one_result['event_manipulation'] = $trigger['Event'];
		$one_result['definition'] = $trigger['Statement'];
		$one_result['definer'] = $trigger['Definer'];
		$one_result['full_trigger_name'] = '`'.$trigger['Trigger'].'`';
		$one_result['drop'] = 'DROP TRIGGER IF EXISTS `' . $db .'`.'. $one_result['full_trigger_name'].';';
		$one_result['create'] = 'CREATE TRIGGER '
			. $one_result['full_trigger_name'] . ' '
			. $trigger['Timing'] . ' '
			. $trigger['Event']
			. ' ON ' . '`'. $trigger['Table'].'`'
			. "\n" . ' FOR EACH ROW '
			. $trigger['Statement'] . "\n" . $delimiter . "\n";
			
		$result[] = $one_result;
	}

	// Sort results by name
	$name = array();
	foreach ($result as $value) {
		$name[] = $value['name'];
	}
	array_multisort($name, SORT_ASC, $result);
	
	return($result);
	
}

function backuply_PMA_getEvents($db, $link){
	
	$query = backuply_mysql_query('SHOW EVENTS FROM `' . $db . '`', $link);

	$result = array();
	while ($event = backuply_mysql_fetch_assoc($query)) {
			$one_result = array();
			$one_result['name'] = $event['Name'];
			$one_result['type'] = $event['Type'];
			$one_result['status'] = $event['Status'];
			$one_result['drop'] = 'DROP EVENT IF EXISTS `' . $db .'`.`'. $one_result['name'].'`;';
			$one_result['create'] = backuply_PMA_getDefinition($db, 'EVENT', $one_result['name'], $link);
			
			$result[] = $one_result;
	}
	
	// Sort results by name
	$name = array();
	foreach ($result as $value) {
		$name[] = $value['name'];
	}
	array_multisort($name, SORT_ASC, $result);

	return $result;
}

/**
 * returns the array of PROCEDURE/FUNCTION names
 *
 * @param string $db	db name
 * @param string $which PROCEDURE | FUNCTION | EVENT
 * @param string $link  connection link to the database
 *
 * @return array names of Procedures/Functions
 */
function backuply_PMA_getProceduresOrFunctions($db, $which, $link)
{
	$query = backuply_mysql_query('SHOW ' . $which . ' STATUS;', $link);
	$result = array();
	
	while($one_show = backuply_mysql_fetch_assoc($query)) {
		if ($one_show['Db'] == $db && $one_show['Type'] == $which) {
			$one_show['drop'] = 'DROP '.$which.' IF EXISTS `' . $db .'`.`'. $one_show['Name'].'`;';
			$one_show['create'] = backuply_PMA_getDefinition($db, $which, $one_show['Name'], $link);
			
			$result[] = $one_show;
		}
	}
	
	return $result;
}

/**
 * returns the definition of a specific PROCEDURE, FUNCTION or EVENT
 *
 * @param string $db	db name
 * @param string $which PROCEDURE | FUNCTION | EVENT
 * @param string $name  the procedure|function|event name
 * @param string $link  connection link to the database
 *
 * @return string the definition
 */
function backuply_PMA_getDefinition($db, $which, $name, $link)
{
	$returned_field = array(
		'PROCEDURE' => 'Create Procedure',
		'FUNCTION'  => 'Create Function',
		'EVENT'	 => 'Create Event'
	);
	$query = backuply_mysql_query('SHOW CREATE '.$which.' `'.$db.'`.`'.$name.'`;', $link);
	
	if ($res = backuply_mysql_fetch_assoc($query)){
		return($res[$returned_field[$which]]);
	}
	
}

// Internal function to add slashes to row values 
function backuply_PMA_sqlAddslashes(&$a_string = '', $is_like = false, $crlf = false, $php_code = false) {

	if ($is_like) {
		$a_string = str_replace('\\', '\\\\\\\\', $a_string);
	} else {
		$a_string = str_replace('\\', '\\\\', $a_string);
	}

	if ($crlf) {
		$a_string = str_replace("\n", '\n', $a_string);
		$a_string = str_replace("\r", '\r', $a_string);
		$a_string = str_replace("\t", '\t', $a_string);
	}

	if ($php_code) {
		$a_string = str_replace('\'', '\\\'', $a_string);
	} else {
		$a_string = str_replace('\'', '\'\'', $a_string);
	}

	return $a_string;
} // end of the 'backuply_PMA_sqlAddslashes()' function


// Form the table structure && the alter queries if any !! 
function backuply_PMA_getTableDef($db, $table, $crlf, $show_dates, $add_semicolon, $link) {
	
	global $sql_drop_table, $sql_alter;
	global $sql_constraints;
	global $sql_constraints_query; // just the text of the query
	global $sql_drop_foreign_keys;

	$schema_create = $auto_increment = $sql_constraints = '';
	$new_crlf = $crlf;
	
	// Get the Status of the table so as to produce the auto increment value
	$qresult = backuply_mysql_query('SHOW TABLE STATUS FROM `'.$db.'` LIKE \''.$table.'\'', $link);

	// Handle auto-increment values
	if (backuply_mysql_num_rows($qresult) > 0) {
		
		$tmpres = backuply_mysql_fetch_assoc($qresult);
		
		// Is auto-increment value is set ??
		if(!empty($tmpres['Auto_increment'])){
			$auto_increment .= ' AUTO_INCREMENT=' . $tmpres['Auto_increment'] . ' ';
		}
	
	}
	// Free resourse
	backuply_mysql_free_result($qresult);
	
	//added as empty so don't give warning when data is empty..
	$dump = '';
	
	// Adding query to delete any existing table if exists.
	$dump .= '-- Delete any existing table `' . $table . '`' . $crlf;
	$dump .= 'DROP TABLE IF EXISTS `'.$table.'`;' . $crlf;

	// Construct the dump for the table structure
	$dump .=  '--' . $crlf
			. '-- Table structure for table ' . '`' . $table.'`' . $crlf
			. '--' . $crlf . $crlf;
		 
	$schema_create .= $new_crlf . $dump;

	// Complete table dump,
	// Whether to quote table and fields names or not
	backuply_mysql_query('SET SQL_QUOTE_SHOW_CREATE = 1', $link);
	
	// Create table structure
	$result = backuply_mysql_query('SHOW CREATE TABLE `'.$db.'`.`'.$table.'`', $link);
	
	if ($row = backuply_mysql_fetch_assoc($result)) {
		
		$create_query = $row['Create Table'];
		unset($row);

		// Convert end of line chars to one that we want (note that MySQL doesn't return query it will accept in all cases)
		if (strpos($create_query, "(\r\n ")) {
			$create_query = str_replace("\r\n", $crlf, $create_query);
		} elseif (strpos($create_query, "(\n ")) {
			$create_query = str_replace("\n", $crlf, $create_query);
		} elseif (strpos($create_query, "(\r ")) {
			$create_query = str_replace("\r", $crlf, $create_query);
		}

		// are there any constraints to cut out?
		if (preg_match('@CONSTRAINT|FOREIGN[\s]+KEY@', $create_query)) {

			// Split the query into lines, so we can easily handle it.
			// We know lines are separated by $crlf (done few lines above).	
			$sql_lines = explode($crlf, $create_query);
			$sql_count = count($sql_lines);

			// Lets find first line with constraints
			for ($i = 0; $i < $sql_count; $i++) {
				if (preg_match('@^[\s]*(CONSTRAINT|FOREIGN[\s]+KEY)@', $sql_lines[$i])) {
				 	break;
				}
			}

			// If we really found a constraint
			if ($i != $sql_count) {
				
				// remove , from the end of create statement
				$sql_lines[$i - 1] = preg_replace('@,$@', '', $sql_lines[$i - 1]);

				// comments for current table
				$sql_constraints .= $crlf
								 . backuply_PMA_exportComment()
								 . backuply_PMA_exportComment('Constraints for table ' . '`' . $table.'`')
								 . backuply_PMA_exportComment();
				
				// Let's do the work
				$sql_constraints_query .= 'ALTER TABLE `'.$table.'`' . $crlf;
				$sql_constraints .= 'ALTER TABLE `'.$table.'`' . $crlf;
				$sql_drop_foreign_keys .= 'ALTER TABLE `'.$table.'` `'.$db.'`' . $crlf;

				$first = TRUE;
				for ($j = $i; $j < $sql_count; $j++) {
					if (preg_match('@CONSTRAINT|FOREIGN[\s]+KEY@', $sql_lines[$j])) {
						if (!$first) {
							$sql_constraints .= $crlf;
						}
						if (strpos($sql_lines[$j], 'CONSTRAINT') === FALSE) {
							$tmp_str = preg_replace('/(FOREIGN[\s]+KEY)/', 'ADD \1', $sql_lines[$j]);
							$sql_constraints_query .= $tmp_str;
							$sql_constraints .= $tmp_str;
						} else {
							$tmp_str = preg_replace('/(CONSTRAINT)/', 'ADD \1', $sql_lines[$j]);
							$sql_constraints_query .= $tmp_str;
							$sql_constraints .= $tmp_str;
							preg_match('/(CONSTRAINT)([\s])([\S]*)([\s])/', $sql_lines[$j], $matches);
							if (! $first) {
								$sql_drop_foreign_keys .= ', ';
							}
							$sql_drop_foreign_keys .= 'DROP FOREIGN KEY ' . $matches[3];
						}
						$first = FALSE;
					} else {
						break;
					}
				}
				$sql_constraints .= ';' . $crlf;
				$sql_constraints_query .= ';';
				
				// Dump the alter queries!!!
				$return['alter'] = $sql_constraints; 
				
				$create_query = implode($crlf, array_slice($sql_lines, 0, $i)) . $crlf . implode($crlf, array_slice($sql_lines, $j, $sql_count - 1));
				unset($sql_lines);
			}
		}
		$schema_create .= $create_query;
	}

	// remove a possible "AUTO_INCREMENT = value" clause
	// that could be there starting with MySQL 5.0.24
	$schema_create = preg_replace('/AUTO_INCREMENT\s*=\s*([0-9])+/', '', $schema_create);

	$schema_create .= $auto_increment;
		
	backuply_mysql_free_result($result);
		
	// Dump the structure !!!
	$return['structure'] = $schema_create . ($add_semicolon ? ';' . $crlf : '');
	
	return $return;
	 
} // end of the 'backuply_PMA_getTableDef()' function

// Internal function to get meta details about the database 
function backuply_PMA_DBI_get_fields_meta($sresult) {
	$fields	   = array();
	$num_fields   = mysql_num_fields($sresult);
	for ($i = 0; $i < $num_fields; $i++) {
		$field = mysql_fetch_field($sresult, $i);
		$field->flags = mysql_field_flags($sresult, $i);
		$field->orgtable = mysql_field_table($sresult, $i);
		$field->orgname = mysql_field_name($sresult, $i);
		$fields[] = $field;
	}
	return $fields;
}

// Export data - values 
function backuply_PMA_exportData($db, $table, $crlf, $handle, $link){
	
	global $current_row, $backuply;
	$count = 10000;
	$limit = !empty($GLOBALS['database_row']) ? intval(esc_sql($GLOBALS['database_row'])) : 0;

	// We have modified this code because we were getting error if inserts were >50000
	if(strpos($table, 'options') !== false){
		$cnt_qry = 'SELECT count(*) FROM `'.$db . '`.`' . $table . '` WHERE option_name != "backuply_status"';
	}else{
		$cnt_qry = 'SELECT count(*) FROM `'.$db . '`.`' . $table . '`';	
	}
	
	$cnt_res = backuply_mysql_fetch_row(backuply_mysql_query($cnt_qry, $link));
	
	if(strpos($table, 'options') !== false){
		$sql_query  = 'SELECT * FROM `'.$db . '`.`' . $table . '` WHERE option_name != "backuply_status" LIMIT '.$limit.',10000';
	}else{
		$sql_query  = 'SELECT * FROM `'.$db . '`.`' . $table . '` LIMIT '.$limit.',10000';
	}
	
	$formatted_table_name = '`' . $table . '`';
	
	$squery = backuply_mysql_query($sql_query, $link);
	
	$fields_cnt = backuply_mysql_num_fields($squery);

	// Get field information
	if(extension_loaded('mysqli')){
		$fields_meta	= backuply_getFieldsMeta($squery);
	}else{
		$fields_meta	= backuply_PMA_DBI_get_fields_meta($squery);
	}
	
	$field_flags	= array();
	for ($j = 0; $j < $fields_cnt; $j++) {
		$field_flags[$j] = backuply_mysql_field_flags($squery, $j);
	}

	for ($j = 0; $j < $fields_cnt; $j++) {
		$field_set[$j] = '`'.$fields_meta[$j]->name . '`';
	}

	$sql_command = 'INSERT';
   
	$insert_delayed = '';
	$separator = ',';

	$schema_insert = $sql_command . $insert_delayed .' INTO `' . $table . '` VALUES';
	
	$search	   = array("\x00", "\x0a", "\x0d", "\x1a"); //\x08\\x09, not required
	$replace	  = array('\0', '\n', '\r', '\Z');
	$current_row  = !empty($GLOBALS['database_row']) ? intval($GLOBALS['database_row']) : 0;
	$new_query  = 0;
	$query_length = 0;
	
	if($GLOBALS['db_in_progress'] === $table && !empty($GLOBALS['database_row_done'])){
		$cnt_res[0] = intval($GLOBALS['database_row_done']);
	}

	$GLOBALS['db_in_progress'] = '';

	$schema_insert .= $crlf;
	for($i = $cnt_res[0]; $i >= 0; $i--){
		
		if(time() > $GLOBALS['end']) {
			backuply_log('Database Backup: Short on time!');

			if(empty($current_row)){
				backuply_status_log('Database Backup: Closing (L'.$backuply['status']['loop'].') : '.$table . ' at row number '. $current_row . ' ' . $i);

				$GLOBALS['db_in_progress'] = $table;
				backuply_die('INCOMPLETE_DB');
				die();
			}
			
			$GLOBALS['database_row'] = $current_row;
			$GLOBALS['database_row_done'] = $i;
			
			backuply_status_log('Database Backup: Closing (L'.$backuply['status']['loop'].') : '.$table . ' at row number '. $current_row . ' ' . $i);
			
			$query_buffer = ';' . $crlf;
			@fwrite($handle, $query_buffer);
			@fclose($handle);
			
			$GLOBALS['db_in_progress'] = $table;
			backuply_mysql_free_result($squery);
			
			backuply_die('INCOMPLETE_DB');
			die();
		}

		// Now if 10000 rows has been processed than select next.
		if($count == 0){
			// Now free the result for preventing memory exhaust
			backuply_mysql_free_result($squery);
			$count = 10000;
			$limit = $limit+10000;
			$sql_query  = 'SELECT * FROM `'.$db . '`.`' . $table . '` LIMIT '.($limit).', 10000';
			$squery= backuply_mysql_query($sql_query, $link);
		}
		
		$row = backuply_mysql_fetch_array($squery);
		
		// If we get empty result than break the loop
		if(!$row){
			break;
		}
		
		if ($current_row == 0) {
			$head = backuply_PMA_exportComment()
				  . backuply_PMA_exportComment('Dumping data for table' . ' ' . $formatted_table_name)
				  . backuply_PMA_exportComment()
				  . $crlf;
			fwrite($handle, $head);
		}
		$current_row++;
		
		if ($current_row == 1 || $new_query == 1 || !empty($GLOBALS['database_row'])) {
			fwrite($handle, $schema_insert .'(');
			$GLOBALS['database_row'] = null;
		}else{
			fwrite($handle, ','.$crlf.'(');
		}
		
		$add_comma = 0;
		for ($j = 0; $j < $fields_cnt; $j++) {
			
			$separator = ($add_comma > 0 ? ', ' : '');
			
			// NULL
			if (!isset($row[$j]) || is_null($row[$j])) {
				fwrite($handle, $separator . 'NULL');
			// a number
			// timestamp is numeric on some MySQL 4.1, BLOBs are sometimes numeric
			} elseif ($fields_meta[$j]->numeric && $fields_meta[$j]->type != 'timestamp' 
					&& !$fields_meta[$j]->blob) {
				fwrite($handle, $separator . $row[$j]);
			} elseif ($fields_meta[$j]->type == 'bit') {
				fwrite($handle, $separator . backuply_PMA_printableBitValue($row[$j], $fields_meta[$j]->length));
			} else { 
				backuply_PMA_sqlAddslashes($row[$j]);
				fwrite($handle, $separator . '\'' . str_replace($search, $replace, $row[$j]) . '\'');				
			} // end if
			
			if(!is_null($row[$j])){
				$query_length += strlen($row[$j]);
			}
			
			$add_comma++;
			$new_query = 0;
		} // end for
		
		fwrite($handle, ')');
		
		// Stop extended insert after 50K chars and open a new INSERT
		if($query_length > 50000){
			$query_buffer = ';' . $crlf;
			fwrite($handle, $query_buffer);
			$add_comma = 0;
			$new_query = 1;
			$query_length = 0;
		}
	
		// Decrement till 0 so that next 10000 rows can be selected
		$count--;
		
	}// End of FOR
	
	if ($current_row > 0) {
		$query_buffer = ';' . $crlf;
		fwrite($handle, $query_buffer);
	}
	
	// Free resourses
	backuply_mysql_free_result($squery);
	
	$end_line = (!empty($query_buffer) ? $crlf : '' ). backuply_PMA_exportComment('--------------------------------------------------------');
	fwrite($handle, $end_line);
	//return $query_buffer . $end_line;
		
} 

function backuply_PMA_exportComment($text = '')
{
	$crlf = "\n";
	$ret = '--' . (empty($text) ? '' : ' ') . $text . $crlf;
	return $ret;
}

function backuply_PMA_exportHeader($db, $ser_ver)
{
	$crlf = "\n";  

	$head  =  backuply_PMA_exportComment('Softaculous SQL Dump')
		   .  backuply_PMA_exportComment('http://www.softaculous.com')
		   .  backuply_PMA_exportComment()
		   .  backuply_PMA_exportComment('Host: localhost')
		   .  backuply_PMA_exportComment('Generation Time: '. date("F j, Y, g:i a") .'')
		   .  backuply_PMA_exportComment('Server version: '. $ser_ver .'')
		   .  backuply_PMA_exportComment('PHP Version' . ': ' . phpversion())
		   .  $crlf;

	/* We want exported AUTO_INCREMENT fields to have still same value, do this only for recent MySQL exports */
	$head .=  'SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";' . $crlf;
	
	/* Change timezone if we should export timestamps in UTC */
	$head .= 'SET time_zone = "+00:00";' . $crlf . $crlf;
  
	// by default we use the connection charset
	$set_names = 'utf8mb4';
		
	$head .=  $crlf
		   . '/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;' . $crlf
		   . '/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;' . $crlf
		   . '/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;' . $crlf
		   . '/*!40101 SET NAMES ' . $set_names . ' */;' . $crlf . $crlf;
	
	$head .= backuply_PMA_exportComment()
		  . backuply_PMA_exportComment('Database: `' . $db . '`')
		  . backuply_PMA_exportComment()
		  . $crlf
		  . backuply_PMA_exportComment('--------------------------------------------------------');

	return $head;

}

function backuply_PMA_exportFooter()
{
	$crlf = "\n";
	$foot = '';

	$foot .=  $crlf
	   . '/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;' . $crlf
	   . '/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;' . $crlf
	   . '/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;' . $crlf;
	
	return $foot;
}

function backuply_PMA_sversion($link){

	// Get version
	$version = backuply_mysql_query('SELECT VERSION()', $link);
	$version = backuply_mysql_fetch_assoc($version);
	
	// Explode to extract version
	$version = explode('-', $version['VERSION()']);
	return $version[0];
	
}

function backuply_PMA_printableBitValue($value, $length){
	// if running on a 64-bit server or the length is safe for decbin()
	if (PHP_INT_SIZE == 8 || $length < 33) {
		$printable = decbin($value);
	} else {
		// FIXME: does not work for the leftmost bit of a 64-bit value
		$i = 0;
		$printable = '';
		while ($value >= pow(2, $i)) {
			++$i;
		}
		if ($i != 0) {
			--$i;
		}

		while ($i >= 0) {
			if ($value - pow(2, $i) < 0) {
				$printable = '0' . $printable;
			} else {
				$printable = '1' . $printable;
				$value = $value - pow(2, $i);
			}
			--$i;
		}
		$printable = strrev($printable);
	}
	$printable = str_pad($printable, $length, '0', STR_PAD_LEFT);
	return $printable;
}

function backuply_mysql_connect($host, $user, $pass, $newlink = false){
	
	if(extension_loaded('mysqli')){
		//To handle connection if user passes a custom port along with the host as localhost:6446
		$exh = explode(':', $host);

		if(!empty($exh[1])){
			$sconn = @mysqli_connect($exh[0], $user, $pass, '', $exh[1]);
		}else{
			$sconn = @mysqli_connect($host, $user, $pass);
		}
	}else{
		//echo 'mysql';
		$sconn = @mysql_connect($host, $user, $pass, $newlink);
	}

	if(empty($sconn)){
		backuply_status_log(mysqli_connect_error(), 'error');
	}

	return $sconn;
}

function backuply_mysql_select_db($db, $conn){
	
	if(extension_loaded('mysqli')){
		$return = @mysqli_select_db($conn, $db);
	}else{
		$return = @mysql_select_db($db, $conn);
	}
	
	return $return;
}

function backuply_mysql_query($query, $conn){
	
	if(extension_loaded('mysqli')){
		$return = @mysqli_query($conn, $query);
	}else{
		$return = @mysql_query($query, $conn);
	}
	
	return $return;
}

function backuply_mysql_fetch_array($result){
	
	if(is_bool($result)){
		return false;
	}
	
	if(extension_loaded('mysqli')){
		$return = @mysqli_fetch_array($result);
	}else{
		$return = @mysql_fetch_array($result);
	}
	
	return $return;
}

function backuply_mysql_fetch_assoc($result){
	
	if(is_bool($result)){
		return $result;
	}
	
	if(extension_loaded('mysqli')){
		$return = @mysqli_fetch_assoc($result);
	}else{
		$return = @mysql_fetch_assoc($result);
	}
	
	return $return;
}

function backuply_mysql_fetch_row($result){
	
	if(is_bool($result)){
		return false;
	}
	
	if(extension_loaded('mysqli')){
		$return = @mysqli_fetch_row($result);
	}else{
		$return = @mysql_fetch_row($result);
	}
	
	return $return;
}


function backuply_mysql_field_flags($result, $i){
	
	if(!extension_loaded('mysqli')){
		return mysql_field_flags($result, $i);
	}
	
	$f = mysqli_fetch_field_direct($result, $i);
	$type = $f->type;
	$charsetnr = $f->charsetnr;
	$f = $f->flags;
	$flags = '';
	if ($f & MYSQLI_UNIQUE_KEY_FLAG) {
		$flags .= 'unique ';
	}
	if ($f & MYSQLI_NUM_FLAG) {
		$flags .= 'num ';
	}
	if ($f & MYSQLI_PART_KEY_FLAG) {
		$flags .= 'part_key ';
	}
	if ($f & MYSQLI_SET_FLAG) {
		$flags .= 'set ';
	}
	if ($f & MYSQLI_TIMESTAMP_FLAG) {
		$flags .= 'timestamp ';
	}
	if ($f & MYSQLI_AUTO_INCREMENT_FLAG) {
		$flags .= 'auto_increment ';
	}
	if ($f & MYSQLI_ENUM_FLAG) {
		$flags .= 'enum ';
	}
	// See http://dev.mysql.com/doc/refman/6.0/en/c-api-datatypes.html:
	// to determine if a string is binary, we should not use MYSQLI_BINARY_FLAG
	// but instead the charsetnr member of the MYSQL_FIELD
	// structure. Watch out: some types like DATE returns 63 in charsetnr
	// so we have to check also the type.
	// Unfortunately there is no equivalent in the mysql extension.
	if (($type == MYSQLI_TYPE_TINY_BLOB || $type == MYSQLI_TYPE_BLOB
		|| $type == MYSQLI_TYPE_MEDIUM_BLOB || $type == MYSQLI_TYPE_LONG_BLOB
		|| $type == MYSQLI_TYPE_VAR_STRING || $type == MYSQLI_TYPE_STRING)
		&& 63 == $charsetnr
	) {
		$flags .= 'binary ';
	}
	if ($f & MYSQLI_ZEROFILL_FLAG) {
		$flags .= 'zerofill ';
	}
	if ($f & MYSQLI_UNSIGNED_FLAG) {
		$flags .= 'unsigned ';
	}
	if ($f & MYSQLI_BLOB_FLAG) {
		$flags .= 'blob ';
	}
	if ($f & MYSQLI_MULTIPLE_KEY_FLAG) {
		$flags .= 'multiple_key ';
	}
	if ($f & MYSQLI_UNIQUE_KEY_FLAG) {
		$flags .= 'unique_key ';
	}
	if ($f & MYSQLI_PRI_KEY_FLAG) {
		$flags .= 'primary_key ';
	}
	if ($f & MYSQLI_NOT_NULL_FLAG) {
		$flags .= 'not_null ';
	}
	return trim($flags);
}


function backuply_mysql_num_rows($result){
	
	if(is_bool($result)){
		return false;
	}
	
	if(extension_loaded('mysqli')){
		$return = @mysqli_num_rows($result);
	}else{
		$return = @mysql_num_rows($result);
	}
	
	return $return;
}

function backuply_mysql_num_fields($result){
	
	if(is_bool($result)){
		return false;
	}
	
	if(extension_loaded('mysqli')){
		$return = @mysqli_num_fields($result);
	}else{
		$return = @mysql_num_fields($result);
	}
	
	return $return;
}

function backuply_mysql_free_result($result){
	
	if(is_bool($result)){
		return false;
	}
	
	if(extension_loaded('mysqli')){
		$return = @mysqli_free_result($result);
	}else{
		$return = @mysql_free_result($result);
	}
	
	return $return;
}

function backuply_getFieldsMeta($result){
	// Build an associative array for a type look up
	
	if(is_bool($result)){
		return false;
	}
	
	if(!defined('MYSQLI_TYPE_VARCHAR')){
		define('MYSQLI_TYPE_VARCHAR', 15);
	}
	
	$typeAr = array();
	$typeAr[MYSQLI_TYPE_DECIMAL]	 = 'real';
	$typeAr[MYSQLI_TYPE_NEWDECIMAL]  = 'real';
	$typeAr[MYSQLI_TYPE_BIT]		 = 'int';
	$typeAr[MYSQLI_TYPE_TINY]		= 'int';
	$typeAr[MYSQLI_TYPE_SHORT]	   = 'int';
	$typeAr[MYSQLI_TYPE_LONG]		= 'int';
	$typeAr[MYSQLI_TYPE_FLOAT]	   = 'real';
	$typeAr[MYSQLI_TYPE_DOUBLE]	  = 'real';
	$typeAr[MYSQLI_TYPE_NULL]		= 'null';
	$typeAr[MYSQLI_TYPE_TIMESTAMP]   = 'timestamp';
	$typeAr[MYSQLI_TYPE_LONGLONG]	= 'int';
	$typeAr[MYSQLI_TYPE_INT24]	   = 'int';
	$typeAr[MYSQLI_TYPE_DATE]		= 'date';
	$typeAr[MYSQLI_TYPE_TIME]		= 'time';
	$typeAr[MYSQLI_TYPE_DATETIME]	= 'datetime';
	$typeAr[MYSQLI_TYPE_YEAR]		= 'year';
	$typeAr[MYSQLI_TYPE_NEWDATE]	 = 'date';
	$typeAr[MYSQLI_TYPE_ENUM]		= 'unknown';
	$typeAr[MYSQLI_TYPE_SET]		 = 'unknown';
	$typeAr[MYSQLI_TYPE_TINY_BLOB]   = 'blob';
	$typeAr[MYSQLI_TYPE_MEDIUM_BLOB] = 'blob';
	$typeAr[MYSQLI_TYPE_LONG_BLOB]   = 'blob';
	$typeAr[MYSQLI_TYPE_BLOB]		= 'blob';
	$typeAr[MYSQLI_TYPE_VAR_STRING]  = 'string';
	$typeAr[MYSQLI_TYPE_STRING]	  = 'string';
	$typeAr[MYSQLI_TYPE_VARCHAR]	 = 'string'; // for Drizzle
	// MySQL returns MYSQLI_TYPE_STRING for CHAR
	// and MYSQLI_TYPE_CHAR === MYSQLI_TYPE_TINY
	// so this would override TINYINT and mark all TINYINT as string
	// https://sourceforge.net/p/phpmyadmin/bugs/2205/
	//$typeAr[MYSQLI_TYPE_CHAR]		= 'string';
	$typeAr[MYSQLI_TYPE_GEOMETRY]	= 'geometry';
	$typeAr[MYSQLI_TYPE_BIT]		 = 'bit';

	$fields = mysqli_fetch_fields($result);

	// this happens sometimes (seen under MySQL 4.0.25)
	if (!is_array($fields)) {
		return false;
	}

	foreach ($fields as $k => $field) {
		$fields[$k]->_type = $field->type;
		$fields[$k]->type = $typeAr[$field->type];
		$fields[$k]->_flags = $field->flags;
		$fields[$k]->flags = backuply_mysql_field_flags($result, $k);

		// Enhance the field objects for mysql-extension compatibilty
		//$flags = explode(' ', $fields[$k]->flags);
		//array_unshift($flags, 'dummy');
		$fields[$k]->multiple_key
			= (int) (bool) ($fields[$k]->_flags & MYSQLI_MULTIPLE_KEY_FLAG);
		$fields[$k]->primary_key
			= (int) (bool) ($fields[$k]->_flags & MYSQLI_PRI_KEY_FLAG);
		$fields[$k]->unique_key
			= (int) (bool) ($fields[$k]->_flags & MYSQLI_UNIQUE_KEY_FLAG);
		$fields[$k]->not_null
			= (int) (bool) ($fields[$k]->_flags & MYSQLI_NOT_NULL_FLAG);
		$fields[$k]->unsigned
			= (int) (bool) ($fields[$k]->_flags & MYSQLI_UNSIGNED_FLAG);
		$fields[$k]->zerofill
			= (int) (bool) ($fields[$k]->_flags & MYSQLI_ZEROFILL_FLAG);
		$fields[$k]->numeric
			= (int) (bool) ($fields[$k]->_flags & MYSQLI_NUM_FLAG);
		$fields[$k]->blob
			= (int) (bool) ($fields[$k]->_flags & MYSQLI_BLOB_FLAG);
	}
	return $fields;
}

function backuply_rmdir_recursive_fn($path){
	
	$path = (substr($path, -1) == '/' || substr($path, -1) == '\\' ? $path : $path.'/');
	
	backuply_resetfilelist();
	
	$files = backuply_filelist_fn($path, 1, 0, 'all');
	$files = (!is_array($files) ? array() : $files);
	
	//First delete the files only
	foreach($files as $k => $v){
		@chmod($k, 0777);
		if(file_exists($k) && is_file($k) && @filetype($k) == "file"){
			@unlink($k);
		}
	}
	
	@clearstatcache();
	
	$folders = backuply_filelist_fn($path, 1, 1, 'all');
	$folders = (!is_array($folders) ? array() : $folders);
	@krsort($folders);

	//Now Delete the FOLDERS
	foreach($folders as $k => $v){
		@chmod($k, 0777);
		if(is_dir($k)){
			@rmdir($k);
		}
	}
	
	@rmdir($path);
	
	@clearstatcache();
}

// To delete any file created while backing up
function backuply_clean_on_stop() {
	global $data, $backuply;
	
	backuply_status_log('Stopping your backup', 'info');
	
	if(!isset($data['name'])) {
		return;
	}
	
	$ext = !empty($data['ext']) ? $data['ext'] : 'tar.gz';
	
	//backuply_rmdir_recursive_fn(BACKUPLY_BACKUP_DIR.'backups/tmp');
	
	// clean the tmp files
	backuply_clean($data);
	
	// Delete the tar file with dot at the start
	if(file_exists($data['path'] . '/.' . $data['name'] .'.'. $ext)) {
		@unlink($data['path'] . '/.' . $data['name'] .'.'. $ext);
	}
	
	// Deletes File from remote
	if(!empty($backuply['status']['remote_file_path'])){
		backuply_log('Removing remote file');
		@unlink($backuply['status']['remote_file_path']);
		@unlink($backuply['status']['successfile']);
	}
	
	backuply_status_log('Cleaning the backup folder', 'info', -1);
	
	$backup_info_dir = backuply_glob('backups_info');
	
	if(empty($backup_info_dir)){
		return;
	}
	
	// Delete the Info file
	if(file_exists($backup_info_dir .'/'. $data['name'] . '.php')) {
		@unlink($backup_info_dir . '/' . $data['name'] . '.php');
	}
}

// To check if backup has been stopped
function backuply_backup_stop_checkpoint() {
	global $data, $wpdb, $backuply;
	
	$query = 'SELECT * FROM `'.$wpdb->prefix.'options` WHERE `option_name` = "backuply_backup_stopped"';
	$result = $wpdb->get_results($query);
	
	if(!empty($result[0]->option_value)) {
		delete_option('backuply_backup_stopped');
		backuply_clean_on_stop();
		backuply_status_log('Backup Successfully Stopped', 'success');
		unset($backuply['status']['incomplete_upload']);
		
		backuply_die('stopped');
	}
}

//Updates the Status in the data base to be used when incomplete
function backuply_update_status(){
	global $data, $backuply;
	
	//An array to store only the required fields in sql.
	
	if(isset($backuply['status']['successfile']) && !isset($GLOBALS['successfile'])) {
		$GLOBALS['successfile'] = $backuply['status']['successfile'];
	}
	
	if(!isset($backuply['status']['incomplete_upload']) && empty($GLOBALS['end_file']) && empty($GLOBALS['db_in_progress'])){
		return;
	}

	$backuply['status']['name'] = $data['name'];
	$backuply['status']['last_file'] = $GLOBALS['end_file'];
	$backuply['status']['backup_db'] = $data['backup_db'];
	$backuply['status']['backup_dir'] = $data['backup_dir'];
	$backuply['status']['successfile'] = isset($GLOBALS['successfile']) ? $GLOBALS['successfile'] : '';
	$backuply['status']['database_row'] = !empty($GLOBALS['database_row']) ? $GLOBALS['database_row'] : '';
	$backuply['status']['database_row_done'] = !empty($GLOBALS['database_row_done']) ? $GLOBALS['database_row_done'] : '';
	$backuply['status']['backup_tables'] = !empty($GLOBALS['backup_tables']) ? $GLOBALS['backup_tables'] : array();
	$backuply['status']['db_in_progress'] = !empty($GLOBALS['db_in_progress']) ? $GLOBALS['db_in_progress'] : '';
	
	//Store the $backuply['status'] variable in sql.
	update_option('backuply_status', $backuply['status']);
}

function backuply_info_json(&$info_data = []){
	
	global $data, $can_write, $backuply;
	
	//Get the current Users Information
	$current_user = wp_get_current_user();
	
	if(!function_exists('get_home_path')){
		include_once backuply_cleanpath(ABSPATH) . '/wp-admin/includes/file.php';
	}
	
	//Store all needed information for the info file
	$info_data = array();
	$info_data['name'] = $GLOBALS['data']['name']; 
	$info_data['backup_dir'] = $GLOBALS['data']['backup_dir'];
	$info_data['backup_db'] = $GLOBALS['data']['backup_db'];
	$info_data['email'] = $current_user->user_email;
	$info_data['date_time'] = date("Y-m-d H:i:a");
	$info_data['btime'] = time();
	$info_data['auto_backup'] = isset($data['auto_backup']) ? $data['auto_backup'] : false;
	$info_data['ext'] = 'tar.gz';
	$info_data['size'] = isset($backuply['status']['remote_file_path']) ? filesize($backuply['status']['remote_file_path']) : (isset($GLOBALS['successfile']) ? filesize($GLOBALS['successfile']) : false);
	$info_data['backup_site_url'] = get_site_url();
	$info_data['backup_site_path'] = backuply_cleanpath(get_home_path());
	
	if(isset($data['backup_location']) && !empty($data['backup_location'])){
		$info_data['backup_location'] = $data['backup_location'];
	}

	//Encode the Data and store it in a file
	return "<?php exit();?>\n".json_encode($info_data, JSON_PRETTY_PRINT);
	
}

// Uploads the backup log file
function backuply_upload_log() {
	global $backuply, $data;

	$backuply['status']['successfile'] = BACKUPLY_BACKUP_DIR . $data['name'] . '_log.php';
	
	// Upload the info file as well
	$GLOBALS['start_pos'] = 0;
	unset($backuply['status']['init_data']);
	unset($backuply['status']['proto']);
	$backuply['status']['proto_file_size'] = filesize($backuply['status']['successfile']);
	
	$remote_fp = fopen(dirname($backuply['status']['remote_file_path']).'/'.$data['name'].'.log', 'ab');

	fwrite($remote_fp, file_get_contents($backuply['status']['successfile']));
	fclose($remote_fp);
}

function backuply_die($txt){
	global $data, $can_write, $backuply;
	
	$email = get_option('backuply_notify_email_address');
	$site_url = get_site_url();
	backuply_update_status(); //Updates the Globals in the Status
	
	// Was there an error ?
	if(!empty($GLOBALS['error'])){
	
		// Deletes File from remote
		if(!empty($backuply['status']['remote_file_path'])){
			backuply_log('Removing remote file');
			@unlink($backuply['status']['remote_file_path']);
			@unlink($backuply['status']['successfile']);
		}
	
		$error = $GLOBALS['error'];
		$error_string = '<b>Below are the error(s) :</b> <br />';
	
		foreach($error as $ek => $ev){
			$error_string .= '* '.$ev.'<br />';
		}

		backuply_status_log($error_string, 'info', 100);
		
		
		// Notify user about the backup failure
		$mail = array();
		$mail['to'] = $email;   
		$mail['subject'] = 'Backup of your WordPress installation failed - Backuply';
		$mail['headers'] = "Content-Type: text/html; charset=UTF-8\r\n";
		$mail['message'] = 'Hi, <br><br>

The last backup of your WordPress installation was failed. <br>
Installation URL : '.$site_url.' <br>
'.$error_string.' <br><br>


Regards,<br>
Backuply';
		
		// Send Email
		if(!empty($mail['to'])){
			wp_mail($mail['to'], $mail['subject'], $mail['message'], $mail['headers']);
		}

		backuply_status_log('Backup failed', 'error', 100);
		backuply_report_error($GLOBALS['error']);		
		
		if($timestamp = wp_next_scheduled('backuply_timeout_check', array('is_restore' => false))) {
			wp_unschedule_event($timestamp, 'backuply_timeout_check', array('is_restore' => false));
		}
		
		delete_option('backuply_status');
		backuply_clean($data);
		backuply_copy_log_file(false); // For Last Log File

		die();
	}	
	
	if($txt == 'DONE'){
		backuply_backup_stop_checkpoint();
		
		$backups_info_dir = backuply_glob('backups_info');

		//Create & store the file in the backups_info folder
		$file = fopen($backups_info_dir . '/' . $GLOBALS['data']['name'] . '.php', 'w');
		fwrite($file, backuply_info_json($info_data));
		@fclose($file);
		
		// Send the mail
		if(!empty($email)){
			//backuply_log(' email to : '.$email);
			//$backup_path = (!empty($GLOBALS['is_remote']) ? '/'.$GLOBALS['data']['name'] : $GLOBALS['successfile'] );

			if(!empty($GLOBALS['is_remote'])){
				$backup_location = 'Backup Location : '.$GLOBALS['is_remote_loc']['name'];
				$backup_path = '/'.$GLOBALS['data']['name'];
			}else{
				$backup_location = '';
				$backup_path = $GLOBALS['successfile'];
			}

			$mail = array();
				$mail['to'] = $email;   
				$mail['subject'] = 'Backup of your WordPress installation - Backuply';
				$mail['message'] = 'Hi,

The backup of your WordPress installation was completed successfully.
The details are as follows :
Installation Path : '.$GLOBALS['data']['softpath'].'
Installation URL : '.$site_url.'
Backup Path : '.$backup_path.'
'.$backup_location.'

Regards,
Backuply';

			wp_mail($mail['to'], $mail['subject'], $mail['message']);
			//backuply_log(' mail data : '. var_export($mail, 1));
		}
		
		backuply_status_log('Archive created with a file size of '. backuply_format_size($info_data['size']) , 'info', 100);
		update_option('backuply_last_backup', time());
		backuply_status_log('Backup Successfully Completed', 'success', 100);
		
		backuply_copy_log_file(false); // For Last Log File
		backuply_copy_log_file(false, $info_data['name']); // Log file for that specific backup
		
		if(isset($backuply['status']['remote_file_path'])) {
			backuply_upload_log();
		}
	}
	
	if(strpos($txt, 'INCOMPLETE') !== FALSE) {
		backuply_log('Going to next loop - '.($backuply['status']['loop'] + 1));
		backuply_backup_curl('backuply_curl_backup');
		die();
	}
	
	if($txt === 'incomplete_upload' || isset($backuply['status']['incomplete_upload'])) {
		update_option('backuply_status', $backuply['status']);
		backuply_backup_curl('backuply_curl_upload');
		die();
	}

	if($timestamp = wp_next_scheduled('backuply_timeout_check', array('is_restore' => false))) {
		wp_unschedule_event($timestamp, 'backuply_timeout_check', array('is_restore' => false));
	}
	
	delete_option('backuply_status');
	backuply_clean($data);
	die();
}

// Clean the Backup files
function backuply_clean($data){

	if(isset($GLOBALS['bfh']) && $GLOBALS['bfh']) {
		foreach($GLOBALS['bfh'] as $v){
			if(!empty($v) && is_resource($v)){
				@fclose($v);
			}
		}
	}

	// Delete tmp/ folder only if the process was completed
	if(empty($GLOBALS['end_file'])){
		backuply_rmdir_recursive_fn($data['path'].'/tmp/'.$data['name']);
	}
	
	return false;
}

// Requests backup via curl
function backuply_backup_curl($action) {
	$nonce = wp_create_nonce('backuply_nonce');

	if(empty($nonce)) {
		backuply_kill_process();
		return;
	}

	$url = site_url() . '/?action='.$action.'&security='. $nonce;
	
	backuply_status_log('About to call self to prevent timeout', 'info');

	wp_remote_get($url, array(
		'timeout' => 5,
		'blocking' => false,
		'cookies' => array(LOGGED_IN_COOKIE => $_COOKIE[LOGGED_IN_COOKIE]),
		'sslverify' => false
	));
	
	die();
}

function backuply_remote_upload($finished = false){
	global $backuply, $error;

	// Do we have a remote file ?
	if(empty($backuply['status']['successfile']) || !file_exists($backuply['status']['successfile'])){
		$error['fopen_failed'] = 'Upload Failed! Because the file is not present on the server';
		unset($backuply['status']['incomplete_upload']);
		backuply_die('upload_failed');
	}
	
	if(empty($backuply['status']['init_pos'])){
		$backuply['status']['init_pos'] = 0;
	}	
	$GLOBALS['start_pos'] = $backuply['status']['init_pos'];
	$backuply['status']['proto_file_size'] = filesize($backuply['status']['successfile']);
	
	$remote_fp = fopen($backuply['status']['remote_file_path'], 'ab');

	if($remote_fp == false){
		$error['fopen_failed'] = 'Unable to open the remote location for writing the backup data. Please make sure the Backup Location details and credentials are correct !';
		unset($backuply['status']['incomplete_upload']);
		backuply_die('fopen_failed');
	}
	
	backuply_status_log('Upload Start Position (L'.$backuply['status']['loop'].') : '.$backuply['status']['init_pos']);
	
	$backuply['status']['chunk'] = 262144; // 2MB
	$file_size = filesize($backuply['status']['successfile']);
	$chunks = ceil($file_size / $backuply['status']['chunk']);
	$chunk_no = isset($backuply['status']['chunk_no']) ? $backuply['status']['chunk_no'] : 1;
	
	while($chunk_no <= $chunks) {
		$backuply['status']['chunk_no'] = $chunk_no;
		
		if(!empty($error)) {
			backuply_die('uploaderror');
		}
		
		// Timeout check
		if(time() + 5 > $GLOBALS['end']) {
			backuply_log('Upload: Short on time!');
			$backuply['status']['incomplete_upload'] = true;
			
			if(!isset($backuply['status']['init_data'])) {
				$backuply['status']['init_data'] = $backuply['status']['protocol'];
			};
			
			backuply_status_log('Upload Time Closing (L'.$backuply['status']['loop'].') : '.$backuply['status']['init_pos']);
			
			@fclose($remote_fp);
			
			backuply_die('incomplete_upload');
			die();
		}
		
		backuply_backup_stop_checkpoint();
		
		// For last chunk
		if($chunk_no == $chunks) {
			$backuply['status']['chunk'] = $file_size - $backuply['status']['init_pos'];
			unset($backuply['status']['incomplete_upload']);
		}
		
		$content = file_get_contents($backuply['status']['successfile'], false, null, $backuply['status']['init_pos'], $backuply['status']['chunk']);
		$clen = strlen($content);

		if(!empty($content)){
			fwrite($remote_fp, $content, $clen); // Write to the stream
			
			// If we had to retry then we should use the start_pos to update init_pos
			if(!empty($backuply['status']['upload_retry'])){
				$backuply['status']['init_pos'] = $GLOBALS['start_pos']; // Update length
				$backuply['status']['upload_retry'] = false;
			} else {
				$backuply['status']['init_pos'] += $clen; // Update Length
			}
			
			
		}
		$content = '';
		
		backuply_status_log('Uploaded till (L'.$backuply['status']['loop'].') : '.$backuply['status']['init_pos'].' / '.$file_size);
		
		//Updating the UI status log
		$percentage = ($chunk_no / $chunks) * 100;
		backuply_status_log('<div class="backuply-upload-progress"><span class="backuply-upload-progress-bar" style="width:'.round($percentage).'%;"></span><span class="backuply-upload-size">'.round($percentage).'%</span></div>', 'uploading', 73);
		
		$chunk_no++;
	}
	
	@fclose($remote_fp);
	
	// If we are done, lets delete this file
	if(!isset($backuply['status']['incomplete_upload'])){
		
		// Delete local file
		@unlink($backuply['status']['successfile']);
		
		if(empty($error)){
		
			$info_file = backuply_info_json();
			
			// Upload the info file as well
			$GLOBALS['start_pos'] = 0;
			unset($backuply['status']['init_data']);
			unset($backuply['status']['proto']);
			$backuply['status']['proto_file_size'] = strlen($info_file);
			
			$remote_fp = fopen(dirname($backuply['status']['remote_file_path']).'/'.$GLOBALS['data']['name'].'.info', 'ab');
			fwrite($remote_fp, $info_file);
			fclose($remote_fp);
		
		}
		
		backuply_die('DONE');
	}
	
	backuply_die('incomplete_upload');
}

#####################################################
# BACKUP LOGIC STARTS HERE !
#####################################################

global $user, $globals, $can_write, $error;

// Check if we can write
$can_write = backuply_can_create_file();

if(empty($can_write)){
	$error[] = __('Cannot write a temporary file !', 'backuply');
	backuply_die('cannot_write');
}

// Retrieve all the information from the form
$data = array();

//Exclude the "backuply" folder
$backuply['excludes']['exact'][] = backuply_cleanpath(BACKUPLY_BACKUP_DIR);

//Exclude the "ai1wm-backups" & the "updraft" folder
$backuply['excludes']['exact'][] = backuply_cleanpath(WP_CONTENT_DIR . '/ai1wm-backups');
$backuply['excludes']['exact'][] = backuply_cleanpath(WP_CONTENT_DIR . '/updraft');

if(!empty($backuply['excludes']['exact'])) {
	foreach($backuply['excludes']['exact'] as $exact_path){
		$backuply['excludes']['exact'][] = backuply_cleanpath($exact_path);
	}
}

//Create the filename
$server_name = !empty($_SERVER['SERVER_NAME']) ? wp_kses_post(wp_unslash($_SERVER['SERVER_NAME'])) : '';
$data['name'] =  !isset($backuply['status']['name']) ? (defined('SITEPAD') ? 'sp_' : 'wp_').$server_name.'_'.date('Y-m-d_H-i-s') : $backuply['status']['name'];

//The path where all backups are stored
$backups_dir = backuply_glob('backups');
$data['path'] = backuply_cleanpath($backups_dir);

//Create the tmp folder
if(!is_dir($data['path'].'/tmp/'.$data['name'])) {
	mkdir($data['path'].'/tmp/'.$data['name'], 0755, true);
}

//Check if the user wants to backup the database
//$data['backup_db'] = isset($backuply['status']['backup_db']) ? 1 : 0;
$data['backup_db'] = !empty($backuply['status']['backup_db']) ? $backuply['status']['backup_db'] : false;
$data['auto_backup'] = isset($backuply['status']['auto_backup']) ? $backuply['status']['auto_backup'] : false;

// Setting upload try
if(empty($backuply['status']['upload_try'])){
	$backuply['status']['upload_try'] = 0;
}
$backuply['status']['upload_retry'] = false;

//Database Information
$data['softdb'] = $wpdb->dbname;
$data['softdbhost'] = $wpdb->dbhost;
$data['softdbuser'] = $wpdb->dbuser;
$data['softdbpass'] = $wpdb->dbpassword;

//Check if the user wants to backup the directories
$data['backup_dir'] = !empty($backuply['status']['backup_dir']) ? $backuply['status']['backup_dir'] : 0;

//The directory path that needs to be backed up
$data['softpath'] = backuply_cleanpath(ABSPATH);

// Get backuply core file index as well as additional files for backup
$data['fileindex'] = backuply_core_fileindex();
$data['additional_files_for_backup'] = get_option('backuply_additional_fileindex');

$data['exclude_db'] = !empty($backuply['excludes']['db']) ? $backuply['excludes']['db'] : array();

// Data used to resume backup of database.
$GLOBALS['database_row'] = !empty($backuply['status']['database_row']) ? $backuply['status']['database_row'] : null;
$GLOBALS['database_row_done'] = !empty($backuply['status']['database_row_done']) ? $backuply['status']['database_row_done'] : null;
$GLOBALS['backup_tables'] = !empty($backuply['status']['backup_tables']) ? $backuply['status']['backup_tables'] : array();
$GLOBALS['db_in_progress'] = !empty($backuply['status']['db_in_progress']) ? $backuply['status']['db_in_progress'] : '';

backuply_backup_stop_checkpoint();

// We need to stop execution in 25 secs.. We will be called again if the process is incomplete
// Set default value
$keepalive = 25;
$GLOBALS['end'] = (int) time() + $keepalive;

$name = $data['name'];
$tmpdir = $data['path'].'/tmp';

// For libraries which are creating copies here and then uploading !
$GLOBALS['local_dest'] = $data['path'];
$GLOBALS['is_remote'] = 0;

$backuply['status']['loop'] = (empty($backuply['status']['loop'])) ? 1 : ($backuply['status']['loop'] + 1);

if(!empty($remote_location)){
	$GLOBALS['is_remote'] = 1;
	$GLOBALS['is_remote_loc'] = $remote_location;
	
	$path = $remote_location['full_backup_loc'];
	$backuply['status']['remote_file_path'] = $path.'/'.$name.'.tar.gz';
	$backuply['status']['protocol'] = $remote_location['protocol'];

	$data['backup_location'] = $remote_location['id'];
	
	// Server Side Encryption for AWS
	if('aws' == $remote_location['protocol'] && isset($remote_location['aws_sse'])){
		$backuply['status']['aws_sse'] = $remote_location['aws_sse'];
	}

	backuply_stream_wrapper_register($remote_location['protocol'], $remote_location['protocol']);
}

$path = $data['path'];
$zipfile = $path.'/.'.$name.'.tar.gz';
$successfile = $path.'/'.$name.'.tar.gz';

$GLOBALS['doing_soft_files'] = 0;

$f_list = $pre_soft_list = $post_soft_list = array(); // Files/Folder which has to be added to the tar.gz

// Empty last file everytime as a precaution
$GLOBALS['last_file'] = '';
$GLOBALS['last_file'] = !empty($backuply['status']['last_file']) ? $backuply['status']['last_file'] : '';
if(!empty($GLOBALS['last_file'])){
	$GLOBALS['last_file'] = rawurldecode($GLOBALS['last_file']);
	
	// If the last file is the DB file, we need to skip to start the loop for the next file because we dont add the db file in the tar list once the SQL file is backed up
	$GLOBALS['last_file'] = strpos($GLOBALS['last_file'], 'softsql.sql') !== FALSE ? '' : $GLOBALS['last_file']; 	
}

$GLOBALS['init_pos'] = 0;

//Sets the Position of pointer in the file
if(isset($backuply['status']['init_pos']) && $backuply['status']['init_pos']) {
	$GLOBALS['init_pos'] = (int) $backuply['status']['init_pos'];
}

// Resume uploads - Calls for upload start in the remote upload option. This is subsequent loops
if(!empty($backuply['status']['init_data'])) {
	backuply_log('Resuming upload');
	backuply_remote_upload();
	die();
}

// Save the version
@file_put_contents($data['path'].'/tmp/'.$data['name'].'/softver.txt', BACKUPLY_VERSION);	
$GLOBALS['replace']['from']['softver'] = $data['path'].'/tmp/'.$data['name'].'/softver.txt';
$GLOBALS['replace']['to']['softver'] = 'softver.txt';

// Save the info file data
@file_put_contents($data['path'].'/tmp/'.$data['name'].'/'.$data['name'].'.php', backuply_info_json());
$GLOBALS['replace']['from']['backupinfo'] = $data['path'].'/tmp/'.$data['name'].'/'.$data['name'].'.php';
$GLOBALS['replace']['to']['backupinfo'] = $data['name'] . '.php';


//Backup the DATABASE
if(!empty($data['backup_db']) && !empty($data['softdb']) && empty($backuply['status']['backup_db_done'])){
	// Store the progress
	backuply_status_log('Starting to Backup Database', 'info', 20);
	
	$dbfile = $data['path'].'/tmp/'.$data['name'].'/softsql.sql';
	backuply_status_log('Creating softsql', 'working', 23);

	$pre_soft_list[] = $dbfile;
	
	$GLOBALS['replace']['from']['softsql'] = $dbfile;
	$GLOBALS['replace']['to']['softsql'] = 'softsql.sql';
	
	$dbuser = $data['softdbuser'];
	$dbpass = $data['softdbpass'];
	
	$sql_conn = backuply_mysql_connect($data['softdbhost'], $dbuser, $dbpass);
		
	if(!$sql_conn){
		//$error['mysql_connect'] = 'Cannot connect mysql.';
		$GLOBALS['error']['mysql_connect'] = __('Cannot connect to mysql.', 'backuply');
		backuply_die('conn');
	}
	
	$sel = backuply_mysql_select_db($data['softdb'], $sql_conn);
	
	if(!$sel){
		//$error['mysql_sel_db'] = 'Could not select the database';
		$GLOBALS['error']['mysql_sel_db'] = __('Could not select the database.', 'backuply');
		backuply_die('conn');
	}
	
	$host = $data['softdbhost'];
	$user = $data['softdbuser'];
	$pass = $data['softdbpass'];
	$db = $data['softdb'];

	//include_once('mysql_functions.php');
	backuply_backup_stop_checkpoint();

	if(!backuply_mysql_fn($host, $user, $pass, $db, $dbfile)){
		//$error[] = 'Back up was not successful';
		$GLOBALS['error'][] = __('Back up was unsuccessful.', 'backuply');
		backuply_die('conn');
	}
	
	if(!file_exists($dbfile)){
		//$error['backup_db'] = 'Could not create sql file from database.';
		$GLOBALS['error']['backup_db'] = __('Could not create sql file from database.', 'backuply');

		backuply_die('error');
	}
	
	$backuply['status']['backup_db_done'] = 1;
}

//Backup the DIRECTORY
if(!empty($data['backup_dir'])){
	
	// Store the progress
	backuply_status_log('Backing up your Wordpress Install', 'info', empty($data['backup_db']) ? 31 : 39);	
	backuply_backup_stop_checkpoint();
	
	if(!empty($data['fileindex'])){
		$_root_filelist = backuply_filelist_fn(backuply_cleanpath($data['softpath']), 0);
		$root_filelist = array();

		// Lets get the full paths in fileindex
		$full_fileindex = array();
		foreach($data['fileindex'] as $sfk => $sfv){
			$full_fileindex[] = trim(backuply_cleanpath($data['softpath'])).'/'.$sfv;
		}
		
		// Add additional files in fileindex if selected by user
		if(!empty($data['additional_files_for_backup'])){
			foreach($data['additional_files_for_backup'] as $sfk => $sfv){
				$full_fileindex[] = trim(backuply_cleanpath($data['softpath'])).'/'.$sfv;
			}
		}
		
		foreach($_root_filelist as $rk => $rv){
			$tmp_rk = backuply_cleanpath($rk);
			$tmp_rv = $rv;

			// Do we need to exclude the files ? 
			if(!in_array(trim($tmp_rk), $full_fileindex)){
				continue;
			}
			
			$tmp_rv['path'] = backuply_cleanpath($rv['path']);
			$root_filelist[$tmp_rk] = $tmp_rv;
		}
		
		$final_filelist = array_keys($root_filelist);
		
		foreach($final_filelist as $fk => $fv){
			$f_list[] = $fv;
		}
		
	}else{
		// Adding the directory in $f_list to add to tar
		$f_list[] = $data['softpath'].'/';
	}
	
	// File Permission
	$GLOBALS['bfh']['softperms'] = @fopen($data['path'].'/tmp/'.$data['name'].'/softperms.txt', 'a');
	
	$GLOBALS['replace']['from']['softperms'] = $data['path'].'/tmp/'.$data['name'].'/softperms.txt';
	$GLOBALS['replace']['to']['softperms'] = 'softperms.txt';

	//Did it open the File Stream
	if(!$GLOBALS['bfh']['softperms']){
		$GLOBALS['error'][] = __('There were errors while trying to make a file of permissions', 'backuply');
		backuply_die('permdir');
	}
	
	backuply_backup_stop_checkpoint();
	
	// The directory itself
	@fwrite($GLOBALS['bfh']['softperms'], '/ '.@substr(sprintf('%o', fileperms($data['softpath'])), -4)."\n");
}

// This is done at the end to make sure we have added all possible replace paths before the softpath
if(!empty($data['backup_dir'])){
	$GLOBALS['replace']['from']['softpath'] = $data['softpath'].'/';
	$GLOBALS['replace']['to']['softpath'] = '';
}

// Now we will have to add the permission file to the end os an array of directory list.
if(!empty($GLOBALS['bfh']['softperms'])){
	$GLOBALS['post_soft_list'][] = $data['path'].'/tmp/'.$data['name'].'/softperms.txt';
}

$GLOBALS['post_soft_list'][] = $data['path'].'/tmp/'.$data['name'].'/softver.txt';
$GLOBALS['post_soft_list'][] = $data['path'].'/tmp/'.$data['name'].'/'.$data['name'].'.php';

if(empty($GLOBALS['error']) && (!empty($f_list) || !empty($post_soft_list) || !empty($pre_soft_list))){
	
	// Set default values
	$GLOBALS['start'] = 0;
	$GLOBALS['end_file'] = '';
	$GLOBALS['pre_soft_list'] = $pre_soft_list;
	
	backuply_backup_stop_checkpoint();
	backuply_status_log('Starting to create archive', 'info', 60);
	
	if(!backuply_tar_archive($zipfile, $f_list, true)){
		backuply_clean($data);
		
		//backuply_log('The backup utility could not back up the files.');
		$GLOBALS['error']['backup_dir'] = __('The backup utility could not back up the files.', 'backuply');
		@unlink($zipfile);
		backuply_die('failbackup');
	}
}

if(!empty($GLOBALS['error'])){
	backuply_die('failbackup');
}

//@print_r($GLOBALS['error']);

// CHMOD it to something Safe
@chmod($zipfile, 0600);

// if(empty($remote_location)){
// 	schown($zipfile);
// }

backuply_clean($data);

// Is the backup tar process INCOMPLETE ?
if(!empty($GLOBALS['end_file'])){
	
	//fwrite($file, "\n end file ke check me gya  \n");
	//echo $data['name']."+".$GLOBALS['end_file']."+".$GLOBALS['progress'];
	$data['last_file'] = $GLOBALS['end_file'];
	
	//Let the script know that the process is still incomplete.
	backuply_die('INCOMPLETE');

// Backup tar is created, lets upload if its a remote backup OR simple finish the whole process for a local backup
}else{
	
	// Rename the ZIP file
	@rename($zipfile, $successfile);
	
	backuply_backup_stop_checkpoint();
	$backuply['status']['successfile'] = $successfile;
	
	//Send the users email address & the plugin directory path
	$GLOBALS['data'] = $data;
	
	// Lets upload as this is a remote backup
	if(isset($backuply['status']['remote_file_path'])) {
		backuply_log('Starting to upload file to the selected remote location');
		backuply_remote_upload();
		die();
	}
	
	//Delete the backup information from sql
	if(!isset($backuply['status']['incomplete_upload'])){
		delete_option('backuply_status');
	}
	//fwrite($file, "\n deleted backuply_status  \n");

	backuply_die('DONE', $l_file = '', BACKUPLY_BACKUP_DIR);
}