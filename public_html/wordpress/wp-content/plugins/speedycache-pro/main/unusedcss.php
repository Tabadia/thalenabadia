<?php

/*
* SPEEDYCACHE
* https://speedycache.com/
* (c) SpeedyCache Team
*/

namespace SpeedyCache;

if(!defined('ABSPATH')){
	die('Hacking Attempt');
}

class UnusedCss extends CommonCss{

	static function generate($urls){
		global $speedycache;

		$time = time() + 50;
		set_time_limit(60);
		
		$api = self::get_endpoint(true);
		
		if(empty($urls)){
			self::log('speedycache_unused_css_logs', 'URL not found');
			return false;
		}

		if(empty($speedycache->license['license'])){
			self::log('speedycache_unused_css_logs', 'License Not found, please link your License');
			return false;
		}

		foreach($urls as $url){
			// Handeling php timeout here
			if($time < time()){
				$urls = array_diff($urls, $attempted_url);
				self::schedule('speedycache_unused_css', $urls);
				return;
			}

			$url = trim($url, '/');
			$license = strpos($speedycache->license['license'], 'SPDFY') !== 0 ? '' : $speedycache->license['license'];
			$attempted_url[] = $url;

			$response = wp_remote_post($api, array(
				'timeout' => 30,
				'body' => array(
					'url' => $url,
					'license' => $license,
					'excludes' => !empty($speedycache->options['unused_css_exclude_stylesheets']) ? json_encode($speedycache->options['unused_css_exclude_stylesheets']) : '',
					'include_selectors' => !empty($speedycache->options['speedycache_unusedcss_include_selector']) ? json_encode($speedycache->options['speedycache_unusedcss_include_selector']) : '',
				),
				'sslverify' => false,
			));

			if(is_wp_error($response)){
				$error = $response->get_error_message();
				self::log('speedycache_unused_css_logs', $response->get_error_message(), $url);
				continue;
			}

			$body = json_decode(wp_remote_retrieve_body($response), true);

			if(empty($body)){
				$error = __('The response recieved is empty.', 'speedycache');
				self::log('speedycache_unused_css_logs', __('The response recieved is empty.', 'speedycache'), $url);
				continue;
			}

			if(empty($body['success'])){
				$error = !empty($body['message']) ? wp_strip_all_tags($body['message']) : __('Unable to extract UsedCSS', 'speedycache');
				self::log('speedycache_unused_css_logs', !empty($body['message']) ? wp_strip_all_tags($body['message']) : __('Unable to extract UsedCSS', 'speedycache'), $url);
				continue;
			}

			if(empty($body['css']) || strlen($body['css']) < 20){
				$error = __('Was unable to generate Used CSS', 'speedycache');
				self::log('speedycache_unused_css_logs', __('Was unable to generate Used CSS', 'speedycache'), $url);
				continue;
			}

			self::update_css($url, $body['css']);

			self::log('speedycache_unused_css_logs', 'success', $url); //Updates the log on success
			
			if(!empty($error)){
				return $error;
			}

			return true;
		}
	}
	
	// Adds the generated css and asynchronyses the css includes
	static function update_css($url, $css){
		global $speedycache;

		if(empty($url)){
			return false;
		}
		
		if(empty($css)){
			return false;
		}

		$css = '<style id="speedycache-used-css">'. "\n". wp_strip_all_tags($css) . '</style>';

		$url = parse_url($url);
		$uri = !empty($url['path']) ?  $url['path'] : '';
		$cache_loc = $uri . '/index.html';

		if(empty($cache_loc)){
			return;
		}
		
		if(!empty($_SERVER['HTTP_USER_AGENT']) && $_SERVER['HTTP_USER_AGENT'] === 'SpeedyCacheTest'){
			$cache_path = speedycache_cache_path('test' . $cache_loc);
		} else {
			$cache_path = speedycache_cache_path('all' . $cache_loc);
		}
		
		// For Desktop
		\SpeedyCache\UnusedCss::update_cached($cache_path, $css);

		if(!empty($speedycache->options['mobile_theme']) && $_SERVER['HTTP_USER_AGENT'] !== 'SpeedyCacheTest'){
			$cache_mobile = speedycache_cache_path('mobile-cache' . $cache_loc);

			// For Mobile Cache
			if(file_exists($cache_mobile)){
				\SpeedyCache\UnusedCss::update_cached($cache_mobile, $css);
			}
		}
	}

