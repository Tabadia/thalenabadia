<?php
/*
* SPEEDYCACHE
* https://speedycache.com/
* (c) SpeedyCache Team
*/

namespace SpeedyCache;

if( !defined('SPEEDYCACHE_VERSION') ){
	die('HACKING ATTEMPT!');
}

class Precache{

	static function set($slug = 'speedycache'){
		$preload_arr = array();
		
		\SpeedyCache\Precache::set_arr($preload_arr);
		
		$preload = get_option('speedycache_preload');

		$schedule = \SpeedyCache\Precache::schedule($preload, $slug, $preload_arr);
		
		if($schedule === TRUE){
			return;
		}

		if(!empty($preload_arr)){
			update_option('speedycache_preload', $preload_arr, null, 'yes');

			if(!wp_next_scheduled('speedycache_preload')){
				wp_schedule_event(time() + 5, 'everyfiveminute', 'speedycache_preload');
			}
		}
	}

	static function set_arr(&$preload_arr){
		if(empty($_POST['speedycache_preload'])){
			return;
		}
		
		foreach($_POST as $key => $value){
			$key = esc_attr($key);
			
			if(is_array($value) || is_object($value)){
				$value = map_deep($value, 'esc_attr');
			} else {
				$value = esc_attr($value);
			}

			preg_match('/speedycache_preload_(.+)/', $key, $type);
			
			if(empty($type)){
				continue;
			}

			switch($type[1]){
				case 'number':
					$preload_arr[$type[1]] = $value;
					break;
					
				case 'restart':
					break;
				
				default:
					$preload_arr[$type[1]] = 0;
					break;
			}
		}
	}

	static function schedule($preload, $slug, &$preload_arr){
		if(empty($preload)){
			return false;
		}

		if(!empty($preload_arr)){
			foreach($preload_arr as $key => &$value){
				if(empty($preload[$key])){
					continue;
				}
				
				if($key !== 'number'){
					$value = $preload[$key];
				}
			}

			$preload = $preload_arr;
		}else{
			foreach($preload as $key => &$value){
				if($key !== 'number'){
					$value = 0;
				}
			}
		}

		update_option('speedycache_preload', $preload);

		if(!wp_next_scheduled($slug . '_preload')){
			wp_schedule_event(time() + 5, 'everyfiveminute', $slug . '_preload');
		}
			
		return true;
	}

	static function statistic($pre_load = false){
		$total = new \stdClass();

		if(isset($pre_load['homepage'])){
			$total->homepage = 1;
		}

		if(isset($pre_load['custom_post_types'])){
			global $wpdb;
			$post_types = get_post_types(array('public' => true), 'names', 'and');
			$where_query = '';

			foreach($post_types as $post_type_key => $post_type_value){
				if(!in_array($post_type_key, array('post', 'page', 'attachment'))){
					$where_query = $where_query . $wpdb->prefix . "posts.post_type = '" . $post_type_value . "' OR ";
				}
			}

			if(!empty($where_query)){
				$where_query = preg_replace("/(\s*OR\s*)$/", "", $where_query);

				$recent_custom_posts = $wpdb->get_results("SELECT SQL_CALC_FOUND_ROWS  COUNT(" . $wpdb->prefix . "posts.ID) as total FROM " . $wpdb->prefix . "posts  WHERE 1=1  AND (" . $where_query . ") AND ((" . $wpdb->prefix . "posts.post_status = 'publish'))  ORDER BY " . $wpdb->prefix . "posts.ID", ARRAY_A);
				$total->custom_post_types = $recent_custom_posts[0]['total'];
			}
		}

		if(isset($pre_load['post'])){
			$count_posts = wp_count_posts('post', array('post_status' => 'publish', 'suppress_filters' => true));

			$total->post = $count_posts->publish;
		}

		if(isset($pre_load['attachment'])){
			$total_attachments = wp_count_attachments();
			$total->attachment = array_sum((array)$total_attachments) - $total_attachments->trash;
		}

		if(isset($pre_load['page'])){
			$count_pages = wp_count_posts('page', array('post_status' => 'publish', 'suppress_filters' => true));
			$total->page = $count_pages->publish;
		}

		if(isset($pre_load['category'])){
			$total->category = wp_count_terms('category', array('hide_empty' => false));
		}

		if(isset($pre_load['tag'])){
			$total->tag = wp_count_terms('post_tag', array('hide_empty' => false));
		}

		if(isset($pre_load['customTaxonomies'])){
			$taxo = get_taxonomies(array('public' => true, '_builtin' => false), 'names', 'and');

			if(count($taxo) > 0){
				$total->custom_taxonomies = wp_count_terms($taxo, array('hide_empty' => false));
			}
		}

		foreach($total as $key => $value){
			$pre_load[$key] = $pre_load[$key] == -1 ? $value : $pre_load[$key];
			echo esc_html($key) . ": " . esc_html($pre_load[$key]) . '/' . esc_html($value) . '<br>';
		}
	}

