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

class Metabox{

	static function html(){
		global $speedycache;

		echo '<style>
.speedycache-option-wrap{display:flex;flex-direction:row;justify-content:space-between;padding-top: 5px;line-height: 18px;}.speedycache-custom-checkbox {display:inline-block;width:40px;height:23px;position:relative;flex-shrink: 0;}.speedycache-custom-checkbox input {display:none;}.speedycache-input-slider {background-color: #ccc;bottom: 0;cursor: pointer;left: 0;position: absolute;right: 0;top: 0;transition: background-color .4s;border-radius: 4px;width:40px;}.speedycache-input-slider:before {content: "";background-color: #fff;bottom: 2px;top:4px;height: 15px;left: 4px;position: absolute;transition: transform .4s;width: 15px;border-radius:4px;}input:checked + .speedycache-input-slider {background-color: #3d5afe;}input:checked + .speedycache-input-slider:before {transform: translateX(18px);}
</style>';
		
		$disable_cache = get_post_meta(speedycache_optget('post'), 'speedycache_disable_cache');

		if(empty($disable_cache)){
			$post_meta = get_post_meta(speedycache_optget('post'), 'speedycache_post_meta', true);
			$disable_cache = !empty($post_meta['disable_cache']) ? esc_html($post_meta['disable_cache']) : '';
			
		}
		
		wp_nonce_field('speedycache_metabox_save', 'speedycache_metabox_save_nonce' );
		
		
		echo '<div class="speedycache-option-wrap">
	<div class="speedycache-option-info">
		<span class="speedycache-option-name">'.esc_html__('Disable Cache', 'speedycache').'</span>
	</div>
	<label for="speedycache-disable-cache" class="speedycache-custom-checkbox">
		<input type="checkbox" id="speedycache-disable-cache" name="speedycache_disable_cache" '. (!empty($disable_cache) ? ' checked' : ''). '/>
		<div class="speedycache-input-slider"></div>
	</label>
</div>
'
.apply_filters('speedycache_pro_metabox', '', speedycache_optget('post'));

	}
	
	static function disable_cache(){
		$id = speedycache_optget('id');
		$prevent = speedycache_optget('disable');
		
		if(empty($id)){
			wp_send_json(array(''));
		}
	}
	
	static function save_settings($post_id, $post){
		global $pagenow, $speedycache;

		if(empty($_REQUEST['speedycache_metabox_save_nonce'])){
			return;
		}

		//TODO:: Maybe add nonce here
		if((!empty($_REQUEST['action']) && $_REQUEST['action'] == 'trash') || $pagenow != 'post.php' || !$post || !is_object($post)){
			return;
		}
		
		if(empty($speedycache->options['status'])){
			return;
		}
		
		if(!wp_verify_nonce($_REQUEST['speedycache_metabox_save_nonce'], 'speedycache_metabox_save')){
			die('Security Check Failed');
		}

		if(defined('DOING_AUTOSAVE') && DOING_AUTOSAVE){
			return;
		}

		$post_meta = get_post_meta($post_id, 'speedycache_post_meta', true);
		$options = apply_filters('speedycache_metabox_fields', ['disable_cache']);
		$new_meta = [];
		
		foreach($options as $option){
			if(!speedycache_optpost('speedycache_' . $option)){
				continue;
			}

			$new_meta[$option] = speedycache_optpost('speedycache_' . $option);
		}

		update_post_meta($post_id, 'speedycache_post_meta', $new_meta);
		
		return;
	}

	static function add(){
		global $speedycache;
		
		add_meta_box( 
			'speedycache_meta_box', // this is HTML id
			'SpeedyCache Options', 
			'\SpeedyCache\Metabox::html', // the callback function
			array('page', 'post', 'product', 'docs'),
			'side',
			'high'
		);
	}
}
