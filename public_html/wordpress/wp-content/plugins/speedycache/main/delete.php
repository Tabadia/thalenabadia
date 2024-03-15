<?php

namespace SpeedyCache;

if(!defined('ABSPATH')){
	die('HACKING ATTEMPT!');
}

class Delete{

	static function edit_comment($comment_id, $comment_data){
		if($comment_data['comment_approved'] == 1){
			speedycache_single_delete_cache($comment_id);
		}
	}

	static function comment_post($comment_id, $comment_approved){
		// if(current_user_can( 'manage_options') || !get_option('comment_moderation')){
		if($comment_approved === 1){
			speedycache_single_delete_cache($comment_id);
		}
	}

	static function single_cache($comment_id = false, $post_id = false){

		\SpeedyCache\CDN::purge();
		self::varnish();

		$to_clear_parents = true;
		$to_clear_feed = true;
		
		$action = speedycache_optpost('action');

		// not to clear cache of homepage/cats/tags after ajax request by other plugins
		if(!empty($action)){
			// kk Star Rating
			if($action == 'kksr_ajax'){
				$to_clear_parents = false;
			}

			// All In One Schema.org Rich Snippets
			if(preg_match('/bsf_(update|submit)_rating/i', $action)){
				$to_clear_parents = false;
			}

			// Yet Another Stars Rating
			if($action == 'yasr_send_visitor_rating'){
				$to_clear_parents = false;
				$post_id = speedycache_optpost('post_id');
			}

			// All In One Schema.org Rich Snippets
			if(preg_match('/bsf_(update|submit)_rating/i', $action)){
				$to_clear_feed = false;
			}
		}

		if(!empty($comment_id)){
			$comment_id = intval($comment_id);
			
			$comment = get_comment($comment_id);
			
			if(!empty($comment) && !empty($comment->comment_post_ID)){
				$post_id = $comment->comment_post_ID;
			}
		}

		if(empty($post_id)){
			return;
		}

		$post_id = intval($post_id);

		$permalink = get_permalink($post_id);

		$permalink = urldecode(get_permalink($post_id));

		//for trash contents
		$permalink = rtrim($permalink, '/');
		$permalink = preg_replace('/__trashed$/', '', $permalink);
		//for /%postname%/%post_id% : sample-url__trashed/57595
		$permalink = preg_replace("/__trashed\/(\d+)$/", "/$1", $permalink);

		if(preg_match('/https?:\/\/[^\/]+\/(.+)/', $permalink, $out)){
			$path = speedycache_cache_path('all/').$out[1];
			$mobile_path = speedycache_cache_path('mobile-cache/').$out[1];
			
			if(defined('SPEEDYCACHE_PRO')){
				\SpeedyCache\Logs::log('delete');
				\SpeedyCache\Logs::action();
			}
			
			$files = array();

			if(is_dir($path)){
				array_push($files, $path);
			}

			if(is_dir($mobile_path)){
				array_push($files, $mobile_path);
			}

			if(defined('SPEEDYCACHE_CACHE_QUERYSTRING') && SPEEDYCACHE_CACHE_QUERYSTRING){
				$files_with_query_string = glob($path."\?*");
				$mobile_files_with_query_string = glob($mobile_path."\?*");

				if(is_array($files_with_query_string) && (count($files_with_query_string) > 0)){
					$files = array_merge($files, $files_with_query_string);
				}

				if(is_array($mobile_files_with_query_string) && (count($mobile_files_with_query_string) > 0)){
					$files = array_merge($files, $mobile_files_with_query_string);
				}
			}

			if(!empty($to_clear_feed)){
				// to clear cache of /feed
				if(preg_match("/https?:\/\/[^\/]+\/(.+)/", get_feed_link(), $feed_out)){
					array_push($files, speedycache_cache_path('all/').$feed_out[1]);
				}

				// to clear cache of /comments/feed/
				if(preg_match("/https?:\/\/[^\/]+\/(.+)/", get_feed_link('comments_'), $comment_feed_out)){
					array_push($files, speedycache_cache_path('all/').$comment_feed_out[1]);
				}
			}

			foreach((array)$files as $file){
				\SpeedyCache\Delete::rm_dir($file);
			}
		}

		if(empty($to_clear_parents)){
			\SpeedyCache\Delete::multiple_domain_mapping_cache();
		}

		// to clear cache of homepage
		speedycache_delete_home_page_cache();

		// to clear cache of author page
		\SpeedyCache\Delete::author_page_cache($post_id);

		// to clear sitemap cache
		\SpeedyCache\Delete::sitemap_cache();

		// to clear cache of cats and  tags which contains the post (only first page)
		global $wpdb;
		$terms = $wpdb->get_results("SELECT * FROM `".$wpdb->prefix."term_relationships` WHERE `object_id`=".$post_id, ARRAY_A);

		foreach($terms as $term_key => $term_val){
			\SpeedyCache\Delete::cache_of_term($term_val['term_taxonomy_id']);
		}
		
		if(!empty($speedycache->options['critical_css'])){
			self::critical_css($post_id);
		}
	
		do_action('speedycache_purge_cache', 'single', $post_id);

		\SpeedyCache\Delete::multiple_domain_mapping_cache();
	}