	static function create(){
		global $speedycache;
		
		$pre_load = get_option('speedycache_preload');
		
		if(empty($pre_load)){
			if(isset($_GET['type']) && $_GET['type'] == 'preload'){
				die();
			}
		}
		
		if(!isset($speedycache->options['status'])){
			die('Cache System must be enabled');
		}

		$number = $pre_load['number'];
		if(defined('SPEEDYCACHE_PRELOAD_NUMBER') && SPEEDYCACHE_PRELOAD_NUMBER){
			$number = SPEEDYCACHE_PRELOAD_NUMBER;
		}

		//START:ORDER
		if(!empty($pre_load['order'])){
			$order_arr = explode(',', $pre_load['order']);
		} else {
			if(!empty($speedycache->options['preload_order'])){
				$order_arr = explode(',', $speedycache->options['preload_order']);
			}
		}

		if(isset($order_arr) && is_array($order_arr)){
			foreach($order_arr as $o_key => $o_value){
				if($o_value == 'order' || $o_value == 'number'){
					unset($order_arr[$o_key]);
				}

				if(!isset($pre_load[$o_value])){
					unset($order_arr[$o_key]);
				}
			}
			$order_arr = array_values($order_arr);
		}

		$current_order = isset($order_arr[0]) ? $order_arr[0] : 'go';
		//END:ORDER

		$urls_limit = isset($speedycache->options['preload_number']) ? $speedycache->options['preload_number'] : 4; // must be even
		$urls = array();

		$mobile_theme = false;
		if(!empty($speedycache->options['mobile_theme'])){
			$mobile_theme = true;
			$number = round($number / 2);
		}

		$url_funcs = array('\SpeedyCache\Precache::home_urls', '\SpeedyCache\Precache::custom_posts_url', '\SpeedyCache\Precache::posts_url', '\SpeedyCache\Precache::attachments_url',  '\SpeedyCache\Precache::pages_url', '\SpeedyCache\Precache::categories_url', '\SpeedyCache\Precache::tags_url', '\SpeedyCache\Precache::taxonomies_url');

		foreach($url_funcs as $func_name){
			call_user_func_array($func_name, array($current_order, &$pre_load, $mobile_theme, &$number, &$urls));
		}

		if(isset($pre_load[$current_order]) && $pre_load[$current_order] == -1){
			array_shift($order_arr);

			if(isset($order_arr[0])){
				$pre_load['order'] = implode(',', $order_arr);

				update_option('speedycache_preload', $pre_load);

				\SpeedyCache\Precache::create();
			}else{
				unset($pre_load['order']);
			}
		}

		if(count($urls) > 0){
			foreach($urls as $key => $arr){
				$user_agent = '';

				if($arr['user-agent'] == 'desktop'){
					$user_agent = 'speedycache_preload Bot';
				}else if($arr['user-agent'] == 'mobile'){
					$user_agent = 'speedycache_preload iPhone Mobile Bot';
				}


				if(\SpeedyCache\Precache::is_excluded($arr['url'])){
					$status = '<strong style="color:blue;">Excluded</strong>';
				}else{
					if(speedycache_remote_get($arr['url'], $user_agent)){
						$status = '<strong style="color:lightgreen;">OK</strong>';
					}else{
						$status = '<strong style="color:var(--speedycache-red);">ERROR</strong>';
					}
				}

				echo esc_html($status) . ' ' . esc_html($arr['url']) . ' (' . esc_html($arr['user-agent']) . ')<br>';
			}
			echo '<br>';
			echo esc_html(count($urls)) . ' page have been cached';

			update_option('speedycache_preload', $pre_load);

			echo '<br><br>';

			\SpeedyCache\Precache::statistic($pre_load);
		} else {
			if(isset($options['preload_restart'])){
				foreach($pre_load as $pre_load_key => &$pre_load_value){
					if($pre_load_key != 'number' && $pre_load_key != 'order'){
						$pre_load_value = 0;
					}
				}

				update_option('speedycache_preload', $pre_load);

				echo 'Preload Restarted';
				\SpeedyCache\CDN::purge();
				
			} else {
				
				echo 'Completed';
				wp_clear_scheduled_hook('speedycache_preload');
			}
		}

		if(isset($_GET['type']) && $_GET['type'] == 'preload'){
			die();
		}
	}

