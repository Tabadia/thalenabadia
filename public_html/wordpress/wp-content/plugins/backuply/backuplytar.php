<?php

if(!defined('ABSPATH')){
	die('HACKING ATTEMPT');
}

class backuply_tar{

	var $_tarname = '';
	var $_compress = false;
	var $_compress_type = 'none';
	var $_separator = ',';
	var $_file = 0;
	var $_temp_tarname = '';
	var $_ignore_regexp = '';
	var $error_object = null;
	
	var $_local_tar = ''; // The local file	
	var $_orig_tar = ''; // The remote file
	var $remote_fp = ''; // The remote file pointer	
	var $remote_fp_filter = NULL;
	var $remote_hctx = NULL;
	var $remote_content_size = 0;
	
	function __construct($p_tarname, $p_compress = null, $handle_remote = false){
		
		$this->_compress = false;
		$this->_compress_type = 'none';
		if (($p_compress === null) || ($p_compress == '')) {
			if (@file_exists($p_tarname)) {
				if ($fp = @fopen($p_tarname, "rb")) {
					// look for gzip magic cookie
					$data = fread($fp, 2);
					fclose($fp);
					if ($data == "\37\213") {
						$this->_compress = true;
						$this->_compress_type = 'gz';
						// No sure it's enought for a magic code ....
					} elseif ($data == "BZ") {
						$this->_compress = true;
						$this->_compress_type = 'bz2';
					}
				}
			} else {
				// probably a remote file or some file accessible
				// through a stream interface
				if (substr($p_tarname, -2) == 'gz') {
					$this->_compress = true;
					$this->_compress_type = 'gz';
				} elseif ((substr($p_tarname, -3) == 'bz2') ||
						  (substr($p_tarname, -2) == 'bz')) {
					$this->_compress = true;
					$this->_compress_type = 'bz2';
				}
			}
		} else {
			if (($p_compress === true) || ($p_compress == 'gz')) {
				$this->_compress = true;
				$this->_compress_type = 'gz';
			} else if ($p_compress == 'bz2') {
				$this->_compress = true;
				$this->_compress_type = 'bz2';
			} else {
				$this->_error("Unsupported compression type '$p_compress'\n".
					"Supported types are 'gz' and 'bz2'.\n");
				return false;
			}
		}
		$this->_tarname = $p_tarname;
		if ($this->_compress) { // assert zlib or bz2 extension support
			if ($this->_compress_type == 'gz')
				$extname = 'zlib';
			else if ($this->_compress_type == 'bz2')
				$extname = 'bz2';

			if (!extension_loaded($extname)) {
				PEAR::loadExtension($extname);
			}
			if (!extension_loaded($extname)) {
				$this->_error("The extension '$extname' couldn't be found.\n".
					"Please make sure your version of PHP was built ".
					"with '$extname' support.\n");
				return false;
			}
		}
	}
	// }}}

	// {{{ destructor
	function _backuply_tar()
	{
		$this->_close();
		// ----- Look for a local copy to delete
		if ($this->_temp_tarname != '')
		   @unlink($this->_temp_tarname);
		
		// In case of REMOTE
		if(!empty($this->_orig_tar) && empty($GLOBALS['end_file'])){
			unlink($this->_local_tar);
		}
	}
	// }}}
	
	function __destruct(){
		$this->_backuply_tar();
	}

	// {{{ create()
	function create($p_filelist)
	{
		return $this->createModify($p_filelist, '', '');
	}
	// }}}

	// {{{ add()
	function add($p_filelist)
	{	
		return $this->addModify($p_filelist, '', '');
	}
	// }}}

	// {{{ extract()
	function extract($p_path='', $p_preserve=false)
	{
		return $this->extractModify($p_path, '', $p_preserve);
	}
	// }}}

	// {{{ listContent()
	function listContent()
	{
		$v_list_detail = array();

		if ($this->_openRead()) {
			if (!$this->_extractList('', $v_list_detail, "list", '', '')) {
				unset($v_list_detail);
				$v_list_detail = 0;
			}
			$this->_close();
		}

		return $v_list_detail;
	}
	// }}}
		
	// {{{ createModify()
	function createModify($p_filelist, $p_add_dir, $p_remove_dir='')
	{
	
		$v_result = true;

		if (!$this->_openWrite())
			return false;
		
		// Backup the Softaculous pre list (e.g. softsql.sql)
		if(!empty($GLOBALS['pre_soft_list'])){
			$GLOBALS['doing_soft_files'] = 1;
			$v_result = $this->_addList($GLOBALS['pre_soft_list'], $p_add_dir, $p_remove_dir);
			$GLOBALS['doing_soft_files'] = 0;
		}
		
		if ($p_filelist != '') {
			if (is_array($p_filelist))
				$v_list = $p_filelist;
			elseif (is_string($p_filelist))
				$v_list = explode($this->_separator, $p_filelist);
			else {
				$this->_cleanFile();
				$this->_error('Invalid file list');
				return false;
			}
			
			$v_result = $this->_addList($v_list, $p_add_dir, $p_remove_dir);
		}
		
		if ($v_result) {
			// --- write footer only if end file is empty..
			if(empty($GLOBALS['end_file'])){ 
				
				// Add the Softaculous post files i.e. softperms.txt at the end
				if(!empty($GLOBALS['post_soft_list'])){
					$GLOBALS['doing_soft_files'] = 1;
					$v_result = $this->_addList($GLOBALS['post_soft_list'], $p_add_dir, $p_remove_dir);
					$GLOBALS['doing_soft_files'] = 0;
				}
				
				if($v_result){
					$this->_writeFooter();
				}
			}
			$this->_close();
		} else
			$this->_cleanFile();
	
		return $v_result;
	}
	// }}}

	// {{{ addModify()
	function addModify($p_filelist, $p_add_dir, $p_remove_dir='')
	{
		$v_result = true;
		
		if (!$this->_isArchive())
			$v_result = $this->createModify($p_filelist, $p_add_dir,
											$p_remove_dir);
		else {
			if (is_array($p_filelist))
				$v_list = $p_filelist;
			elseif (is_string($p_filelist))
				$v_list = explode($this->_separator, $p_filelist);
			else {
				$this->_error('Invalid file list');
				return false;
			}

			$v_result = $this->_append($v_list, $p_add_dir, $p_remove_dir);
		}

		return $v_result;
	}
	// }}}

	// {{{ addString()
	function addString($p_filename, $p_string)
	{
		$v_result = true;

		if (!$this->_isArchive()) {
			if (!$this->_openWrite()) {
				return false;
			}
			$this->_close();
		}

		if (!$this->_openAppend())
			return false;

		// Need to check the get back to the temporary file ? ....
		$v_result = $this->_addString($p_filename, $p_string);

		$this->_writeFooter();

		$this->_close();

		return $v_result;
	}
	// }}}

	// {{{ extractModify()
	function extractModify($p_path, $p_remove_path, $p_preserve=false)
	{
		$v_result = true;
		$v_list_detail = array();

		if ($v_result = $this->_openRead()) {
			$v_result = $this->_extractList($p_path, $v_list_detail,
				"complete", 0, $p_remove_path, $p_preserve);
			$this->_close();
		}

		return $v_result;
	}
	// }}}

	// {{{ extractInString()
	function extractInString($p_filename)
	{
		if ($this->_openRead()) {
			$v_result = $this->_extractInString($p_filename);
			$this->_close();
		} else {
			$v_result = null;
		}

		return $v_result;
	}
	// }}}

