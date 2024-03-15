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

class GoogleFonts{

	// Plucks out the google font urls from the content
	static function get($content){

		preg_match_all('/<link.*href=(["\'])(.*fonts\.googleapis\.com\/css.*?)\1/m', $content, $font_rels);

		//We just need the index 2
		if(empty($font_rels[2])){
			return $content;
		}
		
		$fonts = array();
		
		for($i = 0; $i < count($font_rels[2]); $i++){
			if(empty($font_rels[2][$i])){
				continue;
			}
		
			$fonts[md5($font_rels[2][$i])] = $font_rels[2][$i];
		}
		
		if(empty($fonts)){
			return;
		}

		self::fetch($fonts);
	}

	// Reads the font css and saves it to /speedycache/fonts/font-name/
	static function fetch($fonts){
		
		$html = '<!DOCTYPE html>
<html>
<body>
<a href="https://speedycache.com">SpeedyCache</a>
</body>
</html>';
		

		foreach($fonts as $font_name => $url){
			$url = esc_url($url);

			if(substr($url, 0, 2) === '//'){
				$url = 'https:' . $url;
			}

			$response = wp_remote_get($url, array('user-agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_4) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/81.0.4044.122 Safari/537.36'));
			
			if(is_wp_error($response) || !is_array($response)){
				continue;
			}
			
			$css = wp_remote_retrieve_body($response);
			
			if(is_wp_error($css) || empty($css)){
				continue;
			}
			
			if(!file_exists(speedycache_cache_path('fonts'))){
				@mkdir(speedycache_cache_path('fonts'));
			}
			
			if(!file_exists(speedycache_cache_path('fonts/index.html'))){
				file_put_contents(speedycache_cache_path('fonts/index.html'), $html);
			}

			if(!file_exists(speedycache_cache_path('fonts/').$font_name)){
				@mkdir(speedycache_cache_path('fonts/').$font_name);
			}
			
			if(!file_exists(speedycache_cache_path('fonts/').$font_name . '/index.html')){
				file_put_contents(speedycache_cache_path('fonts/').$font_name . '/index.html', $html);
			}

			preg_match_all('/url\((.*?)\)/m', $response['body'], $urls); // Get URL from the CSS we got

			if(empty($urls) || empty($urls[1])){
				continue;
			}
			
			foreach($urls[1] as $url){
				$file_name = basename($url);
				
				if(file_exists(speedycache_cache_path('fonts/') . $font_name . '/' . $file_name)){
					continue;
				}

				if(strpos($url, 'display=swap') === FALSE){
					$url_to_hit = add_query_arg(array('display' => 'swap'), $url);
				}

				$response = wp_remote_get($url_to_hit);

				if(is_wp_error($response) || !is_array($response)){
					continue;
				}
				
				$font = wp_remote_retrieve_body($response);

				if(is_wp_error($font) || empty($font)){
					continue;
				}

				file_put_contents(speedycache_cache_path('fonts/').$font_name.'/'.$file_name, $font); // Creating the font file
				$css = str_replace($url, SPEEDYCACHE_CACHE_URL .'/'. SPEEDYCACHE_SERVER_HOST . '/fonts/'. $font_name .'/'. $file_name, $css);
			}

			if(file_exists(speedycache_cache_path('fonts/').$font_name.'/'.$font_name . '.css')){
				return;
			}

			//If we need to add swap then either we failed to add display=swap to the url or it didnt return what we expected.
			if(strpos($css, 'swap') === FALSE){
				$css = preg_replace('/(^@font-face\s{)/m', "$1\n  font-display: swap;", $css);
			}
			
			file_put_contents(speedycache_cache_path('fonts/').$font_name.'/'.$font_name . '.css', $css);
		}
	}

	// Replaces font url to the local font url
	static function replace($content){
		
		$cache_dir = speedycache_cache_path();
		
		if(!is_dir($cache_dir . '/fonts')){
			@mkdir($cache_dir . '/fonts');
		}

		$fonts = array_diff(@scandir($cache_dir . 'fonts'), array('..', '.'));
		
		if(empty($fonts)){
			return $content;
		}

		// To remove any preload or dns-fetch or preconnect for google fonts
		preg_match_all('/<link(?:[^>]+)?href=(["\'])([^>]*?fonts\.(gstatic|googleapis)\.com.*?)\1.*?>/i', $content, $google_links, PREG_SET_ORDER);
			
		if(!empty($google_links)){
			foreach($google_links as $google_link){

				preg_match('/rel=(["\'])(.*?(preload|preconnect|dns-fetch).*?)\1/i', $google_link[2], $removeable_link);

				if(!empty($removeable_link)){
					$content = str_replace($google, '', $html);
				}
			}
		}
		
		/**
		  * Our Font css name is in md5(created from the font URL) and we dont have URL in this function to get
		  * all the google fonts url to replace the fonts .
		*/
		preg_match_all('/<link.*href=(["\'])(.*fonts\.googleapis\.com\/css.*?)\1/m', $content, $font_rels);
		
		if(empty($font_rels[2])){
			return $content;
		}
		
		foreach($font_rels[2] as $url){
			foreach($fonts as $font){

				if(in_array($font, array('.', '..'))){
					continue;
				}
				
				if(!file_exists($cache_dir . 'fonts/' . $font . '/' . $font . '.css')){
					continue;
				}
				
				$css_url = SPEEDYCACHE_CACHE_URL .'/'. SPEEDYCACHE_SERVER_HOST . '/fonts/' . $font . '/' . $font . '.css';
				
				if(md5($url) === $font){
					$content = preg_replace('/<link(.*)href=(["\'])(.*fonts\.googleapis\.com\/css.*?)\2/m', '<link$1 href="'.$css_url .'" ', $content);
				}
			}
		}
		
		return $content;
	}
	
	static function add_swap($content){	
		$content = str_replace('&#038;display=swap', '', $content);
		$content = str_replace('&display=swap', '', $content);

		// Add font-display=swap as a querty parameter to Google fonts
		$content = str_replace('googleapis.com/css?family', 'googleapis.com/css?display=swap&family', $content);
		
		return $content;
	}

}