	static function home_urls($current_order, &$pre_load, $mobile_theme, &$number, &$urls){
		
		// HOME
		if(empty($current_order) || ($current_order !== 'homepage' && $current_order !== 'go')){
			return;
		}

		if(empty($pre_load['homepage']) || $pre_load['homepage'] <= -1){
			return;
		}

		if(!empty($mobile_theme)){
			array_push($urls, array('url' => get_option('home'), 'user-agent' => 'mobile'));
			$number--;
		}

		array_push($urls, array('url' => get_option('home'), 'user-agent' => 'desktop'));
		$number--;

		$pre_load['homepage'] = -1;

	}

	static function custom_posts_url($current_order, &$pre_load, $mobile_theme, &$number, &$urls){
		
		// CUSTOM POSTS
		if(empty($current_order) || ($current_order !== 'custom_post_types' && $current_order !== 'go')){
			return;
		}

		if($number <= 0 || empty($pre_load['custom_post_types']) || $pre_load['custom_post_types'] <= -1){
			return;
		}

		global $wpdb;
		$post_types = get_post_types(array('public' => true), 'names', 'and');
		$where_query = '';

		foreach($post_types as $post_type_key => $post_type_value){
			if(!in_array($post_type_key, array('post', 'page', 'attachment'))){
				$where_query = $where_query . $wpdb->prefix . "posts.post_type = '" . $post_type_value . "' OR ";
			}
		}

		if(empty($where_query)){
			return;
		}

		$where_query = preg_replace("/(\s*OR\s*)$/", '', $where_query);

		$recent_custom_posts = $wpdb->get_results("SELECT SQL_CALC_FOUND_ROWS  " . $wpdb->prefix . "posts.ID FROM " . $wpdb->prefix . "posts  WHERE 1=1  AND (" . $where_query . ") AND ((" . $wpdb->prefix . "posts.post_status = 'publish'))  ORDER BY " . $wpdb->prefix . "posts.ID DESC LIMIT " . $pre_load['custom_post_types'] . ", " . $number, ARRAY_A);

		if(count($recent_custom_posts) <= 0){
			$pre_load['custom_post_types'] = -1;
			return;
		}

		foreach($recent_custom_posts as $key => $post){
			if(!empty($mobile_theme)){
				array_push($urls, array('url' => get_permalink($post['ID']), 'user-agent' => 'mobile'));
				$number--;
			}

			array_push($urls, array('url' => get_permalink($post['ID']), 'user-agent' => 'desktop'));
			$number--;

			$pre_load['custom_post_types'] = $pre_load['custom_post_types'] + 1;
		}

	}

	static function posts_url($current_order, &$pre_load, $mobile_theme, &$number, &$urls){
		// POST
		if(empty($current_order) || ($current_order !== 'post' && $current_order !== 'go')){
			return;
		}
		
		if($number <= 0 || empty($pre_load['post']) || $pre_load['post'] <= -1){
			return;
		}

		global $wpdb;
		$recent_posts = $wpdb->get_results("SELECT SQL_CALC_FOUND_ROWS  " . $wpdb->prefix . "posts.ID FROM " . $wpdb->prefix . "posts  WHERE 1=1  AND (" . $wpdb->prefix . "posts.post_type = 'post') AND ((" . $wpdb->prefix . "posts.post_status = 'publish'))  ORDER BY " . $wpdb->prefix . "posts.ID DESC LIMIT " . $pre_load['post'] . ", " . $number, ARRAY_A);

		if(count($recent_posts) <= 0){
			$pre_load['post'] = -1;
			return;
		}

		foreach($recent_posts as $key => $post){
			if($mobile_theme){
				array_push($urls, array('url' => get_permalink($post['ID']), 'user-agent' => 'mobile'));
				$number--;
			}

			array_push($urls, array('url' => get_permalink($post['ID']), 'user-agent' => 'desktop'));
			$number--;

			$pre_load['post'] = $pre_load['post'] + 1;
		}

	}

