<?php

if(!defined('ABSPATH')){
	die();
}

echo '
<style>
.fileorganizer_button {
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

.fileorganizer_button:focus{
border: none;
color: white;
}

.fileorganizer_button1 {
color: white;
background-color: #4CAF50;
border:3px solid #4CAF50;
}

.fileorganizer_button1:hover {
box-shadow: 0 6px 8px 0 rgba(0,0,0,0.24), 0 9px 25px 0 rgba(0,0,0,0.19);
color: white;
border:3px solid #4CAF50;
}

.fileorganizer_button2 {
color: white;
background-color: #0085ba;
}

.fileorganizer_button2:hover {
box-shadow: 0 6px 8px 0 rgba(0,0,0,0.24), 0 9px 25px 0 rgba(0,0,0,0.19);
color: white;
}

.fileorganizer_button3 {
color: white;
background-color: #365899;
}

.fileorganizer_button3:hover {
box-shadow: 0 6px 8px 0 rgba(0,0,0,0.24), 0 9px 25px 0 rgba(0,0,0,0.19);
color: white;
}

.fileorganizer_button4 {
color: white;
background-color: rgb(66, 184, 221);
}

.fileorganizer_button4:hover {
box-shadow: 0 6px 8px 0 rgba(0,0,0,0.24), 0 9px 25px 0 rgba(0,0,0,0.19);
color: white;
}

.fileorganizer_promo-close{
float:right;
text-decoration:none;
margin: 5px 10px 0px 0px;
}

.fileorganizer_promo-close:hover{
color: red;
}

#fileorganizer_promo li {
list-style-position: inside;
list-style-type: circle;
}

.fileorganizer-loc-types {
display:flex;
flex-direction: row;
align-items:center;
flex-wrap: wrap;
}

.fileorganizer-loc-types li{
list-style-type:none !important;
margin-right: 10px;
}

</style>

<script>
jQuery(document).ready( function() {
	(function($) {
		$("#fileorganizer_promo .fileorganizer_promo-close").click(function(){
			var data;
			
			// Hide it
			$("#fileorganizer_promo").hide();
			
			// Save this preference
			$.get("'.admin_url('admin-ajax.php?action=fileorganizer_hide_promo').'&security='.wp_create_nonce('fileorganizer_promo_nonce').'", data, function(response) {
				//alert(response);
			});
		});
	})(jQuery);
});
</script>';

function fileorganizer_base_promo(){
	echo '<div class="notice notice-success" id="fileorganizer_promo" style="min-height:120px; background-color:#FFF; padding: 10px;">
	<a class="fileorganizer_promo-close" href="javascript:" aria-label="Dismiss this Notice">
		<span class="dashicons dashicons-dismiss"></span> Dismiss
	</a>
	<table>
	<tr>
		<th>
			<img src="'.FILEORGANIZER_URL.'/images/logo.png" style="float:left; margin:10px 20px 10px 10px" width="100" />
		</th>
		<td>
			<p style="font-size:16px;">You have been using FileOrganizer for few days and we hope FileOrganizer is able to help you to manage files from your Website.<br/>
			If you like our plugin would you please show some love by doing actions like :
			</p>
			<p>
				<a class="fileorganizer_button fileorganizer_button1" target="_blank" href="https://fileorganizer.net/pricing">Upgrade to Pro</a>
				<a class="fileorganizer_button fileorganizer_button2" target="_blank" href="https://wordpress.org/support/view/plugin-reviews/fileorganizer">Rate it 5★\'s</a>
				<a class="fileorganizer_button fileorganizer_button3" target="_blank" href="https://www.facebook.com/fileorganizer/">Like Us on Facebook</a>
				<a class="fileorganizer_button fileorganizer_button4" target="_blank" href="https://twitter.com/intent/tweet?text='.rawurlencode('I easily manage my #WordPress #files using @fileorganizer - https://fileorganizer.net').'">Tweet about FileOrganizer</a>
			</p>
			<p style="font-size:16px">FileOrganizer Pro comes with features like <b>Allow User Roles, Change Upload Size, User Restrictions, User Role Restrictions, Email Alert etc.</b> that helps you to manage files more securely at multiple user level.</p>
	</td>
	</tr>
	</table>
</div>';
}

