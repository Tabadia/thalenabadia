<?php

/*
* SPEEDYCACHE
* https://speedycache.com/
* (c) SpeedyCache Team
*/

namespace SpeedyCache;

if( !defined('SPEEDYCACHE_PRO_VERSION') ){
	die('HACKING ATTEMPT!');
}

class Statistics{

	static function init($extension = false, $size = false){
		global $speedycache;
		$speedycache->settings['static_extension'] = $extension ? $extension : false;
		$speedycache->settings['static_size'] = $size ? $size : false;
	}

	static function get(){
		
		$desktop_files = get_option('speedycache_html');
		$desktop_size = round(get_option('speedycache_html_size')/1000, 2);
		$mobile_files = get_option('speedycache_mobile');
		$mobile_size = round(get_option('speedycache_mobile_size')/1000, 2);
		$js_files = get_option('speedycache_js');
		$js_size = round(get_option('speedycache_js_size')/1000, 2);
		$css_files = get_option('speedycache_css');
		$css_size = round(get_option('speedycache_css_size')/1000, 2);
		
		$stats = array();
		$stats['desktop'] = array('size' => $desktop_size, 'file' => $desktop_files);
		$stats['mobile'] = array('size' => $mobile_size, 'file' => $mobile_files);
		$stats['js'] = array('size' => $js_size, 'file' => $js_files);
		$stats['css'] = array('size' => $css_size, 'file' => $css_files);

		return $stats;
	}

	static function update_db(){
		global $speedycache;
		
		// We do not need stats if its a test
		if(!empty($_GET['test_speedycache'])){
			return;
		}

		$option_name = 'speedycache_' . $speedycache->settings['static_extension'];
		$option_name_for_size = $option_name . '_size';
		
		$current_opt = get_option($option_name);
		
		if(!empty($current_opt)){
			$current_opt = $current_opt + 1;
			update_option($option_name, $current_opt);
		}else{
			update_option($option_name, 1, null, 'yes');
		}

		$size_current_opt = get_option($option_name_for_size);

		if(!empty($size_current_opt)){
			$size_current_opt = $size_current_opt + $speedycache->settings['static_size'];
			update_option($option_name_for_size, $size_current_opt);
			return;
		}
		
		update_option($option_name_for_size, $speedycache->settings['static_size'], null, 'yes');

	}

	static function statics(){
		?>
		<div class="speedycache-block">
			<div class="speedycache-block-title">
				<h2 id="cache-statics-h2"><?php _e('Cache Statistics', 'speedycache'); ?></h2>
			</div>
			<div id="speedycache-cache-statics">
				<div id="speedycache-cache-statics-desktop" class="speedycache-card">
					<div class="speedycache-card-body">
						<div class="speedycache-stats-info">
							<span>Desktop Cache</span>
							<p id="speedycache-cache-statics-desktop-data">
								<span class="speedycache-size">0Kb</span><br/>
								<span class="speedycache-files">of 0 Items</span>
							</p>
						</div>
						<div class="speedycache-stat-icon">
							<i class="fas fa-desktop"></i>
							<!--<span class="dashicons dashicons-desktop"></span>-->
						</div>
					</div>
				</div>
				<div id="speedycache-cache-statics-mobile" class="speedycache-card">
					<div class="speedycache-card-body">
						<div class="speedycache-stats-info">
							<span>Mobile Cache</span>
							<p id="speedycache-cache-statics-mobile-data">
								<span class="speedycache-size">0Kb</span><br/>
								<span class="speedycache-files">of 0 Items</span></p>
						</div>
						<div class="speedycache-stat-icon">
							<i class="fas fa-mobile"></i>
						</div>
					</div>
				</div>
				<div id="speedycache-cache-statics-css" class="speedycache-card">
					<div class="speedycache-card-body">
						<div class="speedycache-stats-info">
							<span>Minified CSS</span>
							<p id="speedycache-cache-statics-css-data">
								<span class="speedycache-size">0Kb</span><br/>
								<span class="speedycache-files">of 0 Items</span>
							</p>
						</div>
						<div class="speedycache-stat-icon"><i class="fab fa-css3-alt"></i></div>
					</div>
				</div>
				<div id="speedycache-cache-statics-js" class="speedycache-card">
					<div class="speedycache-card-body">	
						<div class="speedycache-stats-info">
							<span>Minified JS</span>
							<p id="speedycache-cache-statics-js-data">
								<span class="speedycache-size">0Kb</span><br/>
								<span class="speedycache-files">of 0 Items</span>
							</p>
						</div>
						<div class="speedycache-stat-icon"><i class="fab fa-js-square"></i></div>
					</div>
				</div>
			</div>
		</div>
	<?php }

}