	// {{{ extractList()
	function extractList($p_filelist, $p_path='', $p_remove_path='', $p_preserve=false)
	{
		$v_result = true;
		$v_list_detail = array();

		if (is_array($p_filelist))
			$v_list = $p_filelist;
		elseif (is_string($p_filelist))
			$v_list = explode($this->_separator, $p_filelist);
		else {
			$this->_error('Invalid string list');
			return false;
		}
		

		if ($v_result = $this->_openRead()) {
			$v_result = $this->_extractList($p_path, $v_list_detail, "partial",
				$v_list, $p_remove_path, $p_preserve);
			$this->_close();
		}

		return $v_result;
	}
	// }}}

	// {{{ setAttribute()
	function setAttribute()
	{
		$v_result = true;

		// ----- Get the number of variable list of arguments
		if (($v_size = func_num_args()) == 0) {
			return true;
		}

		// ----- Get the arguments
		$v_att_list = func_get_args();

		// ----- Read the attributes
		$i=0;
		while ($i<$v_size) {

			// ----- Look for next option
			switch ($v_att_list[$i]) {
				// ----- Look for options that request a string value
				case ARCHIVE_TAR_ATT_SEPARATOR :
					// ----- Check the number of parameters
					if (($i+1) >= $v_size) {
						$this->_error('Invalid number of parameters for '
									  .'attribute ARCHIVE_TAR_ATT_SEPARATOR');
						return false;
					}

					// ----- Get the value
					$this->_separator = $v_att_list[$i+1];
					$i++;
				break;

				default :
					$this->_error('Unknow attribute code '.$v_att_list[$i].'');
					return false;
			}

			// ----- Next attribute
			$i++;
		}

		return $v_result;
	}
	// }}}

	// {{{ setIgnoreRegexp()
	function setIgnoreRegexp($regexp)
	{
		$this->_ignore_regexp = $regexp;
	}
	// }}}

	// {{{ setIgnoreList()
	function setIgnoreList($list)
	{
		$regexp = str_replace(array('#', '.', '^', '$'), array('\#', '\.', '\^', '\$'), $list);
		$regexp = '#/'.join('$|/', $list).'#';
		$this->setIgnoreRegexp($regexp);
	}
	// }}}

	// {{{ _error()
	function _error($p_message)
	{
		//we have changed this since PEAR is not used
		//$this->error_object = &$this->raiseError($p_message); 
		backuply_status_log($p_message);
	}
	// }}}

	// {{{ _warning()
	function _warning($p_message)
	{
		//we have changed this since PEAR is not used
		//$this->error_object = &$this->raiseError($p_message); 
		backuply_status_log($p_message);
	}
	// }}}

	// {{{ _isArchive()
	function _isArchive($p_filename=null)
	{
		if ($p_filename == null) {
			$p_filename = $this->_tarname;
		}
		clearstatcache();
		return @is_file($p_filename) && !@is_link($p_filename);
	}
	// }}}

	// {{{ _openWrite()
	function _openWrite()
	{
		if ($this->_compress_type == 'gz' && function_exists('gzopen'))
		{
			$this->_file = @gzopen($this->_tarname, "ab9"); //added 'a' for append as 'w' mode truncated the file...
		}
		else if ($this->_compress_type == 'bz2' && function_exists('bzopen'))
		{
			$this->_file = @bzopen($this->_tarname, "w");
			echo 'bz+';
		}
		else if ($this->_compress_type == 'none')
		{
			$this->_file = @fopen($this->_tarname, "ab");
			echo 'ez+';
		}
		else
		{
			$this->_error('Unknown or missing compression type ('
						  .$this->_compress_type.')');
		}

		if ($this->_file == 0) {
			$this->_error('Unable to open in write mode \''
						  .$this->_tarname.'\'');
			return false;
		}

		return true;
	}
	// }}}

	// {{{ _openRead()
	function _openRead()
	{
		if (strtolower(substr($this->_tarname, 0, 7)) == 'http://') {

		  // ----- Look if a local copy need to be done
		  if ($this->_temp_tarname == '') {
			  $this->_temp_tarname = uniqid('tar').'.tmp';
			  if (!$v_file_from = @fopen($this->_tarname, 'rb')) {
				$this->_error('Unable to open in read mode \''
							  .$this->_tarname.'\'');
				$this->_temp_tarname = '';
				return false;
			  }
			  if (!$v_file_to = @fopen($this->_temp_tarname, 'wb')) {
				$this->_error('Unable to open in write mode \''
							  .$this->_temp_tarname.'\'');
				$this->_temp_tarname = '';
				return false;
			  }
			  while ($v_data = @fread($v_file_from, 1024))
				  @fwrite($v_file_to, $v_data);
			  @fclose($v_file_from);
			  @fclose($v_file_to);
		  }

		  // ----- File to open if the local copy
		  $v_filename = $this->_temp_tarname;

		} else
		  // ----- File to open if the normal Tar file
		  $v_filename = $this->_tarname;

		if ($this->_compress_type == 'gz')
			$this->_file = @gzopen($v_filename, "rb");
		else if ($this->_compress_type == 'bz2')
			$this->_file = @bzopen($v_filename, "r");
		else if ($this->_compress_type == 'none')
			$this->_file = @gzopen($v_filename, "rb");
		else
			$this->_error('Unknown or missing compression type ('
						  .$this->_compress_type.')');

		if ($this->_file == 0) {
			$this->_error('Unable to open in read mode \''.$v_filename.'\'');
			return false;
		}

		return true;
	}
	// }}}

	// {{{ _openReadWrite()
	function _openReadWrite()
	{
		if ($this->_compress_type == 'gz')
			$this->_file = @gzopen($this->_tarname, "r+b");
		else if ($this->_compress_type == 'bz2') {
			$this->_error('Unable to open bz2 in read/write mode \''
						  .$this->_tarname.'\' (limitation of bz2 extension)');
			return false;
		} else if ($this->_compress_type == 'none')
			$this->_file = @fopen($this->_tarname, "r+b");
		else
			$this->_error('Unknown or missing compression type ('
						  .$this->_compress_type.')');

		if ($this->_file == 0) {
			$this->_error('Unable to open in read/write mode \''
						  .$this->_tarname.'\'');
			return false;
		}

		return true;
	}
	// }}}

	// {{{ _close()
	function _close()
	{
		//if (isset($this->_file)) {
		if (is_resource($this->_file)) {
			if ($this->_compress_type == 'gz')
				@gzclose($this->_file);
			else if ($this->_compress_type == 'bz2')
				@bzclose($this->_file);
			else if ($this->_compress_type == 'none')
				@fclose($this->_file);
			else
				$this->_error('Unknown or missing compression type ('
							  .$this->_compress_type.')');

			$this->_file = 0;
		}

		// ----- Look if a local copy need to be erase
		// Note that it might be interesting to keep the url for a time : ToDo
		if ($this->_temp_tarname != '') {
			@unlink($this->_temp_tarname);
			$this->_temp_tarname = '';
		}

		return true;
	}
	// }}}

	// {{{ _cleanFile()
	function _cleanFile()
	{
		$this->_close();

		// ----- Look for a local copy
		if ($this->_temp_tarname != '') {
			// ----- Remove the local copy but not the remote tarname
			@unlink($this->_temp_tarname);
			$this->_temp_tarname = '';
		} else {
			// ----- Remove the local tarname file
		   @unlink($this->_tarname);
		}
		$this->_tarname = '';

		return true;
	}
	// }}}

