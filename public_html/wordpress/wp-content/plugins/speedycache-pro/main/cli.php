<?php
/*
* SPEEDYCACHE
* https://speedycache.com/
* (c) SpeedyCache Team
*/

if(!defined('ABSPATH')){
    exit;
}

if(!defined('WP_CLI')){
    return;
}

class speedycache_cli extends \WP_CLI_Command{


	/**
	  * Purges/Cleares cache or minified files
	  * ## OPTIONS
	  * <type>
	  * : Purges cache or minified
	  * ---
	  * options:
	  *  - cache
	  *  - and minified
	  *
	  * ## EXAMPLES
	  * # Purge Cache
	  * $ wp speedycache purge cache
	  *
	  * # Purge cache and minfied
	  * $ wp speedycache purge cache and minified
	*/
	public function purge($args, $args_assoc){
		global $speedycache;

		if(!isset($speedycache)){
			WP_CLI::error('speedycache has not been defined!');
		}

		if(!function_exists('speedycache_delete_cache')){
			WP_CLI::error('speedycache_delete_cache() does not exist!');
		}

		if(empty($args[0]) || $args[0] !== 'cache'){
			self::wrong_usage();
		}
		
		if(empty($args[1]) || empty($args[2])){
			$this->delete_cache();
			return;
		}
		
		if($args[1] == 'and' && $args[2] == 'minified'){
			$this->delete_cache(true);
			return;
		}
	
		self::wrong_usage();
	}
	
	private function delete_cache($minified_too = false){
		if(function_exists('speedycache_delete_cache')){
			WP_CLI::error('Somethinng Went Wrong: Unable to delete cache');
		}

		WP_CLI::line('Clearing the ALL cache...');
		speedycache_delete_cache($minified_too);
		WP_CLI::success('The cache has been cleared!');
	}
}

WP_CLI::add_command('speedycache', 'speedycache_cli');