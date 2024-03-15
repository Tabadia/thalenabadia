/*
* SPEEDYCACHE
* https://speedycache.com/
* (c) SpeedyCache Team
*/

if(window.onload) {
	var Conload = window.onload;
	var Nonload = function(evt) {
		Conload(evt);
		speedycache_column_button_action();
	};
	window.onload = Nonload;
} else {
	window.onload = speedycache_column_button_action;
}

function speedycache_column_button_action(){
	jQuery(document).ready(function(){
		jQuery('a[id^="speedycache-clear-cache-link"]').click(function(e){
			var post_id = jQuery(e.target).attr('data-id');
			var nonce = jQuery(e.target).attr('data-nonce');

			jQuery('#speedycache-clear-cache-link-' + post_id).css('cursor', 'wait');

			jQuery.ajax({
				type: 'GET',
				url: ajaxurl,
				data : {
					'action': 'speedycache_clear_cache_column',
					'id' : post_id,
					'security' : nonce
				},
				success: function(data){
					jQuery('#speedycache-clear-cache-link-' + post_id).css('cursor', 'pointer');

					if(typeof data.success != 'undefined' && data.success == true){
						alert('Cache Cleared Successfully!');
						return;
					}

					alert('Clear Cache Error');
				}
			});

			return false;
		});
	});
}