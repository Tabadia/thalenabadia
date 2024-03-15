<?php

/*
* FILEORGANIZER
* https://fileorganizer.net/
* (c) FileOrganizer Team
*/

global $fileorganizer;

if(!defined('FILEORGANIZER_VERSION')){
	die('Hacking Attempt!');
}

function fileorganizer_page_header($title = 'FileOrganizer'){
	wp_enqueue_style( 'forg-admin' );
	
	echo '<h2 class="fileorganizer-notices"></h2>
	<div class="fileorganizer-box-container" style="margin:0">
		<table class="fileorganizer-settings-header" cellpadding="2" cellspacing="1" width="100%" class="fixed" border="0">
			<tr>
				<td class="fileorganizer-td" valign="top">
					<img src="'.FILEORGANIZER_URL .'/images/logo.png">
					<h3 class="fileorganizer-heading">'.$title.'</h3>
				</td>
				<td align="right"><a target="_blank" class="button button-primary" href="https://wordpress.org/support/view/plugin-reviews/fileorganizer">Review FileOrganizer</a></td>
				<td align="right" width="40"><a target="_blank" href="https://twitter.com/fileorganizer"><img src="'.FILEORGANIZER_URL.'/images/twitter.png" /></a></td>
				<td align="right" width="40"><a target="_blank" href="https://www.facebook.com/fileorganizer/"><img src="'.FILEORGANIZER_URL.'/images/facebook.png" /></a></td>
			</tr>
		</table>
	</div>';

}

function fileorganizer_page_footer($no_twitter = 0){
	
	$promos = apply_filters('pagelayer_right_bar_promos', true);

	if($promos){

		echo '
		<div class="fileorganizer-promotion" style="width:100%;" >
			<div class="fileorganizer-promotion-content">
				<h2 class="fileorganizer-promotion-logo">
					<span><a target="_blank" href="https://pagelayer.com/?from=fileorganizer-plugin"><img src="'. FILEORGANIZER_URL.'/images/pagelayer_product.png" width="100%"></a></span>
				</h2>
				<div>
					<em>The Best WordPress <b>Site Builder</b> </em>:<br>
					<ul style="font-size:13px;">
						<li>Drag &amp; Drop Editor</li>
						<li>Widgets</li>
						<li>In-line Editing</li>
						<li>Styling Options</li>
						<li>Animations</li>
						<li>Easily customizable</li>
						<li>Real Time Design</li>
						<li>And many more ...</li>
					</ul>
					<center><a class="button button-primary" target="_blank" href="https://pagelayer.com/?from=fileorganizer-plugin">Visit Pagelayer</a></center>
				</div>
			</div>

			<div class="fileorganizer-promotion-content">
				<h2 class="fileorganizer-promotion-logo">
					<span><a target="_blank" href="https://loginizer.com/?from=fileorganizer-plugin"><img src="'.FILEORGANIZER_URL.'/images/loginizer_product.png" width="100%"></a></span>
				</h2>
				<div>
					<em>Protect your WordPress website from <b>unauthorized access and malware</b> </em>:<br>
					<ul style="font-size:13px;">
						<li>BruteForce Protection</li>
						<li>reCaptcha</li>
						<li>Two Factor Authentication</li>
						<li>Black/Whitelist IP</li>
						<li>Detailed Logs</li>
						<li>Extended Lockouts</li>
						<li>2FA via Email</li>
						<li>And many more ...</li>
					</ul>
					<center><a class="button button-primary" target="_blank" href="https://loginizer.com/?from=fileorganizer-plugin">Visit Loginizer</a></center>
				</div>
			</div>
		</div>';
		
	}

	echo '</div>
	</div>';

	if(empty($no_twitter)){
		echo '
		<div style="width:45%;background:#FFF;padding:15px; margin:40px auto">
			<b>'. __('Let your followers know that you use FileOrganizer to manage your wordpress files :').'</b>
			<form method="get" action="https://twitter.com/intent/tweet" id="tweet" onsubmit="return dotweet(this);">
				<textarea name="text" cols="45" row="3" style="resize:none;">'. __('I easily manage my #WordPress #files using @fileorganizer').'</textarea>
				&nbsp; &nbsp; <input type="submit" value="Tweet!" class="button button-primary" onsubmit="return false;" id="twitter-btn" style="margin-top:20px;">
			</form>	
		</div>';
	}
}