	// Updates the content of the cached file
	static function update_content($content, $css){
		global $speedycache;

		// Includes the Used CSS file in the head.
		$content = str_replace('</title>', '</title>' . $css, $content);

		$css_links = '/(?=<link[^>]*\s(rel\s*=\s*[\'"]stylesheet["\']))<link[^>]*\shref\s*=\s*[\'"]([^\'"]+)[\'"](.*)>/iU';
		
		preg_match_all($css_links, $content, $matches, PREG_SET_ORDER, 0);
		
		if(empty($matches)){
			return $content;
		}
		
		$exclude_stylesheets = array(
			'/widget-google-reviews/assets/css/public-main.css',
			'/uploads/elementor/css/post-',
			'/uploads/oxygen/css/',
			'/uploads/bb-plugin/cache/',
			'/et-cache/',
			'woocommerce-smallscreen.css',
			'dashicons.min.css',
			'animations.min.css',
			'/uploads/generateblocks/',
			'woocommerce-mobile.min.css',
			'fonts.googleapis.com',
		);
		
		// Mergeing user added excludes which the one we have.
		if(!empty($speedycache->options['unused_css_exclude_stylesheets']) && !is_array($speedycache->options['unused_css_exclude_stylesheets'])){
			$exclude_stylesheets = array_merge($exclude_stylesheets, $speedycache->options['unused_css_exclude_stylesheets']);
		}
		
		$noscript_wrap = '';
		
		foreach($matches as $tag){

			foreach($exclude_stylesheets as $style){
				if(strpos($tag[0], $style) !== FALSE){
					continue 2;
				}
			}
			
			// We dont want to delay te Used CSS file
			if(strpos($tag[0], 'unused-css') !== FALSE){
				continue;
			}

			// Removing the styles after getting unused css
			if(!empty($speedycache->options['unusedcss_load']) && $speedycache->options['unusedcss_load'] === 'remove'){
				$content = str_replace($tag[0], '', $content);		
			} elseif(!empty($speedycache->options['unusedcss_load']) && $speedycache->options['unusedcss_load'] === 'interaction'){
				$new_tag = preg_replace('#href=([\'"]).+?\1#', 'data-spcdelay="' . $tag[2] . '"',$tag[0]);
			
				$content = str_replace($tag[0], $new_tag, $content);
				
				$noscript_wrap .= $tag[0];
			} else {
				// Loading the Unused CSS Async
				$preload = str_replace('stylesheet', 'preload', $tag[1]);
				$onload = preg_replace('~' . preg_quote($tag[3], '~') . '~iU', ' as="style" onload="" ' . $tag[3] . '>', $tag[3]);

				$new_tag = str_replace($tag[3] . '>', $onload, $tag[0]);
				$new_tag = str_replace($tag[1], $preload, $new_tag);
				$new_tag = str_replace('onload=""', 'onload="this.onload=null;this.rel=\'stylesheet\'"', $new_tag);
				$new_tag = preg_replace('/(id\s*=\s*[\"\'](?:[^\"\']*)*[\"\'])/i', '', $new_tag);

				$content = str_replace($tag[0], $new_tag, $content);
				
				$noscript_wrap .= $tag[0];
			}
		}
		
		if(!empty($noscript_wrap)){
			$noscript_wrap .= '</noscript>';
			
			if(!empty($speedycache->options['unusedcss_load']) && $speedycache->options['unusedcss_load'] === 'interaction'){
				$noscript_wrap .= '<script type="text/javascript" id="speedycache-delayed-styles">!function(){const e=["keydown","mousemove","wheel","touchmove","touchstart","touchend"];function t(){document.querySelectorAll("link[data-spcdelay]").forEach(function(e){e.setAttribute("href",e.getAttribute("data-spcdelay"))}),e.forEach(function(e){window.removeEventListener(e,t,{passive:!0})})}e.forEach(function(e){window.addEventListener(e,t,{passive:!0})})}();</script>';
			}
			
			$noscript_wrap = '<noscript>' . $noscript_wrap;
		
			$content = str_replace($noscript_wrap, '', $content);
			
			$content = str_replace('</body>', $noscript_wrap . '</body>', $content);
		}

		return $content;
	}
}
