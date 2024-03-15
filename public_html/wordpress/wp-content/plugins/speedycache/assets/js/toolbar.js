/*
* SPEEDYCACHE
* https://speedycache.com/
* (c) SpeedyCache Team
*/

(function($) {
speedycache_toolbar();
	
function speedycache_toolbar() {
	jQuery('body').append('<div class="speedycache-loader"><div class="speedycache-loader-circle"></div></div>');
	
	if(typeof speedycache_toolbar_ajax.url == 'undefined'){
		alert('ajax has NOT been defined');
	}
	
	jQuery('#wp-admin-bar-speedycache-toolbar-parent-default li').click(function(e){
		var id = (typeof e.target.id != 'undefined' && e.target.id) ? e.target.id : jQuery(e.target).parent('li').attr('id');
		var action = '';
		
		if(id == 'wp-admin-bar-speedycache-toolbar-parent-settings'){
			if(jQuery('div[modal-id^="speedycache-modal-toolbarsettings"]').length !== 0){
				open_settings();
			}
			return;
		}

		switch(id){
			case 'wp-admin-bar-speedycache-toolbar-parent-delete-cache':
				action = 'speedycache_delete_cache';
				break;

			case 'wp-admin-bar-speedycache-toolbar-parent-delete-cache-and-minified':
				action = 'speedycache_delete_cache_and_minified';
				break;

			case 'wp-admin-bar-speedycache-toolbar-parent-clear-cache-of-this-page':
				action = 'speedycache_delete_current_page_cache';
				break;

			case 'wp-admin-bar-speedycache-toolbar-parent-clear-cache-of-allsites':
				action = 'speedycache_clear_cache_of_allsites';
				break;
		}
		
		toolbar_send({'action': action, 'path' : window.location.pathname, 'security' : speedycache_toolbar_ajax.nonce});
	});
	
	var open_settings = function() {
		jQuery('.speedycache-loader').css('display','flex');
		
		jQuery.ajax({
			type: 'GET',
			url: speedycache_toolbar_ajax.url,
			data : {
				'action': 'speedycache_toolbar_get_settings',
				'path' : window.location.pathname,
				'security' : speedycache_toolbar_ajax.nonce
			},
			success: function(data) {
				setTimeout(function(){
					jQuery('.speedycache-loader').hide();
				}, 500);
				
				if(!data.success){
					alert('Toolbar Settings Error!');
					return;
				}

				var data_json = {
					'action': 'speedycache_toolbar_save_settings',
					'path' : window.location.pathname,
					'roles' : {},
					'security' : speedycache_toolbar_ajax.nonce
				},
				speedycache_modal = jQuery('[modal-id="speedycache-modal-toolbarsettings"]');
		
				speedycache_modal.find('input[type="checkbox"]').each(function(){
					if(typeof data.roles[jQuery(this).attr('name')] != 'undefined'){
						jQuery(this).attr('checked', true);
					}
				});

				if(speedycache_modal && speedycache_modal.css('visibility') === 'hidden'){
					speedycache_modal.css('visibility','visible');
					close_modal();
				}
				
				speedycache_modal.find('.speedycache-modal-footer button').off('click').on('click', function() {
					speedycache_modal.find('input[type="checkbox"]:checked').each(function(){
						data_json.roles[jQuery(this).attr('name')] = 1;
					});
					
					toolbar_send(data_json);
					speedycache_modal.find('.speedycache-close-modal').trigger('click');
					alert('SpeedyCache Toolbar Settings Saved Successfully!');
				});
				
				
			}
		});
	}
	
	var close_modal = function() {
		jQuery('.speedycache-modal-footer > button, .speedycache-close-modal').on('click', function() {
			jQuery(this).closest('.speedycache-modal').find('form').trigger('reset');
			jQuery(this).closest('.speedycache-modal *').off();
			jQuery(this).closest('.speedycache-modal').css('visibility','hidden');
		});
	}
	
	var toolbar_send = function(data_json) {
		jQuery('.speedycache-loader').css('display','flex');
		
		jQuery.ajax({
			type: 'GET',
			url: speedycache_toolbar_ajax.url,
			data : data_json,
			success: function(data){
				
				if(!data.success){
					var speedycache_modal = jQuery('[modal-id="speedycache-modal-permission"]');
					
					if(speedycache_modal && speedycache_modal.css('visibility') === 'hidden'){
						speedycache_modal.css('visibility','visible');
						close_modal();
					}
				}
				
				try{
					speedycache_update_cache_stats();
				}catch(err) {
					//
				}
				
				setTimeout(function(){
					jQuery('.speedycache-loader').hide();
				}, 500);
			}
		});
	}
}
})(jQuery);