	static function attachments_url($current_order, &$pre_load, $mobile_theme, &$number, &$urls){
		
		// ATTACHMENT
		if(empty($current_order) || ($current_order !== 'attachment' && $current_order !== 'go')){
			return;
		}
		
		if($number <= 0 || empty($pre_load['attachment']) || $pre_load['attachment'] <= -1){
			return;
		}

		global $wpdb;
		$recent_attachments = $wpdb->get_results("SELECT SQL_CALC_FOUND_ROWS  " . $wpdb->prefix . "posts.ID FROM " . $wpdb->prefix . "posts  WHERE 1=1  AND (" . $wpdb->prefix . "posts.post_type = 'attachment') ORDER BY " . $wpdb->prefix . "posts.ID DESC LIMIT " . $pre_load['attachment'] . ", " . $number, ARRAY_A);

		if(count($recent_attachments) <= 0){
			$pre_load['attachment'] = -1;
			return;
		}

		foreach($recent_attachments as $key => $attachment){
			if(!empty($mobile_theme)){
				array_push($urls, array('url' => get_permalink($attachment['ID']), 'user-agent' => 'mobile'));
				$number--;
			}

			array_push($urls, array('url' => get_permalink($attachment['ID']), 'user-agent' => 'desktop'));
			$number--;

			$pre_load['attachment'] = $pre_load['attachment'] + 1;
		}
	}

	static function pages_url($current_order, &$pre_load, $mobile_theme, &$number, &$urls){
		// PAGE
		if(empty($current_order) || ($current_order !== 'page' && $current_order !== 'go')){
			return;
		}

		if($number <= 0 || empty($pre_load['page']) || $pre_load['page'] <= -1){
			return;
		}

		global $wpdb;
		$pages = $wpdb->get_results("SELECT SQL_CALC_FOUND_ROWS  " . $wpdb->prefix . "posts.ID FROM " . $wpdb->prefix . "posts  WHERE 1=1  AND (" . $wpdb->prefix . "posts.post_type = 'page') AND ((" . $wpdb->prefix . "posts.post_status = 'publish'))  ORDER BY " . $wpdb->prefix . "posts.ID DESC LIMIT " . $pre_load['page'] . ", " . $number, ARRAY_A);


		if(count($pages) <= 0){
			$pre_load['page'] = -1;
			return;
		}

		foreach($pages as $key => $page){
			if(!empty($mobile_theme)){
				array_push($urls, array('url' => get_page_link($page['ID']), 'user-agent' => 'mobile'));
				$number--;
			}

			array_push($urls, array('url' => get_page_link($page['ID']), 'user-agent' => 'desktop'));
			$number--;

			$pre_load['page'] = $pre_load['page'] + 1;
		}
	}

	static function categories_url($current_order, &$pre_load, $mobile_theme, &$number, &$urls){
		
		// CATEGORY
		if(empty($current_order) || ($current_order !== 'category' && $current_order !== 'go')){
			return;
		}

		if($number <= 0 || empty($pre_load['category']) || $pre_load['category'] <= -1){
			return;
		}

		$categories = get_terms(array(
			'taxonomy'          => array('category'),
			'orderby'           => 'id',
			'order'             => 'ASC',
			'hide_empty'        => false,
			'number'            => $number,
			'fields'            => 'all',
			'pad_counts'        => false,
			'offset'            => $pre_load['category']
		));

		if(count($categories) <= 0){
			$pre_load['category'] = -1;
			return;
		}

		foreach($categories as $key => $category){
			if(!empty($mobile_theme)){
				array_push($urls, array('url' => get_term_link($category->slug, $category->taxonomy), 'user-agent' => 'mobile'));
				$number--;
			}

			array_push($urls, array('url' => get_term_link($category->slug, $category->taxonomy), 'user-agent' => 'desktop'));
			$number--;

			$pre_load['category'] = $pre_load['category'] + 1;
		}
	}