	static function sitemap_cache(){
		//to clear sitemap.xml and sitemap-(.+).xml
		$files = array_merge(glob(speedycache_cache_path('all/').'sitemap*.xml'), glob(speedycache_cache_path('mobile-cache/').'sitemap*.xml'));

		foreach((array)$files as $file){
			\SpeedyCache\Delete::rm_dir($file);
		}
	}

	static function multiple_domain_mapping_cache($minified = false){
		//https://wordpress.org/plugins/multiple-domain-mapping-on-single-site/
		if(empty(speedycache_is_plugin_active('multiple-domain-mapping-on-single-site/multidomainmapping.php'))){
			return;
		}
		
		$multiple_arr = get_option('falke_mdm_mappings');

		if(empty($multiple_arr) || empty($multiple_arr['mappings']) || empty($multiple_arr['mappings'][0])){
			return;
		}
		
		foreach($multiple_arr['mappings'] as $mapping_key => $mapping_value){
			if(empty($minified)){
				$mapping_domain_path = preg_replace('/(\/speedycache\/[^\/]+\/all)/', '/speedycache/'.$mapping_value['domain'].'/all', speedycache_cache_path('all/index.html'));

				@unlink($mapping_domain_path);
				
				continue;
			}

			$mapping_domain_path = preg_replace('/(\/speedycache\/[^\/]+\/all\/)/', '/speedycache/'.$mapping_value['domain'].'/', speedycache_cache_path('all/'));

			if(is_dir($mapping_domain_path)){
				if(@rename($mapping_domain_path, speedycache_get_wp_content_dir('/cache/speedycache/tmp_cache/').$mapping_value['domain'].'_'.time())){

				}
			}
		}
	}

	static function author_page_cache($post_id){
		$author_id = get_post_field ('post_author', $post_id);
		$permalink = get_author_posts_url($author_id);

		if(preg_match("/https?:\/\/[^\/]+\/(.+)/", $permalink, $out)){
			$path = speedycache_cache_path('all/').$out[1];
			$mobile_path = speedycache_cache_path('mobile-cache/').$out[1];
		
			\SpeedyCache\Delete::rm_dir($path);
			\SpeedyCache\Delete::rm_dir($mobile_path);
		}
	}

	static function cache_of_term($term_taxonomy_id){
		$term = get_term_by('term_taxonomy_id', $term_taxonomy_id);

		if(empty($term) || is_wp_error($term)){
			return false;
		}

		//if(preg_match("/cat|tag|store|listing/", $term->taxonomy)){}

		$url = get_term_link($term->term_id, $term->taxonomy);

		if(preg_match('/^http/', $url)){
			$path = preg_replace("/https?\:\/\/[^\/]+/i", '', $url);
			$path = trim($path, '/');
			$path = urldecode($path);

			// to remove the cache of tag/cat
			if(file_exists(speedycache_cache_path('all/').$path.'/index.html')){
				@unlink(speedycache_cache_path('all/').$path.'/index.html');
			}

			if(file_exists(speedycache_cache_path('mobile-cache/').$path.'/index.html')){
				@unlink(speedycache_cache_path('mobile-cache/').$path.'/index.html');
			}

			// to remove the cache of the pages
			\SpeedyCache\Delete::rm_dir(speedycache_cache_path('all/').$path.'/page');
			\SpeedyCache\Delete::rm_dir(speedycache_cache_path('mobile-cache/').$path.'/page');

			// to remove the cache of the feeds
			\SpeedyCache\Delete::rm_dir(speedycache_cache_path('all/').$path.'/feed');
			\SpeedyCache\Delete::rm_dir(speedycache_cache_path('mobile-cache/').$path.'/feed');
		}

		if($term->parent > 0){
			$parent = get_term_by('id', $term->parent, $term->taxonomy);
			\SpeedyCache\Delete::cache_of_term($parent->term_taxonomy_id);
		}
	}

