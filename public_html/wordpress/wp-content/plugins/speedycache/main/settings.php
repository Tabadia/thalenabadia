<?php
/*
* SPEEDYCACHE
* https://speedycache.com/
* (c) SpeedyCache Team
*/

if( !defined('SPEEDYCACHE_VERSION') ){
	die('Hacking Attempt!');
}

function speedycache_page_footer($tweet = false){
	
	if(!defined('SITEPAD')){
	
		if(!empty($tweet)){
			
			echo '
				<div style="width:45%;background:#FFF;padding:15px; margin:auto">
					<b>Let your followers know that you use SpeedyCache to speedup your website :</b>
					<form method="get" action="https://twitter.com/intent/tweet" id="tweet" onsubmit="return dotweet(this);">
						<textarea name="text" cols="45" row="3" style="resize:none;">I increased the page load speed of my #WordPress #site using @speedycache</textarea>
						&nbsp; &nbsp; <input type="submit" value="Tweet!" class="speedycache-btn speedycache-btn-primary" onsubmit="return false;" id="twitter-btn" style="margin-top:20px;"/>
					</form>	
				</div>
				<br />

				<script>
				function dotweet(ele){
					window.open(jQuery("#"+ele.id).attr("action")+"?"+jQuery("#"+ele.id).serialize(), "_blank", "scrollbars=no, menubar=no, height=400, width=500, resizable=yes, toolbar=no, status=no");
					return false;
				}
				</script>';
	
		}
	}
}

function speedycache_save_settings(){
	global $speedycache;
	
	if( isset($_POST['submit']) || isset($_POST['speedycache_status']) ){
		if(empty($_POST['speedycache_status'])){
			unset($speedycache->options['status']); 
		} else{
			$speedycache->options['status'] = 1;
		}
	}
	
	if( isset($_POST['submit']) || isset($_POST['speedycache_automatic_cache']) ){ 
		$speedycache->options['automatic_cache'] = empty($_POST['speedycache_automatic_cache']) ? 0 : 1;
	}
	
	if( isset($_POST['submit']) || isset($_POST['speedycache_post_types']) ){ 
		$speedycache->options['post_types'] = empty($_POST['speedycache_post_types']) ? [] : speedycache_optpost('speedycache_post_types');
	}
	
	if( isset($_POST['submit']) || isset($_POST['speedycache_preload']) ){
		$speedycache->options['preload'] = empty($_POST['speedycache_preload']) ? 0 : 1;

		$preload_time = wp_next_scheduled('speedycache_preload');
		if(empty($speedycache->options['preload']) && !empty($preload_time)){
			wp_unschedule_event($preload_time, 'speedycache_preload');
		}
	}

	//Preload Settings
	
	//Preload Homepage
	if( isset($_POST['submit']) || isset($_POST['speedycache_preload_homepage']) ){
		$speedycache->options['preload_homepage'] = empty($_POST['speedycache_preload_homepage']) ? 0 : 1;
	}
	
	//Preload Post
	if( isset($_POST['submit']) || isset($_POST['speedycache_preload_post']) ){
		$speedycache->options['preload_post'] = empty($_POST['speedycache_preload_post']) ? 0 : 1;
	}
	
	//Preload Category
	if( isset($_POST['submit']) || isset($_POST['speedycache_preload_category']) ){
		$speedycache->options['preload_category'] = empty($_POST['speedycache_preload_category']) ? 0 : 1;
	}
	
	//Preload Page
	if( isset($_POST['submit']) || isset($_POST['speedycache_preload_page']) ){
		$speedycache->options['preload_page'] = empty($_POST['speedycache_preload_page']) ? 0 : 1;
	}
	
	//Preload Tag
	if( isset($_POST['submit']) || isset($_POST['speedycache_preload_tag']) ){
		$speedycache->options['preload_tag'] = empty($_POST['speedycache_preload_tag']) ? 0 : 1;
	}
	
	//Preload Attachment
	if( isset($_POST['submit']) || isset($_POST['speedycache_preload_attachment']) ){
		$speedycache->options['preload_attachment'] = empty($_POST['speedycache_preload_attachment']) ? 0 : 1;
	}
	
	//Preload Custom Post Types
	if( isset($_POST['submit']) || isset($_POST['speedycache_preload_customposttypes']) ){
		$speedycache->options['preload_custom_post_types'] = empty($_POST['speedycache_preload_custom_post_types']) ? 0 : 1;
	}
	
	//Preload Custom Post Taxonomies
	if( isset($_POST['submit']) || isset($_POST['speedycache_preload_custom_taxonomies']) ){
		$speedycache->options['preload_custom_taxonomies'] = empty($_POST['speedycache_preload_custom_taxonomies']) ? 0 : 1;
	}
	
	//Preload Number
	if( isset($_POST['submit']) || isset($_POST['speedycache_preload_number']) ){
		$speedycache->options['preload_number'] = empty($_POST['speedycache_preload_number']) ? 4 : speedycache_optpost('speedycache_preload_number');
	}
	
	//Preload Restart
	if( isset($_POST['submit']) || isset($_POST['speedycache_preload_restart']) ){
		$speedycache->options['preload_restart'] = empty($_POST['speedycache_preload_restart']) ? 0 : 1;
	}
	
	//Preload Order
	if( isset($_POST['submit']) || isset($_POST['speedycache_preload_order']) ){
		$speedycache->options['preload_order'] = empty($_POST['speedycache_preload_order']) ? '' : speedycache_optpost('speedycache_preload_order');
	}
	
	//speedycache for Logged in user
	if( isset($_POST['submit']) || isset($_POST['speedycache_logged_in_user']) ){
		$speedycache->options['logged_in_user'] = empty($_POST['speedycache_logged_in_user']) ? 0 : 1;
	}
	
	if( isset($_POST['submit']) || isset($_POST['speedycache_mobile']) ){
		$speedycache->options['mobile'] = empty($_POST['speedycache_mobile']) ? 0 : 1;
	}
	
	//Mobile Theme
	if( isset($_POST['submit']) || isset($_POST['speedycache_mobile_theme']) ){
		$speedycache->options['mobile_theme'] = empty($_POST['speedycache_mobile_theme']) ? 0 : 1;
	}
		
	//Mobile Theme Name
	if( isset($_POST['submit']) || isset($_POST['speedycache_mobile_theme_name']) ){
		$speedycache->options['mobile_theme_name'] = empty($_POST['speedycache_mobile_theme_name']) ? '' : speedycache_optpost('speedycache_mobile_theme_name');
	}
	
	//New Post
	if( isset($_POST['submit']) || isset($_POST['speedycache_new_post']) ){
		$speedycache->options['new_post'] = empty($_POST['speedycache_new_post']) ? 0 : 1;
	}
	
	//New Post Type 
	if( isset($_POST['submit']) || isset($_POST['speedycache_new_post_type']) ){
		$speedycache->options['new_post_type'] = empty($_POST['speedycache_new_post_type']) ? 'all' : speedycache_optpost('speedycache_new_post_type');
	}
	
	//Update Post
	if( isset($_POST['submit']) || isset($_POST['speedycache_update_post']) ){
		$speedycache->options['update_post'] = empty($_POST['speedycache_update_post']) ? 0 : 1;
	}
	
	//Update Post Type
	if( isset($_POST['submit']) || isset($_POST['speedycache_update_post_type']) ){
		$speedycache->options['update_post_type'] = empty($_POST['speedycache_update_post_type'])? 'post' : speedycache_optpost('speedycache_update_post_type');
	}
	
	// Enable Varnish
	if( isset($_POST['submit']) || isset($_POST['speedycache_purge_varnish']) ){
		$speedycache->options['purge_varnish'] = empty($_POST['speedycache_purge_varnish']) ? 0 : 1; 
	}
	
	if( isset($_POST['submit']) || isset($_POST['speedycache_varniship']) ){
		$speedycache->options['varniship'] = empty($_POST['speedycache_varniship']) ? '127.0.0.1' : speedycache_optpost('speedycache_varniship');
	}
	
	//Minify HTML
	if( isset($_POST['submit']) || isset($_POST['speedycache_minify_html']) ){
		$speedycache->options['minify_html'] = empty($_POST['speedycache_minify_html']) ? 0 : 1;
	}
	
	//Minify CSS
	if( isset($_POST['submit']) || isset($_POST['speedycache_minify_css']) ){
		$speedycache->options['minify_css'] = empty($_POST['speedycache_minify_css']) ? 0 : 1;
	}
	
	//Minify CSS Powerful
	if( isset($_POST['submit']) || isset($_POST['speedycache_minify_css_enhanced']) ){
		$speedycache->options['minify_css_enhanced'] = empty($_POST['speedycache_minify_css_enhanced']) ? 0 : 1;
	}
	
	//Combine CSS
	if( isset($_POST['submit']) || isset($_POST['speedycache_combine_css']) ){
		$speedycache->options['combine_css'] = empty($_POST['speedycache_combine_css']) ? 0 : 1;
	}
	
	if( isset($_POST['submit']) || isset($_POST['speedycache_minify_js']) ){
		$speedycache->options['minify_js'] = empty($_POST['speedycache_minify_js']) ? 0 : 1;
	}
	
	//Combine Js
	if( isset($_POST['submit']) || isset($_POST['speedycache_combine_js']) ){
		$speedycache->options['combine_js'] = empty($_POST['speedycache_combine_js']) ? 0 : 1;
	}
	
	//Combine Js powerful
	if( isset($_POST['submit']) || isset($_POST['speedycache_combine_js_enhanced']) ){
		$speedycache->options['combine_js_enhanced'] = empty($_POST['speedycache_combine_js_enhanced']) ? 0 : 1;
	}
	
	// Delay JS
	if( isset($_POST['submit']) || isset($_POST['speedycache_delay_js']) ){
		$speedycache->options['delay_js'] = empty($_POST['speedycache_delay_js']) ? 0 : 1;
	}
	
	// Delay JS mode
	if( isset($_POST['submit']) || isset($_POST['speedycache_delay_js_mode']) ){
		$speedycache->options['delay_js_mode'] = empty($_POST['speedycache_delay_js_mode']) ? 'selected' : speedycache_optpost('speedycache_delay_js_mode');
	}
	
	// Delay JS Exclude Scripts
	if( isset($_POST['submit']) || isset($_POST['speedycache_delay_js_excludes']) ){
		$speedycache->options['delay_js_excludes'] = empty($_POST['speedycache_delay_js_excludes']) ? [] : explode(' ', speedycache_optpost('speedycache_delay_js_excludes'));
	}
	
	// Delay JS Scripts
	if( isset($_POST['submit']) || isset($_POST['speedycache_delay_js_scripts']) ){
		$speedycache->options['delay_js_scripts'] = empty($_POST['speedycache_delay_js_scripts']) ? [] : explode(' ', speedycache_optpost('speedycache_delay_js_scripts'));
	}
	
	// Lazy Load HTML
	if( isset($_POST['submit']) || isset($_POST['speedycache_lazy_load_html']) ){
		$speedycache->options['lazy_load_html'] = empty($_POST['speedycache_lazy_load_html']) ? 0 : 1;
	}
	
	// Lazy Load HTML element list
	if( isset($_POST['submit']) || isset($_POST['speedycache_lazy_load_html_elements']) ){
		$speedycache->options['lazy_load_html_elements'] = empty($_POST['speedycache_lazy_load_html_elements']) ? [] : explode(' ', speedycache_optpost('speedycache_lazy_load_html_elements'));
	}
	
	// Critical CSS
	if( isset($_POST['submit']) || isset($_POST['speedycache_critical_css']) ){
		$speedycache->options['critical_css'] = empty($_POST['speedycache_critical_css']) ? 0 : 1;
	}
	
	// Unused CSS
	if( isset($_POST['submit']) || isset($_POST['speedycache_unused_css']) ){
		$speedycache->options['unused_css'] = empty($_POST['speedycache_unused_css']) ? 0 : 1;
	}

	// How to handle unused css, load async, load on user interaction, remove unused CSS.
	if( isset($_POST['submit']) || isset($_POST['speedycache_unusedcss_load']) ){
		$speedycache->options['unusedcss_load'] = empty($_POST['speedycache_unusedcss_load']) ? 'async' : speedycache_optpost('speedycache_unusedcss_load');
	}
	
	// Stylesheets to exclude from removing Unused CSS.
	if( isset($_POST['submit']) || isset($_POST['speedycache_unused_css_exclude_stylesheets']) ){
		$speedycache->options['unused_css_exclude_stylesheets'] = empty($_POST['speedycache_unused_css_exclude_stylesheets']) ? [] : explode(' ', speedycache_optpost('speedycache_unused_css_exclude_stylesheets'));
	}
	
	// Selector you want to make sure to be included in Used CSS.
	if( isset($_POST['submit']) || isset($_POST['speedycache_unusedcss_include_selector']) ){
		$speedycache->options['unusedcss_include_selector'] = empty($_POST['speedycache_unusedcss_include_selector']) ? [] : explode(' ', speedycache_optpost('speedycache_unusedcss_include_selector'));
	}
	
	//Gzip
	if( isset($_POST['submit']) || isset($_POST['speedycache_gzip'])){
		$speedycache->options['gzip'] = empty($_POST['speedycache_gzip']) ? 0 : 1;
	}
	
	if( isset($_POST['submit']) || isset($_POST['speedycache_font_rendering'])){
		$speedycache->options['font_rendering'] = empty($_POST['speedycache_font_rendering']) ? 0 : 1;
	}
	
	//LBC
	if( isset($_POST['submit']) || isset($_POST['speedycache_lbc']) ){
		$speedycache->options['lbc'] = empty($_POST['speedycache_lbc']) ? 0 : 1;
	}
	
	//Disable Emojis
	if( isset($_POST['submit']) || isset($_POST['speedycache_disable_emojis']) ){
		$speedycache->options['disable_emojis'] = empty($_POST['speedycache_disable_emojis']) ? 0 : 1;
	}
	
	//Render Blocking
	if( isset($_POST['submit']) || isset($_POST['speedycache_render_blocking']) ){
		$speedycache->options['render_blocking'] = empty($_POST['speedycache_render_blocking']) ? 0 : 1;
	}
	
	//Google Fonts
	if( isset($_POST['submit']) || isset($_POST['speedycache_google_fonts']) ){
		$speedycache->options['google_fonts'] = empty($_POST['speedycache_google_fonts']) ? 0 : 1;
	}
	
	//Lazy Load
	if( isset($_POST['submit']) || isset($_POST['speedycache_lazy_load']) ){
		$speedycache->options['lazy_load'] = empty($_POST['speedycache_lazy_load']) ? 0 : 1;
	}
	
	//Lazy Load Placeholder
	if( isset($_POST['submit']) || isset($_POST['speedycache_lazy_load_placeholder']) ){
		$speedycache->options['lazy_load_placeholder'] = empty($_POST['speedycache_lazy_load_placeholder']) ? '' : speedycache_optpost('speedycache_lazy_load_placeholder');
	}
	
	// Lazy Load Above the Fold
	if( isset($_POST['submit']) || isset($_POST['speedycache_exclude_above_fold']) ){
		$speedycache->options['exclude_above_fold'] = !isset($_POST['speedycache_exclude_above_fold']) ? 2 : speedycache_optpost('speedycache_exclude_above_fold');
	}

	//Lazy Load Keywords
	if( isset($_POST['submit']) || isset($_POST['speedycache_lazy_load_keywords']) ){
		$speedycache->options['lazy_load_keywords'] = empty($_POST['speedycache_lazy_load_keywords']) ? '' : speedycache_optpost('speedycache_lazy_load_keywords');
	}
	
	//Lazy load custom placeholder url
	if( isset($_POST['submit']) || isset($_POST['speedycache_lazy_load_placeholder_custom_url']) ){
		$speedycache->options['lazy_load_placeholder_custom_url'] = empty($_POST['speedycache_lazy_load_placeholder_custom_url']) ? '' : speedycache_optpost('speedycache_lazy_load_placeholder_custom_url');
	}
	
	//Lazy Load Exclude Full Size IMG
	if( isset($_POST['submit']) || isset($_POST['speedycache_lazy_load_exclude_full_size_img']) ){
		$speedycache->options['lazy_load_exclude_full_size_img'] = empty($_POST['speedycache_lazy_load_exclude_full_size_img']) ? '' : speedycache_optpost('speedycache_lazy_load_exclude_full_size_img');
	}

	// Display Swap
	if( isset($_POST['submit']) || isset($_POST['speedycache_display_swap']) ){
		$speedycache->options['display_swap'] = empty($_POST['speedycache_display_swap']) ? 0 : 1;
	}	
	
	// Instant Page
	if( isset($_POST['submit']) || isset($_POST['speedycache_instant_page']) ){
		$speedycache->options['instant_page'] = empty($_POST['speedycache_instant_page']) ? 0 : 1;
	}
	
	// Local Google Fonts
	if( isset($_POST['submit']) || isset($_POST['speedycache_local_gfonts']) ){
		$speedycache->options['local_gfonts'] = empty($_POST['speedycache_local_gfonts']) ? 0 : 1;
	}
	
	// Critical Images
	if( isset($_POST['submit']) || isset($_POST['speedycache_image_dimensions']) ){
		$speedycache->options['image_dimensions'] = empty($_POST['speedycache_image_dimensions']) ? 0 : 1;
	}
	
	// Critcial Image Count
	if( isset($_POST['submit']) || isset($_POST['speedycache_critical_image_count']) ){
		$speedycache->options['critical_image_count'] = empty($_POST['speedycache_critical_image_count']) ? 1 : sanitize_text_field($_POST['speedycache_critical_image_count']);
	}
	
	// Preload Critical Images
	if( isset($_POST['submit']) || isset($_POST['speedycache_critical_images']) ){
		$speedycache->options['critical_images'] = empty($_POST['speedycache_critical_images']) ? 0 : 1;
	}
	
	// Preconnect
	if(isset($_POST['submit']) || isset($_POST['speedycache_pre_connect'])){
		$speedycache->options['pre_connect'] = empty($_POST['speedycache_pre_connect']) ? 0 : 1;
	}
	
	// Preload Resources
	if(isset($_POST['submit']) || isset($_POST['speedycache_preload_resources'])){
		$speedycache->options['preload_resources'] = empty($_POST['speedycache_preload_resources']) ? 0 : 1;
	}
	
	//DNS Prefetch
	if( isset($_POST['submit']) || isset($_POST['speedycache_dns_prefetch']) ){
		$speedycache->options['dns_prefetch'] = empty($_POST['speedycache_dns_prefetch']) ? 0 : 1;
	}

	if(isset($_POST['submit']) && isset($_POST['speedycache_dns_urls'])){
		$speedycache->options['dns_urls'] = empty($_POST['speedycache_dns_urls']) ? [] : explode(' ', speedycache_optpost('speedycache_dns_urls'));
	}
	
	// Gravatar Cache
	if( isset($_POST['submit']) || isset($_POST['speedycache_gravatar_cache']) ){
		$speedycache->options['gravatar_cache'] = empty($_POST['speedycache_gravatar_cache']) ? 0 : 1;
	}
	
	// Test Mode
	if( isset($_POST['submit']) || isset($_POST['speedycache_test_mode']) ){
		set_transient('speedycache_test_mode', empty($_POST['speedycache_test_mode']) ? 0 : 1, 1800);
	}
	
	// Change settings
	if(isset($_POST['submit'])){
		update_option('speedycache_options', $speedycache->options);
		$speedycache->settings['system_message'] = \SpeedyCache\htaccess::modify();
	}
	
	if(!isset($speedycache->settings['system_message'][1]) || $speedycache->settings['system_message'][1] == 'error'){
		speedycache_notify($speedycache->settings['system_message']);
		return;
	}

	$message = speedycache_check_cache_path_writeable();
	if(empty($message)){
		speedycache_notify($speedycache->settings['system_message']);
		return;
	}

	if(is_array($message)){
		$speedycache->settings['system_message'] = $message;
		speedycache_notify($speedycache->settings['system_message']);
		
		return;
	}
	
	if(!empty($speedycache->options['preload'])){
		\SpeedyCache\Precache::set();
	} else {
		delete_option('speedycache_preload');
		wp_clear_scheduled_hook('speedycache_Preload');
	}
	
	speedycache_exclude_urls();
	speedycache_notify($speedycache->settings['system_message']);
}

