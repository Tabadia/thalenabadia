<?php
/*
* SPEEDYCACHE
* https://speedycache.com/
* (c) SpeedyCache Team
*/

namespace SpeedyCache;

// Third Party Plugins
class Install{
	
	// Called during activation
	static function activate(){
		
		global $speedycache;
		
		if(empty($speedycache)){
			$speedycache = new \SpeedyCache();
		}
		
		$speedycache->options = get_option('speedycache_options', []);
		$speedycache->options['lbc'] = true;
		$speedycache->options['minify_css'] = true;
		$speedycache->options['gzip'] = true;
		$speedycache->options['automatic_cache'] = true;

		update_option('speedycache_options', $speedycache->options);
		update_option('speedycache_version', SPEEDYCACHE_VERSION);

		\SpeedyCache\htaccess::modify();
		
	}

	// Called during Deactivation
	static function deactivate(){
		speedycache_set_host();
		$path = speedycache_get_abspath();

		if(is_file($path.'.htaccess') && is_writable($path.'.htaccess')){
			$htaccess = file_get_contents($path.'.htaccess');
			$htaccess = preg_replace("/#\s?BEGIN\s?speedycache.*?#\s?END\s?speedycache/s", '', $htaccess);
			$htaccess = preg_replace("/#\s?BEGIN\s?Gzipspeedycache.*?#\s?END\s?Gzipspeedycache/s", '', $htaccess);
			$htaccess = preg_replace("/#\s?BEGIN\s?LBCspeedycache.*?#\s?END\s?LBCspeedycache/s", '', $htaccess);
			$htaccess = preg_replace("/#\s?BEGIN\s?WEBPspeedycache.*?#\s?END\s?WEBPspeedycache/s", '', $htaccess);
			@file_put_contents($path.'.htaccess', $htaccess);
		}

		speedycache_delete_cache(false, false, true);
	}

}
