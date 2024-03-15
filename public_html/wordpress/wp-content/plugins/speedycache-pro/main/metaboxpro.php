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

class MetaboxPro{

	static function html($content, $post_id){
		global $speedycache;
		
		if(empty($speedycache->options['critical_css'])){
			return '';
		}

		$post_meta = get_post_meta($post_id, 'speedycache_post_meta', true);

		$html = '<div class="speedycache-option-wrap">
	<div class="speedycache-option-info">
		<span class="speedycache-option-name">'.esc_html__('Disable CriticalCSS', 'speedycache').'</span>
	</div>
	<label for="speedycache-disable-critical-css" class="speedycache-custom-checkbox">
		<input type="checkbox" id="speedycache-disable-critical-css" name="speedycache_disable_critical_css" '. (!empty($post_meta['disable_critical_css']) ? ' checked' : ''). '/>
		<div class="speedycache-input-slider"></div>
	</label>
</div>

<h3>Critical CSS</h3>
<p>Create Crtical CSS for this page</p>
<button class="button" id="speedycache-generate-specific-cache">Generate CriticalCSS</button>
';

		return $html;

	}

	static function enqueue_scripts(){
		global $speedycache, $post;

		if(empty($speedycache->options['critical_css'])){
			return;
		}

		wp_enqueue_script('speedycache_metabox', SPEEDYCACHE_PRO_URL . '/assets/js/metabox.js', array('jquery'), SPEEDYCACHE_PRO_VERSION, true);
		
		wp_localize_script('speedycache_metabox', 'speedycache_metabox', array(
			'nonce' => wp_create_nonce('speedycache_nonce'),
			'post_id' => !empty($post->ID) ? $post->ID : '',
			'url' => admin_url('admin-ajax.php')
		));
	}
	
	// Filter to sets options to be saved for post meta
	static function options($options){
		global $speedycache;
		
		if(empty($options)){
			$options = [];
		}
		
		if(!is_array($options)){
			$options = [$options]; 
		}
		
		if(!empty($speedycache->options['critical_css'])){
			$options[] = 'disable_critical_css';
		}
		
		return $options;
	}
}
