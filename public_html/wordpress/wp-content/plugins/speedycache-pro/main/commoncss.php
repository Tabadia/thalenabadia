<?php 

/*
* SPEEDYCACHE
* https://speedycache.com/
* (c) SpeedyCache Team
*/

namespace SpeedyCache;

if(!defined('ABSPATH')){
	die('Hacking Attempt!');
}

class CommonCss{
	
	static function get_endpoint($is_unusedcss = false){
		global $speedycache;

		$endpoints = get_transient('speedycache_ccss_endpoint');

		$target_file = empty($is_unusedcss) ? 'index.php' : 'ucss.php';

		$mirror = 'https://s4.softaculous.com/a/speedycache/critical-css/' . $target_file;		
		$license = strpos($speedycache->license['license'], 'SPDFY') !== 0 ? '' : $speedycache->license['license'];

		if(empty($endpoints)){
			$res = wp_remote_get(SPEEDYCACHE_API.'license.php?license='.$license);

			//Did we get a response ?
			if(!is_array($res)){
				return $mirror;
			}
			
			if(empty($res['body'])){
				return $mirror;
			}

			$body = json_decode($res['body'], true);

			if(empty($body['fast_mirrors'])){
				return $mirror;
			}
			
			$endpoints = $body['fast_mirrors'];
			
			if(empty($endpoints) || !is_array($endpoints)){
				return $mirror;
			}
		}
		
		$index = floor(rand(0, count($endpoints) - 1));

		if(empty($endpoints[$index])){
			return $mirror;
		}

		set_transient('speedycache_ccss_endpoint', $endpoints, 180);

		$mirror = str_replace('a/softaculous', 'a/speedycache/critical-css/'.$target_file, $endpoints[$index]);
		
		return $mirror;
		
	}
	
	static function schedule($schedule_name, $urls){

		$scheduled = self::get_schedule(array($schedule_name));
		$time = time();
		
		if(!empty($scheduled) && isset(end($scheduled)['time'])){
			// getting the last index to get the last scheduled event
			$time = end($scheduled)['time'] + 10;
		}
		
		$final_schd_time = $time;
	
		if(!wp_next_scheduled($schedule_name, array('urls' => $urls))){
			wp_schedule_single_event($final_schd_time, $schedule_name, array('urls' => $urls));
		}
	}
	
	// Returns an array of cron event "speedycache_unused_css"
	static function get_schedule($event_name){
		$cron = _get_cron_array();
		
		if(empty($cron)){
			return false;
		}
		
		$scheduled = array();
		
		foreach($cron as $key => $crn){
			foreach($crn as $e_key => $event){
				if(!in_array($e_key, $event_name)){
					continue;
				}

				$args = [];

				foreach($event as $evt){
					if(!empty($evt['args'][0])){
						$args = $evt['args'][0];
					}
				}
				
				array_push($scheduled, array('name' => get_the_title($args), 'time' => $key));
			}
		}
		
		return $scheduled;
	}
	
	// Adds the Critical CSS to the cache file
	static function update_cached($file, $css){
		global $speedycache;

		if(!file_exists($file)){
			return;
		}

		$content = file_get_contents($file);
		
		if(empty($content)){
			return;
		}
		
		$content = static::update_content($content, $css);

		// Updates the .html.gz file
		if(!empty($speedycache->options['gzip'])){
			self::update_gzip($file, $content);
		}
		
		// Updates the .html file
		file_put_contents($file, $content);
	}

	static function update_gzip($file, $content){
		
		$gz_file = str_replace('.html', '.html.gz', $file);
		
		if(file_exists($gz_file)){
			unlink($gz_file);
		}
		
		$content = gzencode($content, 6);
		
		if(!empty($content)){
			file_put_contents($gz_file, $content);
		}
	}
	
	static function log($log_name, $message, $url = '-'){
		$ccss_logs = get_option($log_name, []);
		
		if(count($ccss_logs) > 10){
			array_shift($ccss_logs);
		}
		
		$ccss_logs[$url] = array('time' => date('h:i:s'), 'message' => $message);
		update_option($log_name, $ccss_logs);
	}
	
	
}