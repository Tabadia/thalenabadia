<?php

if(!defined('ABSPATH')){
	die();
}

echo '
<style>
.backuply_button {
background-color: #4CAF50; /* Green */
border: none;
color: white;
padding: 8px 16px;
text-align: center;
text-decoration: none;
display: inline-block;
font-size: 16px;
margin: 4px 2px;
-webkit-transition-duration: 0.4s; /* Safari */
transition-duration: 0.4s;
cursor: pointer;
}

.backuply_button:focus{
border: none;
color: white;
}

.backuply_button1 {
color: white;
background-color: #4CAF50;
border:3px solid #4CAF50;
}

.backuply_button1:hover {
box-shadow: 0 6px 8px 0 rgba(0,0,0,0.24), 0 9px 25px 0 rgba(0,0,0,0.19);
color: white;
border:3px solid #4CAF50;
}

.backuply_button2 {
color: white;
background-color: #0085ba;
}

.backuply_button2:hover {
box-shadow: 0 6px 8px 0 rgba(0,0,0,0.24), 0 9px 25px 0 rgba(0,0,0,0.19);
color: white;
}

.backuply_button3 {
color: white;
background-color: #365899;
}

.backuply_button3:hover {
box-shadow: 0 6px 8px 0 rgba(0,0,0,0.24), 0 9px 25px 0 rgba(0,0,0,0.19);
color: white;
}

.backuply_button4 {
color: white;
background-color: rgb(66, 184, 221);
}

.backuply_button4:hover {
box-shadow: 0 6px 8px 0 rgba(0,0,0,0.24), 0 9px 25px 0 rgba(0,0,0,0.19);
color: white;
}

.backuply_promo-close{
float:right;
text-decoration:none;
margin: 5px 10px 0px 0px;
}

.backuply_promo-close:hover{
color: red;
}

#backuply_promo li {
list-style-position: inside;
list-style-type: circle;
}

.backuply-loc-types {
display:flex;
flex-direction: row;
align-items:center;
flex-wrap: wrap;
}

.backuply-loc-types li{
list-style-type:none !important;
margin-right: 10px;
}

.backuply-free-trial{
position:relative;
width:99%;
background-color: #000;
color:#FFF;
font-weight:500;
border-radius:4px;
padding:20px;
box-sizing:border-box;
margin-top: 10px;
}

.backuply-promo-dismiss{
position:absolute;
top:10px;
right:10px;
color:white;
}

</style>

<script>
jQuery(document).ready( function() {
	(function($) {
		$("#backuply_promo .backuply_promo-close").click(function(){
			var data;
			
			// Hide it
			$("#backuply_promo").hide();
			
			// Save this preference
			$.post("'.admin_url('?backuply_promo=0').'&security='.wp_create_nonce('backuply_promo_nonce').'", data, function(response) {
				//alert(response);
			});
		});
		
		$("#backuply_offer .backuply_offer-close").click(function(){
			var data;
			
			// Hide it
			$("#backuply_offer").hide();
			
			// Save this preference
			$.post("'.admin_url('?backuply_offer=0').'&security='.wp_create_nonce('backuply_promo_nonce').'", data, function(response) {
				//alert(response);
			});
		});
		
		$("#backuply_holiday_promo .backuply_promo-close").click(function(){
			var data;
			
			// Hide it
			$("#backuply_holiday_promo").hide();
			
			// Save this preference
			$.post("'.admin_url('?backuply_holiday_promo=0').'&security='.wp_create_nonce('backuply_promo_nonce').'", data, function(response) {
				//alert(response);
			});
		});
	})(jQuery);
});
</script>';

function backuply_base_promo(){
	echo '<div class="notice notice-success" id="backuply_promo" style="min-height:120px; background-color:#FFF; padding: 10px;">
	<a class="backuply_promo-close" href="javascript:" aria-label="Dismiss this Notice">
		<span class="dashicons dashicons-dismiss"></span> Dismiss
	</a>
	<table>
	<tr>
		<th>
			<img src="'.BACKUPLY_URL.'/assets/images/backuply-square.png" style="float:left; margin:10px 20px 10px 10px" width="100" />
		</th>
		<td>
			<p style="font-size:16px;">You have been using Backuply for few days and we hope we were able to add some value through Backuply.
			</p>
			<p style="font-size:16px">
			If you like our plugin would you please show some love by doing actions like
			</p>
			<p>
				<a class="backuply_button backuply_button1" target="_blank" href="https://backuply.com/pricing">Upgrade to Pro</a>
				<a class="backuply_button backuply_button2" target="_blank" href="https://wordpress.org/support/view/plugin-reviews/backuply">Rate it 5★\'s</a>
				<a class="backuply_button backuply_button3" target="_blank" href="https://www.facebook.com/backuply/">Like Us on Facebook</a>
				<a class="backuply_button backuply_button4" target="_blank" href="https://twitter.com/intent/tweet?text='.rawurlencode('I use @wpbackuply to backup my #WordPress site - https://backuply.com').'">Tweet about Backuply</a>
			</p>
	</td>
	</tr>
	</table>
</div>';
}

