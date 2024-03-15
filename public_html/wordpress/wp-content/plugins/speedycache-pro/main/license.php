<?php

if(!defined('ABSPATH')){
	die('HACKING ATTEMPT!');
}

include_once SPEEDYCACHE_DIR . '/main/settings.php';

function speedycache_license_page(){
	global $speedycache;
	
	if(isset($_REQUEST['save_speedycache_license'])){
		speedycache_license();
	}
	
	speedycache_add_javascript();
	settings_errors('speedycache-notice');
	
	?>
	
	<div class="speedycache-setting-content">
		<div class="speedycache-tab-group" style="width:78%">
			<table class="wp-list-table fixed striped users speedycache-license-table" cellspacing="1" border="0" width="78%" cellpadding="10" align="center">
				<tbody>
					<tr>				
						<th align="left" width="25%"><?php esc_html_e('SpeedyCache Version', 'speedycache'); ?></th>
						<td><?php
							echo SPEEDYCACHE_PRO_VERSION.(defined('SPEEDYCACHE_PRO') ? ' (Pro Version)' : '');
						?>
						</td>
					</tr>
					<tr>			
						<th align="left" valign="top"><?php esc_html_e('SpeedyCache License', 'speedycache'); ?></th>
						<td align="left">
							<form method="post" action="">
								<span style="color:var(--speedycache-red)">
									<?php echo (defined('SPEEDYCACHE_PRO') && empty($speedycache->license) ? '<span style="color:var(--speedycache-red)">Unlicensed</span> &nbsp; &nbsp;' : '')?>
								</span>
								<input type="hidden" name="speedycache_license_nonce" value="<?php echo wp_create_nonce('speedycache_license');?>"/>
								<input type="text" name="speedycache_license" value="<?php echo (empty($speedycache->license) ? empty($_POST['speedycache_license']) ? '' : speedycache_optpost('speedycache_license') : $speedycache->license['license'])?>" size="30" placeholder="e.g. SPDFY-11111-22222-33333-44444" style="width:300px;"> &nbsp; 
								<input name="save_speedycache_license" class="speedycache-btn speedycache-btn-primary" value="Update License" type="submit">
							</form>
							<?php if(!empty($speedycache->license)){
								
								$expires = $speedycache->license['expires'];
								$expires = substr($expires, 0, 4).'/'.substr($expires, 4, 2).'/'.substr($expires, 6);
								
								echo '<div style="margin-top:10px;">License Status : '.(empty($speedycache->license['status_txt']) ? 'N.A.' : wp_kses_post($speedycache->license['status_txt'])).' &nbsp; &nbsp; &nbsp; 
								License Expires : '.($speedycache->license['expires'] <= date('Ymd') ? '<span style="color:var(--speedycache-red)">'.esc_attr($expires).'</span>' : esc_attr($expires)).'
								</div>';
								
							}?>
						</td>
					</tr>
					<tr>
						<th align="left">URL</th>
						<td><?php echo get_site_url(); ?></td>
					</tr>
					<tr>				
						<th align="left">Path</th>
						<td><?php echo ABSPATH; ?></td>
					</tr>
					<tr>				
						<th align="left">Server's IP Address</th>
						<td><?php echo esc_html($_SERVER['SERVER_ADDR']); ?></td>
					</tr>
					<tr>				
						<th align="left">.htaccess is writable</th>
						<td><?php echo (is_writable(ABSPATH.'/.htaccess') ? '<span style="color:var(--speedycache-red)">Yes</span>' : '<span style="color:green">No</span>');?></td>
					</tr>		
				</tbody>
			</table>
		</div>
	<?php speedycache_promotion_tmpl(); ?>
	</div>
<?php
	speedycache_page_footer(true);
}

function speedycache_license(){
	global $speedycache;

	if(!wp_verify_nonce($_POST['speedycache_license_nonce'], 'speedycache_license')){
		speedycache_notify(array(__('Security Check Failed', 'speedycache'),'error'));
		return;
	}

	$license = sanitize_key($_POST['speedycache_license']);
	
	if(empty($license)){
		speedycache_notify(array(__('The license key was not submitted', 'speedycache'),'error'));
		return;
	}
	
	$resp = wp_remote_get(SPEEDYCACHE_API.'license.php?license='.$license, array('timeout' => 30));
	
	if(!is_array($resp)){
		speedycache_notify(array(__('The response was malformed<br>'.var_export($resp, true), 'speedycache'), 'error'));
		return;
	}

	$json = json_decode($resp['body'], true);
	
	// Save the License
	if(empty($json['license'])){
		speedycache_notify(array(__('The license key is invalid', 'speedycache'), 'error'));
		return;
		
	}
	
	$speedycache->license = $json;
	
	if(get_option('speedycache_license')){
		update_option('speedycache_license', $json);
		return;
	}
	
	update_option('speedycache_license', $json);
}