function speedycache_obj_settings(){
	global $speedycache;

	$memory = 'None';	
	if(defined('SPEEDYCACHE_PRO') && class_exists('Redis')){
		try{
			$memory = \SpeedyCache\ObjectCache::get_memory();
		} catch(Exception $e) {
			$memory = 'None';
		}
	}
	
	?>

	<div class="speedycache-block">
		<?php if(!defined('SPEEDYCACHE_PRO')){ ?>
		<div class="speedycache-disabled-block">
			<div class="speedycache-disabled-block-info">
				<i class="fas fa-lock"></i>
				<p><?php esc_html_e('Object Cache is only available in Pro version', 'speedycache'); ?></p>
				<a href="https://speedycache.com/pricing" target="_blank"><?php esc_html_e('Check out the Pro version', 'speedycache'); ?></a>
			</div>
		</div>
		<?php } ?>
	
		<div class="speedycache-block-title">
			<h2><?php esc_html_e('Object Cache', 'speedycache'); ?></h2>
		</div>
		
		<div class="speedycache-object-stats">
			<div style="display:flex; justify-content:space-between;">
				<div><strong>Caching Status:</strong>  <?php echo !empty($speedycache->object['enable']) ? '<span style="color:green;">Enabled</span>' : '<span style="color:red;">Disabled</span>'; ?></div>
				<div><strong>Memory Usage:</strong> <span><?php esc_html_e($memory); ?></span></div>
			</div>
			<div class="speedycache-drop-in"><strong>Drop In:</strong> <?php echo defined('SPEEDYCACHE_OBJECT_CACHE') ? '<span style="color:green;">Valid</span>' : '<span style="color:red;">Not Valid</span>';?></div>
			<div style="margin-top:7px;"><strong>phpRedis Status:</strong> <?php echo empty(phpversion('redis')) ? '<span style="color:red">' . __('phpRedis Not Found', 'speedycache') : (version_compare(phpversion('redis'), '3.1.1') > 0 ? '<span style="color:green">'. __('Available', 'speedycache') . '('.phpversion('redis').')' : '<span style="color:red">' . __('You are using older version of PHPRedis')); ?></span></div>
			
			<button class="button button-primary speedycache-flush-db">Flush DB<div class="speedycache-btn-loader"><img src="<?php echo site_url() . '/wp-admin/images/loading.gif';?>"/></div></button>
		</div>
		<div class="speedycache-object-charts"></div>
	</div>
	
	<div class="speedycache-block">
		<?php if(!defined('SPEEDYCACHE_PRO')){ ?>
		<div class="speedycache-disabled-block"></div>
		<?php } ?>
		<form method="POST">
		<?php wp_nonce_field('speedycache_nonce', 'security');  ?>
		<input type="hidden" value="object_cache" name="speedycache_page">
		
		<div class="speedycache-block-title">
			<h2><?php esc_html_e('Settings', 'speedycache'); ?></h2>
		</div>
		<table class="wp-list-table speedycache-table" style="width:100%;">
			<tr>
				<th>
					<label for="speedycache_enable_object">
					<?php esc_html_e('Enable', 'speedycache'); ?>
					</label>
				</th>
				<td>
					<label for="speedycache_enable_object" class="speedycache-custom-checkbox">
						<input type="checkbox" id="speedycache_enable_object" name="enable_object" <?php echo (isset($speedycache->object['enable']) && $speedycache->object['enable']) ? ' checked="true"' : '';?>/>
						<div class="speedycache-input-slider"></div>
					</label>
					<div class="speedycache-option-desc"><?php esc_html_e('Enables Object caching, if you have full page caching then it might show some conflicts.', 'speedycache'); ?></div>
				</td>
			</tr>
			
			<tr style="display:none;">
				<th>
					<label for="speedycache_object_driver">
						<?php esc_html_e('Driver', 'speedycache'); ?>
					</label>
				</th>
				<td>
					<label for="speedycache_object_driver">
						<select name="driver" id="speedycache_object_driver">
							<option value="Redis" selected>Redis</option>
						</select>
					</label>
					<div class="speedycache-option-desc"><?php esc_html_e('Choose which Object Cache Driver you want to use.', 'speedycache'); ?></div>
				</td>
			</tr>
			
			<tr>
				<th>
					<label for="speedycache_object_host">
						<?php esc_html_e('Host', 'speedycache'); ?>
					</label>
				</th>
				<td>
					<label for="speedycache_object_host">
						<input type="text" name="host" id="speedycache_object_host" value="<?php echo !empty($speedycache->object['host']) ? esc_attr($speedycache->object['host']) : 'localhost'; ?>"/>
					</label>
					<div class="speedycache-option-desc"><?php esc_html_e('Your Redis host name or IP address.', 'speedycache'); ?></div>
				</td>
			</tr>

			<tr>
				<th>
					<label for="speedycache_object_port">
						<?php esc_html_e('Port', 'speedycache'); ?>
					</label>
				</th>
				<td>
					<label for="speedycache_object_port">
						<input type="text" name="port" id="speedycache_object_port" value="<?php echo !empty($speedycache->object['port']) ? esc_attr($speedycache->object['port']) : '6379'; ?>"/>
					</label>
					<div class="speedycache-option-desc"><?php esc_html_e('Your Redis host port number', 'speedycache'); ?></div>
				</td>
			</tr>
			
			<tr>
				<th>
					<label for="speedycache_object_username">
						<?php esc_html_e('Username', 'speedycache'); ?>
					</label>
				</th>
				<td>
					<label for="speedycache_object_username">
						<input type="password" id="speedycache_object_username" name="username" value="<?php echo (!empty($speedycache->object['username'])) ? esc_html($speedycache->object['username']) : '';?>" />
					</label>
					<div class="speedycache-option-desc"><?php esc_html_e('Username of your Redis acccount.', 'speedycache'); ?></div>
				</td>
			</tr>
			
			<tr>
				<th>
					<label for="speedycache_object_password">
						<?php esc_html_e('Password', 'speedycache'); ?>
					</label>
				</th>
				<td>
					<label for="speedycache_object_password">
						<input type="password" id="speedycache_object_password" name="password" value="<?php echo (!empty($speedycache->object['password'])) ? esc_html($speedycache->object['password']) : '';?>" />
					</label>
					<div class="speedycache-option-desc"><?php esc_html_e('Password for your Redis Account.', 'speedycache'); ?></div>
				</td>
			</tr>
			
			<tr>
				<th>
					<label for="speedycache_object_ttl">
						<?php esc_html_e('Object Time to live', 'speedycache'); ?>
					</label>
				</th>
				<td>
					<label for="speedycache_object_ttl">
						<input type="text" name="ttl" id="speedycache_object_ttl" value="<?php echo !empty($speedycache->object['ttl']) ? esc_attr($speedycache->object['ttl']) : '360'; ?>"/>
					</label>
					<div class="speedycache-option-desc"><?php esc_html_e('How long you want the cached Object to persist', 'speedycache'); ?></div>
				</td>
			</tr>
			
			<tr>
				<th>
					<label for="speedycache_object_db_id">
						<?php esc_html_e('Redis DB ID', 'speedycache'); ?>
					</label>
				</th>
				<td>
					<label for="speedycache_object_db_id">
						<input type="text" name="db-id" id="speedycache_object_db_id" value="<?php echo !empty($speedycache->object['db-id']) ? esc_attr($speedycache->object['db-id']) : '0'; ?>" style="width:45px;"/>
					</label>
					<div class="speedycache-option-desc"><?php esc_html_e('Set the database number, make sure to keep it different for every website you use it on', 'speedycache'); ?></div>
				</td>
			</tr>
			
			<tr>
				<th>
					<label for="speedycache_persistant_object">
						<?php esc_html_e('Persistent Connection', 'speedycache'); ?>
					</label>
				</th>
				<td>
					<label for="speedycache_persistent_object" class="speedycache-custom-checkbox">
						<input type="checkbox" id="speedycache_persistent_object" name="persistent" <?php echo (!empty($speedycache->object['persistent'])) ? ' checked="true"' : '';?>/>
						<div class="speedycache-input-slider"></div>
					</label>
					<div class="speedycache-option-desc"><?php esc_html_e('This will Keep Alive the connection to redis.', 'speedycache'); ?></div>
				</td>
			</tr>
			
			<tr>
				<th>
					<label for="speedycache_object_admin">
						<?php esc_html_e('Cache WP_Admin', 'speedycache'); ?>
					</label>
				</th>
				<td>
					<label for="speedycache_object_admin" class="speedycache-custom-checkbox">
						<input type="checkbox" id="speedycache_object_admin" name="admin" <?php echo (!empty($speedycache->object['admin'])) ? ' checked="true"' : '';?>/>
						<div class="speedycache-input-slider"></div>
					</label>
					<div class="speedycache-option-desc"><?php esc_html_e('This will cache the admin pages too.', 'speedycache'); ?></div>
				</td>
			</tr>
			
			<tr>
				<th>
					<label for="speedycache_async_flush">
						<?php esc_html_e('Asynchronous Flushing', 'speedycache'); ?>
					</label>
				</th>
				<td>
					<label for="speedycache_async_flush" class="speedycache-custom-checkbox">
						<input type="checkbox" id="speedycache_async_flush" name="async_flush" <?php echo (!empty($speedycache->object['async_flush'])) ? ' checked="true"' : '';?>/>
						<div class="speedycache-input-slider"></div>
					</label>
					<div class="speedycache-option-desc"><?php esc_html_e('Deletes asynchronously, without blocking', 'speedycache'); ?></div>
				</td>
			</tr>
			
			<tr>
				<th>
					<label for="speedycache_serialization_method">
						<?php esc_html_e('Serialization Method', 'speedycache'); ?>
					</label>
				</th>
				<td>
					<?php

					$serialization_methods = ['SERIALIZER_PHP', 'SERIALIZER_IGBINARY'];

					echo '<label for="speedycache_serialization_method">
					
						<select id="speedycache_serialization_method" name="serialization" value="'. (!empty($speedycache->object['serialization']) ? esc_attr($speedycache->object['serialization']) : 'php').'">
							<option value="none">None</option>';

							foreach($serialization_methods as $method){
								$selected = '';

								if(empty($speedycache->object['serialization']) && $method === 'SERIALIZER_PHP'){
									$selected = 'selected';
								}else if(!empty($speedycache->object['serialization']) && $speedycache->object['serialization'] === $method){
									$selected = 'selected';
								}

								if(defined('Redis::'.$method)){
									echo '<option value="'.esc_attr($method).'" '.esc_attr($selected).'>'.esc_html($method).'</option>';
								}
							}
						echo '</select>
					</label>
					<div class="speedycache-option-desc">'.esc_html('If you don\'t see IG_BINARY option then the phpredis is not built with IG_BINARY, IG_BINARY can save upto 50% space', 'speedycache').'</div>';
					?>
				</td>
			</tr>

			<tr>
				<th>
					<label for="speedycache_compression_method">
						<?php esc_html_e('Compression Method', 'speedycache'); ?>
					</label>
				</th>
				<td>
					<?php

					$serialization_methods = ['None', 'COMPRESSION_ZSTD', 'COMPRESSION_LZ4', 'COMPRESSION_LZF'];

					echo '<label for="speedycache_compression_method">
					
						<select id="speedycache_compression_method" name="compress" value="'. (!empty($speedycache->object['compress']) ? esc_attr($speedycache->object['compress']) : 'php').'">
							<option value="none">None</option>';

							foreach($serialization_methods as $method){
								$selected = '';

								if(empty($speedycache->object['compress']) && $method === 'None'){
									$selected = 'selected';
								}else if(!empty($speedycache->object['compress']) && $speedycache->object['compress'] === $method){
									$selected = 'selected';
								}

								if(defined('Redis::'.$method)){
									echo '<option value="'.esc_attr($method).'" '.esc_attr($selected).'>'.esc_html($method).'</option>';
								}
							}
						echo '</select>
					</label>
					<div class="speedycache-option-desc">'.esc_html('If you dont see any option then your phpredis is not built with compression options', 'speedycache').'</div>';
					?>
				</td>
			</tr>
			
			<tr>
				<th>
					<label for="speedycache_non_cache_group">
						<?php esc_html_e('Do not cache groups', 'speedycache'); ?>
					</label>
				</th>
				<td>
					<label for="speedycache_non_cache_group">
						<textarea id="speedycache_non_cache_group" name="non_cache_group" rows="5" cols="30"><?php
							if(empty($speedycache->object['non_cache_group'])){
								$speedycache->object['non_cache_group'] = ['plugins', 'comment', 'counts', 'wc_session_id'];
							}

							foreach($speedycache->object['non_cache_group'] as $group){
								echo $group . "\n";
							}
						?></textarea>
					</label>
					<div class="speedycache-option-desc"><?php esc_html_e('These are the groups which should not be cached, One Per Line', 'speedycache'); ?></div>
				</td>
			</tr>
			
		</table>
		<div class="speedycache-option-wrap speedycache-submit-btn">
			<input type="submit" name="submit" value="Save Settings" class="speedycache-btn speedycache-btn-primary">
		</div>
		</form>
	</div>
	<?php	
}

function speedycache_obj_save(){
	global $speedycache;
	
	if(!defined('SPEEDYCACHE_PRO')){
		speedycache_notify(array(__('Object Cache is a Pro feature and you are using the free version', 'speedycache'), 'error'));
		return;
	}

	if(!class_exists('Redis')){
		speedycache_notify(array(__('phpRedis Library not found', 'speedycache'), 'error'));
		return;
	}
	
	if(isset($_POST['submit']) || isset($_POST['enable_object'])){
		$speedycache->object['enable'] = empty($_POST['enable_object']) ? 0 : 1;
	}
	
	if(isset($_POST['submit']) || isset($_POST['driver'])){ 
		$speedycache->object['driver'] = empty($_POST['driver']) ? 'Redis' : sanitize_text_field($_POST['host']);
	}
	
	if(isset($_POST['submit']) || isset($_POST['host'])){ 
		$speedycache->object['host'] = empty($_POST['host']) ? 'localhost' : sanitize_text_field($_POST['host']);
	}
	
	if(isset($_POST['submit']) || isset($_POST['port'])){ 
		$speedycache->object['port'] = $_POST['port'] === FALSE ? 6379 : (int)sanitize_text_field($_POST['port']);
	}
	
	if(isset($_POST['submit']) || isset($_POST['ttl'])){ 
		$speedycache->object['ttl'] = empty($_POST['ttl']) ? 360 : (int)sanitize_text_field($_POST['ttl']);
	}

	if(isset($_POST['submit']) || isset($_POST['username'])){ 
		$speedycache->object['username'] = empty($_POST['username']) ? '' : sanitize_text_field($_POST['username']);
	}
	
	if(isset($_POST['submit']) || isset($_POST['password'])){ 
		$speedycache->object['password'] = empty($_POST['password']) ? '' : sanitize_text_field($_POST['password']);
	}
	
	if(isset($_POST['submit']) || isset($_POST['db-id'])){ 
		$speedycache->object['db-id'] = empty($_POST['db-id']) ? 0 : (int)sanitize_text_field($_POST['db-id']);
	}
	
	if(isset($_POST['submit']) || isset($_POST['admin'])){ 
		$speedycache->object['admin'] = empty($_POST['admin']) ? 0 : 1;
	}
	
	if(isset($_POST['submit']) || isset($_POST['persistent'])){ 
		$speedycache->object['persistent'] = empty($_POST['persistent']) ? 0 : 1;
	}
	
	if(isset($_POST['submit']) || isset($_POST['async_flush'])){ 
		$speedycache->object['async_flush'] = empty($_POST['async_flush']) ? 0 : 1;
	}
	
	if(isset($_POST['submit']) || isset($_POST['serialization'])){ 
		$speedycache->object['serialization'] = empty($_POST['serialization']) ? '' : sanitize_text_field($_POST['serialization']);
	}
	
	if(isset($_POST['submit']) || isset($_POST['compress'])){ 
		$speedycache->object['compress'] = empty($_POST['compress']) ? '' : sanitize_text_field($_POST['compress']);
	}
	
	if(isset($_POST['submit']) || isset($_POST['non_cache_group'])){
		
		$non_cache_group = [];
		
		if(!empty($_POST['non_cache_group'])){
			$non_cache_group = sanitize_text_field($_POST['non_cache_group']);
			$non_cache_group = explode(' ', $non_cache_group);
			
			foreach($non_cache_group as $key => $group){
				$non_cache_group[$key] = trim($group);
			}
		}

		$speedycache->object['non_cache_group'] = !empty($non_cache_group) ? $non_cache_group : [];
	}
	
	update_option('speedycache_object_cache', $speedycache->object);

	if(!file_put_contents(\SpeedyCache\ObjectCache::$conf_file, json_encode($speedycache->object))){
		speedycache_notify(array(__('Unable to modify Object Cache Conf file, the issue might be related to permission on your server.', 'speedycache'), 'error'));
		return;
	}
	
	if(!empty($speedycache->object['enable'])){
		\SpeedyCache\ObjectCache::update_file();
	} else {
		unlink(WP_CONTENT_DIR . '/object-cache.php');
	}
	
	try{
		\SpeedyCache\ObjectCache::boot();
	} catch(Exception $e) {
		speedycache_notify(array($e->getMessage(), 'error'));
		return;
	}
	\SpeedyCache\ObjectCache::flush_db();
	\SpeedyCache\ObjectCache::$instance = null;
	speedycache_notify(array(__('Object Cache Settings have been saved', 'speedycache'), 'success'));
}

// Save Settings of Bloat
function speedycache_save_bloat(){
	global $speedycache;

	// Disable XML-RPC
	if(isset($_POST['submit']) || isset($_POST['speedycache_disable_xmlrpc'])){
		$speedycache->bloat['disable_xmlrpc'] = empty($_POST['speedycache_disable_xmlrpc']) ? 0 : 1;
	}
	
	// Revove Google Fonts and use user's system fonts
	if(isset($_POST['submit']) || isset($_POST['speedycache_remove_gfonts'])){
		$speedycache->bloat['remove_gfonts'] = empty($_POST['speedycache_remove_gfonts']) ? 0 : 1;
	}
	
	// Disable Jquery Migrate
	if(isset($_POST['submit']) || isset($_POST['speedycache_disable_jmigrate'])){
		$speedycache->bloat['disable_jmigrate'] = empty($_POST['speedycache_disable_jmigrate']) ? 0 : 1;
	}
	
	// Disable Dashicons
	if(isset($_POST['submit']) || isset($_POST['speedycache_disable_dashicons'])){
		$speedycache->bloat['disable_dashicons'] = empty($_POST['speedycache_disable_dashicons']) ? 0 : 1;
	}
	
	// Disable Block CSS
	if(isset($_POST['submit']) || isset($_POST['speedycache_disable_block_css'])){
		$speedycache->bloat['disable_block_css'] = empty($_POST['speedycache_disable_block_css']) ? 0 : 1;
	}
	
	// Disable Gutenberg
	if(isset($_POST['submit']) || isset($_POST['speedycache_disable_gutenberg'])){
		$speedycache->bloat['disable_gutenberg'] = empty($_POST['speedycache_disable_gutenberg']) ? 0 : 1;
	}

	// Disable oEmbeds
	if(isset($_POST['submit']) || isset($_POST['speedycache_disable_oembeds'])){
		$speedycache->bloat['disable_oembeds'] = empty($_POST['speedycache_disable_oembeds']) ? 0 : 1;
	}
	
	// Disable RSS
	if(isset($_POST['submit']) || isset($_POST['speedycache_disable_rss'])){
		$speedycache->bloat['disable_rss'] = empty($_POST['speedycache_disable_rss']) ? 0 : 1;
	}
	
	// Update HeartBeat
	if(isset($_POST['submit']) || isset($_POST['speedycache_update_heartbeat'])){
		$speedycache->bloat['update_heartbeat'] = empty($_POST['speedycache_update_heartbeat']) ? 0 : 1;
	}
	
	// Heartbeat settings for Frontend
	if(isset($_POST['submit']) || isset($_POST['speedycache_heartbeat_frequency'])){
		$speedycache->bloat['heartbeat_frequency'] = empty($_POST['speedycache_heartbeat_frequency']) ? '' : sanitize_text_field($_POST['speedycache_heartbeat_frequency']);
	}
	
	// Heartbeat settings for Backend
	if(isset($_POST['submit']) || isset($_POST['speedycache_disable_heartbeat'])){
		$speedycache->bloat['disable_heartbeat'] = empty($_POST['speedycache_disable_heartbeat']) ? '' : sanitize_text_field($_POST['speedycache_disable_heartbeat']);
	}
	
	// Limit Post Revesions
	if(isset($_POST['submit']) || isset($_POST['speedycache_limit_post_revision'])){
		$speedycache->bloat['limit_post_revision'] = empty($_POST['speedycache_limit_post_revision']) ? 0 : 1;
	}
	
	// Disable WooCommerce Assets.
	if(isset($_POST['submit']) || isset($_POST['speedycache_disable_woo_assets'])){
		$speedycache->bloat['disable_woo_assets'] = empty($_POST['speedycache_disable_woo_assets']) ? 0 : 1;
	}

	// Disable Cart Fragment.
	if(isset($_POST['submit']) || isset($_POST['speedycache_disable_cart_fragment'])){
		$speedycache->bloat['disable_cart_fragment'] = empty($_POST['speedycache_disable_cart_fragment']) ? 0 : 1;
	}
	
	// Set Post revesion count
	if(isset($_POST['submit']) || isset($_POST['speedycache_post_revision_count'])){
		$speedycache->bloat['post_revision_count'] = empty($_POST['speedycache_post_revision_count']) ? '' : sanitize_text_field($_POST['speedycache_post_revision_count']);
	}
	
	update_option('speedycache_bloat', $speedycache->bloat);
	
	speedycache_notify(array(__('Bloat settings have been saved successfully.', 'speedycache'), 'success'));
	
}