	// {{{ _writeBlock()
	function _writeBlock($p_binary_data, $p_len=null, $finished = false)
	{
		if (is_resource($this->_file)) {
			if ($p_len === null) {
				if ($this->_compress_type == 'gz')
					$write = @gzputs($this->_file, $p_binary_data);
				else if ($this->_compress_type == 'bz2')
					$write = @bzwrite($this->_file, $p_binary_data);
				else if ($this->_compress_type == 'none')
					$write = @fputs($this->_file, $p_binary_data);
				else
					$this->_error('Unknown or missing compression type ('.$this->_compress_type.')');
			} else {
				if ($this->_compress_type == 'gz')
					$write = @gzputs($this->_file, $p_binary_data, $p_len);
				else if ($this->_compress_type == 'bz2')
					$write = @bzwrite($this->_file, $p_binary_data, $p_len);
				else if ($this->_compress_type == 'none')
					$write = @fputs($this->_file, $p_binary_data, $p_len);
				else
					$this->_error('Unknown or missing compression type ('.$this->_compress_type.')');
			}

			if(empty($write)){
				$this->_error('Failed to write to the backup file. Please check you have enough disk quota available.');
				return false;
			}

			// If there is anything to handle for remote uploads
			$this->remote_write_handle($finished);
		}
		return true;
	}
	// }}}
	
	function remote_write_handle($finished = false){
		global $error;

		// Do we have a remote file ?
		if(empty($this->_orig_tar)){
			return false;
		}
	
		clearstatcache();
		
		// Now is the size exceeding 2 MB
		if(!$finished && filesize($this->_local_tar) < 2097152){
			return false;
		}

		// Open the file pointer if not opened
		if(!is_resource($this->remote_fp)){
			
			$this->remote_fp = fopen($this->_orig_tar, 'ab');

			if($this->remote_fp == false){
				$error['fopen_failed'] = 'Unable to open in write mode';
				backuply_die('fopen_failed');
			}
			
			/*	// GZip Header
			fputs($this->remote_fp, "\x1F\x8B\x08\x08".pack("V", time())."\0\xFF");
			
			// Filename
			$oname = str_replace("\0", '', ltrim(basename($this->_orig_tar, '.gz'), '.'));
			fwrite($this->remote_fp, $oname."\0", 1+strlen($oname));
			
			// Create Stream
			$this->remote_fp_filter = stream_filter_append($this->remote_fp, "zlib.deflate", STREAM_FILTER_WRITE, -1);
			$this->remote_hctx = hash_init('crc32b');*/
			
			$this->remote_content_size = 0;
			if(!empty($GLOBALS['init_pos'])){
				$this->remote_content_size = $GLOBALS['init_pos'];
			}
			
			$GLOBALS['start_pos'] = $this->remote_content_size;
		}
		
		// Close the LOCAL file
		$this->_close();
		
		// Write to remote
		$content = file_get_contents($this->_local_tar);
		$clen = strlen($content);
		
		if(!empty($content)){
			//hash_update($this->remote_hctx, $content); // Update Hash
			fwrite($this->remote_fp, $content, $clen); // Write to the stream
			$this->remote_content_size += $clen; // Update Length
		}
		$content = '';
		
		// Delete Local file
		@unlink($this->_local_tar);
		
		// ReOpen the local tar
		$this->_openWrite();
		
		// If we are done, lets delete this file
		if($finished){
			
			/* // Remove Stream
			stream_filter_remove($this->remote_fp_filter);
			
			// Calculate Hash and write it
			$crc = hash_final($this->remote_hctx, true);
			@fwrite($this->remote_fp, $crc[3].$crc[2].$crc[1].$crc[0], 4);
			
			// Also the size
			@fwrite($this->remote_fp, pack("V", $this->remote_content_size), 4); */
			
			// Close
			@fclose($this->remote_fp);
		}
	}

	// {{{ _readBlock()
	function _readBlock()
	{
	  $v_block = null;
	  if (is_resource($this->_file)) {
		  if ($this->_compress_type == 'gz')
			  $v_block = @gzread($this->_file, 512);
		  else if ($this->_compress_type == 'bz2')
			  $v_block = @bzread($this->_file, 512);
		  else if ($this->_compress_type == 'none')
			  $v_block = @fread($this->_file, 512);
		  else
			  $this->_error('Unknown or missing compression type ('
							.$this->_compress_type.')');
	  }
	  return $v_block;
	}
	// }}}

	// {{{ _jumpBlock()
	function _jumpBlock($p_len=null)
	{
		if (is_resource($this->_file)) {
			if ($p_len === null)
				$p_len = 1;

			if ($this->_compress_type == 'gz') {
				@gzseek($this->_file, gztell($this->_file)+($p_len*512));
			}
			else if ($this->_compress_type == 'bz2') {
				// ----- Replace missing bztell() and bzseek()
				for ($i=0; $i<$p_len; $i++)
				$this->_readBlock();
			} else if ($this->_compress_type == 'none')
				@fseek($this->_file, $p_len*512, SEEK_CUR);
			else
				$this->_error('Unknown or missing compression type ('
				.$this->_compress_type.')');
		}
		return true;
	}
	// }}}

	// {{{ _writeFooter()
	function _writeFooter()
	{
		if (is_resource($this->_file)) {
			// ----- Write the last 0 filled block for end of archive
			$v_binary_data = pack('a1024', '');
			if(!$this->_writeBlock($v_binary_data, null, true)){
				return false;
			}
		}
		return true;
	}
	// }}}

	// {{{ _addList()
	function _addList($p_list, $p_add_dir, $p_remove_dir) {

		$v_result = true;
		$v_header = array();
		
		// ----- Remove potential windows directory separator
		$p_add_dir = $this->_translateWinPath($p_add_dir);
		$p_remove_dir = $this->_translateWinPath($p_remove_dir, false);

		if (!$this->_file) {
			$this->_error('Invalid file descriptor');
			return false;
		}

		if (sizeof($p_list) == 0)
			return true;
		
		foreach ($p_list as $v_filename) {
			
			if (!$v_result) {
				break;
			}
				
			// ----- Skip the current tar name
			if ($v_filename == $this->_tarname)
				continue;

			if ($v_filename == '')
				continue;

			// ----- ignore files and directories matching the ignore regular expression
			if ($this->_ignore_regexp && preg_match($this->_ignore_regexp, '/'.$v_filename)) {
				$this->_warning("File '$v_filename' ignored");
				continue;
			}

			if (!file_exists($v_filename) && !is_link($v_filename)) {
				$this->_warning("File '$v_filename' does not exist");
				continue;
			}

			// ----- break the loop once last file is found...
			if(!empty($GLOBALS['end_file'])){
				break;
			}
			
			// ----- Add the file or directory header
			if (!$this->_addFile($v_filename, $v_header, $p_add_dir, $p_remove_dir))
				return false;

			if (@is_dir($v_filename) && !@is_link($v_filename)) {
				if (!($p_hdir = opendir($v_filename))) {
					$this->_warning("Directory '$v_filename' can not be read");
					continue;
				}
				
				$p_temp_list = array();
				while (false !== ($p_hitem = readdir($p_hdir))) {
					if (($p_hitem != '.') && ($p_hitem != '..')) {
						if ($v_filename != "."){
							//Double slashes were added and caused issue when the backup directory is inside the installation directory.
							$v_filename = $this->cleanpath($v_filename);
							$p_temp_list[0] = $v_filename.'/'.$p_hitem;
						}else{
							$p_temp_list[0] = $p_hitem;
						}

						// ----- break the loop once last file is found...
						if(!empty($GLOBALS['end_file'])){
							break;
						}
						
						$v_result = $this->_addList($p_temp_list,
												$p_add_dir,
												$p_remove_dir);
					}
				}

				unset($p_temp_list);
				unset($p_hdir);
				unset($p_hitem);
			}
		}
		
		return $v_result;
	}
	// }}}

