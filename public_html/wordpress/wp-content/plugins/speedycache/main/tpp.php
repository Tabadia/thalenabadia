<?php
/*
* SPEEDYCACHE
* https://speedycache.com/
* (c) SpeedyCache Team
*/

namespace SpeedyCache;

// Third Party Plugins
class TPP{

	static function clear_after_update_plugin($upgrader_object, $options){
		if($options['action'] !== 'update'){
			return;
		}

		if($options['type'] == 'plugin' && isset($options['plugins'])){
			speedycache_delete_cache(true);
		}
	}

	static function clear_after_update_theme($upgrader_object, $options){
		if($options['action'] !== 'update'){
			return;
		}
		if($options['type'] == 'theme' && isset($options['themes'])){
			speedycache_delete_cache(true);
		}
	}

	static function clear_cache_after_woocommerce_order_status_changed($order_id = false){
		if(!function_exists('wc_get_order')){
			return;
		}
		
		if(empty($order_id)){
			return;
		}
			
		$order = wc_get_order($order_id);

		if(empty($order)){
			return;
		}

		foreach($order->get_items() as $item_key => $item_values){
			if(method_exists($item_values, 'get_product_id')){
				speedycache_single_delete_cache(false, $item_values->get_product_id());
			}
		}
	}

	static function clear_cache_on_kksr_rate($id){
		speedycache_single_delete_cache(false, $id);
	}


	static function clear_cache_after_woo_update_product($product_id){
		global $speedycache;
		
		if(!$speedycache->settings['deleted_before']){
			speedycache_single_delete_cache(false, $product_id);
		}
	}

	static function postratings_clear_cache($rate_userid, $post_id){
		// to remove cache if vote is from homepage or category page or tag
		if(!empty($_SERVER['HTTP_REFERER'])){
			$url =  parse_url(esc_url(wp_unslash($_SERVER['HTTP_REFERER'])));

			$url['path'] = isset($url['path']) ? $url['path'] : '/index.html';

			if(isset($url['path'])){
				if($url['path'] == '/'){
					\SpeedyCache\Delete::rm_dir(speedycache_cache_path('all/index.html'));
				}else{
					// to prevent changing path with ../ or with another method
					if($url['path'] == realpath('.'.$url['path'])){
						\SpeedyCache\Delete::rm_dir(speedycache_cache_path('all').$url['path']);
					}
				}
			}
		}

		if(!empty($post_id)){
			speedycache_single_delete_cache(false, $post_id);
		}
	}

}
