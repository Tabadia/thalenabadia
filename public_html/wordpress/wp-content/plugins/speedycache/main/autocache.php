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

class AutoCache{
	
	static function init($post_id){
		global $speedycache;

		$speedycache->settings['auto_cache_urls'] = array();
		$speedycache->settings['auto_cache_id'] = $post_id;

		self::set_urls();
		self::set_urls_with_terms();
		
		if(empty($speedycache->settings['auto_cache_urls'])){
			return;
		}
		
		foreach($speedycache->settings['auto_cache_urls'] as $urls){
			self::create_cache($urls['url'], $urls['user-agent']);
		}
	}

	static function set_urls(){
		global $speedycache;
		
		if(empty($speedycache->settings['auto_cache_id'])){
			return;
		}
		
		$permalink = get_permalink($speedycache->settings['auto_cache_id']);
		$options = array('url' => $permalink, 'user-agent' => 'speedycache_preload Bot');
		
		array_push($speedycache->settings['auto_cache_urls'], $options);

		if(self::is_mobile_active()){
			$options = array('url' => $permalink, 'user-agent' => 'speedycache_preload iPhone Mobile Bot');
			array_push($speedycache->settings['auto_cache_urls'], $options);
		}
	}

	static function set_term_urls($term_taxonomy_id){
		global $speedycache;

		$term = get_term_by('term_taxonomy_id', $term_taxonomy_id);

		if(empty($term) || is_wp_error($term)){
			return;
		}

		$term_link = get_term_link($term->term_id, $term->taxonomy);
		$options = array('url' => $term_link, 'user-agent' => 'speedycache_preload Bot');

		array_push($speedycache->settings['auto_cache_urls'], $options);

		if(self::is_mobile_active()){
			$options = array('url' => $term_link, 'user-agent' => 'speedycache_preload iPhone Mobile Bot');
			array_push($speedycache->settings['auto_cache_urls'], $options);
		}

		if($term->parent <= 0){
			return;
		}
		
		$parent = get_term_by('id', $term->parent, $term->taxonomy);
		self::set_term_urls($parent->term_taxonomy_id);
	}

	static function set_urls_with_terms(){
		global $wpdb, $speedycache;

		$terms = $wpdb->get_results("SELECT * FROM `".$wpdb->prefix."term_relationships` WHERE `object_id`=".$speedycache->settings['auto_cache_id'], ARRAY_A);

		foreach($terms as $term_key => $term_val){
			self::set_term_urls($term_val['term_taxonomy_id']);
		}
	}
	
	static function create_cache($url, $user_agent){
		
		$res = speedycache_remote_get(esc_url($url), sanitize_text_field($user_agent));

		return;
	}

	static function is_mobile_active(){
		global $speedycache;

		if(!empty($speedycache->options['mobile']) && !empty($speedycache->options['mobile_theme'])){
			return true;
		}

		return false;
	}
}