	// {{{ _addFile()
	function _addFile($p_filename, &$p_header, $p_add_dir, $p_remove_dir) {
		
		global $backuply;
		
		// check last file and skip the files that have been already backed up...
		if(!empty($GLOBALS['last_file']) && $GLOBALS['start'] == 0){
			if(preg_match('#^'.$GLOBALS['last_file'].'$#', $p_filename)){
				
				$GLOBALS['start'] = 1; // give a jump start once the last backed up file is found..
			}
			return true; //return true to skip files
		}
	
		if (!$this->_file) {
		  $this->_error('Invalid file descriptor');
		  return false;
		}

		if ($p_filename == '') {
		  $this->_error('Invalid file name');
		  return false;
		}
		
		// ----- Calculate the stored filename
		$p_filename = $this->_translateWinPath($p_filename, false);
		$v_stored_filename = $p_filename;
		if (strcmp($p_filename, $p_remove_dir) == 0) {
		  return true;
		}
		
		// Match filename to be excluded as provided by script
		foreach($backuply['excludes']['exact'] as $ek => $ev) {
			if(empty($GLOBALS['doing_soft_files']) && !empty($ev) && preg_match('#^'.$ev.'#', $p_filename)) {
				return true;
			}
		}
		
		// Exclude the sql file if it has already been backedup
		if(strpos($p_filename, 'softsql.sql') !== FALSE && empty($GLOBALS['doing_soft_files'])){
			return true;
		}

		$home_path = backuply_cleanpath(WP_CONTENT_DIR);
		
		// Checks if the the file we are excluding is in WP CONTENT DIR
		if(strpos($p_filename, $home_path) !== FALSE){

			/* The str_replace below will change the Path to this /plugins/backuply-pro/init.php 
			*	as we just want to exclude the folders or files inside WP Content
			*/
			$rel_path = str_replace($home_path, '', $p_filename);
			
			// Excluding files with specific extension
			if(!empty($backuply['excludes']['extension'])){
				$ext = pathinfo($p_filename, PATHINFO_EXTENSION);
				
				if(in_array($ext, $backuply['excludes']['extension'])){
					return true;
				}
			}
			
			// Excluding a pattern that starts with
			if(!empty($backuply['excludes']['beginning'])){
		
				foreach($backuply['excludes']['beginning'] as $beginning){
					// Here it checks if the pattern we are looking for has slash(/) before it then its the start of the name of the folder or file
					preg_match('/\/'.preg_quote(trim($beginning)).'/', $rel_path, $matches);
					
					if(!empty($matches) && strpos($rel_path, 'softsql.sql') == FALSE){
						return true;
					}
				}
			}

			// Excluding a pattern that ends with
			if(!empty($backuply['excludes']['end'])){

				$matches = [];
				foreach($backuply['excludes']['end'] as $end){
					/* Here it checks if the pattern we are looking for has slash(/) after it then its the end of the name of the folder or file
					*	/(?:wp\/|wp$)/ this is the regex used and wp is the word that we are matching
					*/
					preg_match('/(?:' . preg_quote(trim($end)).'\/|' . preg_quote(trim($end)). '$)/', $rel_path, $matches);
					
					if(!empty($matches) && strpos($rel_path, 'softsql.sql') == FALSE){
						return true;
					}
				}
			}

			// Excluding a pattern that is anywhere in the path
			if(!empty($backuply['excludes']['anywhere'])){
				
				$matches = [];
				foreach($backuply['excludes']['anywhere'] as $pattern){
					// Here it checks if the pattern we are looking for is present anywhere in the path
					preg_match('/'.preg_quote(trim($pattern)). '/', $rel_path, $matches);
					
					if(!empty($matches) && strpos($rel_path, 'softsql.sql') == FALSE){
						return true;
					}
				}
			}
		}
		
		if ($p_remove_dir != '') {
			if (substr($p_remove_dir, -1) != '/')
			$p_remove_dir .= '/';

			if (substr($p_filename, 0, strlen($p_remove_dir)) == $p_remove_dir)
			$v_stored_filename = substr($p_filename, strlen($p_remove_dir));
		}
		
		$v_stored_filename = $this->_translateWinPath($v_stored_filename);
		if ($p_add_dir != '') {
			if (substr($p_add_dir, -1) == '/')
				$v_stored_filename = $p_add_dir.$v_stored_filename;
			else
				$v_stored_filename = $p_add_dir.'/'.$v_stored_filename;
		}

		$v_stored_filename = $this->_pathReduction($v_stored_filename);
		
		backuply_backup_stop_checkpoint();
		
		// mt_rand to reduce the number of folder in log
		if(is_dir($p_filename) && mt_rand(0,1)) {
			backuply_status_log('Adding (L'.$backuply['status']['loop'].') : ' . $p_filename, 'adding', 65);
		}
		
		if ($this->_isArchive($p_filename)) {
			if (($v_file = @fopen($p_filename, 'rb')) == 0) {
				$this->_warning("Unable to open file '".$p_filename
							  ."' in binary read mode");
				return true;
			}

			if (!$this->_writeHeader($p_filename, $v_stored_filename)){
				$this->_warning('Unable to write header "'.$p_filename . '"');
				return false;
			}

			while (($v_buffer = fread($v_file, 512)) != '') {
				$v_binary_data = pack('a512', "$v_buffer");
				if(!$this->_writeBlock($v_binary_data)){
					$this->_warning('Unable to write Block "'.$p_filename . '"');
					return false;
				}
			}

			fclose($v_file);

		} else {
			// ----- Only header for dir
			if (!$this->_writeHeader($p_filename, $v_stored_filename)){
				$this->_warning('Unable to writeHeader "'.$p_filename . '"');
				return false;
			}
		}
		
		// We can run the scripts for the end time already set
		if(time() >= $GLOBALS['end']){
			$GLOBALS['end_file'] = $p_filename; // set end file so that we know where to start from
		}

		return true;
	}
	// }}}

	// {{{ _addString()
	function _addString($p_filename, $p_string) {
		if (!$this->_file) {
			$this->_error('Invalid file descriptor');
			return false;
		}

		if ($p_filename == '') {
			$this->_error('Invalid file name');
			return false;
		}

		// ----- Calculate the stored filename
		$p_filename = $this->_translateWinPath($p_filename, false);

		if (!$this->_writeHeaderBlock($p_filename, strlen($p_string),
								  time(), 384, '', 0, 0))
			return false;

		$i=0;
		while (($v_buffer = substr($p_string, (($i++)*512), 512)) != '') {
			$v_binary_data = pack('a512', $v_buffer);
			if(!$this->_writeBlock($v_binary_data)) {
				return false;
			}
		}

		return true;
	}
	// }}}

