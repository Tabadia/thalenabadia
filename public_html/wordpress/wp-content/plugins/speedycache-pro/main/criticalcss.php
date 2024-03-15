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

class CriticalCss extends CommonCss{

	static function generate($urls){
		global $speedycache;

		$time = time() + 50;
		set_time_limit(60);
		
		$api = self::get_endpoint();
		
		if(empty($urls)){
			self::log('speedycache_ccss_logs', 'URL not found');
			return false;
		}

		if(empty($speedycache->license['license'])){
			self::log('speedycache_ccss_logs', 'License Not found, please link your License');
			return false;
		}

		$path = speedycache_cache_path('critical-css/');
		$error = ''; // To hold errors when single Critical CSS is generated
		$attempted_url = []; // Keeping track of URL that have been proccessed to generate CriticalCSS to handle in case of Timeout

		if(!is_dir($path)){
			mkdir($path);
			touch($path . 'index.html');
		}

		foreach($urls as $url){
			// Handeling php timeout here
			if($time < time()){
				$urls = array_diff($urls, $attempted_url);
				self::schedule($urls);
				return;
			}

			$url = trim($url, '/');
			$license = strpos($speedycache->license['license'], 'SPDFY') !== 0 ? '' : $speedycache->license['license'];
			$attempted_url[] = $url;

			$basename = md5($url);
			$file_name = $path . $basename . '.css';

			$response = wp_remote_post($api, array(
				'timeout' => 30,
				'body' => array(
					'url' => $url,
					'license' => $license,
				),
				'sslverify' => false,
			));

			if(is_wp_error($response)){
				$error = $response->get_error_message();
				self::log('speedycache_ccss_logs', $response->get_error_message(), $url);
				continue;
			}

			$body = json_decode(wp_remote_retrieve_body($response), true);
			
			if(empty($body)){
				$error = __('The response recieved is empty.', 'speedycache');
				self::log('speedycache_ccss_logs', __('The response recieved is empty.', 'speedycache'), $url);
				continue;
			}

			if(empty($body['success'])){
				$error = !empty($body['message']) ? wp_strip_all_tags($body['message']) : __('Unable to extract CriticalCss', 'speedycache');
				self::log('speedycache_ccss_logs', !empty($body['message']) ? wp_strip_all_tags($body['message']) : __('Unable to extract CriticalCss', 'speedycache'), $url);
				continue;
			}

			if(empty($body['css']) || strlen($body['css']) < 20){
				$error = __('Was unable to generate Critical CSS', 'speedycache');
				self::log('speedycache_ccss_logs', __('Was unable to generate Critical CSS', 'speedycache'), $url);
				continue;
			}
			
			if(!is_dir($path)){
				mkdir($path);
			}
			
			file_put_contents($file_name, $body['css']);

			self::update_css($url, $body['css']);
			
			self::log('speedycache_ccss_logs', 'success', $url); //Updates the log on success
			
			if(!empty($error)){
				return $error;
			}

			return true;
		}
	}
	
	// Builds up the list to schedule URLs
	static function get_url_list(){
		global $blog_id;
		
		$pages = get_pages(array('child_of' => 0, 'number' => 9));
		
		if(empty($pages)){
			return false;
		}

		$page_to_crawl = [];

		$url = get_home_url(!empty($blog_id) ? $blog_id : null);
		
		if(!empty($url)){
			$page_to_crawl['home'] = $url;
		}

		foreach($pages as $p){
			$page_to_crawl[$p->ID] = get_page_link($p->ID);
		}

		return $page_to_crawl;
	}
	
	// Adds the generated css and asynchronyses the css includes
	static function update_css($url, $css){
		global $speedycache;
		
		if(empty($url)){
			return false;
		}
		
		if(empty($css) && file_exists(speedycache_cache_path('critical-css/') . md5($url) . '.css')){
			$css = file_get_contents(speedycache_cache_path('critical-css/') . md5($url) . '.css');
		}
		
		if(empty($css)){
			return false;
		}

		$css = '<style id="speedycache-generated-criticalcss">'. "\n". wp_strip_all_tags($css) . '</style>';

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
		\SpeedyCache\CriticalCss::update_cached($cache_path, $css);

		if(!empty($speedycache->options['mobile_theme']) && $_SERVER['HTTP_USER_AGENT'] !== 'SpeedyCacheTest'){
			$cache_mobile = speedycache_cache_path('mobile-cache' . $cache_loc);
			
			// For Mobile Cache
			if(file_exists($cache_mobile)){
				\SpeedyCache\CriticalCss::update_cached($cache_mobile, $css);
			}	
		}

	}
	
	// Updates the content of the cached file
	static function update_content($content, $css){
		
		if(strpos($content, 'speedycache-generated-criticalcss') !== FALSE){
			$content = preg_replace('/<style id="speedycache-generated-criticalcss">(.*)<\/style>/sU', $css, $content);
		} else{
			$content = preg_replace('#</title>#iU', "</title>\n". $css, $content);
		}

		$content = \SpeedyCache\CriticalCss::make_css_defer($content);
		
		return $content;
	}
	