// fileorganizer Setting page
function fileorganizer_settings_page(){

	$options = get_option('fileorganizer_options');
	$options = empty($options) || !is_array($options) ? array() : $options;

	//Settings
	if(isset($_POST['save_settings'])){
		
		// Check nonce
		check_admin_referer('fileorganizer_settings');

		// General settings
		$path = fileorganizer_optpost('root_path');
		$disable_path_restriction = fileorganizer_optpost('disable_path_restriction');
		
		if(!defined('FILEORGANIZER_PRO') || empty($disable_path_restriction)){
			$verify = fileorganizer_validate_path($path);
			$path =  $verify ? $path : ABSPATH;
			if(!$verify){
				fileorganizer_notify(__('Invalid File Manager Path Detected!'), 'error');
			}
		}

		$options['root_path'] = fileorganizer_cleanpath($path);
		$options['default_view'] = fileorganizer_optpost('default_view');
		$options['default_lang'] = fileorganizer_optpost('default_lang');
		$options['hide_htaccess'] = fileorganizer_optpost('hide_htaccess');
		$options['enable_trash'] = fileorganizer_optpost('enable_trash');

		if(defined('FILEORGANIZER_PRO')){
			$options['user_roles'] = fileorganizer_optpost('user_roles');
			$options['disable_path_restriction'] = fileorganizer_optpost('disable_path_restriction');
			$options['max_upload_size'] = fileorganizer_optpost('max_upload_size');
			$options['enable_ftp'] = fileorganizer_optpost('enable_ftp');
		}

		if(update_option( 'fileorganizer_options', $options )){
			fileorganizer_notify(__('Settings saved successfully.'));
		}
	}

	$settings = get_option('fileorganizer_options', array());

	if( empty($settings) || !is_array($settings) ){
		$settings = array();
	}

?>
<div class="wrap">
	<?php fileorganizer_page_header('FileOrganizer'); ?>
	<div class="fileorganizer-setting-content">
		<form class="fileorganizer-settings fileorganizer-mr20" name="fileorganizer_settings" method="post" >
			<?php wp_nonce_field('fileorganizer_settings'); ?>
			<div class="tabs-wrapper">
				<h2 class="nav-tab-wrapper fileorganizer-wrapper">
					<a href="#fileorganizer-general" class="fileorganizer-nav-tab nav-tab nav-tab-active"><?php _e('General'); ?></a>
					<a href="#fileorganizer-advanced" class="fileorganizer-nav-tab nav-tab"><?php _e('Advanced'); ?></a>
					<a href="#fileorganizer-support" class="fileorganizer-nav-tab nav-tab "><?php _e('Support'); ?></a>
				</h2>

				<!-- General settings start -->
				<div class="fileorganizer-tab-panel" id="fileorganizer-general" style="display:block;">
					<table class="form-table">
						<tr>
							<th scope="row"><?php _e('File Manager Path'); ?></th>
							<td>
								<div class="fileorganizer-form-input">
									<input name="root_path" type="text" class="regular-text always_active" placeholder="<?php echo fileorganizer_cleanpath(ABSPATH); ?>"  value="<?php if(!empty($settings['root_path'])){
										echo esc_attr($settings['root_path']);
									}?>"> 
									<p class="description">
										<?php echo __( 'Set file manager root path.<br> Default path is:').'<code>'.fileorganizer_cleanpath(ABSPATH).__('</code><br>Please change the path carefully. an incorrect path can cause the FileOrganizer plugin to goes down.');
										?>
									</p>
									<?php 
									if(!defined('FILEORGANIZER_PRO')){
										echo '<p class="description"><b>';
										_e('Note: The free version does not allow setting a path outside your WordPress installation!');
										echo '</b></p>';
									} ?>
								</div>
							</td>
						</tr>
						<?php 
						if( defined('FILEORGANIZER_PRO') && (!is_multisite() || is_super_admin())){
						?>
						<tr>
							<th scope="row"><?php _e('File Manager Path Restriction'); ?></th>
							<td>
								<div class="fileorganizer-form-input">
									<label class="fileorganizer-switch">
										<input name="disable_path_restriction" type="checkbox" value="yes" <?php if(!empty($settings['disable_path_restriction'])){
											echo "checked";
										}?>>
										<span class="fileorganizer-slider fileorganizer-round"></span>
									</label>
									<p class="description">
										<?php 
											_e('Disable root path restriction.');
											echo '<br>'.__('Allow FileOrganizer to set a path outside of your WordPress installation.');
										?>
									</p>
								</div>
							</td>
						</tr>
						<?php 
						}
						?>
						<tr>
							<th scope="row"><?php _e('Files View'); ?></th>
							<td>
								<div class="fileorganizer-form-input">
									<?php $view = empty($settings['default_view']) ? '' : $settings['default_view']; ?>
									<select name='default_view'>
										<option <?php selected( $view , 'icons'); ?> value="icons"><?php _e('Icons'); ?></option>
										<option <?php selected( $view , 'list'); ?> value="list"><?php _e('List'); ?></option>
									</select>
									<p class="description"><?php _e( "Set default folder view." ); ?></p>
								</div>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php _e('Select Language'); ?></th>
							<td>
								<?php
									$fileman_languages = [
										'English' => 'en',
										'العربية' => 'ar',
										'Български' => 'bg',
										'Català' => 'ca',
										'Čeština' => 'cs',
										'Dansk' => 'da',
										'Deutsch' => 'de',
										'Ελληνικά' => 'el',
										'Español' => 'es',
										'فارسی' => 'fa',
										'Føroyskt' => 'fo',
										'Français' => 'fr',
										'Français (Canada)' => 'fr_CA',
										'עברית' => 'he',
										'Hrvatski' => 'hr',
										'Magyar' => 'hu',
										'Bahasa Indonesia' => 'id',
										'Italiano' => 'it',
										'日本語' => 'ja',
										'한국어' => 'ko',
										'Nederlands' => 'nl',
										'Norsk' => 'no',
										'Polski' => 'pl',
										'Português' => 'pt_BR',
										'Română' => 'ro',
										'Pусский' => 'ru',
										'සිංහල' => 'si',
										'Slovenčina' => 'sk',
										'Slovenščina' => 'sl',
										'Srpski' => 'sr',
										'Svenska' => 'sv',
										'Türkçe' => 'tr',
										'ئۇيغۇرچە' => 'ug_CN',
										'Український' => 'uk',
										'Tiếng Việt' => 'vi',
										'简体中文' => 'zh_CN',
										'正體中文' => 'zh_TW',
									];
									
									$curlang = empty($settings['default_lang']) ? '' : $settings['default_lang'];
								?>
								<div class="fileorganizer-form-input">
									<select name='default_lang'>
										<?php 
											foreach( $fileman_languages as $lang => $code ){
												echo '<option '.(selected( $curlang , $code)).' value="'.$code.'">'.$lang.'</option>';
											}
										?>
									</select>
									<p class="description"><?php _e( "Change the FileOrganizer default language." ); ?></p>
								</div>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php _e('Hide .htaccess?'); ?></th>
							<td>
								<div class="fileorganizer-form-input">
									<label class="fileorganizer-switch">
										<input name="hide_htaccess" type="checkbox" value="yes" <?php if(!empty($settings['hide_htaccess'])){
											echo "checked";
										}?>>
										<span class="fileorganizer-slider fileorganizer-round"></span>
									</label>
									<p class="description"><?php _e( "Hide .htaccess file if exists." ); ?></p>
								</div>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php _e('Enable Trash?'); ?></th>
							<td>
								<div class="fileorganizer-form-input">
									<label class="fileorganizer-switch">
										<input name="enable_trash" type="checkbox" value="yes" <?php if(!empty($settings['enable_trash'])){
											echo "checked";
										}?>>
										<span class="fileorganizer-slider fileorganizer-round"></span>
									</label>
									<p class="description">
										<?php 
										_e( "Enable trash to temporary  store files after deletion." );
										echo '<br>'.__('The trash files are saved in the following path.').'<br><code>'.fileorganizer_cleanpath(wp_upload_dir()['basedir'].'/fileorganizer/.trash/').'</code>'; 
										?>
									</p>
								</div>
							</td>
						</tr>
					</table>
					<p>
						<input type="submit" name="save_settings" class="button fileorganizer-button-primary" value="Save Changes">
					</p>
				</div>
				<!-- General settings end -->

				<!-- Advance settings start -->
				<div class="fileorganizer-tab-panel <?php echo !defined('FILEORGANIZER_PRO') ? 'fileorganizer-disabled-panel' : ''; ?>" id="fileorganizer-advanced">
					<?php if (!defined('FILEORGANIZER_PRO')){
							echo '
							<div class="fileorganizer-pro-overlay">
								<div class="fileorganizer-lock-content">
									<span class="dashicons dashicons-lock fileorganizer-lock-icon"></span>
									<label class="fileorganizer-lock-text">'. __("Available in Pro version!") .'</label>
								</div>
							</div>';
						} ?>
					<div class="fileorganizer-tab-panel-wrap">
						<table class="form-table">
							<tr>
								<th scope="row"><?php _e('Allowed User Roles'); ?></th>
								<td>
									<?php $roles = !empty($settings['user_roles']) ? $settings['user_roles'] : ''; ?>
									<div class="fileorganizer-form-input">
									<?php if(is_multisite()){ ?>
										<input name="user_roles[]" type="checkbox" value="administrator" <?php if(is_array($roles) && in_array('administrator', $roles)){
											echo "checked";
										}?>>
										<span class="description"><?php _e( "Administrator" ); ?></span>&nbsp;&nbsp;
									<?php } ?>
										<input name="user_roles[]" type="checkbox" value="editor" <?php if(is_array($roles) && in_array('editor', $roles)){
											echo "checked";
										}?>>
										<span class="description"><?php _e( "Editor" ); ?></span>&nbsp;&nbsp;

										<input name="user_roles[]" type="checkbox" value="author" <?php if(is_array($roles) && in_array('author', $roles)){
											echo "checked";
										}?>>
										<span class="description"><?php _e( "Author" ); ?></span>&nbsp;&nbsp;

										<input name="user_roles[]" type="checkbox" value="contributor" <?php if(is_array($roles) && in_array('contributor', $roles)){
											echo "checked";
										}?>>
										<span class="description"><?php _e( "Contributor" ); ?></span>&nbsp;&nbsp;

										<input name="user_roles[]" type="checkbox" value="subscriber" <?php if(is_array($roles) && in_array('subscriber', $roles)){
											echo "checked";
										}?>>
										<span class="description"><?php _e( "Subscriber" ); ?></span>&nbsp;&nbsp;

										<input name="user_roles[]" type="checkbox" value="customer" <?php if(is_array($roles) && in_array('customer', $roles)){
											echo "checked";
										}?>>
										<span class="description"><?php _e( "Customer" ); ?></span>&nbsp;&nbsp;

										<input name="user_roles[]" type="checkbox" value="shop_manager" <?php if(is_array($roles) && in_array('shop_manager', $roles)){
											echo "checked";
										}?>>
										<span class="description"><?php _e( "Shop Manager" ); ?></span>&nbsp;&nbsp;

										<p class="description">
											<?php echo __( 'Enabling access to the FileOrganizer for User Roles'); ?>
										</p>
									</div>
								</td>
							</tr>
							<tr>
								<th scope="row"><?php _e('Maximum Upload Size'); ?></th>
								<td>
									<div class="fileorganizer-form-input">
										<input name="max_upload_size" type="number" class="regular-text always_active" placeholder="0"  value="<?php  if(!empty($settings['max_upload_size'])){
											echo esc_attr($settings['max_upload_size']);
										}?>"> <?php _e('MB');  ?>
										<p class="description"><?php _e( "Increase the maximum upload size if you are getting errors while uploading files.<br> Default: 0 means unlimited upload." ); ?></p>
									</div>
								</td>
							</tr>
							<tr>
								<th scope="row"><?php _e('Enable Network Volume'); ?></th>
								<td>
									<div class="fileorganizer-form-input">
										<label class="fileorganizer-switch">
											<input name="enable_ftp" type="checkbox" value="yes" <?php if(!empty($settings['enable_ftp'])){
												echo "checked";
											}?>>
											<span class="fileorganizer-slider fileorganizer-round"></span>
										</label>
										<p class="description"><?php _e( "Enable network volume." ); ?></p>
									</div>
								</td>
							</tr>
							<tr>
								<td>
									<input type="submit" name="save_settings" class="button fileorganizer-button-primary" value="Save Changes">
								</td>
							</tr>
						</table>
					</div>
				</div>
				<!-- Advance settings end -->
				<!-- Support tab start -->
				<div class="fileorganizer-tab-panel" id="fileorganizer-support">
					<div class="fileorganizer-tab-panel-wrap">
						<div style="width:70%; margin:20px auto; display:flex; justify-content:center; flex-direction:column; align-items:center; line-height:1.5;">
							<div style="display:flex">
								<img src="<?php echo esc_url(FILEORGANIZER_URL) .'/images/logo.png'?>" width="60"/>
								<span style="font-size:30px;font-weight:600;margin:auto;color:var(--primary)">FileOrganizer</span>
							</div>
							<h2><?php esc_html_e('You can contact the FileOrganizer Team via email. Our email address is', 'fileorganizer'); ?> <a href="mailto:support@fileorganizer.net">support@fileorganizer.net</a> <?php esc_html_e('or through Our Premium Support Ticket System at', 'fileorganizer'); ?> <a href="https://softaculous.deskuss.com" target="_blank"><?php _e('here'); ?></a></h2>
						</div>
					</div>
				</div>
				<!-- Support tab end -->
			</div>
		</form>
	<?php fileorganizer_page_footer(); ?>
<script>
jQuery(document).ready(function(){
	
	// Tabs Handler
	var tabs = jQuery('.fileorganizer-wrapper').find('.nav-tab');
	var tabsPanel = jQuery('.tabs-wrapper').find('.fileorganizer-tab-panel');

	function fileorganizer_load_tab(event){ 

		var hash  = window.location.hash;

		// No action needed when there is know hash value 
		if(!hash){
			return;
		}

		// Select elements
		jEle = jQuery(".nav-tab-wrapper").find("[href='" + hash + "']"); 

		if(jEle.length < 1){
			return;
		}
		
		// Remove active tab
		tabs.removeClass('nav-tab-active');
		tabsPanel.hide();
		
		// Make tab active
		jEle.addClass('nav-tab-active');
		jQuery('.tabs-wrapper').find(hash).show();

	}

	// Load function when hash value change
	jQuery( window ).on( 'hashchange', fileorganizer_load_tab);

	// Tabs load for First load
	fileorganizer_load_tab();
});

</script>
<?php
}