	// {{{ _writeHeader()
	function _writeHeader($p_filename, $p_stored_filename)
	{
		if ($p_stored_filename == '')
			$p_stored_filename = $p_filename;
		$v_reduce_filename = $this->_pathReduction($p_stored_filename);
		
		
		//echo $v_reduce_filename." - ";
 
		$v_reduce_filename = str_replace($GLOBALS['replace']['from'], $GLOBALS['replace']['to'], $v_reduce_filename);
		
		//echo $v_reduce_filename."<br />";

		if (strlen($v_reduce_filename) > 99) {
		  if (!$this->_writeLongHeader($v_reduce_filename))
			return false;
		}
		
		// We have to write the entries in softperms.txt
		if (isset($GLOBALS['bfh']['softperms']) && preg_match('/'.preg_quote($GLOBALS['replace']['from']['softpath'], '/').'/is', $p_filename)) {
			fwrite($GLOBALS['bfh']['softperms'], trim($v_reduce_filename, '/')." ".(!empty($v_linkname) ? "linkto=".rtrim($v_linkname, '/')." " : ""). (substr(sprintf('%o', fileperms($p_filename)), -4)) ."\n");

		}

		// To make sure we have the correct data after the file is written above
		clearstatcache();

		$v_info = lstat($p_filename);
		$v_uid = sprintf("%07s", DecOct($v_info[4]));
		$v_gid = sprintf("%07s", DecOct($v_info[5]));
		$v_perms = sprintf("%07s", DecOct($v_info['mode'] & 000777));

		$v_mtime = sprintf("%011s", DecOct($v_info['mtime']));

		$v_linkname = '';

		if (@is_link($p_filename)) {
		  $v_typeflag = '2';
		  $v_linkname = readlink($p_filename);
		  $v_size = sprintf("%011s", DecOct(0));
		} elseif (@is_dir($p_filename)) {
		  $v_typeflag = '5';
		  $v_size = sprintf("%011s", DecOct(0));
		} else {
		  $v_typeflag = '0';
		  clearstatcache();
		  $v_size = sprintf("%011s", DecOct($v_info['size']));
		}
		
		$v_magic = 'ustar ';

		$v_version = ' ';
		
		if (function_exists('posix_getpwuid'))
		{
		  $userinfo = posix_getpwuid($v_info[4]);
		  $groupinfo = posix_getgrgid($v_info[5]);
		  
		  $v_uname = $userinfo['name'];
		  $v_gname = $groupinfo['name'];
		}
		else
		{
		  $v_uname = '';
		  $v_gname = '';
		}

		$v_devmajor = '';

		$v_devminor = '';

		$v_prefix = '';
		$v_linkname = ''; // This is empty because we will create symlinks with our restore utility using softperms.txt

		$v_binary_data_first = pack("a100a8a8a8a12a12",
									$v_reduce_filename, $v_perms, $v_uid,
									$v_gid, $v_size, $v_mtime);
		$v_binary_data_last = pack("a1a100a6a2a32a32a8a8a155a12",
								   $v_typeflag, $v_linkname, $v_magic,
								   $v_version, $v_uname, $v_gname,
								   $v_devmajor, $v_devminor, $v_prefix, '');

		// ----- Calculate the checksum
		$v_checksum = 0;
		// ..... First part of the header
		for ($i=0; $i<148; $i++)
			$v_checksum += ord(substr($v_binary_data_first,$i,1));
		// ..... Ignore the checksum value and replace it by ' ' (space)
		for ($i=148; $i<156; $i++)
			$v_checksum += ord(' ');
		// ..... Last part of the header
		for ($i=156, $j=0; $i<512; $i++, $j++)
			$v_checksum += ord(substr($v_binary_data_last,$j,1));

		// ----- Write the first 148 bytes of the header in the archive
		if(!$this->_writeBlock($v_binary_data_first, 148)){
			return false;
		}

		// ----- Write the calculated checksum
		$v_checksum = sprintf("%06s ", DecOct($v_checksum));
		$v_binary_data = pack("a8", $v_checksum);
		if(!$this->_writeBlock($v_binary_data, 8)){
			return false;
		}

		// ----- Write the last 356 bytes of the header in the archive
		if(!$this->_writeBlock($v_binary_data_last, 356)){
			return false;
		}

		return true;
	}
	// }}}

	// {{{ _writeHeaderBlock()
	function _writeHeaderBlock($p_filename, $p_size, $p_mtime=0, $p_perms=0,
							   $p_type='', $p_uid=0, $p_gid=0)
	{
		$p_filename = $this->_pathReduction($p_filename);

		if (strlen($p_filename) > 99) {
		  if (!$this->_writeLongHeader($p_filename))
			return false;
		}

		if ($p_type == '5') {
		  $v_size = sprintf("%011s", DecOct(0));
		} else {
		  $v_size = sprintf("%011s", DecOct($p_size));
		}

		$v_uid = sprintf("%07s", DecOct($p_uid));
		$v_gid = sprintf("%07s", DecOct($p_gid));
		$v_perms = sprintf("%07s", DecOct($p_perms & 000777));

		$v_mtime = sprintf("%11s", DecOct($p_mtime));

		$v_linkname = '';

		$v_magic = 'ustar ';

		$v_version = ' ';

		if (function_exists('posix_getpwuid'))
		{
		  $userinfo = posix_getpwuid($p_uid);
		  $groupinfo = posix_getgrgid($p_gid);
		  
		  $v_uname = $userinfo['name'];
		  $v_gname = $groupinfo['name'];
		}
		else
		{
		  $v_uname = '';
		  $v_gname = '';
		}
		
		$v_devmajor = '';

		$v_devminor = '';

		$v_prefix = '';

		$v_binary_data_first = pack("a100a8a8a8a12A12",
									$p_filename, $v_perms, $v_uid, $v_gid,
									$v_size, $v_mtime);
		$v_binary_data_last = pack("a1a100a6a2a32a32a8a8a155a12",
								   $p_type, $v_linkname, $v_magic,
								   $v_version, $v_uname, $v_gname,
								   $v_devmajor, $v_devminor, $v_prefix, '');

		// ----- Calculate the checksum
		$v_checksum = 0;
		// ..... First part of the header
		for ($i=0; $i<148; $i++)
			$v_checksum += ord(substr($v_binary_data_first,$i,1));
		// ..... Ignore the checksum value and replace it by ' ' (space)
		for ($i=148; $i<156; $i++)
			$v_checksum += ord(' ');
		// ..... Last part of the header
		for ($i=156, $j=0; $i<512; $i++, $j++)
			$v_checksum += ord(substr($v_binary_data_last,$j,1));

		// ----- Write the first 148 bytes of the header in the archive
		if(!$this->_writeBlock($v_binary_data_first, 148)){
			return false;
		}

		// ----- Write the calculated checksum
		$v_checksum = sprintf("%06s ", DecOct($v_checksum));
		$v_binary_data = pack("a8", $v_checksum);
		if(!$this->_writeBlock($v_binary_data, 8)){
			return false;
		}

		// ----- Write the last 356 bytes of the header in the archive
		if(!$this->_writeBlock($v_binary_data_last, 356)){
			return false;
		}

		return true;
	}
	// }}}

