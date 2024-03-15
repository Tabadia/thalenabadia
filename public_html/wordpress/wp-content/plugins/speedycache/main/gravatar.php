<?php

namespace SpeedyCache;

if(!defined('ABSPATH')){
	die('Hacking Attempt');
}

class Gravatar{
	
	// Gets gravatar data
	static function get_avatar_data($args, $id_or_email){

		if(empty($args) || empty($args['found_avatar'])){
			return $args;
		}
		
		if(empty($args['url'])){
			return $args;
		}
		
		// Path to Gravatars
		$path = speedycache_cache_path('gravatars/');

		if(!is_dir($path)){
			mkdir($path);
			touch($path . 'index.html');
		}

		$email_hash = self::get_email_hash($id_or_email);
		if(empty($email_hash)){
			$email_hash = 'default';
		}

		$file_name = $email_hash . 'x' . $args['size'] . '.jpg';
		$file = $path . $file_name;

		if(file_exists($file)){
			$url = self::convert_path_to_link($path) . $file_name;

			$args['url'] = esc_url($url);
			return $args;
		}

		$res = wp_remote_get($args['url']);

		if(empty($res) || is_wp_error($res)){
			return $args;
		}

		if(empty($res['body'])){
			return $args;
		}

		// If we fail to write return the same URL;
		if(!file_put_contents($file, $res['body'])){
			return $args;
		}

		$url = self::convert_path_to_link($path) . $file_name;

		$args['url'] = esc_url($url);

		return $args;
	}
	
	// Gets the Email hash which is used in the URL.
	static function get_email_hash($id_or_email){

		if(is_numeric($id_or_email)){
			$user = get_user_by('id', $id_or_email);

			if(empty($user) || !is_a($user, 'WP_User')){
				return false;
			}

		} elseif(is_a($id_or_email, 'WP_User')){
			$user = $id_or_email;
		} elseif(is_a($id_or_email, 'WP_Post')){
			$user = get_user_by('id', (int) $id_or_email->post_author);
		} elseif(is_a($id_or_email, 'WP_Comment')){
			if(!empty($id_or_email->user_id)){
				$user = get_user_by('id', (int) $id_or_email->user_id);
			}

			if((empty($user) || is_wp_error($user)) && !empty($id_or_email->comment_author_email)){
				$id_or_email = $id_or_email->comment_author_email;
			}

			if(is_a($id_or_email, 'WP_Comment')){
				return false;
			}
		}
		
		if(!empty($user) && is_a($user, 'WP_User')){
			$id_or_email = $user->user_email;
		}
		
		// We need an email which should be a string if something else is being passed then just return
		if(!is_string($id_or_email)){
			return false;
		}

		$email_hash = md5(strtolower(trim($id_or_email)));
		
		return $email_hash;
	}
	
	// Deletes all the gravatar stored
	static function delete(){
		$path = speedycache_cache_path('gravatars/');
		
		$files = scandir($path);
		
		if(empty($files)){
			return __('No file present to delete', 'speedycache');
		}
		
		foreach($files as $file){
			// We dont want to delete index.html or any directory.
			if(file_exists($path . $file) && !is_dir($path . $file) && $file != 'index.html'){
				@unlink($path . $file);
			}
		}

		return __('Gravatar files deleted', 'speedycache');
	}
	
	static function convert_path_to_link($path){
		preg_match('/\/cache\/speedycache\/.+/', $path, $out);
		$prefix_link = str_replace(array('http:', 'https:'), '', SPEEDYCACHE_WP_CONTENT_URL);

		return $prefix_link . $out[0];
	}

}