function speedycache_settings_page(){
	global $speedycache;

	speedycache_options_page_request();

	$cloudflare_integration_exist = false;

	if(!empty(speedycache_optserver('HTTP_CDN_LOOP')) && speedycache_optserver('HTTP_CDN_LOOP') == 'cloudflare'){
		$cloudflare_integration_exist = true;
		$cdn_values = get_option('speedycache_cdn');

		if($cdn_values){
			foreach($cdn_values as $key => $value){
				if($value['id'] == 'cloudflare'){
					$cloudflare_integration_exist = false;
					break;
				}
			}
		}
	}
	
	?>

	<div class="speedycache-wrap">
		<?php 

		settings_errors('speedycache-notice'); ?>

		<div class="speedycache-setting-content">
		<div class="speedycache-tab-group" style="width:<?php echo (defined('SITEPAD') || wp_is_mobile()) ? '100%' : '83%'?>">
			<?php
			$tabs = array(
				array('id' => 'speedycache-options', 'title' => esc_html('Settings', 'speedycache')),
				array('id' => 'speedycache-manage-cache', 'title' => esc_html('Manage Cache', 'speedycache')),
				array('id' => 'speedycache-image-optimisation', 'title' => esc_html('Image Optimization', 'speedycache' )),
				array('id' => 'speedycache-cdn', 'title' => 'CDN'),
				array('id' => 'speedycache-exclude', 'title' => esc_html('Exclude', 'speedycache')),
				array('id' => 'speedycache-object', 'title' => 'Object Cache'),
				array('id' => 'speedycache-bloat', 'title' => 'Bloat Remover'),
			);

			if(!defined('SITEPAD')){
				array_push($tabs, array('id' => 'speedycache-db', 'title' => 'DB'));
				array_push($tabs, array('id' => 'speedycache-support', 'title' => esc_html('Support', 'speedycache')));
			}
			
			$page_now = speedycache_optget('page');
			
			foreach($tabs as $key => $value){
				if($value['id'] == 'speedycache-image-optimisation' && !defined('SPEEDYCACHE_PRO')){
					continue;
				}
				
				if(defined('SPEEDYCACHE_PRO') && in_array($value['id'], $speedycache->settings['disabled_tabs'])){
					continue;
				}

				$checked = '';
				
				if($value['id'] == $page_now){
					$checked = 'checked';
				} else if($value['id'] === 'speedycache-options' && $page_now === 'speedycache'){
					$checked = 'checked';
				}
			
				//tab of "delete css and js" has been removed so there is need to check it
				if(!empty($_POST['speedycache_page']) && $_POST['speedycache_page'] == 'speedycache_delete_css_and_js_cache'){
					$speedycache_page = 'delete_cache';
				}

				echo '<input type="radio" id="' . esc_attr($value['id']) . '" name="speedycache_tabgroup" style="display:none;" '.esc_attr($checked).'>' . "\n";
				//echo '<label for="' . esc_attr($value['id']) . '">' . esc_attr($value['title']) . '</label>' . "\n";
			}
			
			echo '<div class="speedycache-tab-wrap">';
			foreach($tabs as $key => $value){
				if($value['id'] == 'speedycache-image-optimisation' && !defined('SPEEDYCACHE_PRO')){
					continue;
				}
				
				if(defined('SPEEDYCACHE_PRO') && in_array($value['id'], $speedycache->settings['disabled_tabs'])){
					continue;
				}

				echo '<label for="' . esc_attr($value['id']) . '">' . esc_attr($value['title']) . '</label>' . "\n";
			}
			
			echo '</div>';
			?>

			<div class="speedycache-tab-settings">
				<form method="post">
					<?php wp_nonce_field('speedycache_nonce', 'security');  ?>

					<input type="hidden" value="options" name="speedycache_page">
					<div class="speedycache-block">
						<div class="speedycache-block-title">
							<h2><?php esc_html_e('Caching', 'speedycache'); ?></h2>
						</div>
						<div class="speedycache-option-group">
							<div class="speedycache-option-wrap">
								<label for="speedycache_status" class="speedycache-custom-checkbox">
									<input type="checkbox" id="speedycache_status" name="speedycache_status" <?php echo (!empty($speedycache->options['status']) ? ' checked' : ''); ?>/>
									<div class="speedycache-input-slider"></div>
								</label>
								<div class="speedycache-option-info">
									<span class="speedycache-option-name"><?php esc_html_e('Enable Cache', 'speedycache'); ?> <span class="speedycache-modal-settings-link" setting-id="speedycache_status" style="display:<?php echo (!empty($speedycache->options['status']) ? 'inline-block' : 'none');?>;">- <?php esc_html_e('Settings', 'speedycache'); ?></span></span>
									<span class="speedycache-option-desc"><?php esc_html_e('Enables caching', 'speedycache'); ?> | <a href="<?php echo admin_url('admin.php?page=speedycache-test'); ?>" style="color:#3d5afe">Test Before Enabling</a></span> 
								</div>
							</div>
							
							<?php if(empty($speedycache->options['status'])){ ?>
							<!-- Test Mode option -->
							<div class="speedycache-option-wrap">
								<label for="speedycache_test_mode" class="speedycache-custom-checkbox">
									<input type="checkbox" id="speedycache_test_mode" name="speedycache_test_mode" <?php echo !empty(get_transient('speedycache_test_mode', false)) ? 'checked' : ''; ?>/>
									<div class="speedycache-input-slider"></div>
								</label>
								<div class="speedycache-option-info">
									<span class="speedycache-option-name"><span style="color:var(--speedycache-red);"><?php esc_html_e('Test Mode', 'speedycache'); ?></span><a href="https://speedycache.com/docs/miscellaneous/how-to-use-test-mode-in-speedycache/" target="_blank"><span class="dashicons dashicons-info" style="font-size:14px"></span></a></span>
									<span class="speedycache-option-desc"><?php esc_html_e('Test before using it on Live site, add ?test_speedycache=1 after every URL to test.', 'speedycache'); ?></span>
								</div>
							</div>
							<?php } ?>
							<div class="speedycache-modal" modal-id="speedycache_status">
								<div class="speedycache-modal-wrap">
									<div class="speedycache-modal-header">
										<div><?php esc_html_e('SpeedyCache Cache', 'speedycache'); ?></div>
										<div title="Close Modal" class="speedycache-close-modal">
											<span class="dashicons dashicons-no"></span>
										</div>
									</div>
									<div class="speedycache-modal-content speedycache-info-modal">
										<h3><?php esc_html_e('Select Post Types', 'speedycache'); ?></h3>
										<p><?php esc_html_e('Only Selected Post types will be cached', 'speedycache'); ?></p>
										<?php 
											
										foreach(get_post_types(array('public' => true)) as $key => $type){
											$cache_post = $speedycache->options['post_types'];
											
											if(is_string($speedycache->options['post_types']) && strpos($speedycache->options['post_types'], ',') > -1){
												$cache_post = explode(',', $speedycache->options['post_types']);
											}

											$checked = '';
											if(is_array($cache_post) && in_array($type, $cache_post)){
												$checked = 'checked';
											}

											$type = ucfirst($type); 
										?>
										<div class="speedycache-auto-cache-input-wrap">
											<label for="speedycache_automatic_cache_<?php echo esc_attr($key);?>" class="speedycache-custom-checkbox">
												<input type="checkbox" id="speedycache_automatic_cache_<?php echo esc_attr($key);?>" name="speedycache_post_types[]" value="<?php echo esc_attr($key);?>" <?php echo esc_html($checked); ?>/>
												<div class="speedycache-input-slider"></div>
											</label>
											<div class="speedycache-option-info">
												<span class="speedycache-option-name"><?php esc_html_e($type, 'speedycache'); ?></span>
											</div>
										</div>
										<?php } ?>
									</div>
									<div class="speedycache-modal-footer">
										<button type="button" action="close">
											<span><?php esc_html_e('Submit', 'speedycache'); ?></span>
										</button>
									</div>
								</div>
							</div>
							
							<div class="speedycache-option-wrap">
								<label for="speedycache_automatic_cache" class="speedycache-custom-checkbox">
									<input type="checkbox" id="speedycache_automatic_cache" name="speedycache_automatic_cache" <?php echo (!empty($speedycache->options['automatic_cache']) ? ' checked' : ''); ?>/>
									<div class="speedycache-input-slider"></div>
								</label>
							
								<div class="speedycache-option-info">
									<span class="speedycache-option-name"><?php esc_html_e('Automatic Cache', 'speedycache'); ?></span>
									<span class="speedycache-option-desc"><?php esc_html_e('Create cache automatically after contents are saved.', 'speedycache'); ?></span>
								</div>
							</div>

							<!--Preload Starts here-->
							<div class="speedycache-option-wrap">
								<label for="speedycache_preload" class="speedycache-custom-checkbox">
									<input type="checkbox" <?php echo (!empty($speedycache->options['preload']) ? ' checked' : ''); ?> id="speedycache_preload" name="speedycache_preload"/>
									<div class="speedycache-input-slider"></div>
								</label>
								<div class="speedycache-option-info">
									<span class="speedycache-option-name"><?php esc_html_e('Preload', 'speedycache'); ?>
									<span class="speedycache-modal-settings-link" setting-id="speedycache_preload" style="display:<?php echo (!empty($speedycache->options['preload']) ? 'inline-block' : 'none');?>;">- Settings</span>
									</span>
									<span class="speedycache-option-desc"><?php esc_html_e('Create the cache of all the site automatically', 'speedycache'); ?></span>
								</div>
							</div>
							
							<!--Preload Modal-->
							<div modal-id="speedycache_preload" class="speedycache-modal">
								<div class="speedycache-modal-wrap">
										<div class="speedycache-modal-header">
											<div><?php esc_html_e('Preload', 'speedycache'); ?></div>
											<div title="Close Modal" class="speedycache-close-modal">
												<span class="dashicons dashicons-no"></span>
											</div>
										</div>
									<div class="speedycache-modal-content">
										<div class="speedycache-sortable">
											<?php 
												$preload_types = array(
													'homepage' => __('Homepage', 'speedycache'),
													'post' => __('Posts', 'speedycache'),
													'category' => __('Categories', 'speedycache'),
													'page' => __('Pages', 'speedycache'),
													'tag' => __('Tags', 'speedycache'),
													'attachment' => __('Attachments', 'speedycache'),
													'custom_post_types' => __('Custom Post Types', 'speedycache'),
													'custom_taxonomies' => __('Custom Taxonomies', 'speedycache')
												);

												if(!empty($speedycache->options['preload_order'])){
													$preload_order = explode(',', $speedycache->options['preload_order']);
													
													if(count($preload_order) == count($preload_types)){
														$preload_types = array_merge(array_flip($preload_order), $preload_types);
													}
												} 
												
												foreach($preload_types as $ptype => $ptype_lang){
													echo '<div class="speedycache-form-input" data-type="'.esc_attr($ptype).'">
													<div class="speedycache-preload-input-wrap">
														<label for="speedycache_preload_'.esc_attr($ptype).'" class="speedycache-custom-checkbox">
															<input type="checkbox"'.(!empty($speedycache->options['preload_'.$ptype]) ? ' checked' : '') .' id="speedycache_preload_'.esc_attr($ptype).'" name="speedycache_preload_'.esc_attr($ptype).'"/>
															<div class="speedycache-input-slider"></div>
														</label>
														<div class="speedycache-option-info">
															<span class="speedycache-option-name">'.esc_html($ptype_lang).'</span>
														</div>
													</div>
													<span class="dashicons dashicons-menu"></span>
												</div>';
												}
										
											?>
										</div>
								
										<div class="speedycache-form-input">
											<label for="speedycache_preload_number">
												<input type="number" class="speedycache-form-spinner-input" name="speedycache_preload_number" min="0" value="<?php echo (!empty($speedycache->options['preload_number']) ? esc_attr($speedycache->options['preload_number']) : 4); ?>" />
												<?php esc_html_e('pages per minute', 'speedycache'); ?>
											</label>
										</div>

										<div class="speedycache-form-input">
											<div class="speedycache-preload-input-wrap">
												<label for="speedycache_preload_restart" class="speedycache-custom-checkbox">
													<input type="checkbox" id="speedycache_preload_restart" name="speedycache_preload_restart" <?php echo !empty($speedycache->options['preload_restart']) ? ' checked' : ''; ?> />
													<div class="speedycache-input-slider"></div>
												</label>
												<div class="speedycache-option-info">
													<span class="speedycache-option-name">
														<?php esc_html_e('Restart After Completed', 'speedycache'); ?><a style="margin-left:5px;" target="_blank" href="https://speedycache.com/docs/caching/how-to-precache/"><span class="dashicons dashicons-info"></span></a>
													</span>
												</div>
											</div>
										</div>

										<input type="hidden" value="<?php echo isset($speedycache->options['preload_order']) ? esc_attr($speedycache->options['preload_order']) : ''; ?>" id="speedycache_preload_order" name="speedycache_preload_order">

									</div>
									<div class="speedycache-modal-footer">
										<button type="button" action="close">
											<span><?php esc_html_e('Submit', 'speedycache'); ?></span>
										</button>
									</div>
								</div>
							</div>
							<!--Preload Modal ends here-->
							
							<div class="speedycache-option-wrap">
								<label for="speedycache_lbc" class="speedycache-custom-checkbox">
									<input type="checkbox" id="speedycache_lbc" name="speedycache_lbc" <?php echo (!empty($speedycache->options['lbc']) ? ' checked' : ''); ?>/>
									<div class="speedycache-input-slider"></div>
								</label>
								<div class="speedycache-option-info">
									<span class="speedycache-option-name"><?php esc_html_e('Browser Caching', 'speedycache'); ?></span>
									<span class="speedycache-option-desc"><?php esc_html_e('Reduce page load times for repeat visitors', 'speedycache'); ?></span>
								</div>
							</div>

							<div class="speedycache-option-wrap">
								<label for="speedycache_logged_in_user" class="speedycache-custom-checkbox">
									<input type="checkbox" <?php echo (!empty($speedycache->options['logged_in_user']) ? ' checked' : ''); ?> id="speedycache_logged_in_user" name="speedycache_logged_in_user"/>
									<div class="speedycache-input-slider"></div>
								</label>
								<div class="speedycache-option-info">
									<span class="speedycache-option-name"><?php esc_html_e('Logged-in Users', 'speedycache'); ?></span>
									<span class="speedycache-option-desc"><?php esc_html_e('Don\'t show the cached version for logged-in users', 'speedycache'); ?></span>
								</div>
							</div>

							<div class="speedycache-option-wrap">
								<label for="speedycache_mobile" class="speedycache-custom-checkbox">
									<input type="checkbox" <?php echo !empty($speedycache->options['mobile']) ? ' checked' : ''; ?> id="speedycache_mobile" name="speedycache_mobile"/>
									<div class="speedycache-input-slider"></div>
								</label>
								<div class="speedycache-option-info">
									<span class="speedycache-option-name"><?php esc_html_e('Mobile', 'speedycache'); ?></span>
									<span class="speedycache-option-desc"><?php esc_html_e('Don\'t show the cached version for desktop to mobile devices', 'speedycache'); ?></span>
								</div>
							</div>

							<?php if(defined('SPEEDYCACHE_PRO')){ ?>
								<div class="speedycache-option-wrap">
									<label for="speedycache_mobile_theme" class="speedycache-custom-checkbox">
										<input type="checkbox" <?php echo (!empty($speedycache->options['mobile_theme']) ? ' checked' : ''); ?> id="speedycache_mobile_theme" name="speedycache_mobile_theme"/>
										<div class="speedycache-input-slider"></div>
									</label>
									<div class="speedycache-option-info">
										<span class="speedycache-option-name"><?php esc_html_e('Mobile Theme', 'speedycache'); ?></span>
										<span class="speedycache-option-desc"><?php esc_html_e('Create cache for mobile theme', 'speedycache'); ?></span>
									</div>
								</div>

								<?php
							} else { ?>
								<div class="speedycache-option-wrap speedycache-disabled">
									<label for="speedycache_mobile_theme" class="speedycache-custom-checkbox">
										<input type="checkbox" id="speedycache_mobile_theme" disabled/>
										<div class="speedycache-input-slider"></div>
									</label>
									<div class="speedycache-option-info">
										<span class="speedycache-option-name"><?php esc_html_e('Mobile Theme', 'speedycache'); ?></span>
										<span class="speedycache-option-desc"><?php esc_html_e('Create cache for mobile theme', 'speedycache'); ?></span>
									</div>
									<div class="speedycache-premium-tag">
										<i class="fas fa-crown"></i> <?php esc_html_e('Premium', 'speedycache'); ?>
									</div>
								</div>
							<?php } ?>
							
							<!-- SpeedyCache New Post Starts Here-->
							<div class="speedycache-option-wrap">
								<label for="speedycache_new_post" class="speedycache-custom-checkbox">
									<input type="checkbox" <?php echo (!empty($speedycache->options['new_post']) ? ' checked' : ''); ?> id="speedycache_new_post" name="speedycache_new_post"/>
									<div class="speedycache-input-slider"></div>
								</label>
								<div class="speedycache-option-info">
									<span class="speedycache-option-name"><?php esc_html_e('New Post', 'speedycache'); ?>
									<span class="speedycache-modal-settings-link" setting-id="speedycache_new_post" style="display:<?php echo (!empty($speedycache->options['new_post']) ? 'inline-block' : 'none');?>;">- Settings</span>
									</span>
									<span class="speedycache-option-desc"><?php esc_html_e('Clear cache files when a post or page is published', 'speedycache'); ?></span>
								</div>
							</div>
							
							<!--SpeedyCache NewPost Modal-->
							<div modal-id="speedycache_new_post" class="speedycache-modal">
								<div class="speedycache-modal-wrap">
									<div class="speedycache-modal-header">
										<div><?php esc_html_e('New Post', 'speedycache'); ?></div>
										<div title="Close Modal" class="speedycache-close-modal">
											<span class="dashicons dashicons-no"></span>
										</div>
									</div>
									<div class="speedycache-modal-content">
										<p style="color:#666;margin-top:0 !important;"><?php esc_html_e('What do you want to happen after publishing the new post?', 'speedycache'); ?></p>
										
										<div class="speedycache-form-input">
											<label style="margin-right: 5px;" for="speedycache_new_post_type_all">
												<input type="radio" action-id="speedycache_new_post_type_all" id="speedycache_new_post_type_all" name="speedycache_new_post_type" value="all" <?php echo isset($speedycache->options['new_post_type']) && ($speedycache->options['new_post_type'] == 'all') ? ' checked' : ''; ?>/>
												<?php esc_html_e('Clear All Cache', 'speedycache'); ?>
											</label>
										</div>
										
										<div class="speedycache-form-input">
											<label style="margin-right: 5px;" for="speedycache_new_post_type_homepage">
												<input type="radio" action-id="speedycache_new_post_type_homepage" id="speedycache_new_post_type_homepage" name="speedycache_new_post_type" value="homepage" <?php echo isset($speedycache->options['new_post_type']) && ($speedycache->options['new_post_type'] == 'homepage') ? ' checked' : ''; ?>/>
											<?php esc_html_e('Clear Cache of Homepage', 'speedycache'); ?>, 
											<?php esc_html_e('Post Categories', 'speedycache'); ?>, 
											<?php esc_html_e('Post Tags', 'speedycache'); ?>, 
											<?php esc_html_e('Pagination', 'speedycache'); ?> 
											</label>
										</div>
									</div>
									<div class="speedycache-modal-footer">
										<button class="" type="button" action="close">
											<span><?php esc_html_e('Submit', 'speedycache'); ?></span>
										</button>
									</div>
								</div>
							</div>
							
							<!--SpeedyCache New Post Modal Ends Here-->
							<!--SpeedyCache New Post Ends here-->

							<!--SpeedyCache Update Post Starts here-->
							<div class="speedycache-option-wrap">
								<label for="speedycache_update_post" class="speedycache-custom-checkbox">
									<input type="checkbox" <?php echo (!empty($speedycache->options['update_post']) ? ' checked' : ''); ?> id="speedycache_update_post" name="speedycache_update_post" />
									<div class="speedycache-input-slider"></div>
								</label>
								<div class="speedycache-option-info">
									<span class="speedycache-option-name"><?php esc_html_e('Update Post', 'speedycache'); ?>
									<span class="speedycache-modal-settings-link" setting-id="speedycache_update_post" style="display:<?php echo (!empty($speedycache->options['update_post']) ? 'inline-block' : 'none');?>;">- Settings</span>
									</span>
									<span class="speedycache-option-desc"><?php esc_html_e('Clear cache files when a post or page is updated', 'speedycache'); ?></span>
								</div>
							</div>
							
							<!--SpeedyCache Update Post Modal Starts Here-->
							<div modal-id="speedycache_update_post" class="speedycache-modal">
								<div class="speedycache-modal-wrap">
									<div class="speedycache-modal-header">
										<div><?php esc_html_e('Update Post', 'speedycache'); ?></div>
										<div title="Close Modal" class="speedycache-close-modal">
											<span class="dashicons dashicons-no"></span>
										</div>
									</div>
									<div class="speedycache-modal-content">
											<p style="color:#666;margin-top:0 !important;"><?php esc_html_e('What do you want to happen after update a post or a page?', 'speedycache'); ?></p>

											<div class="speedycache-form-input">
												<label>
													<input type="radio" action-id="speedycache_update_post_type_all" id="speedycache_update_post_type_all" name="speedycache_update_post_type" value="all" <?php echo isset($speedycache->options['update_post_type']) && ($speedycache->options['update_post_type'] == 'all') ? ' checked' : ''; ?>/>
													<?php esc_html_e('Clear All Cache', 'speedycache'); ?>
												</label>
											</div>
											
											<div class="speedycache-form-input">
												<label>
													<input type="radio" action-id="speedycache_update_post_type_post" id="speedycache_update_post_type_post" name="speedycache_update_post_type" value="post" <?php echo isset($speedycache->options['update_post_type']) && ($speedycache->options['update_post_type'] == 'post') ? ' checked' : ''; ?>/>
													<?php esc_html_e('Clear Cache of Post / Page', 'speedycache'); ?>, 
													<?php esc_html_e('Post Categories', 'speedycache'); ?>, 
													<?php esc_html_e('Post Tags', 'speedycache'); ?>, 
													<?php esc_html_e('Homepage', 'speedycache'); ?>
												</label>
											</div>
									</div>
									<div class="speedycache-modal-footer">
										<button type="button" action="close">
											<span><?php esc_html_e('Submit', 'speedycache'); ?></span>
										</button>
									</div>
								</div>
							</div>
							<!--SpeedyCache Update Post Modal Starts Here-->
							<!--SpeedyCache Update Post Ends here-->
							
							<div class="speedycache-option-wrap">
								<label for="speedycache_purge_varnish" class="speedycache-custom-checkbox">
									<input type="checkbox" <?php echo (!empty($speedycache->options['purge_varnish']) ? ' checked' : ''); ?> id="speedycache_purge_varnish" name="speedycache_purge_varnish" />
									<div class="speedycache-input-slider"></div>
								</label>
								<div class="speedycache-option-info">
									<span class="speedycache-option-name"><?php esc_html_e('Purge Varnish', 'speedycache'); ?>
									<span class="speedycache-modal-settings-link" setting-id="speedycache_purge_varnish" style="display:<?php echo (!empty($speedycache->options['purge_varnish']) ? 'inline-block' : 'none');?>;">- Settings</span>
									</span>
									<span class="speedycache-option-desc"><?php esc_html_e('Deletes cache created by Varnish on Deletion of cache from SpeedyCache', 'speedycache'); ?></span>
								</div>
							</div>
							
							<!--SpeedyCache Update Post Modal Starts Here-->
							<div modal-id="speedycache_purge_varnish" class="speedycache-modal">
								<div class="speedycache-modal-wrap">
									<div class="speedycache-modal-header">
										<div><?php esc_html_e('Varnish Settings', 'speedycache'); ?></div>
										<div title="Close Modal" class="speedycache-close-modal">
											<span class="dashicons dashicons-no"></span>
										</div>
									</div>
									<div class="speedycache-modal-content">
										<p style="color:#666;margin-top:0 !important;"><?php esc_html_e('If you use any different IP for Varnish than the default then set it here.', 'speedycache'); ?></p>

										<div class="speedycache-form-input">
											<label style="width:100%;">
												<span style="font-weight:500; margin-bottom:5px"><?php esc_html_e('Set your Varnish IP', 'speedycache'); ?></span>
												<input type="text" name="speedycache_varniship" style="width:100%;" value="<?php echo !empty($speedycache->options['varniship']) ? esc_attr($speedycache->options['varniship']) : '127.0.0.1';?>"/><br/>
												
											</label>
										</div>
									</div>
									<div class="speedycache-modal-footer">
										<button type="button" action="close">
											<span><?php esc_html_e('Submit', 'speedycache'); ?></span>
										</button>
									</div>
								</div>
							</div>
						</div>
					</div>
					
					<div class="speedycache-block">
						<div class="speedycache-block-title">
							<h2><?php esc_html_e('File Optimization', 'speedycache'); ?></h2>
						</div>
						
						<div class="speedycache-option-group">

						<?php if(defined('SPEEDYCACHE_PRO')){ ?>
							<div class="speedycache-option-wrap">
								<label for="speedycache_minify_html" class="speedycache-custom-checkbox">
									<input type="checkbox" id="speedycache_minify_html" name="speedycache_minify_html" <?php echo !empty($speedycache->options['minify_html']) ? ' checked' : ''; ?>/>
									<div class="speedycache-input-slider"></div>
								</label>
								<div class="speedycache-option-info">
									<span class="speedycache-option-name"><?php esc_html_e('Minify HTML', 'speedycache'); ?></span>
									<span class="speedycache-option-desc"><?php esc_html_e('Minifies HTML', 'speedycache'); ?></span>
								</div>
							</div>
						<?php } else { ?>
							<div class="speedycache-option-wrap speedycache-disabled">
								<label for="speedycache_minify_html" class="speedycache-custom-checkbox">
									<input type="checkbox" id="speedycache_minify_html" disabled/>
									<div class="speedycache-input-slider"></div>
								</label>
								<div class="speedycache-option-info">
									<span class="speedycache-option-name"><?php esc_html_e('Minify HTML', 'speedycache'); ?></span>
									<span class="speedycache-option-desc"><?php esc_html_e('Minifies HTML', 'speedycache'); ?></span>
								</div>
								<div class="speedycache-premium-tag">
									<i class="fas fa-crown"></i> <?php esc_html_e('Premium', 'speedycache'); ?>
								</div>
							</div>
						<?php } ?>

						<div class="speedycache-option-wrap">
							<label for="speedycache_minify_css" class="speedycache-custom-checkbox">
								<input type="checkbox" id="speedycache_minify_css" name="speedycache_minify_css" <?php echo !empty($speedycache->options['minify_css']) ? ' checked' : ''; ?>/>
								<div class="speedycache-input-slider"></div>
							</label>
							<div class="speedycache-option-info">
								<span class="speedycache-option-name"><?php esc_html_e('Minify CSS', 'speedycache'); ?></span>
								<span class="speedycache-option-desc"><?php esc_html_e('You can decrease the size of CSS files', 'speedycache'); ?></span>
							</div>
						</div>

						<?php if(defined('SPEEDYCACHE_PRO')){ ?>
							<div class="speedycache-option-wrap">
								<label for="speedycache_minify_css_enhanced" class="speedycache-custom-checkbox">
									<input type="checkbox" id="speedycache_minify_css_enhanced" name="speedycache_minify_css_enhanced" <?php echo !empty($speedycache->options['minify_css_enhanced']) ? ' checked' : ''; ?>/>
									<div class="speedycache-input-slider"></div>
								</label>
								<div class="speedycache-option-info">
									<span class="speedycache-option-name"><?php esc_html_e('Advanced Minify CSS', 'speedycache'); ?></span>
									<span class="speedycache-option-desc"><?php esc_html_e('Better minification of CSS', 'speedycache'); ?></span>
								</div>
							</div>
						<?php } else { ?>
							<div class="speedycache-option-wrap speedycache-disabled">
								<label for="speedycache_minify_css_enhanced" class="speedycache-custom-checkbox">
									<input type="checkbox" id="speedycache_minify_css_enhanced" disabled/>
									<div class="speedycache-input-slider"></div>
								</label>
								<div class="speedycache-option-info">
									<span class="speedycache-option-name"><?php esc_html_e('Advanced Minfiy CSS', 'speedycache'); ?></span>
									<span class="speedycache-option-desc"><?php esc_html_e('Better minification of CSS', 'speedycache'); ?></span>
								</div>
								<div class="speedycache-premium-tag">
									<i class="fas fa-crown"></i> <?php esc_html_e('Premium', 'speedycache'); ?>
								</div>
							</div>
						<?php } ?>

						<div class="speedycache-option-wrap">
							<label for="speedycache_combine_css" class="speedycache-custom-checkbox">
								<input type="checkbox" id="speedycache_combine_css" name="speedycache_combine_css" <?php echo (!empty($speedycache->options['combine_css']) ? ' checked' : ''); ?>/>
								<div class="speedycache-input-slider"></div>
							</label>
							<div class="speedycache-option-info">
								<span class="speedycache-option-name"><?php esc_html_e('Combine CSS', 'speedycache'); ?></span>
								<span class="speedycache-option-desc"><?php esc_html_e('Reduce HTTP requests through combined CSS files', 'speedycache'); ?></span>
							</div>
						</div>

						<?php if(defined('SPEEDYCACHE_PRO')){ ?>
							<div class="speedycache-option-wrap">
								<label for="speedycache_minify_js" class="speedycache-custom-checkbox">
									<input type="checkbox" id="speedycache_minify_js" name="speedycache_minify_js" <?php echo (!empty($speedycache->options['minify_js']) ? ' checked' : ''); ?>/>
									<div class="speedycache-input-slider"></div>
								</label>
								<div class="speedycache-option-info">
									<span class="speedycache-option-name"><?php esc_html_e('Minify JS', 'speedycache'); ?></span>
									<span class="speedycache-option-desc"><?php esc_html_e('You can decrease the size of JS files', 'speedycache'); ?></span>
								</div>
							</div>
						<?php } else { ?>
							<div class="speedycache-option-wrap speedycache-disabled">
								<div class="speedycache-form-input">
									<label for="speedycache_minify_js" class="speedycache-custom-checkbox">
										<input type="checkbox" id="speedycache_minify_js" disabled/>
										<div class="speedycache-input-slider"></div>
									</label>
								</div>
								<div class="speedycache-option-info">
									<span class="speedycache-option-name"><?php esc_html_e('Minify JS', 'speedycache'); ?></span>
									<span class="speedycache-option-desc"><?php esc_html_e('You can decrease the size of JS files', 'speedycache'); ?></span>
								</div>
								<div class="speedycache-premium-tag">
									<i class="fas fa-crown"></i> <?php esc_html_e('Premium', 'speedycache'); ?>
								</div>
							</div>
						<?php } ?>

						<div class="speedycache-option-wrap">
							<label for="speedycache_combine_js" class="speedycache-custom-checkbox">
								<input type="checkbox" id="speedycache_combine_js" name="speedycache_combine_js" <?php echo (!empty($speedycache->options['combine_js']) ? ' checked' : ''); ?>/>
								<div class="speedycache-input-slider"></div>
							</label>
							
							<div class="speedycache-option-info">
								<span class="speedycache-option-name"><?php esc_html_e('Combine JS', 'speedycache'); ?></span>
								<span class="speedycache-option-desc"><?php esc_html_e('Reduce HTTP requests by Combining JS files in header', 'speedycache'); ?></span>
							</div>
						</div>

						<?php if(defined('SPEEDYCACHE_PRO')){ ?>
							<div class="speedycache-option-wrap">								
								<label for="speedycache_combine_js_enhanced" class="speedycache-custom-checkbox">
									<input type="checkbox" id="speedycache_combine_js_enhanced" name="speedycache_combine_js_enhanced" <?php echo (!empty($speedycache->options['combine_js_enhanced']) ? ' checked' : ''); ?>/>
									<div class="speedycache-input-slider"></div>
								</label>
								<div class="speedycache-option-info">
									<span class="speedycache-option-name"><?php esc_html_e('Advanced Combine JS', 'speedycache'); ?></span>
									<span class="speedycache-option-desc"><?php esc_html_e('Reduce HTTP requests by combining JS files in footer', 'speedycache'); ?></span>
								</div>
							</div>
						<?php 
						} else { ?>
							<div class="speedycache-option-wrap speedycache-disabled">
								<label for="speedycache_combine_js_enhanced" class="speedycache-custom-checkbox">
									<input type="checkbox" id="speedycache_combine_js_enhanced" disabled/>
									<div class="speedycache-input-slider"></div>
								</label>
								<div class="speedycache-option-info">
									<span class="speedycache-option-name"><?php esc_html_e('Advanced Combine JS', 'speedycache'); ?></span>
									<span class="speedycache-option-desc"><?php esc_html_e('Reduce HTTP requests by combining JS files in footer', 'speedycache'); ?></span>
								</div>
								<div class="speedycache-premium-tag">
									<i class="fas fa-crown"></i> <?php esc_html_e('Premium', 'speedycache'); ?>
								</div>
							</div>
						<?php } ?>				
						
						<?php // Critical CSS Option
						if(defined('SPEEDYCACHE_PRO') && !empty($speedycache->license) && !empty($speedycache->license['active'])){ ?>
							<div class="speedycache-option-wrap">
								<label for="speedycache_critical_css" class="speedycache-custom-checkbox" style="margin-top:0;">
									<input type="checkbox" id="speedycache_critical_css" name="speedycache_critical_css" <?php echo (!empty($speedycache->options['critical_css']) ? ' checked' : ''); ?>/>
									<div class="speedycache-input-slider"></div>
								</label>
								<div class="speedycache-option-info">
									<?php
									echo '<span class="speedycache-option-name">'.__('Critical CSS', 'speedycache');
									
									if(!empty($speedycache->options['critical_css'])){
										echo ' - 
									<span class="speedycache-action-link" action-name="speedycache_critical_css">'.__('Create Now', 'speedycache').'</span>
									&nbsp;&nbsp;|&nbsp;&nbsp;
									<span class="speedycache-modal-settings-link" setting-id="speedycache_critical_css">'.__('Logs', 'speedycache').'</span>';
									}
									echo '</span><span class="speedycache-option-desc">'.__('It extracts the necessary CSS of the viewport on load to improve load speed.', 'speedycache').'</span>';
									?>
								</div>
							</div>
							
							<?php echo \SpeedyCache\CriticalCss::status_modal(); ?>
							
						<?php 
						} else { 
						
							if(empty($speedycache->license) || empty($speedycache->license['active'])){
								$need_key = true;
							}
						
						?>
							<div class="speedycache-option-wrap speedycache-disabled">
								<label class="speedycache-custom-checkbox">
									<input type="checkbox" disabled/>
									<div class="speedycache-input-slider"></div>
								</label>
								<div class="speedycache-option-info">
									<span class="speedycache-option-name"><?php esc_html_e('Critical CSS', 'speedycache'); ?></span>
									<span class="speedycache-option-desc"><?php esc_html_e('It extracts the necessary CSS of the viewport on load to improve load speed.', 'speedycache'); ?></span>
								</div>
								<div class="speedycache-premium-tag">
									
									<i class="fas fa-crown"></i> <?php !empty($need_key) ? esc_html_e('Link your Key', 'speedycache') : esc_html_e('Premium', 'speedycache'); ?>
								</div>
							</div>
						<?php } 

						// Delay JS option
						if(defined('SPEEDYCACHE_PRO')){ ?>
							<div class="speedycache-option-wrap">
								<label for="speedycache_delay_js" class="speedycache-custom-checkbox" style="margin-top:0;">
									<input type="checkbox" id="speedycache_delay_js" name="speedycache_delay_js" <?php echo (!empty($speedycache->options['delay_js']) ? ' checked' : ''); ?>/>
									<div class="speedycache-input-slider"></div>
								</label>
								<div class="speedycache-option-info">
									<?php
									echo '<span class="speedycache-option-name"><span>'.__('Delay JS', 'speedycache').'</span><a href="https://speedycache.com/docs/file-optimization/how-to-delay-js-until-user-interaction/" target="_blank"><span class="dashicons dashicons-info" style="font-size:14px"></span></a>
									<span class="speedycache-modal-settings-link" setting-id="speedycache_delay_js" style="display:'.(!empty($speedycache->options['delay_js']) ? 'inline-block' : 'none').';">- Settings</span>
									</span><span class="speedycache-option-desc">'.__('Delays JS until user interaction(like scroll, click etc) to improve performance', 'speedycache').'</span>';
									?>
								</div>
							</div>
							
							<div modal-id="speedycache_delay_js" class="speedycache-modal">
								<div class="speedycache-modal-wrap">
									<div class="speedycache-modal-header">
										<div><?php esc_html_e('Delay JS', 'speedycache'); ?></div>
										<div title="Close Modal" class="speedycache-close-modal">
											<span class="dashicons dashicons-no"></span>
										</div>
									</div>
									<div class="speedycache-modal-content speedycache-info-modal">
										<p><?php esc_html_e('Delay All is a more aggressive option which can increase the chances of breaking the site too.', 'speedycache'); ?></p>
										<input type="radio" id="speedycache_delayjs_selected" name="speedycache_delay_js_mode" value="selected" <?php echo (empty($speedycache->options['delay_js_mode']) || (!empty($speedycache->options['delay_js_mode']) && $speedycache->options['delay_js_mode'] == 'selected')) ? 'checked' : ''; ?>/>
										<input type="radio" id="speedycache_delayjs_all" name="speedycache_delay_js_mode" value="all" <?php echo !empty($speedycache->options['delay_js_mode']) && $speedycache->options['delay_js_mode'] == 'all' ? 'checked' : ''; ?>/>
										
										<div class="speedycache-radio-input">
											<label for="speedycache_delayjs_selected"><?php _e('Delay Selected', 'speedycache'); ?></label>
											<label for="speedycache_delayjs_all"><?php _e('Delay All', 'speedycache'); ?></label>
										</div>
										<div class="speedycache-delay_js_list">
											<label for="speedycache_delay_js_excludes" style="width:100%;">
												<span style="font-weight:500; margin:20px 0 3px 0; display:block;">Scripts to exclude</span>
												<span style="display:block; font-weight:400; font-size:12px; color: #2c2a2a;">Enter Below The Scipts that you no not want to be delayed.</span>
												<textarea name="speedycache_delay_js_excludes" id="speedycache_delay_js_excludes" rows="4" placeholder="jquery.min"><?php echo !empty($speedycache->options['delay_js_excludes']) ? esc_html(implode("\n", $speedycache->options['delay_js_excludes'])) : '';?></textarea>
											</label>
											
											<label for="speedycache_delay_js_scripts" style="width:100%;">
												<span style="font-weight:500; margin:20px 0 3px 0; dispaly:block;">Scripts to Delay</span>
												<span style="display:block; font-weight:400; font-size:12px; color: #2c2a2a;">Enter the scripts that you want to be delayed like googletagmanager.com</span>
												<textarea name="speedycache_delay_js_scripts" id="speedycache_delay_js_scripts" rows="4" placeholder="googletagmanager.com"><?php echo !empty($speedycache->options['delay_js_scripts']) ? esc_html(implode("\n", $speedycache->options['delay_js_scripts'])) : '';?></textarea>
												<h5>Suggestions</h5>
												<p>
												fbevents.js<br>
												google-analytics.com<br>
												adsbygoogle.js<br>
												googletagmanager.com<br>
												</p>
											</label>
										</div>
										<div class="speedycache-modal-footer">
											<button type="button" action="close">
												<span><?php esc_html_e('Submit', 'speedycache'); ?></span>
											</button>
										</div>
									</div>
								</div>
							</div>
							<?php }else{?>
							<div class="speedycache-option-wrap speedycache-disabled">
								<label class="speedycache-custom-checkbox" style="margin-top:0;">
									<input type="checkbox" disabled/>
									<div class="speedycache-input-slider"></div>
								</label>
								<div class="speedycache-option-info">
									<?php
									echo '<span class="speedycache-option-name">'.__('Delay JS', 'speedycache').'</span>';
									echo '</span><span class="speedycache-option-desc">'.__('Delays JS until user interaction(like scroll, click etc) to improve performance', 'speedycache').'</span>';
									?>
								</div>
								<div class="speedycache-premium-tag">
									<i class="fas fa-crown"></i> <?php esc_html_e('Premium', 'speedycache'); ?>
								</div>
							</div>
							<?php }
							
							// Unused CSS
							if(defined('SPEEDYCACHE_PRO') && !empty($speedycache->license) && !empty($speedycache->license['active'])){ ?>
								<div class="speedycache-option-wrap">
									<label for="speedycache_unused_css" class="speedycache-custom-checkbox" style="margin-top:0;">
										<input type="checkbox" id="speedycache_unused_css" name="speedycache_unused_css" <?php echo (!empty($speedycache->options['unused_css']) ? ' checked' : ''); ?>/>
										<div class="speedycache-input-slider"></div>
									</label>
									<div class="speedycache-option-info">
										<?php
										echo '<span class="speedycache-option-name"><span>'.__('Unused CSS', 'speedycache').'</span><a href="https://speedycache.com/docs/file-optimization/how-to-remove-unused-css/" target="_blank"><span class="dashicons dashicons-info" style="font-size:14px"></span></a>
										<span class="speedycache-modal-settings-link" setting-id="speedycache_unused_css" style="display:'.(!empty($speedycache->options['unused_css']) ? 'inline-block' : 'none').';">- Settings</span>
										</span><span class="speedycache-option-desc">'.__('It removes the unused CSS.', 'speedycache').'</span>';
										?>
									</div>
								</div>
								
								<div modal-id="speedycache_unused_css" class="speedycache-modal">
									<div class="speedycache-modal-wrap">
										<div class="speedycache-modal-header">
											<div><?php esc_html_e('Unused CSS Settings', 'speedycache'); ?></div>
											<div title="Close Modal" class="speedycache-close-modal">
												<span class="dashicons dashicons-no"></span>
											</div>
										</div>
										<div class="speedycache-modal-content speedycache-info-modal">
											<p><?php esc_html_e('Extracts the CSS being used on the page.', 'speedycache'); ?></p>
											<div>
												<label>
													<span style="font-weight:500; margin:20px 0 3px 0; display:block;"><?php _e('Load Unused CSS', 'speedycache'); ?></span>
													<span class="speedycache-model-label-description" style="margin-bottom:5px;"><?php _e('Select the way you want the Unused CSS to load.', 'speedycache'); ?></span>
												</label>
												<input type="radio" id="speedycache_unusedcss_async" name="speedycache_unusedcss_load" value="async" <?php echo (empty($speedycache->options['unusedcss_load']) || (!empty($speedycache->options['unusedcss_load']) && $speedycache->options['unusedcss_load'] == 'async')) ? 'checked' : ''; ?>/>
												<input type="radio" id="speedycache_unusedcss_interaction" name="speedycache_unusedcss_load" value="interaction" <?php echo !empty($speedycache->options['unusedcss_load']) && $speedycache->options['unusedcss_load'] == 'interaction' ? 'checked' : ''; ?>/>
												<input type="radio" id="speedycache_unusedcss_remove" name="speedycache_unusedcss_load" value="remove" <?php echo !empty($speedycache->options['unusedcss_load']) && $speedycache->options['unusedcss_load'] == 'remove' ? 'checked' : ''; ?>/>
												<div class="speedycache-radio-input">
													<label for="speedycache_unusedcss_async"><?php esc_html_e('Asynchronously', 'speedycache'); ?></label>
													<label for="speedycache_unusedcss_interaction"><?php esc_html_e('On User Interaction', 'speedycache'); ?></label>
													<label for="speedycache_unusedcss_remove"><?php esc_html_e('Remove', 'speedycache'); ?></label>
												</div>
											</div>
											<div class="speedycache-unusedcss-excludes">
												<label for="speedycache_unused_css_exclude_stylesheets" style="width:100%;">
													<span style="font-weight:500; margin:20px 0 3px 0; display:block;"><?php esc_html_e('Exclude Stylesheets', 'speedycache'); ?></span>
													<span class="speedycache-model-label-description"><?php esc_html_e('Enter the URL, name or the stylesheet to be excluded from removing unused CSS.', 'speedycache'); ?></span>
													<textarea name="speedycache_unused_css_exclude_stylesheets" id="speedycache_unused_css_exclude_stylesheets" rows="4" placeholder="Enter URL, CSS file name one per line"><?php echo !empty($speedycache->options['unused_css_exclude_stylesheets']) ? esc_html(implode("\n", $speedycache->options['unused_css_exclude_stylesheets'])) : '';?></textarea>
												</label>
												<br><br>
												<label for="speedycache_unusedcss_include_selector" style="width:100%;">
													<span style="font-weight:500; margin:20px 0 3px 0; dispaly:block;"><?php esc_html_e('Include Selectors', 'speedycache'); ?></span>
													<span class="speedycache-model-label-description"><?php esc_html_e('Enter Selectors you want to be included in used CSS', 'speedycache'); ?></span>
													<textarea name="speedycache_unusedcss_include_selector" id="speedycache_unusedcss_include_selector" rows="4" placeholder="Enter selector one per line"><?php echo !empty($speedycache->options['unusedcss_include_selector']) ? esc_html(implode("\n", $speedycache->options['unusedcss_include_selector'])) : '';?></textarea>
												</label>
											</div>
											<div class="speedycache-modal-footer">
												<button type="button" action="close">
													<span><?php esc_html_e('Submit', 'speedycache'); ?></span>
												</button>
											</div>
										</div>
									</div>
								</div>
								
							<?php 
							} else { ?>
								<div class="speedycache-option-wrap speedycache-disabled">
									<label class="speedycache-custom-checkbox">
										<input type="checkbox" disabled/>
										<div class="speedycache-input-slider"></div>
									</label>
									<div class="speedycache-option-info">
										<span class="speedycache-option-name"><?php esc_html_e('Unused CSS', 'speedycache'); ?></span>
										<span class="speedycache-option-desc"><?php esc_html_e('It removes the unused CSS from the page.', 'speedycache'); ?></span>
									</div>
									<div class="speedycache-premium-tag">
										<i class="fas fa-crown"></i>  <?php !empty($need_key) ? esc_html_e('Link your Key', 'speedycache') : esc_html_e('Premium', 'speedycache'); ?>
									</div>
								</div>
							<?php } 
						
							// Lazy Render HTML element
							if(defined('SPEEDYCACHE_PRO')){ ?>
							<div class="speedycache-option-wrap">
								<label for="speedycache_lazy_load_html" class="speedycache-custom-checkbox" style="margin-top:0;">
									<input type="checkbox" id="speedycache_lazy_load_html" name="speedycache_lazy_load_html" <?php echo (!empty($speedycache->options['lazy_load_html']) ? ' checked' : ''); ?>/>
									<div class="speedycache-input-slider"></div>
								</label>
								<div class="speedycache-option-info">
									<?php
									echo '<span class="speedycache-option-name"><span>'.__('Lazy Render HTML Element', 'speedycache').'</span><a href="https://speedycache.com/docs/file-optimization/how-to-lazy-render-html-elements/" target="_blank"><span class="dashicons dashicons-info" style="font-size:14px"></span></a>
									<span class="speedycache-modal-settings-link" setting-id="speedycache_lazy_load_html" style="display:'.(!empty($speedycache->options['lazy_load_html']) ? 'inline-block' : 'none').';">- Settings</span>
									</span><span class="speedycache-option-desc">'.__('Lazy Render a HTML element(class or id) if not in view-port.', 'speedycache').'</span>';
									?>
								</div>
							</div>

							<div modal-id="speedycache_lazy_load_html" class="speedycache-modal">
								<div class="speedycache-modal-wrap">
									<div class="speedycache-modal-header">
										<div><?php esc_html_e('Lazy Render HTML Elements', 'speedycache'); ?></div>
										<div title="Close Modal" class="speedycache-close-modal">
											<span class="dashicons dashicons-no"></span>
										</div>
									</div>
									<div class="speedycache-modal-content speedycache-info-modal">
										<p><?php esc_html_e('Lazy Rendering HTML is usually good for Comments.', 'speedycache'); ?></p>
										<div>
											<label for="speedycache_lazy_load_html_elements" style="width:100%;">
												<span style="font-weight:500; margin:20px 0 3px 0; display:block;"><?php esc_html_e('Elements to Lazy Render', 'speedycache'); ?></span>
												<span style="display:block; font-weight:400; font-size:12px; color: #2c2a2a;"><?php esc_html_e('Add one element per line, use # as prefix for ID and . as prefix for class.', 'speedycache'); ?></span>
												<textarea name="speedycache_lazy_load_html_elements"id="speedycache_lazy_load_html_elements" rows="4" style="width:100%"><?php echo !empty($speedycache->options['lazy_load_html_elements']) ? esc_html(implode("\n", $speedycache->options['lazy_load_html_elements'])) : '';?></textarea>
											</label>
										</div>
										<div class="speedycache-modal-footer">
											<button type="button" action="close">
												<span><?php esc_html_e('Submit', 'speedycache'); ?></span>
											</button>
										</div>
									</div>
								</div>
							</div>
							<?php }else{?>
							<div class="speedycache-option-wrap speedycache-disabled">
								<label for="speedycache_lazy_load_html" class="speedycache-custom-checkbox" style="margin-top:0;">
									<input type="checkbox" disabled/>
									<div class="speedycache-input-slider"></div>
								</label>
								<div class="speedycache-option-info">
									<?php
									echo '<span class="speedycache-option-name">'.__('Lazy Render HTML Element', 'speedycache').'</span>';
									echo '</span><span class="speedycache-option-desc">'.__('Lazy Render a HTML element(class or id) if not in view-port.', 'speedycache').'</span>';
									?>
								</div>
								<div class="speedycache-premium-tag">
									<i class="fas fa-crown"></i> <?php esc_html_e('Premium', 'speedycache'); ?>
								</div>
							</div>
							<?php } ?>
						</div>
					</div>
					
					<div class="speedycache-block">
						<div class="speedycache-block-title">
							<h2><?php esc_html_e('Preloading', 'speedycache'); ?></h2>
						</div>
						<div class="speedycache-option-group">
							<?php
							// -- Critical Images -- //
							if(defined('SPEEDYCACHE_PRO')){ ?>
							<div class="speedycache-option-wrap">
								<label for="speedycache_critical_images" class="speedycache-custom-checkbox">
									<input type="checkbox" id="speedycache_critical_images" name="speedycache_critical_images" <?php echo !empty($speedycache->options['critical_images']) ? 'checked' : ''; ?>/>
									<div class="speedycache-input-slider"></div>
								</label>
								<div class="speedycache-option-info">
									<span class="speedycache-option-name"><?php esc_html_e('Preload Critical Images', 'speedycache'); ?>
									<span class="speedycache-modal-settings-link" setting-id="speedycache_critical_images" style="display:<?php echo (!empty($speedycache->options['critical_images']) ? 'inline-block' : 'none');?>;">- Settings</span></span>
									<span class="speedycache-option-desc"><?php esc_html_e('Preloads critical Images to improve LCP', 'speedycache'); ?></span>
								</div>
							</div>
							
							<!--SpeedyCache Lazy Load Modal Starts here-->
							<div modal-id="speedycache_critical_images" class="speedycache-modal">
								<div class="speedycache-modal-wrap">
									<div class="speedycache-modal-header">
										<div><?php esc_html_e('Preload Critical Images', 'speedycache'); ?></div>
										<div title="Close Modal" class="speedycache-close-modal">
											<span class="dashicons dashicons-no"></span>
										</div>
									</div>
									<div class="speedycache-modal-content speedycache-info-modal">
										<div class="speedycache-modal-block">
											<p><?php esc_html_e('Select the number of images you want to be preloaded.', 'speedycache');?></p>
											<table>
											<tr>
												<th><?php esc_html_e('Critical Image Count', 'speedycache'); ?></th>
												<td>
													<div class="speedycache-form-input">
														<select name="speedycache_critical_image_count" value="<?php echo !isset($speedycache->options['critical_image_count']) ? '' : esc_attr($speedycache->options['critical_image_count']); ?>">
														<?php
															$image_count = array('1','2','3','4','5');

															foreach($image_count as $count){
																echo '<option value="'.esc_attr($count).'" '. ((!empty($speedycache->options['critical_image_count']) && $speedycache->options['critical_image_count'] == $count ) ? ' selected' : '') .'>'.esc_html($count).'</option>';
															}
														?>
														</select>
													</div>
												</td>
											</tr>
											</table>
										</div>
									</div>
									<div class="speedycache-modal-footer">
										<button type="button" action="close">
											<span>
												<?php esc_html_e('Submit', 'speedycache'); ?>
											</span>
										</button>
									</div>
								</div>
							</div>

							<?php } else { ?>
							<div class="speedycache-option-wrap speedycache-disabled">
								<label class="speedycache-custom-checkbox">
									<input type="checkbox" disabled/>
									<div class="speedycache-input-slider"></div>
								</label>
								<div class="speedycache-option-info">
									<span class="speedycache-option-name"><?php esc_html_e('Preload Critical Images', 'speedycache'); ?></span>
									<span class="speedycache-option-desc"><?php esc_html_e('Preloads critical Images to improve LCP', 'speedycache'); ?></span>
								</div>
								<div class="speedycache-premium-tag">
									<i class="fas fa-crown"></i> <?php esc_html_e('Premium', 'speedycache'); ?>
								</div>
							</div>
							<?php }

							if(defined('SPEEDYCACHE_PRO')){ ?>
							<div class="speedycache-option-wrap">
								<label for="speedycache_instant_page" class="speedycache-custom-checkbox">
									<input type="checkbox" id="speedycache_instant_page" name="speedycache_instant_page" <?php echo !empty($speedycache->options['instant_page']) ? 'checked' : ''; ?>/>
									<div class="speedycache-input-slider"></div>
								</label>
								<div class="speedycache-option-info">
									<span class="speedycache-option-name"><?php esc_html_e('Instant Page', 'speedycache'); ?></span>
									<span class="speedycache-option-desc"><?php esc_html_e('Improves page load speed', 'speedycache'); ?></span>
								</div>
							</div>
							<?php } else { ?>
								<div class="speedycache-option-wrap speedycache-disabled">
									<label for="speedycache_instant_page" class="speedycache-custom-checkbox">
										<input type="checkbox" id="speedycache_instant_page" disabled/>
										<div class="speedycache-input-slider"></div>
									</label>
									<div class="speedycache-option-info">
										<span class="speedycache-option-name"><?php esc_html_e('Instant Page', 'speedycache'); ?></span>
										<span class="speedycache-option-desc"><?php esc_html_e('Improves page load speed', 'speedycache'); ?></span>
									</div>
									<div class="speedycache-premium-tag">
										<i class="fas fa-crown"></i> <?php esc_html_e('Premium', 'speedycache'); ?>
									</div>
								</div>
							<?php } ?>
							<div class="speedycache-option-wrap">
								<label for="speedycache_dns_prefetch" class="speedycache-custom-checkbox">
									<input type="checkbox" id="speedycache_dns_prefetch" name="speedycache_dns_prefetch" <?php echo !empty($speedycache->options['dns_prefetch']) ? 'checked' : ''; ?>/>
									<div class="speedycache-input-slider"></div>
								</label>
								<div class="speedycache-option-info">
									<span class="speedycache-option-name"><?php esc_html_e('DNS Prefetch', 'speedycache'); ?> <span class="speedycache-modal-settings-link" setting-id="speedycache_dns_prefetch" style="display:<?php echo (!empty($speedycache->options['dns_prefetch']) ? 'inline-block' : 'none');?>;">- Settings</span></span>
									<span class="speedycache-option-desc"><?php esc_html_e('DNS prefetching can make external files load faster.', 'speedycache'); ?></span>
								</div>
							</div>
							<div modal-id="speedycache_dns_prefetch" class="speedycache-modal">
								<div class="speedycache-modal-wrap">
									<div class="speedycache-modal-header">
											<div><?php esc_html_e('Prefetch DNS Requests', 'speedycache'); ?></div>
											<div title="Close Modal" class="speedycache-close-modal">
												<span class="dashicons dashicons-no"></span>
											</div>
									</div>
									<div class="speedycache-modal-content speedycache-info-modal">
										<h3><?php esc_html_e('How DNS Prefetch can help?', 'speedycache'); ?></h3>		
										<p><?php esc_html_e('DNS prefetch can improve page load performance by resolving domain names in advance, so that the browser can start loading resources from those domains as soon as possible.', 'speedycache'); ?></p>
										
										<label><strong><?php esc_html_e('URLs to prefetch', 'speedycache'); ?></strong>
										<span style="display:block;"><?php esc_html_e('Specify external hosts to be prefetched (no http:, one per line)', 'speedycache'); ?></span>
										<textarea name="speedycache_dns_urls" style="width:100%" rows="4" placeholder="//example.com"><?php echo !empty($speedycache->options['dns_urls']) ? esc_html(implode("\n", $speedycache->options['dns_urls'])) : '';?></textarea>
										</label>
									</div>
									<div class="speedycache-modal-footer">
										<button type="button" action="close">
											<span><?php esc_html_e('Submit', 'speedycache'); ?></span>
										</button>
									</div>
								</div>
							</div>
							
							<?php
							if(defined('SPEEDYCACHE_PRO')){
							echo '<div class="speedycache-option-wrap">
								<label for="speedycache_preload_resources" class="speedycache-custom-checkbox">
									<input type="checkbox" id="speedycache_preload_resources" name="speedycache_preload_resources" '.(!empty($speedycache->options['preload_resources']) ? 'checked' : '').'/>
									<div class="speedycache-input-slider"></div>
								</label>
								<div class="speedycache-option-info">
									<span class="speedycache-option-name">'.esc_html__('Preload Resources', 'speedycache').' <span class="speedycache-modal-settings-link" setting-id="speedycache_preload_resources" style="display:'.(!empty($speedycache->options['preload_resources']) ? 'inline-block' : 'none').';">- Settings</span></span>
									<span class="speedycache-option-desc">'.esc_html__('Hints browser to load resources early.', 'speedycache').'</span>
								</div>
							</div>';
							} else {
								echo '<div class="speedycache-option-wrap speedycache-disabled">
								<label class="speedycache-custom-checkbox">
									<input type="checkbox" disabled/>
									<div class="speedycache-input-slider"></div>
								</label>
								<div class="speedycache-option-info">
									<span class="speedycache-option-name">'. esc_html__('Preload Resources', 'speedycache').'</span>
									<span class="speedycache-option-desc">'. esc_html__('Hints browser to load resources early.', 'speedycache').'</span>
								</div>
								<div class="speedycache-premium-tag">
									<i class="fas fa-crown"></i>'.esc_html__('Premium', 'speedycache').'
								</div>
							</div>';
							}

							if(defined('SPEEDYCACHE_PRO')){
							echo '<div class="speedycache-option-wrap">
								<label for="speedycache_pre_connect" class="speedycache-custom-checkbox">
									<input type="checkbox" id="speedycache_pre_connect" name="speedycache_pre_connect" '. (!empty($speedycache->options['pre_connect']) ? 'checked' : '') .'/>
									<div class="speedycache-input-slider"></div>
								</label>
								<div class="speedycache-option-info">
									<span class="speedycache-option-name">'. esc_html__('PreConnect', 'speedycache').' <span class="speedycache-modal-settings-link" setting-id="speedycache_pre_connect" style="display:'. (!empty($speedycache->options['pre_connect']) ? 'inline-block' : 'none').';">- Settings</span></span>
									<span class="speedycache-option-desc">'.esc_html__('Establish early connections to speed up page load.', 'speedycache').'</span>
								</div>
							</div>';
							} else {
								echo '<div class="speedycache-option-wrap speedycache-disabled">
								<label class="speedycache-custom-checkbox">
									<input type="checkbox" disabled/>
									<div class="speedycache-input-slider"></div>
								</label>
								<div class="speedycache-option-info">
									<span class="speedycache-option-name">'. esc_html__('Preconnect', 'speedycache') .'</span>
									<span class="speedycache-option-desc">'. esc_html__('Establish early connections to speed up page load.', 'speedycache').'</span>
								</div>
								<div class="speedycache-premium-tag">
									<i class="fas fa-crown"></i>'.esc_html__('Premium', 'speedycache').'
								</div>
							</div>';
							}
							?>
						</div>
					</div>

					<div class="speedycache-block">
						<div class="speedycache-block-title">
							<h2><?php esc_html_e('Miscellaneous', 'speedycache'); ?></h2>
						</div>
						
						<div class="speedycache-option-group">
							<div class="speedycache-option-wrap">
								<label for="speedycache_gzip" class="speedycache-custom-checkbox">
									<input type="checkbox" id="speedycache_gzip" name="speedycache_gzip" <?php echo (!empty($speedycache->options['gzip']) ? ' checked' : ''); ?>/>
									<div class="speedycache-input-slider"></div>
								</label>
								<div class="speedycache-option-info">
									<span class="speedycache-option-name"><?php esc_html_e('Gzip', 'speedycache'); ?></span>
									<span class="speedycache-option-desc"><?php esc_html_e('Reduce the size of files sent from your server', 'speedycache'); ?></span>
								</div>
							</div>

							<?php
							if(!empty(speedycache_optserver('SERVER_SOFTWARE')) && preg_match('/nginx/i', speedycache_optserver('SERVER_SOFTWARE'))){
								?>
								<!--SpeedyCache Gzip Modal-->
								<div modal-id="speedycache_gzip" class="speedycache-modal">
									<div class="speedycache-modal-wrap">
										<div class="speedycache-modal-header">
												<div><?php esc_html_e('Enable Gzip', 'speedycache'); ?></div>
												<div title="Close Modal" class="speedycache-close-modal">
													<span class="dashicons dashicons-no"></span>
												</div>
										</div>
										<div class="speedycache-modal-content speedycache-info-modal">
											<h3><?php esc_html_e('How to Enable Gzip?', 'speedycache'); ?></h3>		
											<p><?php esc_html_e('Nginx is used in the server so you need to enable the Gzip manually. Please take a look at the following tutorial.', 'speedycache'); ?></p>
											
											<div class="speedycache-modal-highlight">
												<label>
													<a href="https://speedycache.com/docs/miscellaneous/how-to-enable-gzip-on-nginx/" target="_blank">https://speedycache.com/docs/miscellaneous/how-to-enable-gzip-on-nginx/</a>
												</label>
											</div>
										</div>
										<div class="speedycache-modal-footer">
											<button type="button" action="close">
												<span><?php esc_html_e('Submit', 'speedycache'); ?></span>
											</button>
										</div>
									</div>
								</div>
								<!--SpeedyCache Gzip Modal Ends here-->
							<?php }
							?>
							
							<div class="speedycache-option-wrap">
								<label for="speedycache_disable_emojis" class="speedycache-custom-checkbox">
									<input type="checkbox" id="speedycache_disable_emojis" name="speedycache_disable_emojis" <?php echo (!empty($speedycache->options['disable_emojis']) ? ' checked' : ''); ?>/>
									<div class="speedycache-input-slider"></div>
								</label>
								<div class="speedycache-option-info">
									<span class="speedycache-option-name"><?php esc_html_e('Disable Emojis', 'speedycache'); ?></span>
									<span class="speedycache-option-desc"><?php esc_html_e('You can remove the emoji inline css and wp-emoji-release.min.js', 'speedycache'); ?></span>
								</div>
							</div>

							<?php if(defined('SPEEDYCACHE_PRO')){ ?>
								<div class="speedycache-option-wrap">
									<label for="speedycache_render_blocking" class="speedycache-custom-checkbox">
										<input type="checkbox" id="speedycache_render_blocking" name="speedycache_render_blocking" <?php echo (!empty($speedycache->options['render_blocking']) ? ' checked' : ''); ?>/>
										<div class="speedycache-input-slider"></div>
									</label>
									<div class="speedycache-option-info">
										<span class="speedycache-option-name"><?php esc_html_e('Render Blocking JS', 'speedycache'); ?></span>
										<span class="speedycache-option-desc"><?php esc_html_e('Defers render-blocking JavaScript resources', 'speedycache'); ?></span>
									</div>
								</div>
	
							<?php } else { ?>
								<div class="speedycache-option-wrap speedycache-disabled">
										<label for="speedycache_render_blocking" class="speedycache-custom-checkbox">
											<input type="checkbox" id="speedycache_render_blocking" name="speedycache_render_blocking" disabled/>
											<div class="speedycache-input-slider"></div>
										</label>
									<div class="speedycache-option-info">
										<span class="speedycache-option-name"><?php esc_html_e('Render Blocking JS', 'speedycache'); ?></span>
										<span class="speedycache-option-desc"><?php esc_html_e('Defers render-blocking JavaScript resources', 'speedycache'); ?></span>
									</div>
									<div class="speedycache-premium-tag">
										<i class="fas fa-crown"></i> <?php esc_html_e('Premium', 'speedycache'); ?>
									</div>
								</div>
							<?php }
							
							if(defined('SPEEDYCACHE_PRO')){ ?>
								<div class="speedycache-option-wrap">
									<label for="speedycache_google_fonts" class="speedycache-custom-checkbox">
										<input type="checkbox" id="speedycache_google_fonts" name="speedycache_google_fonts" <?php echo (!empty($speedycache->options['google_fonts']) ? ' checked' : ''); ?>/>
										<div class="speedycache-input-slider"></div>
									</label>
									<div class="speedycache-option-info">
										<span class="speedycache-option-name"><?php esc_html_e('Google Fonts', 'speedycache'); ?></span>
										<span class="speedycache-option-desc"><?php esc_html_e('Load Google Fonts asynchronously', 'speedycache'); ?></span>
									</div>
								</div>
							<?php 
							} else { ?>
								<div class="speedycache-option-wrap speedycache-disabled">
									<label for="speedycache_google_fonts" class="speedycache-custom-checkbox">
										<input type="checkbox" id="speedycache_google_fonts" name="speedycache_google_fonts" disabled/>
										<div class="speedycache-input-slider"></div>
									</label>
									<div class="speedycache-option-info">
										<span class="speedycache-option-name"><?php esc_html_e('Google Fonts', 'speedycache'); ?></span>
										<span class="speedycache-option-desc"><?php esc_html_e('Load Google Fonts asynchronously', 'speedycache'); ?></span>
									</div>
									<div class="speedycache-premium-tag">
										<i class="fas fa-crown"></i> <?php esc_html_e('Premium', 'speedycache'); ?>
									</div>
								</div>
							<?php }
							
							if(defined('SPEEDYCACHE_PRO')){ ?>
								<div class="speedycache-option-wrap">
									<input type="hidden" value="<?php echo isset($speedycache->options['lazy_load_placeholder']) ? esc_attr($speedycache->options['lazy_load_placeholder']) : ''; ?>" id="speedycache_lazy_load_placeholder" name="speedycache_lazy_load_placeholder"/>
									<input style="display: none;" type="checkbox" <?php echo isset($speedycache->options['lazy_load_exclude_full_size_img']) ? esc_attr($speedycache->options['lazy_load_exclude_full_size_img']) : '';?> id="speedycache_lazy_load_exclude_full_size_img" name="speedycache_lazy_load_exclude_full_size_img">
									
									<label for="speedycache_lazy_load" class="speedycache-custom-checkbox">
										<input type="checkbox" id="speedycache_lazy_load" name="speedycache_lazy_load" <?php echo (!empty($speedycache->options['lazy_load']) ? ' checked' : ''); ?>/>
										<div class="speedycache-input-slider"></div>
									</label>
									<div class="speedycache-option-info">
										<span class="speedycache-option-name"><?php esc_html_e('Lazy Load', 'speedycache'); ?>  <span class="speedycache-modal-settings-link" setting-id="speedycache_lazy_load" style="display:<?php echo (!empty($speedycache->options['lazy_load']) ? 'inline-block' : 'none');?>;">- Settings</span></span>
										<span class="speedycache-option-desc"><?php esc_html_e('Load images and iframes when they enter the browsers viewport', 'speedycache'); ?></span>
									</div>
								</div>

								<!--SpeedyCache Lazy Load Modal Starts here-->
								<div modal-id="speedycache_lazy_load" class="speedycache-modal">
									<div class="speedycache-modal-wrap">
										<div class="speedycache-modal-header">
											<div><?php esc_html_e('Lazy Load Settings', 'speedycache'); ?></div>
											<div title="Close Modal" class="speedycache-close-modal">
												<span class="dashicons dashicons-no"></span>
											</div>
										</div>
										<div class="speedycache-modal-content speedycache-info-modal">
											<div class="speedycache-modal-block">
												<h4><?php esc_html_e('Image Placeholder', 'speedycache'); ?></h4>
												<p>
													<?php esc_html_e('Specify an image to be used as a placeholder while other images finish loading.', 'speedycache');?>
													<a target="_blank" href="https://speedycache.com/docs/miscellaneous/lazy-load-images-and-iframes/">
													<span class="dashicons dashicons-info"></span>
													</a>
												</p>
												<div class="speedycache-form-input">
													<select name="speedycache_lazy_load_placeholder" class="speedycache_lazy_load_placeholder speedycache-full-width" value="<?php echo !isset($speedycache->options['lazy_load_placeholder']) ? '' : esc_attr($speedycache->options['lazy_load_placeholder']); ?>">
														<option value="default" <?php echo (isset($speedycache->options['lazy_load_placeholder']) && $speedycache->options['lazy_load_placeholder'] == 'default') ? 'selected' : '';?>><?php echo preg_replace("/https?\:\/\//", '', esc_url(SPEEDYCACHE_URL)).'/assets/images/image-palceholder.png'; ?></option>
														<option value="base64" <?php echo (isset($speedycache->options['lazy_load_placeholder']) && $speedycache->options['lazy_load_placeholder'] == 'base64') ? 'selected' : '';?>>data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7</option>
														<option value="custom" <?php echo (isset($speedycache->options['lazy_load_placeholder']) && $speedycache->options['lazy_load_placeholder'] == 'custom') ? 'selected' : '';?>><?php esc_html_e('Custom Placeholder', 'speedycache'); ?></option>
													</select>
													
												</div>
												<?php 
													$hide_css_class = '';
												
													if(isset($speedycache->options['lazy_load_placeholder']) && $speedycache->options['lazy_load_placeholder'] != 'custom'){
														$hide_css_class = 'speedycache-hidden '; 
													}
												?>
												
												<div class="speedycache-form-input">
													<input type="text" class="<?php echo esc_attr($hide_css_class); ?>speedycache-full-width" placeholder="https://example.com/sample.jpg" name="speedycache_lazy_load_placeholder_custom_url" value="<?php echo !isset($speedycache->options['lazy_load_placeholder_custom_url']) ? '' : esc_attr($speedycache->options['lazy_load_placeholder_custom_url']); ?>"/>
												</div>
											</div>
											<div class="speedycache-modal-block">
												<h4><?php esc_html_e('Exclude above fold images', 'speedycache');?></h4>
												<p><?php esc_html_e('Number of images you want to exclude from getting lazyloaded from top of the screen', 'speedycache');?></p>
												<select name="speedycache_exclude_above_fold">
												<?php 
												foreach([0,1,2,3,4,5] as $exclude_no){
													$selected = '';
													if(isset($speedycache->options['exclude_above_fold']) && $exclude_no == $speedycache->options['exclude_above_fold']){
														$selected = 'selected';
													}elseif(!isset($speedycache->options['exclude_above_fold']) && $exclude_no == 2){
														$selected = 'selected';
													}

													echo '<option value="'.esc_attr($exclude_no).'" '.esc_attr($selected).'>'.esc_html($exclude_no).'</option>';
												}?>
												</select>
											</div>
											

											<div class="speedycache-modal-block">
												<h4><?php esc_html_e('Exclude Sources', 'speedycache'); ?></h4>
												<p><?php esc_html_e('It is enough to write a keyword such as', 'speedycache')?> <strong>home.jpg or iframe or .gif</strong> instead of full url.</p>
												<div class="speedycache-form-input">		
													<label for="speedycache-full-width">
														<?php esc_html_e('Add Keyword', 'speedycache'); ?>
														<input class="speedycache-exclude-source-keyword speedycache-full-width" type="text" placeholder="Add Keyword"/>
														<span class="speedycache-input-desc"><?php esc_html_e('Use Comma to create new keyword', 'speedycache'); ?></span>
														<div class="speedycache-tags-holder"></div>
														<input type="hidden" value="<?php echo !isset($speedycache->options['lazy_load_keywords']) ? '' : esc_attr($speedycache->options['lazy_load_keywords']); ?>" id="speedycache_lazy_load_keywords" name="speedycache_lazy_load_keywords">
													</label>
												</div>

												<?php if(isset($speedycache->options['lazy_load_exclude_full_size_img'])){ ?>
												<div class="speedycache-form-input">
													<label for="speedycache_lazy_load_exclude_full_size_img" >
														<input type="checkbox" id="speedycache_lazy_load_exclude_full_size_img" name="speedycache_lazy_load_exclude_full_size_img" <?php echo !empty($speedycache->options['lazy_load_exclude_full_size_img']) ? ' checked' : ''; ?>/>
													
														<?php esc_html_e('Exclude full size images in posts or pages', 'speedycache');?>
														<a target="_blank" href="https://speedycache.com/docs/miscellaneous/lazy-load-images-and-iframes/">
															<span class="dashicons dashicons-info"></span>
														</a>
													</label>
												</div>
												<?php } ?>
											</div>
										</div>
										<div class="speedycache-modal-footer">
											<button type="button" action="close">
												<span>
													<?php esc_html_e('Submit', 'speedycache'); ?>
												</span>
											</button>
										</div>
									</div>
								</div>
							<?php
							} else { ?>
								<div class="speedycache-option-wrap speedycache-disabled">
									<label for="speedycache_lazy_load" class="speedycache-custom-checkbox">
										<input type="checkbox" id="speedycache_lazy_load" name="speedycache_lazy_load" disabled/>
										<div class="speedycache-input-slider"></div>
									</label>
									<div class="speedycache-option-info">
										<span class="speedycache-option-name"><?php esc_html_e('Lazy Load', 'speedycache'); ?></span>
										<span class="speedycache-option-desc"><?php esc_html_e('Load images and iframes when they enter the browsers viewport', 'speedycache'); ?></span>
									</div>
									<div class="speedycache-premium-tag">
										<i class="fas fa-crown"></i> <?php esc_html_e('Premium', 'speedycache'); ?>
									</div>
								</div>
							<?php }

							if(defined('SPEEDYCACHE_PRO')){ ?>
							<div class="speedycache-option-wrap">
								<label for="speedycache_display_swap" class="speedycache-custom-checkbox">
									<input type="checkbox" id="speedycache_display_swap" name="speedycache_display_swap" <?php echo !empty($speedycache->options['display_swap']) ? 'checked' : ''; ?>/>
									<div class="speedycache-input-slider"></div>
								</label>
								<div class="speedycache-option-info">
									<span class="speedycache-option-name"><?php esc_html_e('Display Swap', 'speedycache'); ?></span>
									<span class="speedycache-option-desc"><?php esc_html_e('Ensure text remains visible during Google font loads', 'speedycache'); ?></span>
								</div>
							</div>
							<?php } else { ?>
							<div class="speedycache-option-wrap speedycache-disabled">
								<label for="speedycache_display_swap" class="speedycache-custom-checkbox">
									<input type="checkbox" id="speedycache_display_swap" name="speedycache_display_swap" disabled/>
									<div class="speedycache-input-slider"></div>
								</label>
								<div class="speedycache-option-info">
									<span class="speedycache-option-name"><?php esc_html_e('Display Swap', 'speedycache'); ?></span>
									<span class="speedycache-option-desc"><?php esc_html_e('Ensure text remains visible during Google font loads', 'speedycache'); ?></span>
								</div>
								<div class="speedycache-premium-tag">
									<i class="fas fa-crown"></i> <?php esc_html_e('Premium', 'speedycache'); ?>
								</div>
							</div>
							<?php
							}

							if(defined('SPEEDYCACHE_PRO')){ ?>
							<div class="speedycache-option-wrap">
								<label for="speedycache_local_gfonts" class="speedycache-custom-checkbox">
									<input type="checkbox" id="speedycache_local_gfonts" name="speedycache_local_gfonts" <?php echo !empty($speedycache->options['local_gfonts']) ? 'checked' : ''; ?>/>
									<div class="speedycache-input-slider"></div>
								</label>
								<div class="speedycache-option-info">
									<span class="speedycache-option-name"><?php esc_html_e('Local Google Fonts', 'speedycache'); ?></span>
									<span class="speedycache-option-desc"><?php esc_html_e('Loads google fonts from your local server', 'speedycache'); ?></span>
								</div>
							</div>
							
							<?php } else { ?>	
							<div class="speedycache-option-wrap speedycache-disabled">
								<label for="speedycache_local_gfonts" class="speedycache-custom-checkbox">
									<input type="checkbox" id="speedycache_local_gfonts" name="speedycache_local_gfonts" disabled/>
									<div class="speedycache-input-slider"></div>
								</label>
								<div class="speedycache-option-info">
									<span class="speedycache-option-name"><?php esc_html_e('Local Google Fonts', 'speedycache'); ?></span>
									<span class="speedycache-option-desc"><?php esc_html_e('Loads google fonts from your local server', 'speedycache'); ?></span>
								</div>
								<div class="speedycache-premium-tag">
									<i class="fas fa-crown"></i> <?php esc_html_e('Premium', 'speedycache'); ?>
								</div>
							</div>
							<?php }
							
							if(defined('SPEEDYCACHE_PRO')){ ?>
							<div class="speedycache-option-wrap">
								<label for="speedycache_image_dimensions" class="speedycache-custom-checkbox">
									<input type="checkbox" id="speedycache_image_dimensions" name="speedycache_image_dimensions" <?php echo !empty($speedycache->options['image_dimensions']) ? 'checked' : ''; ?>/>
									<div class="speedycache-input-slider"></div>
								</label>
								<div class="speedycache-option-info">
									<span class="speedycache-option-name"><?php esc_html_e('Image Dimensions', 'speedycache'); ?></span>
									<span class="speedycache-option-desc"><?php esc_html_e('Adds dimensions to the image, to reduce CLS', 'speedycache'); ?></span>
								</div>
							</div>
							
							<?php } else { ?>	
							<div class="speedycache-option-wrap speedycache-disabled">
								<label class="speedycache-custom-checkbox">
									<input type="checkbox" disabled/>
									<div class="speedycache-input-slider"></div>
								</label>
								<div class="speedycache-option-info">
									<span class="speedycache-option-name"><?php esc_html_e('Image Dimensions', 'speedycache'); ?></span>
									<span class="speedycache-option-desc"><?php esc_html_e('Adds dimensions to the image, to reduce CLS', 'speedycache'); ?></span>
								</div>
								<div class="speedycache-premium-tag">
									<i class="fas fa-crown"></i> <?php esc_html_e('Premium', 'speedycache'); ?>
								</div>
							</div>
							<?php } ?>
							<div class="speedycache-option-wrap">
								<label for="speedycache_gravatar_cache" class="speedycache-custom-checkbox">
									<input type="checkbox" id="speedycache_gravatar_cache" name="speedycache_gravatar_cache" <?php echo !empty($speedycache->options['gravatar_cache']) ? 'checked' : ''; ?>/>
									<div class="speedycache-input-slider"></div>
								</label>
								<div class="speedycache-option-info">
									<span class="speedycache-option-name"><?php esc_html_e('Gravatar Cache', 'speedycache'); ?></span>
									<span class="speedycache-option-desc"><?php esc_html_e('Locally host Gravatar', 'speedycache'); ?></span>
								</div>
							</div>
							
							<div class="speedycache-option-wrap">
								<label for="speedycache_font_rendering" class="speedycache-custom-checkbox">
									<input type="checkbox" id="speedycache_font_rendering" name="speedycache_font_rendering" <?php echo !empty($speedycache->options['font_rendering']) ? 'checked' : ''; ?>/>
									<div class="speedycache-input-slider"></div>
								</label>
								<div class="speedycache-option-info">
									<span class="speedycache-option-name"><?php esc_html_e('Improve Font Rendering', 'speedycache'); ?></span>
									<span class="speedycache-option-desc"><?php esc_html_e('Improved Font rendeing by adding text-rendring CSS.', 'speedycache'); ?></span>
								</div>
							</div>
						</div>
					</div>
					<div class="speedycache-option-wrap speedycache-submit-btn">
						<input type="submit" name="submit" value="Save Settings" class="speedycache-btn speedycache-btn-primary">
					</div>
				</form>
				
				<?php 
				if(defined('SPEEDYCACHE_PRO')){
					echo '<div modal-id="speedycache_preload_resources" class="speedycache-modal">
						<div class="speedycache-modal-wrap">
							<div class="speedycache-modal-header">
									<div>'.esc_html__('Preload Resource', 'speedycache').'</div>
									<div title="Close Modal" class="speedycache-close-modal">
										<span class="dashicons dashicons-no"></span>
									</div>
							</div>
							<div class="speedycache-modal-content speedycache-info-modal">
								<form class="speedycache-pseudo-form" data-type="preload_resource_list">'.
								speedycache_preload_modal_options('preload_resource', ['type' => true, 'crossorigin' => true]).'
								<div style="display:flex; justify-content:center;">
									<button type="submit" align="center" class="speedycache_preloading_add">Add<div class="speedycache-btn-loader"><img src="'.site_url(). '/wp-admin/images/loading.gif"/></div></button>
								</div>
								</form>';
								
								if(!empty($speedycache->options['preload_resource_list']) && count($speedycache->options['preload_resource_list']) > 7){
									echo '<p><strong>Note:</strong> Preloading too many resources can actually slow down your website, so it\'s important to only preload the resources that are absolutely necessary for the initial load. These might include fonts, image, CSS or JS files.</p>';
								}

								echo '<table class="speedycache-preloading-table" data-type="preload_resource_list">
									<thead>
										<tr>
											<th class="speedycache-table-hitem" scope="col" width="70%">'.esc_html__('Resource', 'speedycache').'</th>
											<th class="speedycache-table-hitem" scope="col" width="15%">'. esc_html__('Type', 'speedycache').'</th>
											<th class="speedycache-table-hitem" scope="col" width="10%"><abbr title="Crossorigin">'. esc_html__('CS', 'speedycache').'</abbr></th>
											<th class="speedycache-table-hitem" scope="col" width="5%"></th>
										</tr>
									</thead>
									<tbody>';
									
									if(empty($speedycache->options['preload_resource_list']) || !is_array($speedycache->options['preload_resource_list'])){
										echo '<tr><td colspan="4" align="center" class="speedycache-preloading-empty">No Resource Preload added yet</td></tr>';
									} else {
										foreach($speedycache->options['preload_resource_list'] as $pkey => $preload_resource){
											echo '<tr>
												<td>'.esc_url($preload_resource['resource']).'</td>
												<td>'.esc_html($preload_resource['type']).'</td>
												<td>'.(!empty($preload_resource['crossorigin']) ? 'Yes' : 'No').'</td>
												<td data-key="'.esc_html($pkey).'"><span class="dashicons dashicons-trash"></span></td>
											</tr>';
										}
									}
									
									echo '</tbody>
								</table>
							</div>
						</div>
					</div>
					<div modal-id="speedycache_pre_connect" class="speedycache-modal">
						<div class="speedycache-modal-wrap">
							<div class="speedycache-modal-header">
									<div>'. esc_html__('Preconnect', 'speedycache').'</div>
									<div title="Close Modal" class="speedycache-close-modal">
										<span class="dashicons dashicons-no"></span>
									</div>
							</div>
							<div class="speedycache-modal-content speedycache-info-modal">
								<form class="speedycache-pseudo-form" data-type="pre_connect_list">								
								'.speedycache_preload_modal_options('pre_connect', ['crossorigin' => true]).'
								<div style="display:flex; justify-content:center;">
									<button tabindex="" type="submit" align="center" class="speedycache_preloading_add">Add<div class="speedycache-btn-loader"><img src="'.site_url(). '/wp-admin/images/loading.gif"/></div></button>
								</div>
								</form>';
								if(!empty($speedycache->options['pre_connect_list']) && count($speedycache->options['pre_connect_list']) > 6){
									echo '<p><strong>Note:</strong> A good rule of thumb is to limit the number of preconnects to 6-8. However, the exact number will vary depending on the specific website and the resources that are being loaded.</p>';
								}

								echo '<table class="speedycache-preloading-table" data-type="pre_connect_list">
									<thead>
										<tr>
											<th class="speedycache-table-hitem" scope="col" width="80%">'.esc_html__('Resource', 'speedycache').'</th>
											<th class="speedycache-table-hitem" scope="col" width="15%">'. esc_html__('Crossorigin', 'speedycache').'</th>
											<th class="speedycache-table-hitem" scope="col" width="5%"></th>
										</tr>
									</thead>
									<tbody>';
									
									if(empty($speedycache->options['pre_connect_list']) || !is_array($speedycache->options['pre_connect_list'])){
										echo '<tr><td colspan="4" align="center" class="speedycache-preloading-empty">No PreConnect added yet</td></tr>';
									} else {
										foreach($speedycache->options['pre_connect_list'] as $pkey => $pre_connect){
											echo '<tr>
												<td>'.esc_html($pre_connect['resource']).'</td>
												<td>'.(!empty($pre_connect['crossorigin']) ? 'Yes' : 'No').'</td>
												<td data-key="'.esc_html($pkey).'"><span class="dashicons dashicons-trash"></span></td>
											</tr>';
										}
									}
									
									echo '</tbody>
								</table>
							</div>
						</div>
					</div>';
				}
				?>
			</div>
			<div class="speedycache-tab-delete-cache">
				<?php if(defined('SPEEDYCACHE_PRO')){ ?>
				<div id="speedycache-toggle-logs">
					<span id="speedycache-show-delete-log"><?php esc_html_e('Show Logs', 'speedycache'); ?></span>
					<span id="speedycache-hide-delete-log"><?php esc_html_e('Hide Logs', 'speedycache'); ?></span>
				</div>
				<?php
				}

				if(defined('SPEEDYCACHE_PRO')){
					\SpeedyCache\Statistics::init();
					\SpeedyCache\Statistics::statics();
				} else {
				?>
					<div class="speedycache-block">
						<div class="speedycache-disabled-block">
							<div class="speedycache-disabled-block-info">
								<i class="fas fa-lock"></i>
								<p><?php esc_html_e('Only available in Pro version', 'speedycache'); ?></p>
								<a href="https://speedycache.com/pricing" target="_blank"><?php esc_html_e('Buy Pro Version Now', 'speedycache'); ?></a>
							</div>
						</div>
						
						<div class="speedycache-block-title">
							<h2 id="cache-statics-h2"><?php esc_html_e('Cache Statistics', 'speedycache'); ?></h2>
						</div>
						<div id="speedycache-cache-statics">
							<div id="speedycache-cache-statics-desktop" class="speedycache-card">
								<div class="speedycache-card-body">
									<div class="speedycache-stats-info">
										<span><?php esc_html_e('Desktop Cache', 'speedycache'); ?></span>
										<p id="speedycache-cache-statics-desktop-data">
											<span class="speedycache-size">0Kb</span><br/>
											<span class="speedycache-files">of 0 Items</span>
										</p>
									</div>
									<div class="speedycache-stat-icon">
										<span class="dashicons dashicons-desktop"></span>
									</div>
								</div>
							</div>
							<div id="speedycache-cache-statics-mobile" class="speedycache-card">
								<div class="speedycache-card-body">
									<div class="speedycache-stats-info">
										<span><?php esc_html_e('Mobile Cache', 'speedycache'); ?></span>
										<p id="speedycache-cache-statics-mobile-data">
											<span class="speedycache-size">0Kb</span><br/>
											<span class="speedycache-files">of 0 Items</span></p>
									</div>
									<div class="speedycache-stat-icon">
										<span class="dashicons dashicons-smartphone"></span>
									</div>
								</div>
							</div>
							<div id="speedycache-cache-statics-css" class="speedycache-card">
								<div class="speedycache-card-body">
									<div class="speedycache-stats-info">
										<span><?php esc_html_e('Minified CSS', 'speedycache'); ?></span>
										<p id="speedycache-cache-statics-css-data">
											<span class="speedycache-size">0Kb</span><br/>
											<span class="speedycache-files">of 0 Items</span>
										</p>
									</div>
									<div class="speedycache-stat-icon"><span class="dashicons dashicons-media-code"></span></div>
								</div>
							</div>
							<div id="speedycache-cache-statics-js" class="speedycache-card">
								<div class="speedycache-card-body">	
									<div class="speedycache-stats-info">
										<span><?esc_html_e('Minified JS', 'speedycache'); ?></span>
										<p id="speedycache-cache-statics-js-data">
											<span class="speedycache-size">0Kb</span><br/>
											<span class="speedycache-files">of 0 Items</span>
										</p>
									</div>
									<div class="speedycache-stat-icon"><span class="dashicons dashicons-media-code"></span></div>
								</div>
							</div>
						</div>
					</div>
				<?php
				}
				?>

				<div class="speedycache-block">
					<div class="speedycache-block-title">
						<h2 id="delete-cache-h2"><?php esc_html_e('Delete Cache', 'speedycache'); ?></h2>
					</div>
					<form method="post">
						<?php wp_nonce_field('speedycache_group', 'security'); ?>
						
						<div class="speedycache-option-wrap">
							<label for="speedycache_delete_minified" class="speedycache-custom-checkbox">
								<input type="checkbox" id="speedycache_delete_minified" name="speedycache_delete_minified"/>
								<div class="speedycache-input-slider"></div>
							</label>
							<div class="speedycache-option-info">
								<span class="speedycache-option-name"><?php esc_html_e('Delete Minified', 'speedycache'); ?></span>
								<span class="speedycache-option-desc"><?php esc_html_e('Deletes Minfied/ Combined CSS/JS files', 'speedycache'); ?></span>
							</div>
						</div>
						<?php 
						if(defined('SPEEDYCACHE_PRO')){ ?>
						<div class="speedycache-option-wrap">
							<label for="speedycache_delete_fonts" class="speedycache-custom-checkbox">
								<input type="checkbox" id="speedycache_delete_fonts" name="speedycache_delete_fonts"/>
								<div class="speedycache-input-slider"></div>
							</label>
							<div class="speedycache-option-info">
								<span class="speedycache-option-name"><?php esc_html_e('Delete Fonts', 'speedycache'); ?></span>
								<span class="speedycache-option-desc"><?php esc_html_e('Deletes Local Google Fonts', 'speedycache'); ?></span>
							</div>
						</div>
						<?php } ?>
						
						<div class="speedycache-option-wrap">
							<label for="speedycache_delete_gravatars" class="speedycache-custom-checkbox">
								<input type="checkbox" id="speedycache_delete_gravatars" name="speedycache_delete_gravatars"/>
								<div class="speedycache-input-slider"></div>
							</label>
							<div class="speedycache-option-info">
								<span class="speedycache-option-name"><?php esc_html_e('Delete Gravatars', 'speedycache'); ?></span>
								<span class="speedycache-option-desc"><?php esc_html_e('Delete locally hosted Gravatars.', 'speedycache'); ?></span>
							</div>
						</div>	
						<input type="hidden" value="delete_cache" name="speedycache_page">
						<div class="speedycache-option-wrap">
							<div class="submit">
								<input type="submit" value="<?php esc_html_e('Clear all cache and the selections', 'speedycache'); ?>" class="speedycache-btn speedycache-btn-primary"/>
							</div>
						</div>
						<div class="speedycache-option-wrap">
							<div>
								<label><?php esc_html_e('Here are the folders that will be deleted', 'speedycache'); ?></label><br>
								<label><?php esc_html_e('Target folder', 'speedycache'); ?></label> <b><?php echo esc_html(speedycache_cache_path('all')); ?></b><br>
								<label><?php esc_html_e('Target folder', 'speedycache'); ?></label> <b><?php echo esc_html(speedycache_cache_path('mobile-cache')); ?></b><br/>
								<?php if(defined('SPEEDYCACHE_PRO')){ ?>
								<label><?php esc_html_e('Target folder', 'speedycache'); ?></label> <b><?php echo esc_html(speedycache_cache_path('critical-css')); ?></b><br/>
								<?php } ?>
								<div class="speedycache-target-gravatars" style="display:none;">
									<label><?php esc_html_e('Target folder', 'speedycache'); ?></label> <b><?php echo esc_html(speedycache_cache_path('gravatars')); ?></b><br/>
								</div>
								<div class="speedycache-target-mini" style="display:none;">
									<label><?php esc_html_e('Target folder', 'speedycache'); ?></label> <b><?php echo esc_html(speedycache_cache_path('assets')); ?></b>
								</div>
								<div class="speedycache-target-fonts" style="display:none;">
									<label><?php esc_html_e('Target folder', 'speedycache'); ?></label> <b><?php echo esc_html(speedycache_cache_path('fonts')); ?></b>
								</div>
							</div>
						</div>
					</form>
				</div>	
				
				<!--Logs Block-->
				<?php if(defined('SPEEDYCACHE_PRO')){
					\SpeedyCache\Logs::log('delete');
					\SpeedyCache\Logs::print_logs();
				}
				?>
				
				<?php
				$disable_wp_cron = '';
				if(defined('DISABLE_WP_CRON')){
					if((is_bool(DISABLE_WP_CRON) && DISABLE_WP_CRON == true) ||
						(is_string(DISABLE_WP_CRON) && preg_match("/^true$/i", DISABLE_WP_CRON))
					){
						$disable_wp_cron = 'disable-wp-cron="true" '; ?>

						<div modal-id="speedycache-modal-disablewpcron" class="speedycache-modal">
							<div class="speedycache-modal-wrap">
								<div class="speedycache-modal-header">
									<div><?php esc_html_e('Warning', 'speedycache'); ?></div>
									<div title="Close Modal" class="speedycache-close-modal">
										<span class="dashicons dashicons-no"></span>
									</div>
								</div>
								<div class="speedycache-modal-content">
									<h3><?php esc_html_e('Disabled Cron', 'speedycache'); ?></h3>		
									<p><?php esc_html_e('The Cron has been disabled entirely by setting', 'speedycache');?><b><a href="https://speedycache.com/docs/miscellaneous/disable-wp-cron/" target="_blank">DISABLE_WP_CRON</a></b> to true.</p>
								</div>
								<div class="speedycache-modal-footer">
									<button type="button" action="close">
										<span>
											<?php esc_html_e('Submit', 'speedycache'); ?>
										</span>
									</button>
								</div>
							</div>
						</div>
				<?php }
				}
				?>
				
				<div class="speedycache-block">
					<div class="speedycache-block-title">
						<h2><?php esc_html_e('Cache Lifespan', 'speedycache'); ?></h2>
						<button type="button" id="speedycache-timeout" <?php echo esc_html($disable_wp_cron); ?> class=" speedycache-dialog-buttons speedycache-btn">
								<span><?php esc_html_e('Add New Rule', 'speedycache'); ?></span>
						</button>
					</div>
					
					<div class="speedycache-timeout-list">
					</div>	
				</div>
				
				<div modal-id="speedycache-timeout" class="speedycache-modal">
					<div class="speedycache-modal-wrap">
						<div class="speedycache-modal-header">
							<div><?php esc_html_e('Cache Timeout', 'speedycache'); ?></div>
							<div title="Close Modal" class="speedycache-close-modal">
								<span class="dashicons dashicons-no"></span>
							</div>
						</div>
						<form>		
						<div class="speedycache-modal-content speedycache-info-modal">
							<div class="speedycache-modal-block">
								<label class="speedycache-timeout-request">
									<span><?php esc_html_e('If REQUEST_URI', 'speedycache'); ?></span>
									<select name="speedycache-timeout-rule-prefix">
										<option selected="" value=""></option>
										<option value="all"><?php esc_html_e('All', 'speedycache'); ?></option>
										<option value="homepage"><?php esc_html_e('Home Page', 'speedycache'); ?></option>
										<option value="startwith"><?php esc_html_e('Starts With', 'speedycache'); ?></option>
										<option value="exact"><?php esc_html_e('Is Equal To', 'speedycache'); ?></option>
										<!-- <option value="contain">_e('Contains', 'speedycache');</option> -->
									</select>
								</label>

								<label class="speedycache-timeout-rule-line-middle speedycache-full-width">
									<input type="text" name="speedycache-timeout-rule-content"  class="speedycache-full-width"/>
								</label>
							</div>
							
							<div class="speedycache-modal-block">
								<?php esc_html_e('Then', 'speedycache'); ?>
								<select name="speedycache-timeout-rule-schedule">
									<?php
										$schedules = wp_get_schedules();

										if(function_exists('wp_list_sort')){
											$schedules = wp_list_sort($schedules, 'interval', 'ASC', true);
										}
										
										$first = true;
										foreach($schedules as $key => $value){
											if(!isset($value['speedycache'])){
												continue;
											}
											
											if($first){
												echo '<option value="">'.esc_html__('Choose One', 'speedycache').'</option>';
												$first = false;
											}
											echo '<option value="'.esc_attr($key).'">'.esc_html($value['display']).'</option>';
										}
									?>
								</select> 
								<span class="speedycache-timeout-at-text" style="padding-right:5px;display:none;">at</span>
								<select name="speedycache-timeout-rule-hour" style="display:none;">
									<?php
										for ($i=0; $i < 24; $i++){ 
											?>
											<option value="<?php echo esc_attr($i); ?>"><?php echo esc_html(str_pad($i, 2, '0', STR_PAD_LEFT)); ?></option>
											<?php
										}
									?>
								</select>
								<select name="speedycache-timeout-rule-minute" style="display:none;">
									<?php
										for ($i=0; $i < 60; $i++){ 
											?>
											<option value="<?php echo esc_attr($i); ?>"><?php echo esc_html(str_pad($i, 2, '0', STR_PAD_LEFT)); ?></option>
											<?php
										}
									?>
								</select>
								<span><?php esc_html_e('delete the files', 'speedycache'); ?></span>
							</div>

							<div class="speedycache-modal-block">
								<p class="speedycache-server-time"><?php esc_html_e('Server Time', 'speedycache'); ?>: <?php echo esc_html(date("H:i:s")); ?></p>
							</div>
						</div>
						</form>
						<div class="speedycache-modal-footer">
							<button type="button" action="close">
								<span><?php esc_html_e('Submit', 'speedycache'); ?></span>
							</button>
						</div>
					</div>
				</div>

				<form method="post" name="wp_manager">
					<input type="hidden" value="timeout" name="speedycache_page">
					<div class="speedycache-timeout-rule-container"></div>
				</form>
			</div>
	
			<div class="speedycache-tab-exclude">
				<div class="speedycache-block">
					<div class="speedycache-block-title">
						<h2 ><?php esc_html_e('Exclude Pages', 'speedycache'); ?></h2>

						<button data-type="page" type="button" class="speedycache-add-new-exclude-button speedycache-btn" >
						<span><?php esc_html_e('Add New Rule', 'speedycache'); ?></span>
					</button>
					</div>

					<div class="speedycache-exclude-page-list">
					</div>
				</div>
				
				<div class="speedycache-block">
					<div class="speedycache-block-title">
						<h2><?php esc_html_e('Exclude User-Agents', 'speedycache'); ?></h2>
						<button data-type="useragent" type="button" class="speedycache-add-new-exclude-button speedycache-btn">
							<span><?php esc_html_e('Add New Rule', 'speedycache'); ?></span>
						</button>
					</div>

					<div class="speedycache-exclude-useragent-list">
					</div>
				</div>

				<div class="speedycache-block">
					<div class="speedycache-block-title">
						<h2><?php esc_html_e('Exclude Cookies', 'speedycache'); ?></h2>
						<button data-type="cookie" type="button" class="speedycache-add-new-exclude-button speedycache-btn" >
							<span><?php esc_html_e('Add New Rule', 'speedycache'); ?></span>
						</button>
					</div>

					<div class="speedycache-exclude-cookie-list">
					</div>
				</div>
				
				<div class="speedycache-block">
					<div class="speedycache-block-title">
						<h2><?php esc_html_e('Exclude CSS', 'speedycache'); ?></h2>
						<button data-type="css" type="button" class="speedycache-add-new-exclude-button speedycache-btn">
							<span><?php esc_html_e('Add New Rule', 'speedycache'); ?></span>
						</button>
					</div>
					<div class="speedycache-exclude-css-list">
					</div>
				</div>

				<div class="speedycache-block">
					<div class="speedycache-block-title">
						<h2><?php esc_html_e('Exclude JS', 'speedycache'); ?></h2>
						<button data-type="js" type="button" class="speedycache-add-new-exclude-button speedycache-btn">
							<span><?php esc_html_e('Add New Rule', 'speedycache'); ?></span>
						</button>
					</div>

					<div class="speedycache-exclude-js-list">
					</div>
				</div>	
				
				<div modal-id="speedycache-exclude" class="speedycache-modal">
					<div class="speedycache-modal-wrap">
						<div class="speedycache-modal-header">
							<div><?php esc_html_e('Exclude Page', 'speedycache'); ?></div>
							<div title="Close Modal" class="speedycache-close-modal">
								<span class="dashicons dashicons-no"></span>
							</div>
						</div>

						<div id="speedycache-wizard-exclude" class="speedycache-modal-content">
							<div class="speedycache-condition-text"><?php esc_html_e('If REQUEST_URI', 'speedycache'); ?></div>
							<form>
								<div>
									<select name="speedycache-exclude-rule-prefix" class="speedycache-full-width">
										<option selected="" value=""><?php esc_html_e('Select a Value', 'speedycache'); ?></option>
										<option value="homepage"><?php esc_html_e('Home Page', 'speedycache'); ?></option>
										<option value="category"><?php esc_html_e('Categories', 'speedycache'); ?></option>
										<option value="tag"><?php esc_html_e('Tags', 'speedycache'); ?></option>
										<option value="post"><?php esc_html_e('Posts', 'speedycache'); ?></option>
										<option value="page"><?php esc_html_e('Pages', 'speedycache'); ?></option>
										<option value="archive"><?php esc_html_e('Archives', 'speedycache'); ?></option>
										<option value="attachment"><?php esc_html_e('Attachments', 'speedycache'); ?></option>
										<option value="startwith"><?php esc_html_e('Starts With', 'speedycache'); ?></option>
										<option value="contain"><?php esc_html_e('Contains', 'speedycache'); ?></option>
										<option value="exact"><?php esc_html_e('Is Equal To', 'speedycache'); ?></option>
										<option value="googleanalytics"><?php esc_html_e('has Google Analytics Parameters', 'speedycache'); ?></option>
										<option value="woocommerce_items_in_cart"><?php esc_html_e('has Woocommerce Items in Cart', 'speedycache'); ?></option>
									</select>
								</div>
								<div class="speedycache-exclude-rule-line-middle">
									<input type="text" name="speedycache-exclude-rule-content" class="speedycache-full-width">
									<input type="hidden" name="speedycache-exclude-rule-type"/>
								</div>
							</form>
						</div>
						<div class="speedycache-modal-footer">
							<button type="button" action="close">
								<span>
									<?php esc_html_e('Submit', 'speedycache'); ?>
								</span>
							</button>
						</div>
					</div>
				</div>
				
				<form method="post" name="wp_manager">
					<input type="hidden" value="exclude" name="speedycache_page">
					<div class="speedycache-exclude-rule-container"></div>
					<!-- <div class="speedycache-option-wrap qsubmit">
						<div class="submit"><input type="submit" class="speedycache-btn speedycache-btn-primary" value="Submit"></div>
					</div> -->
				</form>
			</div>

			<div class="speedycache-tab-cdn">
				<div class="speedycache-snack-bar">
					<span class="speedycache-snack-bar-msg"><?php esc_html_e('CDN Settigs Saved', 'speedycache'); ?></span>
				</div>
				
				<?php
				if(!empty($cloudflare_integration_exist)){
					echo '<div class="speedycache-notice speedycache-notice-blue"><span class="dashicons dashicons-info-outline"></span> '.__('You are using Cloudflare so you should enable Cloudflare Integration. Please take a look at the following documentation', 'speedycache').' <a href="https://speedycache.com/docs/cdn/how-to-setup-cloudflare/" target="_blank"><strong>Check How to intergate CloudFlare</strong></a></div>';
				} ?>
				<div class="speedycache-block">
					<div class="speedycache-block-title">
						<h2><?php esc_html_e('CDN Settings', 'speedycache'); ?></h2>
					</div>
					
					<div class="speedycache-cdn-holder">
						<input type="radio" id="speedycache-cdn-tab-stackpath-input" name="speedycache-cdn-tab"/>
						<input type="radio" id="speedycache-cdn-tab-cloudflare-input" name="speedycache-cdn-tab"/>
						<input type="radio" id="speedycache-cdn-tab-bunny-input" name="speedycache-cdn-tab" checked/>
						<input type="radio" id="speedycache-cdn-tab-other-input" name="speedycache-cdn-tab"/>
						
						<div class="speedycache-cdn-tabs">
							<div speedycache-cdn-name="bunny" class="speedycache-cdn-tab">
								<label for="speedycache-cdn-tab-bunny-input">
									<div class="speedycache-cdn-tab-icon">
										<img src="<?php echo esc_url(SPEEDYCACHE_URL) . '/assets/images/bunny.svg';?>" height="32"/>
									</div>
									<div class="speedycache-cdn-tab-title">
										<div style="font-weight:bold;font-size:14px;">Bunny CDN</div>
										<p><?php esc_html_e('CDN to speed up your website', 'speedycache'); ?></p>
									</div>
								</label>	
							</div>
						
						
							<div speedycache-cdn-name="stackpath" class="speedycache-cdn-tab">
								<label for="speedycache-cdn-tab-stackpath-input">								
									<div class="speedycache-cdn-tab-icon">
										<i class="fab fa-stackpath"></i>
									</div>
									<div class="speedycache-cdn-tab-title">
										<div style="font-weight:bold;font-size:14px;">StackPath</div>
										<p><?php esc_html_e('Secure and accelerate your websites', 'speedycache'); ?></p>
									</div>
								</label>
							</div>

							<div speedycache-cdn-name="cloudflare" class="speedycache-cdn-tab">
								<label for="speedycache-cdn-tab-cloudflare-input">
									<div class="speedycache-cdn-tab-icon">
										<i class="fab fa-cloudflare"></i>
									</div>
									<div class="speedycache-cdn-tab-title">
										<div style="font-weight:bold;font-size:14px;">Cloudflare</div>
										<p><?php esc_html_e('CDN, DNS, DDoS protection and security', 'speedycache'); ?></p>
									</div>
								</label>	
							</div>
							
							<div speedycache-cdn-name="other" class="speedycache-cdn-tab">
								<label for="speedycache-cdn-tab-other-input">
									<div class="speedycache-cdn-tab-icon">
										<i class="fas fa-network-wired"></i>
									</div>
									<div class="speedycache-cdn-tab-title">
										<div style="font-weight:bold;font-size:14px;">Other CDN Providers</div>
										<p><?php esc_html_e('You can use any cdn provider.', 'speedycache'); ?></p>
									</div>
								</label>
							</div>
						</div>
						
						<div class="speedycache-cdn-tab-content">
							<div class="speedycache-cloudflare-settings">
								<form>
									<input type="hidden" name="id" value="cloudflare"/>
									<h3><?php esc_html_e('CloudFlare Settings', 'speedycache'); ?></h3>
									<?php echo speedycache_cdn_actions_tmpl('cloudflare'); ?>
									<hr/>
									<div class="speedycache-block">
										<h4><?php esc_html_e('Enter API Keys', 'speedycache'); ?></h4>	
										<p><?php echo wp_kses_post('Please enter your <strong>API Key</strong> below to to access Cloudflare APIs.', 'speedycache'); ?></p>
										<div class="speedycache-form-input" style="display: none;">
											<label for="cdn-url">
												<?php esc_html_e('Email', 'speedycache'); ?>:
												<input type="text" name="cdn_url" value="speedycache" class="speedycache-api-key" id="cdn-url"/>
											</label>
											<span class="speedycache-error-msg"></span>
										</div>
										<div class="speedycache-form-input">
											<label for="origin-url"><?php esc_html_e('API Token', 'speedycache'); ?>:
												<input type="text" name="origin_url" value="" class="speedycache-api-key" id="origin-url"/>
												<div id="speedycache-cdn-url-loading"><i class="fas fa-circle-notch fa-spin"></i></div>
											</label>
											<span class="speedycache-error-msg"></span>
										</div>
										<p class="speedycache-bottom-note"><a target="_blank" href="https://speedycache.com/docs/cdn/how-to-setup-cloudflare/"><?php esc_html_e('Note: Please read How to Integrate Cloudflare into speedycache', 'speedycache'); ?></a></p>
									</div>
									<div class="speedycache-block">
										<h4><?php esc_html_e('Disable Auto Minify', 'speedycache'); ?></h4>
										<p><?php esc_html_e('The Auto Minify options have been disabled automatically.', 'speedycache'); ?></p>
										
										<div class="speedycache-checkbox-list">
											<img src="<?php echo esc_url(SPEEDYCACHE_URL).'/assets/images/cloudflare-auto-minify.png'?>" style="width:100%;"/>
										</div>
									</div>
									<div class="speedycache-block">
										<h4><?php esc_html_e('Disable Rocket Loader', 'speedycache'); ?></h4>
										<p><?php esc_html_e('The Rocket Loader option has been disabled automatically.', 'speedycache'); ?></p>
										<div class="speedycache-checkbox-list">
											<img src="<?php echo esc_url(SPEEDYCACHE_URL).'/assets/images/cloudflare-rocketloader.png'?>" style="width:100%;"/>
										</div>
									</div>
									<div class="speedycache-block">
										<h4><?php esc_html_e('Browser Cache Expiration', 'speedycache'); ?></h4>
										<p><?php esc_html_e('Browser Cache Expiration option has been set as 6 months.', 'speedycache'); ?></p>
										<div class="speedycache-checkbox-list">
											<img src="<?php echo esc_url(SPEEDYCACHE_URL).'/assets/images/cloudflare-browsercache.png'?>" style="width:100%;"/>
										</div>
									</div>
									<div class="speedycache-cdn-save"><button class="speedycache-btn speedycache-btn-primary"><?php esc_html_e('Save Settings', 'speedycache'); ?></button></div>
								</form>
							</div>
							
							<div class="speedycache-bunny-settings">
								<form>
									<input type="hidden" name="id" value="bunny"/>
									<h3><?php esc_html_e('Bunny CDN Settings', 'speedycache'); ?></h3>
									<?php $action_btns = false;
									
										$action_btns = speedycache_cdn_actions_tmpl('bunny');
										if(!empty($action_btns)){
											echo $action_btns;
										}
									?>
									<hr/>
									<?php if(empty($action_btns)){ ?>
									<div class="speedycache-block">
										<h4><?php esc_html_e('Let\'s Get Started', 'speedycache'); ?></h4>
										<p>
											<?php echo wp_kses_post('Hi! If you don\'t have a <strong>Bunny CDN</strong> account, you can create one. If you already have, please continue...', 'speedycache'); ?>
										</p>
										
										<div class="speedycache-form-input" style="display:flex; align-items:center; text-align:center;">
											<a class="speedycache-green-button" href="https://panel.bunny.net/user/register/" target="_blank">
												<?php esc_html_e('Create a Bunny CDN Account', 'speedycache'); ?>
											</a>
										</div>
									</div>
									<?php } ?>
									<div class="speedycache-block">
										<h4><?php esc_html_e('Enter CDN Url', 'speedycache'); ?></h4>
										<p><?php echo wp_kses_post('Please enter your <strong>Pull Zone</strong> below to deliver your contents via CDN.', 'speedycache'); ?></p>
									
										<div class="speedycache-form-input">
											<label for="cdn-url"><?php esc_html_e('Pull Zone', 'speedycache'); ?>:
												<input type="text" name="cdn_url" value="" class="speedycache-api-key" id="cdn-url"/>
												<div id="speedycache-cdn-url-loading"><i class="fas fa-circle-notch fa-spin"></i></div>
											</label>
											
											<span class="speedycache-error-msg"></span>
										</div>
										<div class="speedycache-form-input">
											<label for="origin-url"><?php esc_html_e('Origin Url', 'speedycache'); ?>:
												<input type="text" name="origin_url" value="" class="speedycache-api-key" id="origin-url"/>
											</label>
										</div>
										
										<div class="speedycache-form-input">
											<label for="bunny_access_key"><?php esc_html_e('Access Key', 'speedycache'); ?>:
												<input type="text" name="bunny_access_key" value="" class="speedycache-api-key" id="bunny_access_key"/>
											</label>
										</div>
									</div>
									<div class="speedycache-block">
										<h4><?php esc_html_e('File Types', 'speedycache'); ?></h4>
										<p><?php esc_html_e('Specify the file types to host with the CDN.', 'speedycache'); ?></p>
										<?php speedycache_file_type(); ?>
									</div>
									
									<div class="speedycache-block">
										<?php speedycache_specify_source(); ?>
									</div>

									<div class="speedycache-block">
										<?php speedycache_exclude_source(); ?>
									</div>
									<div class="speedycache-cdn-save"><button class="speedycache-btn speedycache-btn-primary"><?php esc_html_e('Save Settings', 'speedycache'); ?></button></div>
								</form>
							</div>

							<div class="speedycache-other-settings">
								<form>
									<input type="hidden" name="id" value="other"/>
									<h3><?php esc_html_e('Other CDN Settings', 'speedycache'); ?></h3>
									<?php echo speedycache_cdn_actions_tmpl('other'); 
										
									?>
									<hr/>
									<div class="speedycache-block">
										<h4><?php esc_html_e('Enter CDN Url', 'speedycache'); ?></h4>
										<p><?php echo wp_kses_post('Please enter your <strong>CDN Url</strong> below to deliver your contents via CDN.', 'speedycache'); ?></p>
									
										<div class="speedycache-form-input">
											<label for="cdn-url"><?php esc_html_e('CDN Url', 'speedycache'); ?>:
												<input type="text" name="cdn_url" value="" class="speedycache-api-key" id="cdn-url"/>
												<div id="speedycache-cdn-url-loading"><i class="fas fa-circle-notch fa-spin"></i></div>
											</label>
											
											<span class="speedycache-error-msg"></span>
										</div>
										<div class="speedycache-form-input">
											<label for="origin-url"><?php esc_html_e('Origin Url', 'speedycache'); ?>:
												<input type="text" name="origin_url" value="" class="speedycache-api-key" id="origin-url"/>
											</label>
										</div>
									</div>
									<div class="speedycache-block">
										<h4><?php esc_html_e('File Types', 'speedycache'); ?></h4>
										<p><?php esc_html_e('Specify the file types to host with the CDN.', 'speedycache'); ?></p>
										<?php speedycache_file_type(); ?>
									</div>
									
									<div class="speedycache-block">
										<?php speedycache_specify_source(); ?>
									</div>

									<div class="speedycache-block">
										<?php speedycache_exclude_source(); ?>
									</div>
									<div class="speedycache-cdn-save"><button class="speedycache-btn speedycache-btn-primary"><?php esc_html_e('Save Settings', 'speedycache'); ?></button></div>
								</form>
							</div>

							<div class="speedycache-stackpath-settings">
								<form>
									<input type="hidden" name="id" value="stackpath"/>
									<h3><?php esc_html_e('StackPath Settings', 'speedycache'); ?></h3>
									<?php echo speedycache_cdn_actions_tmpl('stackpath'); ?>
									<hr/>
									<div class="speedycache-block">
										<h4><?php esc_html_e('Enter CDN Url', 'speedycache'); ?></h4>
										<p>
											<?php echo wp_kses_post('Please enter your <strong>StackPath CDN Url</strong> below to deliver your contents via StackPath.', 'speedycache'); ?>
										</p>	
										<div class="speedycache-form-input">
											<label for="cdn-url"><?php esc_html_e('CDN Url', 'speedycache'); ?>:
												<input type="text" name="cdn_url" value="" class="speedycache-api-key" id="cdn-url"/>
												<div id="speedycache-cdn-url-loading"><i class="fas fa-circle-notch fa-spin"></i></div>
											</label>
											
											<span class="speedycache-error-msg"></span>
										</div>
										<div class="speedycache-form-input">
											<label for="origin-url">
												<?php esc_html_e('Origin Url', 'speedycache') ?>:
												<input type="text" name="origin_url" value="" class="speedycache-api-key" id="origin-url"/>
											</label>
										</div>
									</div>
									<div class="speedycache-block">
										<h4><?php esc_html_e('File Types', 'speedycache'); ?></h4>		
										<p><?php esc_html_e('Specify the file types to host with the CDN.', 'speedycache'); ?></p>
										<?php speedycache_file_type(); ?>
									</div>
									<div class="speedycache-block">
										<?php speedycache_specify_source(); ?>
									</div>

									<div class="speedycache-block">
										<?php speedycache_exclude_source(); ?>
									</div>
									
									<div class="speedycache-cdn-save"><button class="speedycache-btn speedycache-btn-primary"><?php esc_html_e('Save Settings', 'speedycache'); ?></button></div>
								</form>
							</div>
						</div>
					</div>
				</div>
			</div>

			<div class="speedycache-tab-object">
			<?php
				speedycache_obj_settings();
			?>
			</div>

			<div class="speedycache-tab-db">
				<div class="speedycache-block">
					<?php if(!defined('SPEEDYCACHE_PRO')){ ?>
					<div class="speedycache-disabled-block">
						<div class="speedycache-disabled-block-info">
							<i class="fas fa-lock"></i>
							<p><?php esc_html_e('Only available in Pro version', 'speedycache'); ?></p>
							<a href="https://speedycache.com/pricing" target="_blank"><?php esc_html_e('Buy Pro Version Now', 'speedycache'); ?></a>
						</div>
					</div>
					<?php } ?>
					<div class="speedycache-block-title">
						<h2><?php esc_html_e('Database Cleanup', 'speedycache'); ?></h2>
					</div>
					
					<div>
						<div class="speedycache-db-page">
							<div speedycache-db-name="all_warnings" class="speedycache-card">
								<div class="speedycache-card-body">
									<div class="speedycache-db-icon speedycache-db-clean">
										<i class="fas fa-database"></i>
									</div>
									<div class="speedycache-db-info db">
										<div><?php esc_html_e('ALL', 'speedycache'); ?> <span class="speedycache-db-number">(0)</span></div>
										<p><?php esc_html_e('Run the all options', 'speedycache'); ?></p>
									</div>
									<div class="meta"></div>
								</div>
							</div>

							<div speedycache-db-name="post_revisions" class="speedycache-card">
								<div class="speedycache-card-body">
									<div class="speedycache-db-icon speedycache-db-clean">
										<i class="fas fa-file-word"></i>
									</div>
									<div class="speedycache-db-info db">
										<div><?php esc_html_e('Post Revisions', 'speedycache');?> <span class="speedycache-db-number">(0)</span></div>
										<p><?php esc_html_e('Clean the all post revisions', 'speedycache'); ?></p>
									</div>
								<div class="meta"></div>
								</div>
							</div>

							<div speedycache-db-name="trashed_contents" class="speedycache-card">
								<div class="speedycache-card-body">
									<div class="speedycache-db-icon speedycache-db-clean">
										<i class="fas fa-trash"></i>
									</div>
									<div class="speedycache-db-info db">
										<div><?php esc_html_e('Trashed Contents', 'speedycache'); ?><span class="speedycache-db-number">(0)</span></div>
										<p><?php esc_html_e('Clean the all trashed posts & pages', 'speedycache'); ?></p>
									</div>
									<div class="meta"></div>
								</div>
							</div>

							<div speedycache-db-name="trashed_spam_comments" class="speedycache-card">
								<div class="speedycache-card-body">
									<div class="speedycache-db-icon speedycache-db-clean">
										<i class="fas fa-comments"></i>
									</div>
									<div class="speedycache-db-info db">
										<div><?php esc_html_e('Trashed & Spam Comments', 'speedycache'); ?> <span class="speedycache-db-number">(0)</span></div>
										<p><?php esc_html_e('Clean the all comments from trash & spam', 'speedycache'); ?></p>
									</div>
									<div class="meta"></div>
								</div>
							</div>

							<div speedycache-db-name="trackback_pingback" class="speedycache-card">
								<div class="speedycache-card-body">
									<div class="speedycache-db-icon speedycache-db-clean">
										<i class="fas fa-exchange-alt"></i>
									</div>
									<div class="speedycache-db-info db">
										<div><?php esc_html_e('Trackbacks and Pingbacks', 'speedycache'); ?> <span class="speedycache-db-number">(0)</span></div>
										<p><?php esc_html_e('Clean the all trackbacks and pingbacks', 'speedycache'); ?></p>
									</div>
									<div class="meta"></div>
								</div>
							</div>

							<div speedycache-db-name="transient_options" class="speedycache-card">
								<div class="speedycache-card-body">
									<div class="speedycache-db-icon speedycache-db-clean">
										<i class="fas fa-history"></i>
									</div>
									<div class="speedycache-db-info db">
										<div style="font-weight:bold;font-size:14px;"><?php esc_html_e('Transient Options', 'speedycache'); ?> <span class="speedycache-db-number">(0)</span></div>
										<p><?php esc_html_e('Clean the all transient options', 'speedycache'); ?></p>
									</div>
									<div class="meta"></div>
								</div>
							</div>
							<div speedycache-db-name="expired_transient" class="speedycache-card">
								<div class="speedycache-card-body">
									<div class="speedycache-db-icon speedycache-db-clean">
										<i class="fas fa-history"></i>
									</div>
									<div class="speedycache-db-info db">
										<div style="font-weight:bold;font-size:14px;"><?php esc_html_e('Expired Transients', 'speedycache'); ?> <span class="speedycache-db-number">(0)</span></div>
										<p><?php esc_html_e('Clean the expired transients', 'speedycache'); ?></p>
									</div>
									<div class="meta"></div>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
			
			<div class="speedycache-tab-image">
				<?php
				if(defined('SPEEDYCACHE_PRO') && class_exists('\SpeedyCache\Image')){
					\SpeedyCache\Image::statics();
					\SpeedyCache\Image::settings();
					\SpeedyCache\Image::list_image_html();
				} ?>
				<div id="revert-loader"></div>
				<script type="text/javascript">
					
				</script>
			</div>
			
			<div class="speedycache-tab-bloat">
				<form method="post">
					<?php wp_nonce_field('speedycache_nonce', 'security');
					
					$bloat_options = array(
						'disable_xmlrpc' => array(
							'id' => 'speedycache_disable_xmlrpc',
							'title' => __('Disable XML RPC', 'speedycache'),
							'description' => __('XML-RPC can cause performance and security issues'),
						),
						'remove_gfonts' => array(
							'id' => 'speedycache_remove_gfonts',
							'title' => __('Disable Google Fonts', 'speedycache'),
							'description' => __('Use users system fonts to prevent loading of fonts from server', 'speedycache'),
						),
						'disable_jmigrate' => array(
							'id' => 'speedycache_disable_jmigrate',
							'title' => __('Disable jQuery Migrate', 'speedycache'),
							'description' => __('Disable jQuery Migrate for better speed.', 'speedycache'),
							'docs' => 'https://speedycache.com/docs/bloat-remover/how-to-remove-jquery-migrate-in-wordpress/',
						),
						'disable_dashicons' => array(
							'id' => 'speedycache_disable_dashicons',
							'title' => __('Disable DashIcons', 'speedycache'),
							'description' => __('DashIcons are used on WordPress admin and might not be used on Front End.', 'speedycache'),
						),
						'disable_gutenberg' => array(
							'id' => 'speedycache_disable_gutenberg',
							'title' => __('Disable Gutenberg', 'speedycache'),
							'description' => __('Decouple Gutenberg if you use another page builder.', 'speedycache'),
						),
						'disable_block_css' => array(
							'id' => 'speedycache_disable_block_css',
							'title' => __('Disable Block Editor CSS', 'speedycache'),
							'description' => __('Some themes might not use Block Editor CSS on the front.', 'speedycache'),
						),
						'disable_oembeds' => array(
							'id' => 'speedycache_disable_oembeds',
							'title' => __('Disable OEmbeds', 'speedycache'),
							'description' => __('OEmbeds increases load on site if a lot of embeds are being used.', 'speedycache'),
						),
						'update_heartbeat' => array(
							'id' => 'speedycache_update_heartbeat',
							'title' => __('Update Heartbeat', 'speedycache'),
							'description' => __('Change how frequently heartbeat is checked.', 'speedycache'),
							'settings' => 'speedycache_update_heartbeat',
						),
						'limit_post_revision' => array(
							'id' => 'speedycache_limit_post_revision',
							'title' => __('Limit Post Revision', 'speedycache'),
							'description' => __('Change how many post revision you want to keep.', 'speedycache'),
							'settings' => 'speedycache_limit_post_revision',
						),
						'disable_cart_fragment' => array(
							'id' => 'speedycache_disable_cart_fragment',
							'title' => __('Disable Cart Fragments', 'speedycache'),
							'description' => __('Disable WooCommerce cart fragments for better performance.', 'speedycache'),
						),
						'disable_woo_assets' => array(
							'id' => 'speedycache_disable_woo_assets',
							'title' => __('Disable WooCommerce Assets', 'speedycache'),
							'description' => __('Disables WooCommerce assets to reduce unwanted asset loading.', 'speedycache'),
							'docs' => 'https://speedycache.com/docs/bloat-remover/how-to-remove-woocommerce-assets/',
						),
						'disable_rss' => array(
							'id' => 'speedycache_disable_rss',
							'title' => __('Disable RSS feeds', 'speedycache'),
							'description' => __('Disable RSS feeds to reduce request which use server resources.', 'speedycache'),
						),
					);
					?>

					<input type="hidden" value="bloat" name="speedycache_page">
					<div class="speedycache-block">
						<div class="speedycache-block-title">
							<h2><?php esc_html_e('Bloat Settings', 'speedycache'); ?></h2>
						</div>
						<div class="speedycache-option-group">
							<?php 
							if(defined('SPEEDYCACHE_PRO')){
								foreach($bloat_options as $bloat_key => $bloat_option){
									echo '<div class="speedycache-option-wrap">
									<label for="'.esc_attr($bloat_option['id']).'" class="speedycache-custom-checkbox">
										<input type="checkbox" id="'.esc_attr($bloat_option['id']).'" name="'.esc_attr($bloat_option['id']).'" '. (!empty($speedycache->bloat[$bloat_key]) ? ' checked' : '').'/>
										<div class="speedycache-input-slider"></div>
									</label>
									<div class="speedycache-option-info">
										<span class="speedycache-option-name"><span>'.esc_html($bloat_option['title']). '</span>';
										
										// Docs Link here
										if(isset($bloat_option['docs'])){
											echo '<a href="'.esc_url($bloat_option['docs']).'" target="_blank"><span class="dashicons dashicons-info" style="font-size:14px"></span></a>';
										}
										
										// Setting if any
										if(isset($bloat_option['settings'])){
										echo '<span class="speedycache-modal-settings-link" setting-id="'.esc_attr($bloat_option['settings']).'" style="display:'. (!empty($speedycache->bloat[$bloat_key]) ? 'inline-block' : 'none').';">- '.esc_html__('Settings', 'speedycache').'</span>';
										}
										echo '</span>
										<span class="speedycache-option-desc">'. $bloat_option['description'].'</span> 
									</div>
								</div>';
								}
							} else {
								echo '<div class="speedycache-disabled-block">
									<div class="speedycache-disabled-block-info">
										<i class="fas fa-lock"></i>
										<p>Only available in Pro version</p>
										<a href="https://speedycache.com/pricing" target="_blank">Buy Pro Version Now</a>
									</div>
								</div>';

								foreach($bloat_options as $bloat_key => $bloat_option){
									echo '<div class="speedycache-option-wrap">
									<label class="speedycache-custom-checkbox">
										<input type="checkbox"/>
										<div class="speedycache-input-slider"></div>
									</label>
									<div class="speedycache-option-info">
										<span class="speedycache-option-name">'.esc_html($bloat_option['title']). '</span>
										<span class="speedycache-option-desc">'. $bloat_option['description'].'</span> 
									</div>
								</div>';
								}
							}
							?>
						</div>
						<div class="speedycache-option-wrap speedycache-submit-btn">
							<input type="submit" name="submit" value="Save Settings" class="speedycache-btn speedycache-btn-primary">
						</div>
					</div>
					
					<div modal-id="speedycache_limit_post_revision" class="speedycache-modal">
						<div class="speedycache-modal-wrap">
							<div class="speedycache-modal-header">
								<div><?php esc_html_e('Limit Post Revision', 'speedycache'); ?></div>
								<div title="Close Modal" class="speedycache-close-modal">
									<span class="dashicons dashicons-no"></span>
								</div>
							</div>
							<div class="speedycache-modal-content">
								<label for="speedycache_post_revision_count"><?php esc_html_e('Select Post Revision Count', 'speedycache'); ?></label>
								<select id="speedycache_post_revision_count" name="speedycache_post_revision_count" value="<?php (!empty($speedycache->bloat['post_revision_count']) ? esc_attr($speedycache->bloat['post_revision_count']) : ''); ?>">
									<?php 
										$post_revision_opts = array(
											'disable' => esc_html__('Disable', 'speedycache'),
											'1' => '1',
											'2' => '2',
											'3' => '3',
											'4' => '4',
											'5' => '5',
											'10' => '10',
											'20' => '20',
										);
										
										foreach($post_revision_opts as $value => $post_revision_opt){
											$selected = '';
											
											if(!empty($speedycache->bloat['post_revision_count']) && $speedycache->bloat['post_revision_count'] == $value){
												$selected = 'selected';
											} elseif(empty($speedycache->bloat['post_revision_count']) && $value == '10'){
												$selected = 'selected';
											}

											echo '<option value="'.esc_attr($value).'" '.esc_attr($selected).'>'.esc_html($post_revision_opt).'</option>';
										}
									?>
								</select>
				
							</div>
							<div class="speedycache-modal-footer">
								<button type="button" action="close">
									<span><?php esc_html_e('Submit', 'speedycache'); ?></span>
								</button>
							</div>
						</div>
					</div>
					
					<!-- Modal Settings for heartbeat of WordPress -->
					<div modal-id="speedycache_update_heartbeat" class="speedycache-modal">
						<div class="speedycache-modal-wrap">
							<div class="speedycache-modal-header">
								<div><?php esc_html_e('Update HeartBeat', 'speedycache'); ?></div>
								<div title="Close Modal" class="speedycache-close-modal">
									<span class="dashicons dashicons-no"></span>
								</div>
							</div>
							<div class="speedycache-modal-content">
								<?php 
								$heartbeat_modes = array(
									'15' => esc_html__('15 Seconds(Default)', 'speedycache'),
									'30' => esc_html__('30 seconds', 'speedycache'),
									'45' => esc_html__('45 Seconds', 'speedycache'),
									'60' => esc_html__('60 seconds', 'speedycache'),
									'120' => esc_html__('2 Minutes', 'speedycache'),
								);
								
								$disable_heartbeat = array(
									'dont' => esc_html__('Do not Disable', 'speedycache'),
									'disable' => esc_html__('Disable', 'speedycache'),
									'editor' => esc_html__('Allow on Editor only', 'speedycache'),
								);

								echo '<table>
								
								<tr>
								<td style="text-align:left;">
								<label for="speedycache_heartbeat_backend">'.esc_html__('Heartbeat Frequency', 'speedycache').'</label></td>
								<td><select id="speedycache_heartbeat_frequency" name="speedycache_heartbeat_frequency" value="'.(!empty($speedycache->bloat['heartbeat_frequency']) ? esc_attr($speedycache->bloat['heartbeat_frequency']) : '').'">';
									foreach($heartbeat_modes as $value => $heartbeat_mode){
										$selected = '';
										
										if(!empty($speedycache->bloat['heartbeat_frequency']) && $speedycache->bloat['heartbeat_frequency'] == $value){
											$selected = 'selected';
										} elseif(empty($speedycache->bloat['heartbeat_frequency']) && $value == '120'){
											$selected = 'selected';
										}

										echo '<option value="'.esc_attr($value).'" '.esc_attr($selected).'>'.esc_html($heartbeat_mode).'</option>';
									}
								echo '</select></td></tr>';

								echo '<tr><td style="text-align:left;"><label for="speedycache_heartbeat_editor">'.esc_html__('Disable Heartbeat', 'speedycache').'</label></td>
								<td><select id="speedycache_disable_heartbeat" name="speedycache_disable_heartbeat" value="'.(!empty($speedycache->bloat['disable_heartbeat']) ? esc_attr($speedycache->bloat['disable_heartbeat']) : '').'">';
									foreach($disable_heartbeat as $value => $disable_mode){
										$selected = '';
										
										if(!empty($speedycache->bloat['disable_heartbeat']) && $speedycache->bloat['disable_heartbeat'] == $value){
											$selected = 'selected';
										} elseif(empty($speedycache->bloat['disable_heartbeat']) && $value == 'dont'){
											$selected = 'selected';
										}

										echo '<option value="'.esc_attr($value).'" '.esc_attr($selected).'>'.esc_html($disable_mode).'</option>';
									}
								echo '</select></td></tr></table>';
 
								?>
								
				
							</div>
							<div class="speedycache-modal-footer">
								<button type="button" action="close">
									<span><?php esc_html_e('Submit', 'speedycache'); ?></span>
								</button>
							</div>
						</div>
					</div>
				</form>
			</div>
			
			<div class="speedycache-tab-support">
				<div style="width:70%; margin:20px auto; display:flex; justify-content:center; flex-direction:column; align-items:center; line-height:1.5;">
					<img src="<?php echo esc_url(SPEEDYCACHE_URL) .'/assets/images/speedycache-black.png'?>" width="200"/>
					<h2><?php esc_html_e('You can contact the SpeedyCache Team via email. Our email address is', 'speedycache'); ?> <a href="mailto:support@speedycache.com">support@speedycache.com</a> <?php esc_html_e('or through Our Premium Support Ticket System at', 'speedycache'); ?> <a href="https://softaculous.deskuss.com/open.php?topicId=19" target="_blank">here</h2>
				</div>
			</div>
		
			<div modal-id="speedycache-modal-permission" class="speedycache-modal">
				<div class="speedycache-modal-wrap">
					<div class="speedycache-modal-header">
						<div><?php esc_html_e('Warning', 'speedycache'); ?></div>
						<div title="Close Modal" class="speedycache-close-modal">
							<span class="dashicons dashicons-no"></span>
						</div>
					</div>
					<div class="speedycache-modal-content speedycache-info-modal">
						<p><?php esc_html_e('The cache has <u>NOT</u> been deleted because of permissions problem please', 'speedycache'); ?> <a href='http://speedycache.com/docs/delete-cache-problem-related-to-permission/' target='_blank'><?php esc_html_e('Read More', 'speedycache'); ?></a></p>
					</div>
					<div class="speedycache-modal-footer">
						<button type="button" action="close">
							<span><?php esc_html_e('Submit', 'speedycache'); ?></span>
						</button>
					</div>
				</div>
			</div>
			
			
			<div modal-id="speedycache-modal-toolbarsettings" class="speedycache-modal">
				<div class="speedycache-modal-wrap">
					<div class="speedycache-modal-header">
							<div><?php esc_html_e('Toolbar Settings', 'speedycache'); ?></div>
							<div title="Close Modal" class="speedycache-close-modal">
								<span class="dashicons dashicons-no"></span>
							</div>
					</div>
					<div class="speedycache-modal-content speedycache-info-modal">
						<h3><?php esc_html_e('Authorities', 'speedycache'); ?></h3>		
						<p><?php esc_html_e('This feature allows you to show the clear cache button which exists on the admin toolbar based on user roles.', 'speedycache'); ?> <a target="_blank" href="https://speedycache.com/docs/caching/how-to-delete-cache-or-minified-files/"><span class="dashicons dashicons-info"></span></a></p>

						<?php
							global $wp_roles;

							if(!isset($wp_roles)){
								$wp_roles = new WP_Roles();
							}

							$speedycache_role_names = $wp_roles->get_names();

							foreach($speedycache_role_names as $key => $value){
								if($key == 'administrator'){
									continue;
								}

								$speedycache_toolbar_element_id = 'speedycache_toolbar_'.$key;
								?>

								<div class="speedycache-form-input">
									<label for="<?php echo esc_attr($speedycache_toolbar_element_id); ?>">
										<input type="checkbox" id="<?php echo esc_attr($speedycache_toolbar_element_id); ?>" name="<?php echo esc_attr($speedycache_toolbar_element_id); ?>"/>
										<?php esc_html_e($value); ?>
									</label>
								</div>
								<?php
							}
						?>
					</div>
					<div class="speedycache-modal-footer">
						<button type="button" action="close">
							<span><?php esc_html_e('Submit', 'speedycache'); ?></span>
						</button>
					</div>
				</div>
			</div>
			
			<div modal-id="speedycache-modal-db-confirmation" class="speedycache-modal">
				<div class="speedycache-modal-wrap">
					<div class="speedycache-modal-content">
						<i class="fas fa-info-circle"></i>
						<h1><?php esc_html_e('Clean up the Database', 'speedycache'); ?></h1>
						<p><?php esc_html_e('Once deleted the changes won\'t be reversible.', 'speedycache'); ?></p>
						<div class="speedycache-modal-db-actions">
							<button class="speedycache-btn speedycache-db-confirm-yes">Yes</button>
							<button class="speedycache-btn speedycache-db-confirm-no">No</button>
						</div>
					</div>
				</div>
			</div>
		</div>
			<?php 
				if(!defined('SITEPAD')){
					speedycache_promotion_tmpl(); 
				}?>
		</div>
	<?php
	if(!empty(speedycache_optserver('SERVER_SOFTWARE')) && !preg_match('/iis/i', speedycache_optserver('SERVER_SOFTWARE')) && !preg_match('/nginx/i', speedycache_optserver('SERVER_SOFTWARE'))){
		if(!isset($_POST['speedycache_page'])){
			speedycache_check_htaccess();
		}
	}
	
	speedycache_page_footer();
}