	// {{{ _writeLongHeader()
	function _writeLongHeader($p_filename)
	{
		$v_size = sprintf("%11s ", DecOct(strlen($p_filename)));

		$v_typeflag = 'L';

		$v_linkname = '';

		$v_magic = '';

		$v_version = '';

		$v_uname = '';

		$v_gname = '';

		$v_devmajor = '';

		$v_devminor = '';

		$v_prefix = '';

		$v_binary_data_first = pack("a100a8a8a8a12a12",
									'././@LongLink', 0, 0, 0, $v_size, 0);
		$v_binary_data_last = pack("a1a100a6a2a32a32a8a8a155a12",
								   $v_typeflag, $v_linkname, $v_magic,
								   $v_version, $v_uname, $v_gname,
								   $v_devmajor, $v_devminor, $v_prefix, '');

		// ----- Calculate the checksum
		$v_checksum = 0;
		// ..... First part of the header
		for ($i=0; $i<148; $i++)
			$v_checksum += ord(substr($v_binary_data_first,$i,1));
		// ..... Ignore the checksum value and replace it by ' ' (space)
		for ($i=148; $i<156; $i++)
			$v_checksum += ord(' ');
		// ..... Last part of the header
		for ($i=156, $j=0; $i<512; $i++, $j++)
			$v_checksum += ord(substr($v_binary_data_last,$j,1));

		// ----- Write the first 148 bytes of the header in the archive
		if(!$this->_writeBlock($v_binary_data_first, 148)){
			return false;
		}

		// ----- Write the calculated checksum
		$v_checksum = sprintf("%06s ", DecOct($v_checksum));
		$v_binary_data = pack("a8", $v_checksum);
		if(!$this->_writeBlock($v_binary_data, 8)){
			return false;
		}

		// ----- Write the last 356 bytes of the header in the archive
		if(!$this->_writeBlock($v_binary_data_last, 356)){
			return false;
		}

		// ----- Write the filename as content of the block
		$i=0;
		while (($v_buffer = substr($p_filename, (($i++)*512), 512)) != '') {
			$v_binary_data = pack("a512", "$v_buffer");
			if(!$this->_writeBlock($v_binary_data)){
				return false;
			}
		}

		return true;
	}
	// }}}

	// {{{ _readHeader()
	function _readHeader($v_binary_data, &$v_header)
	{
		if (strlen($v_binary_data)==0) {
			$v_header['filename'] = '';
			return true;
		}

		if (strlen($v_binary_data) != 512) {
			$v_header['filename'] = '';
			$this->_error('Invalid block size : '.strlen($v_binary_data));
			return false;
		}

		if (!is_array($v_header)) {
			$v_header = array();
		}
		// ----- Calculate the checksum
		$v_checksum = 0;
		// ..... First part of the header
		for ($i=0; $i<148; $i++)
			$v_checksum+=ord(substr($v_binary_data,$i,1));
		// ..... Ignore the checksum value and replace it by ' ' (space)
		for ($i=148; $i<156; $i++)
			$v_checksum += ord(' ');
		// ..... Last part of the header
		for ($i=156; $i<512; $i++)
		   $v_checksum+=ord(substr($v_binary_data,$i,1));

		if (version_compare(PHP_VERSION, "5.5.0-dev") < 0) {
			$fmt = "a100filename/a8mode/a8uid/a8gid/a12size/a12mtime/" .
				"a8checksum/a1typeflag/a100link/a6magic/a2version/" .
				"a32uname/a32gname/a8devmajor/a8devminor/a131prefix";
		} else {
			$fmt = "Z100filename/Z8mode/Z8uid/Z8gid/Z12size/Z12mtime/" .
				"Z8checksum/Z1typeflag/Z100link/Z6magic/Z2version/" .
				"Z32uname/Z32gname/Z8devmajor/Z8devminor/Z131prefix";
		}
		$v_data = unpack($fmt, $v_binary_data);
						 
		if (strlen($v_data["prefix"]) > 0) {
			$v_data["filename"] = "$v_data[prefix]/$v_data[filename]";
		}

		// ----- Extract the checksum
		$v_header['checksum'] = OctDec(trim($v_data['checksum']));
		if ($v_header['checksum'] != $v_checksum) {
			$v_header['filename'] = '';

			// ----- Look for last block (empty block)
			if (($v_checksum == 256) && ($v_header['checksum'] == 0))
				return true;

			$this->_error('Invalid checksum for file "'.$v_data['filename']
						  .'" : '.$v_checksum.' calculated, '
						  .$v_header['checksum'].' expected');
			return false;
		}

		// ----- Extract the properties
		$v_header['filename'] = $v_data['filename'];
		if ($this->_maliciousFilename($v_header['filename'])) {
			$this->_error('Malicious .tar detected, file "' . $v_header['filename'] .
				'" will not install in desired directory tree');
			return false;
		}
		$v_header['mode'] = OctDec(trim($v_data['mode']));
		$v_header['uid'] = OctDec(trim($v_data['uid']));
		$v_header['gid'] = OctDec(trim($v_data['gid']));
		$v_header['size'] = OctDec(trim($v_data['size']));
		$v_header['mtime'] = OctDec(trim($v_data['mtime']));
		if (($v_header['typeflag'] = $v_data['typeflag']) == "5") {
		  $v_header['size'] = 0;
		}
		$v_header['link'] = trim($v_data['link']);

		return true;
	}
	// }}}

	// {{{ _maliciousFilename()
	function _maliciousFilename($file)
	{
		if (strpos($file, '/../') !== false) {
			return true;
		}
		if (strpos($file, '../') === 0) {
			return true;
		}
		return false;
	}
	// }}}

	// {{{ _readLongHeader()
	function _readLongHeader(&$v_header)
	{
	  $v_filename = '';
	  $n = floor($v_header['size']/512);
	  for ($i=0; $i<$n; $i++) {
		$v_content = $this->_readBlock();
		$v_filename .= $v_content;
	  }
	  if (($v_header['size'] % 512) != 0) {
		$v_content = $this->_readBlock();
		$v_filename .= trim($v_content);
	  }

	  // ----- Read the next header
	  $v_binary_data = $this->_readBlock();

	  if (!$this->_readHeader($v_binary_data, $v_header))
		return false;

	  $v_filename = trim($v_filename);
	  $v_header['filename'] = $v_filename;
		if ($this->_maliciousFilename($v_filename)) {
			$this->_error('Malicious .tar detected, file "' . $v_filename .
				'" will not install in desired directory tree');
			return false;
	  }

	  return true;
	}
	// }}}

	// {{{ _extractInString()
	function _extractInString($p_filename)
	{
		$v_result_str = "";

		While (strlen($v_binary_data = $this->_readBlock()) != 0)
		{
		  if (!$this->_readHeader($v_binary_data, $v_header))
			return null;

		  if ($v_header['filename'] == '')
			continue;

		  // ----- Look for long filename
		  if ($v_header['typeflag'] == 'L') {
			if (!$this->_readLongHeader($v_header))
			  return null;
		  }

		  if ($v_header['filename'] == $p_filename) {
			  if ($v_header['typeflag'] == "5") {
				  $this->_error('Unable to extract in string a directory '
								.'entry {'.$v_header['filename'].'}');
				  return null;
			  } else {
				  $n = floor($v_header['size']/512);
				  for ($i=0; $i<$n; $i++) {
					  $v_result_str .= $this->_readBlock();
				  }
				  if (($v_header['size'] % 512) != 0) {
					  $v_content = $this->_readBlock();
					  $v_result_str .= substr($v_content, 0,
											  ($v_header['size'] % 512));
				  }
				  return $v_result_str;
			  }
		  } else {
			  $this->_jumpBlock(ceil(($v_header['size']/512)));
		  }
		}

		return null;
	}
	// }}}