	static function home_page_cache($log = true){
		
		\SpeedyCache\CDN::purge();

		$site_url_path = preg_replace("/https?\:\/\/[^\/]+/i", '', site_url());
		$home_url_path = preg_replace("/https?\:\/\/[^\/]+/i", '', home_url());

		if(!empty($site_url_path)){
			$site_url_path = trim($site_url_path, '/');

			if(!empty($site_url_path)){
				if(file_exists(speedycache_cache_path('all/').$site_url_path.'/index.html')){
					@unlink(speedycache_cache_path('all/').$site_url_path.'/index.html');
				}
				
				if(file_exists(speedycache_cache_path('mobile-cache/').$site_url_path.'/index.html')){
					@unlink(speedycache_cache_path('mobile-cache/').$site_url_path.'/index.html');
				}
				

				//to clear pagination of homepage cache
				\SpeedyCache\Delete::rm_dir(speedycache_cache_path('all/').$site_url_path.'/page');
				\SpeedyCache\Delete::rm_dir(speedycache_cache_path('mobile-cache/').$site_url_path.'/page');
			}
		}

		if(!empty($home_url_path)){
			$home_url_path = trim($home_url_path, '/');

			if($home_url_path){
				if(file_exists(speedycache_cache_path('all/').$home_url_path.'/index.html')){
					@unlink(speedycache_cache_path('all/').$home_url_path.'/index.html');
				}
				
				if(file_exists(speedycache_cache_path('mobile-cache/').$home_url_path.'/index.html')){
					@unlink(speedycache_cache_path('mobile-cache/').$home_url_path.'/index.html');
				}
				

				//to clear pagination of homepage cache
				\SpeedyCache\Delete::rm_dir(speedycache_cache_path('all/').$home_url_path.'/page');
				\SpeedyCache\Delete::rm_dir(speedycache_cache_path('mobile-cache/').$home_url_path.'/page');
			}
		}

		if(function_exists('wc_get_page_id')){
			if($shop_id = wc_get_page_id('shop')){
				$store_url_path = preg_replace("/https?\:\/\/[^\/]+/i", '', get_permalink($shop_id));

				if(!empty($store_url_path)){
					$store_url_path = trim($store_url_path, '/');

					if(!empty($store_url_path)){
						if(file_exists(speedycache_cache_path('all/').$store_url_path.'/index.html')){
							@unlink(speedycache_cache_path('all/').$store_url_path.'/index.html');
						}
						
						if(file_exists(speedycache_cache_path('mobile-cache/').$store_url_path.'/index.html')){
							@unlink(speedycache_cache_path('mobile-cache/').$store_url_path.'/index.html');
						}
						
						//to clear pagination of store homepage cache
						\SpeedyCache\Delete::rm_dir(speedycache_cache_path('all/').$store_url_path.'/page');
						\SpeedyCache\Delete::rm_dir(speedycache_cache_path('mobile-cache/').$store_url_path.'/page');
					}
				}
			}
		}

		if(file_exists(speedycache_cache_path('all/index.html'))){
			@unlink(speedycache_cache_path('all/index.html'));
		}

		if(file_exists(speedycache_cache_path('mobile-cache/index.html'))){
			@unlink(speedycache_cache_path('mobile-cache/index.html'));
		}

		//to clear pagination of homepage cache
		\SpeedyCache\Delete::rm_dir(speedycache_cache_path('all/page'));
		\SpeedyCache\Delete::rm_dir(speedycache_cache_path('mobile-cache/page'));

		// options-reading.php - static posts page
		if($page_for_posts_id = get_option('page_for_posts')){
			$page_for_posts_permalink = urldecode(get_permalink($page_for_posts_id));

			$page_for_posts_permalink = rtrim($page_for_posts_permalink, '/');
			$page_for_posts_permalink = preg_replace("/__trashed$/", '', $page_for_posts_permalink);
			//for /%postname%/%post_id% : sample-url__trashed/57595
			$page_for_posts_permalink = preg_replace("/__trashed\/(\d+)$/", "/$1", $page_for_posts_permalink);

			if(preg_match("/https?:\/\/[^\/]+\/(.+)/", $page_for_posts_permalink, $out)){
				$page_for_posts_path = speedycache_cache_path('all/').$out[1];
				$page_for_posts_mobile_path = speedycache_cache_path('mobile-cache/').$out[1];
				
				\SpeedyCache\Delete::rm_dir($page_for_posts_path);
				\SpeedyCache\Delete::rm_dir($page_for_posts_mobile_path);
			}
		}

		if(defined('SPEEDYCACHE_PRO') && !empty($log)){
			\SpeedyCache\Logs::log('delete');
			\SpeedyCache\Logs::action();
		}
	}

