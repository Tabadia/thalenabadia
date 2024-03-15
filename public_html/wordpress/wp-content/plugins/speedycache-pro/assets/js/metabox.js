jQuery(document).ready(function() {
	
	jQuery('#speedycache-generate-specific-cache').on('click', function(){
		let ccss_disabled = jQuery('#speedycache-disable-critical-css:checked');
		
		if(ccss_disabled.length){
			alert('Critical CSS is disabled for this post');
			return;
		}

		jQuery.ajax({
			'method': 'POST',
			'url': speedycache_metabox.url,
			'data': {
				'post_id': speedycache_metabox.post_id,
				'security': speedycache_metabox.nonce,
				'action': 'speedycache_generate_single_ccss',
			},
			success: function(res){
				if(!res.success){
					alert(res.data.message ? res.data.message : 'Something went wrong ! Unable to intitiate Critical CSS!');
					return;
				}
				
				alert(res.data.message);
			}
		});
	});
});