	// {{{ _extractList()
	function _extractList($p_path, &$p_list_detail, $p_mode,
						  $p_file_list, $p_remove_path, $p_preserve=false)
	{
	$v_result=true;
	$v_nb = 0;
	$v_extract_all = true;
	$v_listing = false;

	$p_path = $this->_translateWinPath($p_path, false);
	if ($p_path == '' || (substr($p_path, 0, 1) != '/'
		&& substr($p_path, 0, 3) != "../" && !strpos($p_path, ':'))) {
	  $p_path = "./".$p_path;
	}
	$p_remove_path = $this->_translateWinPath($p_remove_path);

	// ----- Look for path to remove format (should end by /)
	if (($p_remove_path != '') && (substr($p_remove_path, -1) != '/'))
	  $p_remove_path .= '/';
	$p_remove_path_size = strlen($p_remove_path);

	switch ($p_mode) {
	  case "complete" :
		$v_extract_all = true;
		$v_listing = false;
	  break;
	  case "partial" :
		  $v_extract_all = false;
		  $v_listing = false;
	  break;
	  case "list" :
		  $v_extract_all = false;
		  $v_listing = true;
	  break;
	  default :
		$this->_error('Invalid extract mode ('.$p_mode.')');
		return false;
	}

	clearstatcache();

	while (strlen($v_binary_data = $this->_readBlock()) != 0)
	{
	  $v_extract_file = FALSE;
	  $v_extraction_stopped = 0;

	  if (!$this->_readHeader($v_binary_data, $v_header))
		return false;

	  if ($v_header['filename'] == '') {
		continue;
	  }

	  // ----- Look for long filename
	  if ($v_header['typeflag'] == 'L') {
		if (!$this->_readLongHeader($v_header))
		  return false;
	  }

	  if ((!$v_extract_all) && (is_array($p_file_list))) {
		// ----- By default no unzip if the file is not found
		$v_extract_file = false;

		for ($i=0; $i<sizeof($p_file_list); $i++) {
		  // ----- Look if it is a directory
		  if (substr($p_file_list[$i], -1) == '/') {
			// ----- Look if the directory is in the filename path
			if ((strlen($v_header['filename']) > strlen($p_file_list[$i]))
				&& (substr($v_header['filename'], 0, strlen($p_file_list[$i]))
					== $p_file_list[$i])) {
			  $v_extract_file = true;
			  break;
			}
		  }

		  // ----- It is a file, so compare the file names
		  elseif ($p_file_list[$i] == $v_header['filename']) {
			$v_extract_file = true;
			break;
		  }
		}
	  } else {
		$v_extract_file = true;
	  }

	  // ----- Look if this file need to be extracted
	  if (($v_extract_file) && (!$v_listing))
	  {
		if (($p_remove_path != '')
			&& (substr($v_header['filename'], 0, $p_remove_path_size)
				== $p_remove_path))
		  $v_header['filename'] = substr($v_header['filename'],
										 $p_remove_path_size);
		if (($p_path != './') && ($p_path != '/')) {
		  while (substr($p_path, -1) == '/')
			$p_path = substr($p_path, 0, strlen($p_path)-1);

		  if (substr($v_header['filename'], 0, 1) == '/')
			  $v_header['filename'] = $p_path.$v_header['filename'];
		  else
			$v_header['filename'] = $p_path.'/'.$v_header['filename'];
		}
		if (file_exists($v_header['filename'])) {
		  if (   (@is_dir($v_header['filename']))
			  && ($v_header['typeflag'] == '')) {
			$this->_error('File '.$v_header['filename']
						  .' already exists as a directory');
			return false;
		  }
		  if (   ($this->_isArchive($v_header['filename']))
			  && ($v_header['typeflag'] == "5")) {
			$this->_error('Directory '.$v_header['filename']
						  .' already exists as a file');
			return false;
		  }
		  if (!is_writeable($v_header['filename'])) {
			//We cannot use $globals['ofc'] here and after restoring the files we are anyways changing the file's permissions according to the perms file. Therefore, using 0644/0755 directly here shouldn't be an issue.
			if(is_dir($v_header['filename'])){
				$chmod = chmod($v_header['filename'], 0755);
			}else{
				$chmod = chmod($v_header['filename'], 0644);
			}
			if (!is_writeable($v_header['filename'])) {
				$this->_error('File '.$v_header['filename']
						  .' already exists and is write protected');
				return false;
			}
		  }
		  if (filemtime($v_header['filename']) > $v_header['mtime']) {
			// To be completed : An error or silent no replace ?
		  }
		}

		// ----- Check the directory availability and create it if necessary
		elseif (($v_result
				 = $this->_dirCheck(($v_header['typeflag'] == "5"
									?$v_header['filename']
									:dirname($v_header['filename'])))) != 1) {
			$this->_error('Unable to create path for '.$v_header['filename']);
			return false;
		}

		if ($v_extract_file) {
		  if ($v_header['typeflag'] == "5") {
			if (!@file_exists($v_header['filename'])) {
				if (!@mkdir($v_header['filename'], 0777)) {
					$this->_error('Unable to create directory {'
								  .$v_header['filename'].'}');
					return false;
				}
			}
		  } elseif ($v_header['typeflag'] == "2") {
			  if (@file_exists($v_header['filename'])) {
				 @unlink($v_header['filename']);
			  }
			  if (!@symlink($v_header['link'], $v_header['filename'])) {
				  $this->_error('Unable to extract symbolic link {'
								.$v_header['filename'].'}');
				  return false;
			  }
		  } else {
			  if (($v_dest_file = @fopen($v_header['filename'], "wb")) == 0) {
				  $this->_error('Error while opening {'.$v_header['filename']
								.'} in write binary mode');
				  return false;
			  } else {
				  $n = floor($v_header['size']/512);
				  for ($i=0; $i<$n; $i++) {
					  $v_content = $this->_readBlock();
					  fwrite($v_dest_file, $v_content, 512);
				  }
			if (($v_header['size'] % 512) != 0) {
			  $v_content = $this->_readBlock();
			  fwrite($v_dest_file, $v_content, ($v_header['size'] % 512));
			}

			@fclose($v_dest_file);
			
			if ($p_preserve) {
				@chown($v_header['filename'], $v_header['uid']);
				@chgrp($v_header['filename'], $v_header['gid']);
			}

			// ----- Change the file mode, mtime
			@touch($v_header['filename'], $v_header['mtime']);
			if ($v_header['mode'] & 0111) {
				// make file executable, obey umask
				$mode = fileperms($v_header['filename']) | (~umask() & 0111);
				@chmod($v_header['filename'], $mode);
			}
		  }

		  // ----- Check the file size
		  clearstatcache();
		  if (!is_file($v_header['filename'])) {
			  $this->_error('Extracted file '.$v_header['filename']
							.'does not exist. Archive may be corrupted.');
			  return false;
		  }
		  
		  $filesize = filesize($v_header['filename']);
		  if ($filesize != $v_header['size']) {
			  $this->_error('Extracted file '.$v_header['filename']
							.' does not have the correct file size \''
							.$filesize
							.'\' ('.$v_header['size']
							.' expected). Archive may be corrupted.');
			  return false;
		  }
		  }
		} else {
		  $this->_jumpBlock(ceil(($v_header['size']/512)));
		}
	  } else {
		  $this->_jumpBlock(ceil(($v_header['size']/512)));
	  }

	  if ($v_listing || $v_extract_file || $v_extraction_stopped) {
		// ----- Log extracted files
		if (($v_file_dir = dirname($v_header['filename']))
			== $v_header['filename'])
		  $v_file_dir = '';
		if ((substr($v_header['filename'], 0, 1) == '/') && ($v_file_dir == ''))
		  $v_file_dir = '/';

		// Only if we are to return the list i.e. in listContent() then we fill full $v_header else we just need the count
		$p_list_detail[$v_nb++] = (!empty($v_listing) ? $v_header : '');
		if (is_array($p_file_list) && (count($p_list_detail) == count($p_file_list))) {
			return true;
		}
	  }
	}

		return true;
	}
	// }}}