function speedycache_save_button(){
	return '<div class="speedycache-cdn-save"><button></button></div>';
}

function speedycache_specify_source(){
	?>
	<h4><?php esc_html_e('Specify Sources', 'speedycache'); ?></h4>
	<p><?php esc_html_e('If you want some of the sources instead of all the sources to be served via CDN, you can specify the sources. If a source url contains any keyword below, it is served via CDN.', 'speedycache'); ?></p>
	<div class="speedycache-form-input">		
		<label>
			<?php esc_html_e('Add Keyword', 'speedycache'); ?>
			<input class="speedycache-specify-source-keyword speedycache-full-width speedycache-keyword-input" data-target="speedycache_specify_source_keywords" type="text" placeholder="Add Keyword">
			<span class="speedycache-input-desc"><?php esc_html_e('Use Comma to create new keyword', 'speedycache'); ?></span>
			<div class="speedycache-tags-holder">
			</div>
			<input type="hidden" value="" id="speedycache_specify_source_keywords" name="keywords">
		</label>
	</div>
<?php }


function speedycache_file_type(){
	?>
	<div class="speedycache-checkbox-list">
	<?php
		$types = array('aac', 'css', 'eot', 'gif', 'jpeg', 'js', 'jpg', 'less', 'mp3', 'mp4', 'ogg', 'otf', 'pdf', 'png', 'svg', 'swf', 'ttf', 'webm', 'webp', 'woff', 'woff2');

        foreach($types as $key => $value){
            ?>
            <label for="file-type-<?php echo esc_attr($value); ?>">
                <input id="file-type-<?php echo esc_attr($value); ?>" type="checkbox" value="<?php echo esc_attr($value); ?>" checked="" /><span class="">*.<?php echo esc_html($value); ?></span>
            </label>
            <?php
        }
	?>
	</div>
<?php }