	static function cache($delete_min = false, $delete_fonts = false, $single_delete = false){
		global $speedycache;

		\SpeedyCache\CDN::purge();
		\SpeedyCache\Precache::set();
		self::varnish();

		$created_tmp_cache = false;
		$cache_deleted = false;
		$minifed_deleted = false;

		$cache_path = speedycache_cache_path('all');
		$mobile_cache_path = speedycache_cache_path('mobile-cache');
		$minified_cache_path = speedycache_cache_path('assets');

		if(defined('SPEEDYCACHE_PRO')){

			if(is_dir(speedycache_cache_path('mobile-cache'))){
				if(is_dir(speedycache_cache_path('tmp_cache'))){
					rename(speedycache_cache_path('mobile-cache'), speedycache_cache_path('tmp_cache/mobile_').time());
				}else if(@mkdir(speedycache_cache_path('tmp_cache'), 0755, true)){
					rename(speedycache_cache_path('mobile-cache'), speedycache_cache_path('tmp_cache/mobile_').time());
				}
			}
		}
		
		if(!is_dir(speedycache_cache_path('tmp_cache'))){
			if(@mkdir(speedycache_cache_path('tmp_cache'), 0755, true)){
				$created_tmp_cache = true;
			}else{
				$created_tmp_cache = false;
			}
		}else{
			$created_tmp_cache = true;
		}

		\SpeedyCache\Delete::multiple_domain_mapping_cache($delete_min);
		
		// Deleting Cache stats options
		$cache_deleted = \SpeedyCache\Delete::update_cache_status($cache_path, $mobile_cache_path);
		
		// Deleting Local Google Fonts
		if(!empty($delete_fonts)){
			\SpeedyCache\Delete::fonts();
		}
		
		// Deleting Minified stats options
		$minifed_deleted = \SpeedyCache\Delete::update_minified_status($delete_min, $minified_cache_path);

		if(!empty($created_tmp_cache) && !empty($cache_deleted) && !empty($minifed_deleted)){
			do_action('speedycache_delete_cache');
			
			speedycache_notify(array('All cache files have been deleted', 'updated'));

			if(isset($speedycache)){
				$speedycache->settings['system_message'] = array('message' => 'All cache files have been deleted', 'success' => true);
			}
			
			if(defined('SPEEDYCACHE_PRO') && empty($single_delete)){
				\SpeedyCache\Logs::log('delete');
				\SpeedyCache\Logs::action();
			}
		}
		
		if(!empty($speedycache->options['critical_css'])){
			self::critical_css();
		}
		
		/*
		 * Action after cache is deleted
		 * @param $type string 'all|single'
		 * @param $id int 0 when all cache is being cleared
		 */
		do_action('speedycache_purge_cache', 'all', 0);
		
		// for ajax request
		if(!empty(speedycache_optget('action')) && in_array(speedycache_optget('action'), array('speedycache_delete_cache', 'speedycache_delete_cache_and_minified'))){
			wp_send_json($speedycache->settings['system_message']);
		}
	}

