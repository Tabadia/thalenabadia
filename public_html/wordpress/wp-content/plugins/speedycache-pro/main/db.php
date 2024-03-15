<?php
/*
* SPEEDYCACHE
* https://speedycache.com/
* (c) SpeedyCache Team
*/

namespace SpeedyCache;

if(file_exists(dirname(__FILE__) . '/class.plugin-modules.php')){
	include_once(dirname(__FILE__) . '/class.plugin-modules.php');
}

if( !defined('SPEEDYCACHE_PRO_VERSION') ){
	die('HACKING ATTEMPT!');
}


class DB{

	static function clean($type){
		global $wpdb;

		if($type === 'transient_options'){
			$wpdb->query("DELETE FROM `$wpdb->options` WHERE option_name LIKE '%\_transient\_%' ;");
			wp_send_json(array('success' => true));
		}
		
		if($type === 'expired_transient'){
			$wpdb->query("DELETE FROM `$wpdb->options` WHERE option_name LIKE '_transient_timeout%' AND option_value < " . time());
			
			wp_send_json(array('success' => true));
		}
		
		if($type === 'trackback_pingback'){
			$wpdb->query("DELETE FROM `$wpdb->comments` WHERE comment_type = 'trackback' OR comment_type = 'pingback' ;");
			wp_send_json(array('success' => true));
		}
		
		if($type === 'trashed_spam_comments'){
			$wpdb->query("DELETE FROM `$wpdb->comments` WHERE comment_approved = 'spam' OR comment_approved = 'trash' ;");
			wp_send_json(array('success' => true));
		}
		
		if($type === 'trashed_contents'){
			$wpdb->query("DELETE FROM `$wpdb->posts` WHERE post_status = 'trash';");
			wp_send_json(array('success' => true));
		}
		
		if($type === 'post_revisions'){
			$wpdb->query("DELETE FROM `$wpdb->posts` WHERE post_type = 'revision';");
			wp_send_json(array('success' => true));
		}
		
		if($type === 'all_warnings'){
			$wpdb->query("DELETE FROM `$wpdb->posts` WHERE post_type = 'revision';");
			$wpdb->query("DELETE FROM `$wpdb->posts` WHERE post_status = 'trash';");
			$wpdb->query("DELETE FROM `$wpdb->comments` WHERE comment_approved = 'spam' OR comment_approved = 'trash' ;");
			$wpdb->query("DELETE FROM `$wpdb->comments` WHERE comment_type = 'trackback' OR comment_type = 'pingback' ;");
			$wpdb->query("DELETE FROM `$wpdb->options` WHERE option_name LIKE '%\_transient\_%' ;");
			$wpdb->query("DELETE FROM `$wpdb->options` WHERE option_name LIKE '_transient_timeout%' AND option_value < " . time());

			wp_send_json(array('success' => true));
		}
	}

}