function speedycache_exclude_source(){
	?>
	<h4><?php esc_html_e('Exclude Sources', 'speedycache'); ?></h4>
	<p><?php esc_html_e('If you want some of the sources NOT to be served via CDN, you can specify the sources. If a source url contains any keyword below, it is NOT served via CDN.', 'speedycache'); ?></p>

	<div class="speedycache-form-input">		
		<label>
			<?php esc_html_e('Add Keyword', 'speedycache'); ?>
			<input class="speedycache-exclude-source-keyword speedycache-full-width speedycache-keyword-input" data-target="speedycache_exclude_source_keywords" type="text" placeholder="Add Keyword">
			<span class="speedycache-input-desc"><?php esc_html_e('Use Comma to create new keyword', 'speedycache'); ?></span>
			<div class="speedycache-tags-holder">
			</div>
			<input type="hidden" value="" id="speedycache_exclude_source_keywords" name="excludekeywords">
		</label>
	</div>
<?php }


function speedycache_cdn_actions_tmpl($cdn){
	$cdn_values = get_option('speedycache_cdn');
	$action_html = '';
	
	if(empty($cdn) || empty($cdn_values)){
		return $action_html;
	}
	
	foreach($cdn_values as $value){
		if($value['id'] == $cdn && !empty($value['cdn_url'])){
			$action_html .= '<div class="speedycache-cdn-actions">';
			if(isset($value['status']) && $value['status'] == 'pause'){
				$action_html .= '<button class="speedycache-cdn-start" title="Start CDN">Start</button>';
			} else{
				$action_html .= '<button class="speedycache-cdn-pause" title="Pause CDN">Pause</button>';
			}
			
			$action_html .= '<button class="speedycache-cdn-stop" title="Stop CDN">Stop</button>';
			$action_html .= '</div>';
		}
	}
	
	return $action_html;
}