	static function tags_url($current_order, &$pre_load, $mobile_theme, &$number, &$urls){
		// TAG
		if(empty($current_order) || ($current_order !== 'tag' && $current_order !== 'go')){
			return;
		}

		if($number <= 0 || empty($pre_load['tag']) || $pre_load['tag'] <= -1){
			return;
		}

		$tags = get_terms(array(
			'taxonomy'          => array('post_tag'),
			'orderby'           => 'id',
			'order'             => 'ASC',
			'hide_empty'        => false,
			'number'            => $number,
			'fields'            => 'all',
			'pad_counts'        => false,
			'offset'            => $pre_load['tag']
		));

		if(count($tags) <= 0){
			$pre_load['tag'] = -1;
			return;
		}

		foreach($tags as $key => $tag){
			if(!empty($mobile_theme)){
				array_push($urls, array('url' => get_term_link($tag->slug, $tag->taxonomy), 'user-agent' => 'mobile'));
				$number--;
			}

			array_push($urls, array('url' => get_term_link($tag->slug, $tag->taxonomy), 'user-agent' => 'desktop'));
			$number--;

			$pre_load['tag'] = $pre_load['tag'] + 1;
		}

	}

	static function taxonomies_url($current_order, &$pre_load, $mobile_theme, &$number, &$urls){
		// Custom Taxonomies
		if(empty($current_order) || ($current_order !== 'custom_taxonomies' && $current_order !== 'go')){
			return;
		}

		if($number <= 0 && empty($pre_load['custom_taxonomies']) || (isset($pre_load['custom_taxonomies']) && $pre_load['custom_taxonomies'] <= -1)){
			return;
		}

		$taxo = get_taxonomies(array('public'   => true, '_builtin' => false), 'names', 'and');

		if(count($taxo) <= 0){
			$pre_load['custom_taxonomies'] = -1;
			return;
		}

		$custom_taxos = get_terms(array(
			'taxonomy'          => array_values($taxo),
			'orderby'           => 'id',
			'order'             => 'ASC',
			'hide_empty'        => false,
			'number'            => $number,
			'fields'            => 'all',
			'pad_counts'        => false,
			'offset'            => $pre_load['custom_taxonomies']
		));

		if(count($custom_taxos) <= 0){
			$pre_load['custom_taxonomies'] = -1;
			return;
		}

		foreach($custom_taxos as $key => $custom_tax){
			if(!empty($mobile_theme)){
				array_push($urls, array('url' => get_term_link($custom_tax->slug, $custom_tax->taxonomy), 'user-agent' => 'mobile'));
				$number--;
			}

			array_push($urls, array('url' => get_term_link($custom_tax->slug, $custom_tax->taxonomy), 'user-agent' => 'desktop'));
			$number--;

			$pre_load['custom_taxonomies'] = $pre_load['custom_taxonomies'] + 1;
		}
	}

	static function is_excluded($url){
		global $speedycache;
		
		if(!is_string($url)){
			return false;
		}

		$request_url = parse_url($url, PHP_URL_PATH);
		$request_url = urldecode(trim($request_url, '/'));

		if(empty($request_url)){
			return false;
		}

		if($speedycache->settings['preload_exclude_rules'] === false){
			if($exclude_data = get_option('speedycache_exclude')){
				$speedycache->settings['preload_exclude_rules'] = $exclude_data;
			}else{
				$speedycache->settings['preload_exclude_rules'] = array();
			}
		}

		foreach((array)$speedycache->settings['preload_exclude_rules'] as $key => $value){
			if($value['prefix'] == 'exact'){
				if(strtolower($value['content']) == strtolower($request_url)){
					return true;
				}
			}else{
				if($value['prefix'] == 'startwith'){
					$preg_match_rule = "^" . preg_quote($value['content'], '/');
				}else if($value['prefix'] == 'contain'){
					$preg_match_rule = preg_quote($value['content'], '/');
				}

				if(isset($preg_match_rule)){
					if(preg_match('/' . $preg_match_rule . '/i', $request_url)){
						return true;
					}
				}
			}
		}

		return false;
	}

}