	/**
	 * This extracts the stylesheet links and convertes them to preload to make them async,
	 * and add the stylesheet links in noscript if js is disabled in some browsers
	 */
	static function make_css_defer($content){
		
		$css_links = '/(?=<link[^>]*\s(rel\s*=\s*[\'"]stylesheet["\']))<link[^>]*\shref\s*=\s*[\'"]([^\'"]+)[\'"](.*)>/iU';
		
		preg_match_all($css_links, $content, $matches, PREG_SET_ORDER, 0);

		if(empty($matches)){
			return $content;
		}
		
		$noscript_wrap = '<noscript>';

		foreach($matches as $tag){
			$preload = str_replace('stylesheet', 'preload', $tag[1]);
			$onload = preg_replace('~' . preg_quote($tag[3], '~') . '~iU', ' as="style" onload="" ' . $tag[3] . '>', $tag[3]);

			$new_tag = str_replace($tag[3] . '>', $onload, $tag[0]);
			$new_tag = str_replace($tag[1], $preload, $new_tag);
			$new_tag = str_replace('onload=""', 'onload="this.onload=null;this.rel=\'stylesheet\'"', $new_tag);
			$new_tag = preg_replace('/(id\s*=\s*[\"\'](?:[^\"\']*)*[\"\'])/i', '', $new_tag);

			$content = str_replace($tag[0], $new_tag, $content);
			
			$noscript_wrap .= $tag[0];
		}
		
		$noscript_wrap .= '</noscript>';
		
		$content = str_replace($noscript_wrap, '', $content);
		
		return str_replace('</body>', $noscript_wrap . '</body>', $content);

	}
	
	static function status_modal(){
		$html = '<!--SpeedyCache Critical CSS Logs Modal Starts Here-->
	<div modal-id="speedycache_critical_css" class="speedycache-modal">
		<div class="speedycache-modal-wrap">
			<div class="speedycache-modal-header">
				<div>'.esc_html__('Critical Cache Logs', 'speedycache').'</div>
				<div title="Close Modal" class="speedycache-close-modal">
					<span class="dashicons dashicons-no"></span>
				</div>
			</div>
			<div class="speedycache-modal-content" style="min-height:50vh;">
				<div class="speedycache-critical-css-status">';
			
			$generate_ccss = get_option('speedycache_ccss_logs', []);
			
			$scheduled = self::get_schedule(array('speedycache_generate_ccss'));
		
			if(!empty($scheduled)){
				$time = 'now';
				if(!empty($scheduled[0]['time']) && ($scheduled[0]['time'] - time()) > 0){
					$time = 'in ' . ($scheduled[0]['time'] - time()) . 's';
				}

				$html .= '<p style="color:rgb(1, 67, 97); background-color: rgb(229, 246, 253); padding: 10px; border-radius: 6px; font-family: monospace;">'. esc_html__('A process has been scheduled and will be executed', 'speedycache'). ' <strong>' . esc_html($time).'</strong></p>';
			}

			if(count($generate_ccss) < 1){
				$html .= '<span>'.esc_html__('No Logs found for CriticalCss', 'speedycache').'</span>';
			} else {
				$html .='<table style="margin:auto; width: 100%;">
					<thead>
						<tr>
							<th class="speedycache-table-hitem" scope="col">'.esc_html__('Time', 'speedycache').'</th>
							<th class="speedycache-table-hitem" scope="col">'. esc_html__('URLs', 'speedycache').'</th>
							<th class="speedycache-table-hitem" scope="col">'. esc_html__('Status', 'speedycache').'</th>
						</tr>
					</thead>
					<tbody>';
					
					$generate_ccss = array_reverse($generate_ccss);
					
					foreach($generate_ccss as $url => $status){
						$parsed_url = wp_parse_url($url);
						$path = !empty($parsed_url['path']) ? $parsed_url['path'] : '/';
						
						if($status['message'] == 'success'){
							$status_html = '<span class="dashicons dashicons-yes-alt" style="color:	#198754;" title="Success"></span>';
						} else {
							$status_html = '<div class="speedycache-tt"><span class="dashicons dashicons-info" style="color:#DC3545;"></span><span class="speedycache-tt-text">'.esc_html($status['message']).'</span></div>';
						}
						
						$html .= '<tr><td class="speedycache-table-item">'.esc_html($status['time']).'</td>
						<td class="speedycache-table-item">'.esc_html($path).'</td>
						<td class="speedycache-table-item" style="text-align:right;">'.wp_kses_post($status_html).'</td></tr>';
					}

				$html .= '</tbody>
				</table>';
			}
			
			$html .= '</div>
		</div>
	</div>
</div>';

		return $html;
	}

}