function speedycache_check_htaccess(){
	global $speedycache;
	
	$path = speedycache_get_abspath();
	
	if(!is_writable($path . '.htaccess') && count($_POST) > 0){
		?>
		<div modal-id="speedycache-modal-htaccess" class="speedycache-modal">
			<div class="speedycache-modal-wrap">
				<div class="speedycache-modal-header">
					<div><?php esc_html_e('Manually Modify .htaccess', 'speedycache'); ?></div>
					<div title="Close Modal" class="speedycache-close-modal">
						<span class="dashicons dashicons-no"></span>
					</div>
				</div>
				<div class="speedycache-modal-content speedycache-info-modal">
					<h3><?php esc_html_e('.htaccess is not writeable', 'speedycache'); ?></h3>
					<p><?php esc_html_e('1. Copy the rules from the textarea below', 'speedycache'); ?></p>
					<p><?php esc_html_e('2. Remove everything from .htaccess', 'speedycache'); ?></p>
					<p><?php esc_html_e('3. Paste the rules', 'speedycache'); ?></p>
					<div class="speedycache-form-input">
						<div>
							<label class="speedycache-htaccess-label"></label>
						</div>
						<div>
							<textarea onclick="this.focus();this.select()" class="speedycache-readonly-textarea" readonly="readonly" rows="10" cols="54" style="overflow-x: hidden;">ff</textarea>
						</div>
					</div>
				</div>
				<div class="speedycache-modal-footer">
					<button type="button" action="close">
						<span><?php esc_html_e('Submit', 'speedycache'); ?></span>
					</button>
				</div>
			</div>
		</div>

		<?php
		$htaccess = @file_get_contents($path . '.htaccess');
		
		if(isset($speedycache->options['lbc'])){
			$htaccess = \SpeedyCache\htaccess::browser_cache($htaccess, array('speedycache_lbc' => 'on'));
		}
		if(isset($speedycache->options['gzip'])){
			$htaccess = \SpeedyCache\htaccess::gzip($htaccess, array('speedycache_gzip' => 'on'));
		}
		if(isset($speedycache->options['status'])){
			$htaccess = \SpeedyCache\htaccess::rewrite_rule($htaccess, array('speedycache_status' => 'on'));
		}

		$htaccess = preg_replace("/\n+/", "\n", $htaccess);

		echo '<noscript id="speedycache-htaccess-data">' . esc_html($htaccess) . '</noscript>';
		echo '<noscript id="speedycache-htaccess-path-data">' . esc_html($path) . '.htaccess' . '</noscript>';
	}
}