	static function update_cache_status($cache_path, $mobile_cache_path){
		
		if(!is_dir($cache_path) && !is_dir($mobile_cache_path)){
			return true;
		}

		if(@rename($cache_path, speedycache_cache_path('tmp_cache/').time())){
			$deletable_options = array('speedycache_html', 'speedycache_html_size', 'speedycache_mobile', 'speedycache_mobile_size');
			
			foreach($deletable_options as $deletable_option){
				delete_option($deletable_option);
			}
		}
		
		return true;
	}

	static function update_minified_status($delete_min, $minified_cache_path){
		// Delete Minified CSS
		if(empty($delete_min)){
			return true;
		}
		
		if(is_dir($minified_cache_path) && @rename($minified_cache_path, speedycache_cache_path('tmp_cache/m').time())){
			$deletable = array('speedycache_css', 'speedycache_css_size', 'speedycache_js', 'speedycache_js_size');
			
			foreach($deletable as $delete){
				delete_option($delete);
			}
		}
		
		return true;
		
	}

	static function rm_dir($dir, $i = 1){
		if(!is_dir($dir) || !file_exists($dir)){
			return true;
		}

		$files = @scandir($dir);
		foreach((array)$files as $file){
			if($i > 50 && !preg_match('/speedycache/i', $dir)){
				return true;
			}
			
			$i++;
			
			if('.' === $file || '..' === $file){
				continue;
			}
			
			if(is_dir("$dir/$file")){
				\SpeedyCache\Delete::rm_dir("$dir/$file", $i);
			}else{
				if(file_exists("$dir/$file")){
					@unlink("$dir/$file");
				}
			}
		}

		if(!file_exists($dir)){
			return true;
		}

		$files_tmp = @scandir($dir);
		
		if(!isset($files_tmp[2]) && file_exists($dir)){
			@rmdir($dir);
		}

		return true;
	}

	static function scheduled_delete($args = ''){
		global $speedycache;

		if(empty($args)){
			//for old cron job
			speedycache_delete_cache();
			return;
		}

		$rule = $args;
		
		if('all' === $rule['prefix']){
			speedycache_delete_cache();
		}else if('homepage' === $rule['prefix']){
			@unlink(speedycache_cache_path('all/index.html'));
			@unlink(speedycache_cache_path('mobile-cache/index.html'));

			if(!empty($speedycache->options['preload_homepage'])){
				speedycache_remote_get(get_option('home'), 'speedycache_preload Bot - After Cache Timeout');
				speedycache_remote_get(get_option('home'), 'speedycache_preload iPhone Mobile Bot - After Cache Timeout');
			}
		}else if('startwith' === $rule['prefix']){
				if(!is_dir(speedycache_cache_path('tmp_cache'))){
					@mkdir(speedycache_cache_path('tmp_cache'), 0755, true);
				}

				$rule['content'] = trim($rule['content'], '/');

				$files = glob(speedycache_cache_path('all/').$rule['content'].'*');

				foreach((array)$files as $file){
					$mobile_file = str_replace('/speedycache/'.SPEEDYCACHE_SERVER_HOST.'/all', '/speedycache/'.SPEEDYCACHE_SERVER_HOST.'/mobile-cache/', $file);
					
					@rename($file, speedycache_cache_path('tmp_cache/').time());
					@rename($mobile_file, speedycache_cache_path('tmp_cache/mobile_').time());
				}
		}else if('exact' === $rule['prefix']){
			$rule['content'] = trim($rule['content'], '/');

			@unlink(speedycache_cache_path('all/').$rule['content'].'/index.html');
			@unlink(speedycache_cache_path('mobile-cache/').$rule['content'].'/index.html');
		}

		if(defined('SPEEDYCACHE_PRO') && 'all' !== $rule['prefix']){
			\SpeedyCache\Logs::log('delete');
			\SpeedyCache\Logs::action($rule);
		}
	}