function backuply_holiday_offers(){

	$time = date('nj');

	if($time == 1225 || $time == 1224){
		backuply_christmas_offer();
	}
	
	if($time == 11){
		backuply_newyear_offer();
	}
}

function backuply_christmas_offer(){
	echo '<div class="notice notice-success" id="backuply_holiday_promo" style="min-height:120px; background-color:#FFF; padding: 10px;">
	<a class="backuply_promo-close" href="javascript:" aria-label="Dismiss this Notice">
		<span class="dashicons dashicons-dismiss"></span> Dismiss
	</a>
	<table>
	<tr>
		<th>
			<img src="'.BACKUPLY_URL.'/assets/images/25off.png" style="float:left; margin:10px 20px 10px 10px" width="100" />
		</th>
		<td><h2>Backuply Wishes you Merry Christmas 🎄</h2>
	<p style="font-size:16px">We are offering 25% off on every Backuply Plan today, so upgrade to Backuply Pro now and forget the need to create backups manully with Backuply\'s Auto Backups.</p>
	<a class="backuply_button backuply_button1" target="_blank" href="https://backuply.com/pricing">Upgrade to Pro</a>
	</td>
	</tr>
	</table>
</div>';
}

function backuply_newyear_offer(){
	echo '<div class="notice notice-success" id="backuply_holiday_promo" style="min-height:120px; background-color:#FFF; padding: 10px;">
	<a class="backuply_promo-close" href="javascript:" aria-label="Dismiss this Notice">
		<span class="dashicons dashicons-dismiss"></span> Dismiss
	</a>
	<table>
	<tr>
		<th>
			<img src="'.BACKUPLY_URL.'/assets/images/25off.png" style="float:left; margin:10px 20px 10px 10px" width="100" />
		</th>
		<td><h2>Backuply Wishes you a Happy New Year 🎉</h2>
	<p style="font-size:16px">We are offering 25% off on every Backuply Plan today, so upgrade to Backuply Pro now and forget the need to create backups manully with Backuply\'s Auto Backups.</p>
	<a class="backuply_button backuply_button1" target="_blank" href="https://backuply.com/pricing">Upgrade to Pro</a>
	</td>
	</tr>
	</table>
</div>';
}