function speedycache_promotion_tmpl(){
	
	global $speedycache;
	
	if(wp_is_mobile()){
		return;
	}
	
	$brand_data = !empty($speedycache->settings['brand_data']) ? $speedycache->settings['brand_data'] : [];
	if(defined('SPEEDYCACHE_PRO') && !empty($brand_data)){

		echo '<div class="speedycache-promotion" style="width:13%; margin-top: 39px;">
			<div class="speedycache-promotion-content" style="padding-top:20px; margin-bottom:15px;">
				<h4 style="margin:0 0 15px 0; padding:0; text-align:center;">Brought to you by</h4>
				<div class="speedycache-co-brand-images">
					<a href="https://speedycache.com/" target="_blank"><img src="'.esc_url(SPEEDYCACHE_URL . '/assets/images/speedycache-brand.png') .'"  width="100%" /></a>';

					if(!empty($brand_data['img'])){
						echo '<a href="'.(!empty($brand_data['url']) ? esc_url($brand_data['url']) : '#').'" target="_blank"><img src="'.esc_url($brand_data['img']).'" width="100%"/></a>';
					}
				echo '</div>
			</div>
			<div class="speedycache-promotion-content speedycache-doc-block">
				<h2 style="color:white; margin:0 0 5px 0; padding:0;">'.__('Documentation', 'speedycache').'</h2>
				<p>'.__('If you face any issue or need help with the settings check our docs', 'speedycache').'</p>
				<a style="color:white; margin:0 0 5px 0; padding:0;" href="https://speedycache.com/docs/" target="_blank">Read Docs</a>
			</div>
		</div>';
		
		return;
	} 
	
?>
	<div class="speedycache-promotion" style="width:13%; margin-top: 10px;">
		<div class="speedycache-promotion-content">
				<h2 class="hndle ui-sortable-handle">
					<span><a target="_blank" href="https://pagelayer.com/?utm_source=speedycache_plugin"><img src="<?php echo esc_url(SPEEDYCACHE_URL); ?>/assets/images/pagelayer_product.png" width="100%" /></a></span>
				</h2>
				<div>
					<em>The Best WordPress <b>Site Builder</b> </em>:<br>
					<ul>
						<li>Drag & Drop Editor</li>
						<li>Widgets</li>
						<li>In-line Editing</li>
						<li>Styling Options</li>
						<li>Animations</li>
						<li>Easily customizable</li>
						<li>Real Time Design</li>
						<li>And many more ...</li>
					</ul>
					<center><a class="speedycache-btn speedycache-btn-primary" target="_blank" href="https://pagelayer.com/?utm_source=speedycache_plugin">Visit Pagelayer</a></center>
				</div>
		</div>
	
		<div class="speedycache-promotion-content" style="margin-top: 20px;">
			<h2 class="hndle ui-sortable-handle">
				<span><a target="_blank" href="https://loginizer.com/?utm_source=speedycache_plugin"><img src="<?php echo esc_url(SPEEDYCACHE_URL); ?>/assets/images/loginizer_product.png" width="100%" /></a></span>
			</h2>
			<div>
				<em><?php echo wp_kses_post('Protect your WordPress website from <b>unauthorized access and malware</b>', 'speedycache'); ?> </em>:<br>
				<ul>
					<li>BruteForce Protection</li>
					<li>reCaptcha</li>
					<li>Two Factor Authentication</li>
					<li>Black/Whitelist IP</li>
					<li>Detailed Logs</li>
					<li>Extended Lockouts</li>
					<li>2FA via Email</li>
					<li>And many more ...</li>
				</ul>
				<center><a class="speedycache-btn speedycache-btn-primary" target="_blank" href="https://loginizer.com/?utm_source=speedycache_plugin">Visit Loginizer</a></center>
			</div>
		</div>
	</div>
<?php
}