	// Deletes local fonts
	static function fonts(){

		// to remove the cache of the pages
		if(!file_exists(speedycache_cache_path('fonts/'))){
			return;
		}
		
		\SpeedyCache\Delete::rm_dir(speedycache_cache_path('fonts/'));
	}
	
	static function on_status_transitions($new_status, $old_status, $post){

		global $speedycache;
		
		$speedycache->settings['deleted_before'] = true;
		
		if(!empty(wp_is_post_revision($post->ID))){
			return;
		}
		
		if(isset($post->post_type)){
			if($post->post_type == 'nf_sub'){
				return 0;
			}
		}
		
		if(!empty($speedycache->options['new_post']) && !empty($speedycache->options['status'])){
			if($new_status == 'publish' && $old_status != 'publish'){
				if(!empty($speedycache->options['new_post_type']) && $speedycache->options['new_post_type']){
					if($speedycache->options['new_post_type'] == 'all'){
						speedycache_delete_cache();
					} else if($speedycache->options['new_post_type'] == 'homepage'){
						speedycache_delete_home_page_cache();

						//to clear category cache and tag cache
						speedycache_single_delete_cache(false, $post->ID);
					}
				}else{
					speedycache_delete_cache();
				}
			}
		}

		if($new_status == 'publish' && $old_status == 'publish'){

			if(!empty($speedycache->options['update_post']) && !empty($speedycache->options['status'])){

				if($speedycache->options['update_post_type'] == 'post'){
					speedycache_single_delete_cache(false, $post->ID);
				}else if($speedycache->options['update_post_type'] == 'all'){
					speedycache_delete_cache();
				}
			}
		}

		if($new_status == 'trash' && $old_status == 'publish'){
			speedycache_single_delete_cache(false, $post->ID);
		}else if(($new_status == 'draft' || $new_status == 'pending' || $new_status == 'private') && $old_status == 'publish'){
			speedycache_delete_cache();
		}
	}
	
	static function critical_css($id = ''){
		
		$path = speedycache_cache_path('critical-css/');
		
		// Delete Single CriticalCSS
		if(!empty($id)){
			$url = get_permalink($id);
			
			if(empty($url)){
				return false;
			}
			
			if(file_exists($path . md5($url) . '.css')){
				unlink($path . md5($url) . '.css');
			}
			
			return true;
		}

		if(!is_dir($path)){
			return true;
		}

		$files = scandir($path);

		if(empty($files)){
			return true;
		}
		
		foreach($files as $file){
			if(in_array($file, ['.', '..', 'index.html'])){
				continue;
			}
			
			unlink($path . $file);
		}
		
		return true;
	}
	
	static function varnish(){
		global $speedycache;

		if(empty($speedycache->options['purge_varnish'])){
			return;
		}

		$server = !empty($speedycache->options['varniship']) ? $speedycache->options['varniship'] : '127.0.0.1';

		$url = home_url();
		$url = parse_url($url);

		if($url == FALSE){
			return;
		}
		
		$sslverify = ($url['scheme'] === 'https') ? true : false;
		$request_url = $url['scheme'] .'://'. $server . '/.*';

		$request_args = array(
			'method'    => 'PURGE',
			'headers'   => array(
				'Host'       => $url['host'],
			),
			'sslverify' => $sslverify,
		);

		$res = wp_remote_request($request_url, $request_args);

		if(is_wp_error($res)){
			$msg = $res->get_error_message();
			return array($msg, 'error');
		}

		if(is_array($res) && !empty($res['response']['code']) && '200' != $res['response']['code']){
			$msg = 'Something Went Wrong Unable to Purge Varnish';
			
			if(empty($res['response']['code']) && '501' == $res['response']['code']){
				$msg = 'Your server dosen\'t allows PURGE request';

				if(!empty($res['headers']['allow'])){
					$msg .= 'The accepted HTTP methods are' . $res['headers']['allow'];
				}
				
				$msg = __('Please contact your hosting provider if, Varnish is enabled and still getting this error', 'speedycache');
			}
			
			return array($msg, 'error');
		}

		\SpeedyCache\Logs::log('delete');
		\SpeedyCache\Logs::action();

		return array(__('Purged Varnish Cache Succesfully', 'speedycache'), 'success');
	}


}