function backuply_free_trial(){
	global $backuply, $error;
	
	$has_license = false;
	
	if(defined('BACKUPLY_PRO') && !empty($backuply['license']['license'])){
		$has_license = true;
	}
	
	$verification_wait = false;
	
	if(empty($backuply['bcloud_key']) && !empty($_GET['license']) && !empty($_GET['token'])){
		if($_GET['token'] !== get_transient('bcloud_trial_token')){
			$error[] = 'Your Security Check failed!';
		} else {
			delete_transient('bcloud_trial_token');
			
			$license = sanitize_text_field($_GET['license']);
			
			if(!empty($backuply['license'])){
				$error[] = __('You already have a license linked to this WordPress install, you dont need trial license you can directly add Backuply Cloud', 'backuply');
			}
			
			backuply_update_trial_license($license);
			
			if(empty($error)){
				$verification_wait = true;
			}
			
		}
	}
	
	$token = wp_generate_password(32,false);
	set_transient('bcloud_trial_token', $token, 3600);
	

	echo '<div class="notice notice-success" id="backuply_free-trial" style="min-height:120px; background-color:#FFF; padding: 10px;">
	<a class="backuply_promo-close" href="javascript:" aria-label="Dismiss this Notice">
		<span class="dashicons dashicons-dismiss"></span> Dismiss
	</a>
	<table>
	<tr>
		<th>
			<img src="'.BACKUPLY_URL.'/assets/images/backuply-square.png" style="float:left; margin:10px 20px 10px 10px" width="100" />
		</th>
		<td>
			<p style="font-size:16px; font-weight:bold;">Stress free backups in Cloud</p>
			<p style="font-size:16px">';
			
			if(defined('BACKUPLY_PRO')){
				echo 'With Backuply Pro you get 10GB free storage on Backuply Cloud. Start backing up your website today !<br>
				Backuply Cloud is a secure and reliable cloud backup service that makes it easy to protect your website data.';
			} else {
				echo 'Try Backuply Cloud for free for 30 days with 10GB of storage. With just a click store your WordPress backups on our Cloud. Backups are the best form of security, never lose your data with Backuply Cloud.';
			}

			echo '</p>
			<button class="button button-primary" id="backuply-cloud-trial">Try Now</button>
		</td>
	</tr>
	</table>
	</div>
	<div id="bcloud-dialog" title="Backuply Cloud Trial" style="display:none;">
		<div class="backuply-cloud-state">
			<div class="bcloud-trial-email" '.(!empty($has_license) || !empty($verification_wait) ? esc_html('style=display:none;') : '').'>
				<h2>Get a Trial License</h2>
				<p>Click on the button below and you will be redirected <b>backuply.com</b> to register for a Trail License</p>
				<a href="'.BACKUPLY_API . '/cloud/new_account.php?token='.esc_attr($token).'&callback='.admin_url('admin.php?page=backuply').'" class="button button-primary backuply-email-link">Create a Trial License</a>
				<p><input type="checkbox" id="backuply_has_license"/>I have a License</p>
			</div>
			<div class="backuply-bcloud-trial-verify" '.(empty($verification_wait) ? esc_html('style=display:none;') : '').'>
				<p>A trial license has been created please go to your email and verify</p>
				<p>If you have completed the verification then</p>
				<input type="checkbox" name="backuply-verify-checkbox" id="backuply-verify-checkbox"/>I confirm that I have verified email
				<button class="button button-primary backuply-verify-email" disabled>Click here</button><span class="spinner"></span>
			</div>

			<div class="bcloud-trial-license" '.(empty($has_license) ? esc_html('style=display:none;') : '').'>
				<h2>Enter your License</h2>
				<p>Your License will be used to generate a key to connect to Backuply Cloud</p>
				<input type="text" style="width:100%" value="'.(empty($backuply['license']) || empty($backuply['license']['license']) ? '' : esc_attr($backuply['license']['license'])).'" placeholder="BAKLY-00000-11111-22222-44444" name="backuply_license"/><br/>';
				
				if(!defined('BACKUPLY_PRO')){
					echo '<p><input type="checkbox" id="backuply_no_license"/>Does not have a license</p>';
				}
				
				echo '<button class="button button-primary backuply-license-link" style="margin-top:10px;">Submit</button><span class="spinner"></span>
			</div>
		</div>';

		if(!empty($backuply['cron'])){
			echo '<div class="backuply-cloud-trial-settings" style="display:none;">
				<h3>Updating Settings</h3>
				<p>We have detected that you have a default storage and schedule already set. Do you want to set Backuply Cloud as your default backup location ?</p>
				<div style="text-align:center;">
					<button class="button button-primary backuply-default-yes">Yes</button>
					<button class="button backuply-default-no">No</button>
				</div>
			</div>';
		}

		echo '
		
		<div class="backuply-cloud-state" style="text-align:center; display:none;">
			<p>Integration has been successful now you can try creating Backup on Backuply Cloud</p>
			<a href="'.admin_url('admin.php?page=backuply').'" class="button button-primary">Start Creating Backups to Backuply Cloud</a>
		</div>
	</div>
	';
}

function backuply_promo_scripts(){
	wp_enqueue_script('backuply-promo', BACKUPLY_URL . '/assets/js/promo.js', array('jquery', 'jquery-ui-dialog'), BACKUPLY_VERSION);
	wp_enqueue_style('backuply-dialog', BACKUPLY_URL . '/assets/css/base-jquery-ui.css', [], BACKUPLY_VERSION);
	
	wp_localize_script('backuply-promo', 'backuply_promo', array(
		'nonce' => wp_create_nonce('backuply_trial_nonce'),
		'ajax' => admin_url('admin-ajax.php')
	));
}

function backuply_update_trial_license($license){
	global $backuply, $error;

	$resp = wp_remote_get(BACKUPLY_API.'/license.php?license='.$license, array('timeout' => 30));
	$json = json_decode($resp['body'], true);

	if(empty($json['license'])){
		$error[] = __('There was issue fetching License details', 'backuply');
	}
	
	$backuply['license'] = $json;
	update_option('backuply_license', $backuply['license']);
}

function backuply_regular_offer(){
	
	// The time period this should be visible to the users.
	if(time() > strtotime('20 October 2023')){
		return;
	}

	echo '<div class="notice notice-success" id="backuply_offer" style="min-height:120px; background-color:#FFF; padding: 10px;">
	<a class="backuply_offer-close" href="javascript:" aria-label="Dismiss this Notice">
		<span class="dashicons dashicons-dismiss"></span> Dismiss for 6 months
	</a>
	<table>
	<tr>
		<th>
			<img src="'.BACKUPLY_URL.'/assets/images/30off.png" style="float:left; margin:10px 20px 10px 10px" width="100" />
		</th>
		<td><p style="font-size:16px">Backuply is offering a 30% discount on all subscription plans today! Upgrade to Backuply Pro and receive up to 100 GB of cloud storage, and forget about manually creating backups with our automatic backups feature. Use the code <strong>NEW30</strong> to receive this offer.</p>
		<a class="backuply_button backuply_button1" target="_blank" href="https://backuply.com/pricing">Upgrade to Pro</a>
	</td>
	</tr>
	</table>
	</div>
	</div>';

}
