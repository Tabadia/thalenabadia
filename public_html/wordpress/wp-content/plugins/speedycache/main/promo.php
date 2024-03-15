<?php

if(!defined('ABSPATH')){
	die();
}

echo '
<style>
.speedycache_button {
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

.speedycache_button:focus{
border: none;
color: white;
}

.speedycache_button1 {
color: white;
background-color: #4CAF50;
border:3px solid #4CAF50;
}

.speedycache_button1:hover {
box-shadow: 0 6px 8px 0 rgba(0,0,0,0.24), 0 9px 25px 0 rgba(0,0,0,0.19);
color: white;
border:3px solid #4CAF50;
}

.speedycache_button2 {
color: white;
background-color: #0085ba;
}

.speedycache_button2:hover {
box-shadow: 0 6px 8px 0 rgba(0,0,0,0.24), 0 9px 25px 0 rgba(0,0,0,0.19);
color: white;
}

.speedycache_button3 {
color: white;
background-color: #365899;
}

.speedycache_button3:hover {
box-shadow: 0 6px 8px 0 rgba(0,0,0,0.24), 0 9px 25px 0 rgba(0,0,0,0.19);
color: white;
}

.speedycache_button4 {
color: white;
background-color: rgb(66, 184, 221);
}

.speedycache_button4:hover {
box-shadow: 0 6px 8px 0 rgba(0,0,0,0.24), 0 9px 25px 0 rgba(0,0,0,0.19);
color: white;
}

.speedycache_promo-close{
float:right;
text-decoration:none;
margin: 5px 10px 0px 0px;
}

.speedycache_promo-close:hover{
color: red;
}

#speedycache_promo li {
list-style-position: inside;
list-style-type: circle;
}

.speedycache-loc-types {
display:flex;
flex-direction: row;
align-items:center;
flex-wrap: wrap;
}

.speedycache-loc-types li{
list-style-type:none !important;
margin-right: 10px;
}

</style>

<script>
jQuery(document).ready( function() {
	(function($) {
		$("#speedycache_promo .speedycache_promo-close").click(function(){
			var data;
			
			// Hide it
			$("#speedycache_promo").hide();
			
			// Save this preference
			$.get("'.admin_url('admin-ajax.php?action=speedycache_hide_promo').'&security='.wp_create_nonce('speedycache_promo_nonce').'", data, function(response) {
				//alert(response);
			});
		});
	})(jQuery);
});
</script>';

function speedycache_base_promo(){
	echo '<div class="notice notice-success" id="speedycache_promo" style="min-height:120px; background-color:#FFF; padding: 10px;">
	<a class="speedycache_promo-close" href="javascript:" aria-label="Dismiss this Notice">
		<span class="dashicons dashicons-dismiss"></span> Dismiss
	</a>
	<table>
	<tr>
		<th>
			<img src="'.SPEEDYCACHE_URL.'/assets/images/logo.png" style="float:left; margin:10px 20px 10px 10px" width="100" />
		</th>
		<td>
			<p style="font-size:16px;">You have been using SpeedyCache for few days and we hope SpeedyCache is able to help you speedup your Website.<br/>
			If you like our plugin would you please show some love by doing actions like :
			</p>
			<p>
				<a class="speedycache_button speedycache_button1" target="_blank" href="https://speedycache.com/pricing">Upgrade to Pro</a>
				<a class="speedycache_button speedycache_button2" target="_blank" href="https://wordpress.org/support/view/plugin-reviews/speedycache">Rate it 5★\'s</a>
				<a class="speedycache_button speedycache_button3" target="_blank" href="https://www.facebook.com/speedycache/">Like Us on Facebook</a>
				<a class="speedycache_button speedycache_button4" target="_blank" href="https://twitter.com/intent/tweet?text='.rawurlencode('I use @speedycache improve performance of my #WordPress site - https://speedycache.com').'">Tweet about SpeedyCache</a>
			</p>
			<p style="font-size:16px">SpeedyCache Pro comes with features like <b>Critical CSS, Render-blocking JS, Async Google Fonts, Lazy Load, etc.</b> that speed your website further.</p>
	</td>
	</tr>
	</table>
</div>';
}

function speedycache_enable_nag(){
		echo '<script>
jQuery(document).ready( function() {
	(function($) {
		$("#speedycache_enable_nag .speedycache_nag-close").click(function(){
			var data;
			
			// Hide it
			$("#speedycache_enable_nag").hide();
			
			// Save this preference
			$.get("'.admin_url('admin-ajax.php?action=speedycache_hide_nag').'&security='.wp_create_nonce('speedycache_promo_nonce').'", data, function(response) {
				//alert(response);
			});
		});
	})(jQuery);
});
</script>
	<style>.speedycache_nag-close{
float:right;
text-decoration:none;
margin: 5px 10px 0px 0px;
}
.speedycache_nag-close:hover{
color: red;
}
</style>
	
	<div class="notice notice-error" id="speedycache_enable_nag">
		<a class="speedycache_nag-close" href="javascript:" aria-label="Dismiss this Notice">
			<span class="dashicons dashicons-dismiss"></span> Dismiss for 7 days
		</a>
		<div style="display:flex; align-items:center; padding: 10px 0;">
		<img src="'.SPEEDYCACHE_URL.'/assets/images/logo.png" width="50" height="50"/>
		<p style="font-size:16px;">SpeedyCache is installed. Enable caching to improve website speed. <a class="button button-primary" href="'.admin_url('admin.php?page=speedycache').'">Enable Now</a> or Test SpeedyCache Settings before enabling <a class="button button-primary" href="'.admin_url('admin.php?page=speedycache-test').'">Test SpeedyCache</a></p>
		</div>
	</div>';
}