function speedycache_add_javascript(){
	global $speedycache;
	
	wp_enqueue_script('jquery-ui-sortable');
	
	$speedycache_ajax_url = admin_url().'admin-ajax.php';
	$speedycache_nonce = wp_create_nonce('speedycache_nonce');
	$speedycache_schedules = wp_get_schedules();
	$preload_order = !isset($speedycache->options['preload_order']) ? '' : $speedycache->options['preload_order'];
	$lang = !isset($speedycache->options['language']) ? 'en' : $speedycache->options['language'];

	wp_enqueue_script('speedycache_js', SPEEDYCACHE_URL . '/assets/js/speedycache.js', array(), SPEEDYCACHE_VERSION, false);
	
	wp_localize_script('speedycache_js', 'speedycache_ajax', array(
		'url' => $speedycache_ajax_url,
		'nonce' => $speedycache_nonce,
		'schedules' => $speedycache_schedules,
		'home_url' => home_url(),
		'timeout_rules' => speedycache_get_timeout_rules(),
		'exclude_rules' => get_option('speedycache_exclude'),
		'cdn' => get_option('speedycache_cdn'),
		'preload_order' => $preload_order,
		'lang' => $lang,
		'sitepad' => defined('SITEPAD'),
		'premium' => defined('SPEEDYCACHE_PRO') ? true : false
	));
}

function speedycache_options_page_request(){
	
	$post = speedycache_clean($_POST);
	
	if(empty($post)){
		return;
	}
	
	if(empty($post['speedycache_page'])){
		return;
	}

	include_once ABSPATH .WPINC. '/capabilities.php';
	include_once ABSPATH .WPINC. '/pluggable.php';

	if(isset($post['submit']) && !wp_verify_nonce($post['security'], 'speedycache_nonce')){
		speedycache_notify(array('Security Check Failed', 'error'));
		return;
	}
	
	if(!current_user_can('manage_options')){
		speedycache_notify(array('Must be admin to perform this task', 'error'));
		return;
	}
	
	switch($post['speedycache_page']){
		case 'options':
			speedycache_exclude_urls();
			speedycache_save_settings();
			break;
			
		case 'object_cache':
			speedycache_obj_save();
			break;
			
		case 'bloat':
			speedycache_save_bloat();
			break;

		case 'delete_cache':				
			$delete_fonts = false;
			$delete_minified = false;
			
			if(isset($post['speedycache_delete_fonts'])){
				$delete_fonts = true;
			}
			
			if(isset($post['speedycache_delete_minified'])){
				$delete_minified = true;
			}

			speedycache_delete_cache($delete_minified, $delete_fonts);
			
			// Deletes Gravatars
			if(!empty($post['speedycache_delete_gravatars'])){
				\SpeedyCache\Gravatar::delete();
			}

			break;
	}
}

function speedycache_get_timeout_rules(){
	$schedules_rules = array();
	$crons = _get_cron_array();

	foreach((array)$crons as $cron_key => $cron_value){
		foreach((array) $cron_value as $hook => $events){
			
			if(!preg_match('/^speedycache(.*)/', $hook, $id)){
				continue;
			}

			if(!empty($id[1]) && !preg_match("/^\_(\d+)$/", $id[1])){
				continue;
			}

			$tmp_array = array();
			foreach((array) $events as $event_key => $event){
				if(empty($id[1])){
					break;
				}

				// new cronjob which is (speedycache_d+)
				$tmp_std = $event['args'][0];

				$tmp_array = array(
					'schedule' => $event['schedule'],
					'prefix' => $tmp_std['prefix'],
					'content' => esc_attr($tmp_std['content'])
				);

				if(isset($tmp_std['hour']) && isset($tmp_std['minute'])){
					$tmp_array['hour'] = $tmp_std['hour'];
					$tmp_array['minute'] = $tmp_std['minute'];
				}
			}
	
			array_push($schedules_rules, $tmp_array);
		}
	}
	
	return $schedules_rules;
}

function speedycache_test_page(){

	$old_speed = get_option('speedycache_old_speed', []);
	$new_speed = get_option('speedycache_new_speed', []);

	$stroke_old = !empty($old_speed['score']) ? 100 - $old_speed['score'] : 0;
	$stroke_new = !empty($new_speed['score']) ? 100 - $new_speed['score'] : 0;
	
	$old_color = speedycache_get_test_color(!empty($old_speed['score']) ? $old_speed['score'] : 0);
	$new_color = speedycache_get_test_color(!empty($new_speed['score']) ? $new_speed['score'] : 0);
	
	$improvement = 0;
	if(!empty($old_speed['score']) && !empty($new_speed['score'])){
		$improvement = floor(($new_speed['score'] - $old_speed['score']) * 100 / $old_speed['score']);
	}
	
	// Settings
	if(defined('SPEEDYCACHE_PRO')){
		$settings['minify_html'] = ['text' => __('Minify HTML', 'speedycache')];
		$settings['minify_js'] = ['text' => __('Minify JS', 'speedycache')];
		$settings['minify_css_enhanced'] = ['text' => __('Advanced Minify CSS', 'speedycache')];
		$settings['critical_css'] = ['text' => __('Critical CSS', 'speedycache')];
		$settings['lazy_load'] = ['text' => __('Lazy Load', 'speedycache')];
		$settings['instant_page'] = ['text' => __('Instant Page', 'speedycache')];
		//$settings['local_gfonts'] = true;
		$settings['render_blocking'] = ['text' => __('Render-blocking JS', 'speedycache')];
		$settings['mobile_theme'] = ['text' => __('Mobile Theme', 'speedycache')];
		$settings['mobile'] = ['text' => __('Mobile', 'speedycache')];
		$settings['google_fonts'] = ['text' => __('Async Google Fonts', 'speedycache')];
		$settings['display_swap'] = ['text' => __('Font Display Swap', 'speedycache')];
		$settings['delay_js'] = ['text' => __('Delay JS', 'speedycache')];
		$settings['image_dimensions'] = ['text' => __('Image Dimensions', 'speedycache')];
	}

	$settings['gzip'] = ['text' => __('GZIP Compression', 'speedycache'), 'enabled' => true];
	$settings['minify_css'] = ['text' => __('Minify CSS', 'speedycache'), 'enabled' => true];
	$settings['combine_css'] = ['text' => __('Combine CSS', 'speedycache'), 'enabled' => true];

	echo '<div class="speedycache-test-page">
	<div class="speedycache-test-container">
	<div style="display:flex; gap:10px;"><input type="text" placeholder="https://example.com" name="site_url" value="'.site_url().'"/><button class="speedycache-btn speedycache-btn-primary" id="speedycache-test-btn">Analyse Performance</button>
	</div>

	<div class="speedycache-test-result-wrap">
		<div class="speedycache-test-process">
			<h3>Running Analysis</h3>
			<div class="speedycache-test-tasks">
				<p><span class="spinner is-active"></span>Confirming the websites URL is reachable.</p>
				<p><span class="spinner"></span>Getting the PageSpeed Scores before optimization.</p>
				<p><span class="spinner"></span>Optimizing the page</p>
				<p><span class="spinner"></span>Getting score of optimized page</p>
			</div>
		</div>';

	echo '<div class="speedycache-result">
		<div style="display:flex; flex-direction:row">
		<div class="speedycache-test-settings" style="margin: 0 auto; width:45%;">
			<h3>Test Settings</h3>
			<form style="border-right: 1px dashed #d9d9d9;">
			<div class="speedycache-option-group">';
	
			foreach($settings as $id => $setting){
				echo '<div class="speedycache-option-wrap" style="width:30%;">
					<label for="'.esc_attr($id).'" class="speedycache-custom-checkbox">
						<input type="checkbox" id="'.esc_attr($id).'" name="'.esc_attr($id).'" checked/>
						<div class="speedycache-input-slider"></div>
					</label>
					<div class="speedycache-option-info">
						<span class="speedycache-option-name">'.esc_html($setting['text']).'</span>
					</div>
				</div>';
			}

			echo '</div>
			</form>

		</div>
		<div class="speedycache-score-comparison" style="width:55%; padding: 0 0 0 30px;">';
			if(empty($old_speed)){
				echo '<p class="speedycache-no-tests">'.__('No Analysis has been done yet', 'speedycache'). '</p>';
			}
			echo '<h3>Performance Scores</h3>
			<div class="speedycache-test-chart-wrap">
				<div class="speedycache-donut-wrap speedycache-before-optimization">
					<svg width="100%" height="100%" viewBox="0 0 40 40" class="speedycache-donut">
						<circle class="donut-hole" cx="20" cy="20" r="15.91549430918954" fill="'.esc_attr($old_color[1]).'"></circle>
						<circle class="speedycache-donut-segment speedycache-donut-segment-2" cx="20" cy="20" r="15.91549430918954" fill="transparent" stroke-width="2.5" stroke-linecap="round" stroke-dasharray="'. (!empty($old_speed['score']) ? esc_attr($old_speed['score']) : 0).' ' . esc_attr($stroke_old).'" stroke-dashoffset="25" style="stroke:'.esc_attr($old_color[0]).';"></circle>
						<g class="speedycache-donut-text speedycache-donut-text-1">
							<text y="51%" transform="translate(0, 2)">
								<tspan x="50%" text-anchor="middle" class="speedycache-donut-percent" style="fill:'.esc_attr($old_color[2]).'">'.(!empty($old_speed['score']) ? esc_attr(floor($old_speed['score'])) : 0).'</tspan> 
							</text>
						</g>
					</svg>
					<p style="font-weight:500; font-size:18px;">Before Optimization</p>
				</div>
				<div class="speedycache-donut-wrap speedycache-after-optimization">
					<svg width="100%" height="100%" viewBox="0 0 40 40" class="speedycache-donut">
						<circle class="donut-hole" cx="20" cy="20" r="15.91549430918954" fill="'.esc_attr($new_color[1]).'"></circle>
						<circle class="speedycache-donut-segment speedycache-donut-segment-2" cx="20" cy="20" r="15.91549430918954" fill="transparent" stroke-width="2.5" stroke-linecap="round" stroke-dasharray="'. (!empty($new_speed['score']) ? esc_attr($new_speed['score']) : 0).' '.esc_attr($stroke_new).'" stroke-dashoffset="25" style="stroke:'.esc_attr($new_color[0]).';"></circle>
						<g class="speedycache-donut-text speedycache-donut-text-1">
							<text y="51%" transform="translate(0, 2)">
								<tspan x="50%" text-anchor="middle" class="speedycache-donut-percent" style="fill:'.esc_attr($new_color[2]).'">'.(!empty($new_speed['score']) ? esc_attr(floor($new_speed['score'])) : 0).'</tspan>
							</text>
						</g>
					</svg>
					<p style="font-weight:500; font-size:18px;">After Optimization</p>
				</div>
			</div>
			<div class="speedycache-test-action">Want to enable the SpeedyCache settings used for this test? <button class="speedycache-btn speedycache-btn-primary speedycache-copy-test-settings">Enable Now</button></div>
			<div class="speedycache-test-result-meta-info">
				<p>Your website is <span class="speedycache-test-improvement">'.(!empty($improvement) ? esc_attr($improvement) : 0).'%</span> Better with SpeedyCache Optimization</p>
				<p><b>Website: </b>'.site_url().'</p>
				<p><b>Tested with: </b> Google PageSpeed Insights</p>
				<p><b>Note: </b> This is performance improvement data for mobile device</p>
				
			</div>
		</div></div>
		<div class="speedycache-test-additional-info">
			<h3>Additional Information</h3>
			<div class="speedycache-test-additional-info-container">
				<div class="speedycache-first-contentful-paint">
					<h4>First Contentful Paint</h4>
					<p><span class="speedycache-metric-before">Before: '.(!empty($old_speed['first-contentful-paint']) ? esc_html($old_speed['first-contentful-paint']) : '').'</p>
					<p><span class="speedycache-metric-after">After: '.(!empty($new_speed['first-contentful-paint']) ? esc_html($new_speed['first-contentful-paint']) : '').'</p>
				</div>
				<div class="speedycache-total-blocking-time">
					<h4>Total Blocking Time</h4>
					<p><span class="speedycache-metric-before">Before: '.(!empty($old_speed['total-blocking-time']) ? esc_html($old_speed['total-blocking-time']) : '').'</p>
					<p><span class="speedycache-metric-after">After: '.(!empty($new_speed['total-blocking-time']) ? esc_html($new_speed['total-blocking-time']) : '').'</p>
				</div>
				<div class="speedycache-layout-shift">
					<h4>Cumulative Layout Shift</h4>
					<p><span class="speedycache-metric-before">Before: '.(!empty($old_speed['cumulative-layout-shift']) ? esc_html($old_speed['cumulative-layout-shift']) : '').'</p>
					<p><span class="speedycache-metric-after">After: '.(empty($new_speed) || (empty($new_speed['cumulative-layout-shift']) && is_null($new_speed['cumulative-layout-shift'])) ? '' : esc_html($new_speed['cumulative-layout-shift'])).'</p>
				</div>
				<div class="speedycache-speed-index">
					<h4>Speed Index</h4>
					<p><span class="speedycache-metric-before">Before: '.(!empty($old_speed['speed-index']) ? esc_html($old_speed['speed-index']) : '').'</p>
					<p><span class="speedycache-metric-after">After: '.(!empty($new_speed['speed-index']) ? esc_html($new_speed['speed-index']) : '').'</p>
				</div>
			</div>
		</div>
	</div>
	';

	echo '</div></div>';

	if(!defined('SITEPAD')){
		speedycache_promotion_tmpl(); 
	}
	
	echo '</div>';
}

// echo's the html for preloading modals
function speedycache_preload_modal_options($field_name, $fields){
	
	if(empty($fields)){
		return '';
	}

	switch($field_name){
		case 'pre_connect':
			$placeholder = 'https://fonts.google.com';
			break;

		default:
			$placeholder = site_url() . '/wp-content/uploads/image.jpg';
	}

	$html = '<div class="speedycache-preloading-options"><input type="text" name="resource" style="width:100%;" placeholder="'.esc_html($placeholder).'" required/>
	';
	
	$html .= '<div class="speedycache-preload-checkboxes">';
	if(isset($fields['parent_selector'])){
		$html .= '<label><input type="checkbox" name="parent_selector" value="true"/>Use Parent Selector</label>';
	}
	
	if(isset($fields['crossorigin'])){
		$html .= '<label><input type="checkbox" name="crossorigin" value="true"/>Crossorigin</label>';
	}
	
	$html .= '</div>';
	
	if(isset($fields['type'])){
		$html .= '<label><span>Resource Type</span><select name="type" required>
			<option value="">Select Type</option>
			<option value="image">Image</option>
			<option value="font">Font</option>
			<option value="script">Script</option>
			<option value="style">Style</option>
			<option value="audio">Audio</option>
			<option value="document">Document</option>
			<option value="video">Video</option>
		</select></label>';
	}
	
	if(isset($fields['priority'])){
		$html .= '<label><span>Select Priority</span><select name="priority" required>
			<option value="">Select Priority</option>
			<option value="high">High</option>
			<option value="low">Low</option>
		</select></label>';
	}
	
	$html .= '</div>';
	
	return $html;

}

function speedycache_get_test_color($score){

	if($score == 0){
		return  ['#3d5afe', '#3d5afe36', '#3d5afe'];
	}
	

	// The structure of this array is 0 => [Stroke Color, Background Color, Text Color]
	$score_color_map = array(
		0 => ['#c00', '#c003', '#c00'], // Red
		50 => ['#fa3', '#ffa50036', '#fa3'],// Orange
		90 => ['#0c6', '#00cc663b', '#080']// Green
	);

	if($score >= 0 && $score < 50){
		return $score_color_map[0];
	}

	if($score >= 50  && $score < 90){
		return $score_color_map[50];
	}

	return $score_color_map[90];
}

speedycache_set_custom_interval();