	// {{{ _openAppend()
	function _openAppend()
	{
		
		if (filesize($this->_tarname) == 0)
		  return $this->_openWrite();

		if ($this->_compress) {
			$this->_close();

			if (!@rename($this->_tarname, $this->_tarname.".tmp")) {
				$this->_error('Error while renaming \''.$this->_tarname
							  .'\' to temporary file \''.$this->_tarname
							  .'.tmp\'');
				return false;
			}

			if ($this->_compress_type == 'gz')
				$v_temp_tar = @gzopen($this->_tarname.".tmp", "rb");
			elseif ($this->_compress_type == 'bz2')
				$v_temp_tar = @bzopen($this->_tarname.".tmp", "r");

			if ($v_temp_tar == 0) {
				$this->_error('Unable to open file \''.$this->_tarname
							  .'.tmp\' in binary read mode');
				@rename($this->_tarname.".tmp", $this->_tarname);
				return false;
			}

			if (!$this->_openWrite()) {
				@rename($this->_tarname.".tmp", $this->_tarname);
				return false;
			}

			if ($this->_compress_type == 'gz') {
				$end_blocks = 0;
				
				while (!@gzeof($v_temp_tar)) {
					$v_buffer = @gzread($v_temp_tar, 512);
					if ($v_buffer == ARCHIVE_TAR_END_BLOCK || strlen($v_buffer) == 0) {
						$end_blocks++;
						// do not copy end blocks, we will re-make them
						// after appending
						continue;
					} elseif ($end_blocks > 0) {
						for ($i = 0; $i < $end_blocks; $i++) {
							if(!$this->_writeBlock(ARCHIVE_TAR_END_BLOCK)){
								return false;
						  }
						}
						$end_blocks = 0;
					}
					$v_binary_data = pack("a512", $v_buffer);
					if(!$this->_writeBlock($v_binary_data)){
						return false;
				  }
				}

				@gzclose($v_temp_tar);
			}
			elseif ($this->_compress_type == 'bz2') {
				$end_blocks = 0;
				
				while (strlen($v_buffer = @bzread($v_temp_tar, 512)) > 0) {
					if ($v_buffer == ARCHIVE_TAR_END_BLOCK || strlen($v_buffer) == 0) {
						$end_blocks++;
						// do not copy end blocks, we will re-make them
						// after appending
						continue;
					} elseif ($end_blocks > 0) {
						for ($i = 0; $i < $end_blocks; $i++) {
							if(!$this->_writeBlock(ARCHIVE_TAR_END_BLOCK)){
								return false;
							}
						}
						$end_blocks = 0;
					}
					$v_binary_data = pack("a512", $v_buffer);
					if(!$this->_writeBlock($v_binary_data)){
					return false;
				  }
				}

				@bzclose($v_temp_tar);
			}

			if (!@unlink($this->_tarname.".tmp")) {
				$this->_error('Error while deleting temporary file \''
							  .$this->_tarname.'.tmp\'');
			}

		} else {
			// ----- For not compressed tar, just add files before the last
			//	   one or two 512 bytes block
			if (!$this->_openReadWrite())
			   return false;

			clearstatcache();
			$v_size = filesize($this->_tarname);

			// We might have zero, one or two end blocks.
			// The standard is two, but we should try to handle
			// other cases.
			fseek($this->_file, $v_size - 1024);
			if (fread($this->_file, 512) == ARCHIVE_TAR_END_BLOCK) {
				fseek($this->_file, $v_size - 1024);
			}
			elseif (fread($this->_file, 512) == ARCHIVE_TAR_END_BLOCK) {
				fseek($this->_file, $v_size - 512);
			}
		}

		return true;
	}
	// }}}

	// {{{ _append()
	function _append($p_filelist, $p_add_dir = '', $p_remove_dir = '')
	{
		if (!$this->_openAppend())
			return false;

		if ($this->_addList($p_filelist, $p_add_dir, $p_remove_dir))
		   $this->_writeFooter();

		$this->_close();

		return true;
	}
	// }}}

	// {{{ _dirCheck()
	function _dirCheck($p_dir)
	{
		clearstatcache();
		if ((@is_dir($p_dir)) || ($p_dir == ''))
			return true;

		$p_parent_dir = dirname($p_dir);

		if (($p_parent_dir != $p_dir) &&
			($p_parent_dir != '') &&
			(!$this->_dirCheck($p_parent_dir)))
			 return false;

		if (!@mkdir($p_dir, 0777)) {
			$this->_error("Unable to create directory '$p_dir'");
			return false;
		}

		return true;
	}

	// }}}

	// {{{ _pathReduction()
	function _pathReduction($p_dir)
	{
		$v_result = '';

		// ----- Look for not empty path
		if ($p_dir != '') {
			// ----- Explode path by directory names
			$v_list = explode('/', $p_dir);

			// ----- Study directories from last to first
			for ($i=sizeof($v_list)-1; $i>=0; $i--) {
				// ----- Look for current path
				if ($v_list[$i] == ".") {
					// ----- Ignore this directory
					// Should be the first $i=0, but no check is done
				}
				else if ($v_list[$i] == "..") {
					// ----- Ignore it and ignore the $i-1
					$i--;
				}
				else if (   ($v_list[$i] == '')
						 && ($i!=(sizeof($v_list)-1))
						 && ($i!=0)) {
					// ----- Ignore only the double '//' in path,
					// but not the first and last /
				} else {
					$v_result = $v_list[$i].($i!=(sizeof($v_list)-1)?'/'
								.$v_result:'');
				}
			}
		}
		
		if (defined('OS_WINDOWS') && OS_WINDOWS) {
			$v_result = strtr($v_result, '\\', '/');
		}
		
		return $v_result;
	}

	// }}}

	// {{{ _translateWinPath()
	function _translateWinPath($p_path, $p_remove_disk_letter = true) {
		if (defined('OS_WINDOWS') && OS_WINDOWS) {
			// ----- Look for potential disk letter
			if (($p_remove_disk_letter) && (($v_position = strpos($p_path, ':')) != false)) {
				$p_path = substr($p_path, $v_position+1);
			}
			
			// ----- Change potential windows directory separator
			if ((strpos($p_path, '\\') > 0) || (substr($p_path, 0,1) == '\\')) {
				$p_path = strtr($p_path, '\\', '/');
			}
		}
		
		return $p_path;
	}
	// }}}

	function cleanpath($path){	
		$path = str_replace('\\\\', '/', $path);
		$path = str_replace('\\', '/', $path);
		return rtrim($path, '/');
	}
}