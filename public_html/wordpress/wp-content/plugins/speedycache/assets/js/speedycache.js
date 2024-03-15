/*
* SPEEDYCACHE
* https://speedycache.com/
* (c) SpeedyCache Team
*/

jQuery(document).ready(function() {

	if(speedycache_ajax.premium){
		speedycache_image_optimization();
	}
	
	// Enable SpeedyCache Event
	jQuery('#speedycache_status').change(function() {
		if(!jQuery(this).is(':checked')){
			speedycache_toggle_settings_link(jQuery(this));
			return;
		}
		
		// Disable Test Mode if cache is enabled.
		if(jQuery('#speedycache_test_mode').is(':checked')){
			jQuery('#speedycache_test_mode').click();
		}
		
		speedycache_toggle_settings_link(jQuery(this));
		speedycache_open_modal(jQuery(this));
	});
	
	// Enable Test Mode
	jQuery('#speedycache_test_mode').change(function() {
		if(!jQuery(this).is(':checked')){
			return;
		}
		
		// Disable Cache when Test mode is enabled
		if(jQuery('#speedycache_status').is(':checked')){
			jQuery('#speedycache_status').click();
		}
		
		alert('Test Mode will be enabled for next 30 minutes');
	});

	jQuery('.speedycache-action-link').on('click', function(){
		let action_name = jQuery(this).attr('action-name');

		switch(action_name){
			case 'speedycache_critical_css':
				speedycache_critical_css();
				break;
		}
	});

	jQuery('.speedycache-disabled').on('click', function() {
		jQuery('.speedycache-disabled .speedycache-tool-tip').remove();

		speedycache_premium_tool_tip(jQuery(this));
	});

	//htaccess modal
	if(jQuery('[modal-id="speedycache-modal-htaccess"]').length) {
		var htaccess_modal = jQuery('[modal-id="speedycache-modal-htaccess"]');
	
		htaccess_modal.css('visibility','visible');
		htaccess_modal.find('label.speedycache-htaccess-label').html(jQuery('#speedycache-htaccess-path-data').html());
		htaccess_modal.find('textarea.speedycache-readonly-textarea').html(jQuery('#speedycache-htaccess-data').html());
		speedycache_close_modal();
	}

	if(jQuery('[modal-id="speedycache-modal-cloudflarewarning"]').length) {
		jQuery('[modal-id="speedycache-modal-cloudflarewarning"]').css('visibility','visible');
		speedycache_close_modal();
	}
	
	if(jQuery('[modal-id="speedycache-modal-disablewpcron"]').length) {
		jQuery('[modal-id="speedycache-modal-disablewpcron"]').css('visibility','visible');
		speedycache_close_modal();
	}
	
	// Add the target if Delete Fonts is enabled
	jQuery('#speedycache_delete_fonts').on('change', function(){
		if(jQuery(this).is(':checked')){
			jQuery('.speedycache-target-fonts').show();
			return;
		}
		
		jQuery('.speedycache-target-fonts').hide();
	});
	
	// Add the target if Delete Gravatars is enabled
	jQuery('#speedycache_delete_gravatars').on('change', function(){
		if(jQuery(this).is(':checked')){
			jQuery('.speedycache-target-gravatars').show();
			return;
		}
		
		jQuery('.speedycache-target-gravatars').hide();
	});
	
	// Add the target if Delete Minified is enabled
	jQuery('#speedycache_delete_minified').on('change', function(){
		if(jQuery(this).is(':checked')){
			jQuery('.speedycache-target-mini').show();
			return;
		}
		
		jQuery('.speedycache-target-mini').hide();
	});
	
	jQuery('#speedycache-toggle-logs').show();
	
	//Event Listener for Settings link for popup options
	jQuery('.speedycache-modal-settings-link').off('click').click(function() {
		var id = jQuery(this).attr('setting-id'),
		input = jQuery('#'+id);
		
		input.trigger('change');
	});
	
	let current_page = window.location.search.split('page=');
	
	if(current_page[1] && current_page[1] == 'speedycache-exclude'){
		speedycache_exclude_rules();
	} else if(current_page[1] && current_page[1] == 'speedycache-manage-cache'){
		speedycache_update_cache_stats();
		speedycache_cache_timeout();
	} else if(current_page[1] && current_page[1] == 'speedycache-cdn'){
		speedycache_cdn_settings();
	}
	
	speedycache_db_cleanup(); //fetches database cleanup data on load
	
	
	//Server Clock starts here
	var interval_id = '';
	interval_id = setInterval( function() { server_clock(); }, 1000);

	var server_clock = function(){
		jQuery('.speedycache-server-time').each(function(i, e){
			var time = jQuery(e).text().split(':');

			time[3]++;

			if(time[3] > 59){
				time[3] = '0';
				time[2]++;
			}

			if(time[2] > 59){
				time[2] = '0';
				time[1]++;
			}

			if(time[1] > 23){
				time[1] = '0';
			}

			jQuery(time).each(function(i, e){
				if((time[i] < 10) && ((time[i] + '').length < 2)){
					time[i] = '0' + time[i];
				}
			});

			jQuery(e).text(time.join(':'));
		});
	}
	//End of server Clock
	
	//Preload Option
	jQuery('#speedycache_preload').change(function() {
		if(!jQuery(this).is(':checked')){
			speedycache_toggle_settings_link(jQuery(this));
			return;
		}
		
		jQuery('.speedycache-sortable').sortable(
			{
				'placeholder' : 'speedycache-sortable-placeholder',
				stop : speedycache_sort_preload
			}
		);
		
		speedycache_toggle_settings_link(jQuery(this));
		speedycache_open_modal(jQuery(this));
	});
	
	//New Post Option
	jQuery('#speedycache_new_post').change(function() {
		if(!jQuery(this).is(':checked')){
			speedycache_toggle_settings_link(jQuery(this));
			return;
		}
		
		speedycache_toggle_settings_link(jQuery(this));
		speedycache_open_modal(jQuery(this));
	});
	
	//Updated Post Option
	jQuery('#speedycache_update_post').change(function() {
		if(!jQuery(this).is(':checked')){
			speedycache_toggle_settings_link(jQuery(this));
			return;
		}
		
		speedycache_toggle_settings_link(jQuery(this));
		speedycache_open_modal(jQuery(this));
	});

	// Varnish Option
  jQuery('#speedycache_purge_varnish').change(function() {
		if(!jQuery(this).is(':checked')){
			speedycache_toggle_settings_link(jQuery(this));
			return;
		}
		
		speedycache_toggle_settings_link(jQuery(this));
		speedycache_open_modal(jQuery(this));
	});
	
	// Critical CSS Status
	jQuery('#speedycache_critical_css').change(function(e) {
		let prevent_open = true;

		if(e.isTrigger){
			prevent_open = false;			
		}
		
		if(!jQuery(this).is(':checked')){
			speedycache_toggle_settings_link(jQuery(this));
			return;
		}

		speedycache_toggle_settings_link(jQuery(this));
		speedycache_open_modal(jQuery(this), prevent_open);
	});
	
	// Delay JS
	jQuery('#speedycache_delay_js').change(function() {
		if(!jQuery(this).is(':checked')){
			speedycache_toggle_settings_link(jQuery(this));
			return;
		}

		speedycache_toggle_settings_link(jQuery(this));
		speedycache_open_modal(jQuery(this));
	});
	
	// Unused CSS
	jQuery('#speedycache_unused_css').change(function() {
		if(!jQuery(this).is(':checked')){
			speedycache_toggle_settings_link(jQuery(this));
			return;
		}

		speedycache_toggle_settings_link(jQuery(this));
		speedycache_open_modal(jQuery(this));
	});
	
	// Listener for Post revision option
	jQuery('#speedycache_limit_post_revision').change(function() {
		if(!jQuery(this).is(':checked')){
			speedycache_toggle_settings_link(jQuery(this));
			return;
		}

		speedycache_toggle_settings_link(jQuery(this));
		speedycache_open_modal(jQuery(this));
	});
	
	// Listner for Heartbeat option
	jQuery('#speedycache_update_heartbeat').change(function() {
		if(!jQuery(this).is(':checked')){
			speedycache_toggle_settings_link(jQuery(this));
			return;
		}

		speedycache_toggle_settings_link(jQuery(this));
		speedycache_open_modal(jQuery(this));
	});

	// GZIP option
	jQuery('#speedycache_gzip').click(function() {
		if(!jQuery(this).is(':checked')){
			return;
		}
		
		speedycache_open_modal(jQuery(this));
	});
	
	// Critcial Images
	jQuery('#speedycache_critical_images').change(function() {
		if(!jQuery(this).is(':checked')){
			speedycache_toggle_settings_link(jQuery(this));
			return;
		}

		speedycache_toggle_settings_link(jQuery(this));
		speedycache_open_modal(jQuery(this));
	});
	
	// DNS Prefetch
	jQuery('#speedycache_dns_prefetch').change(function() {
		if(!jQuery(this).is(':checked')){
			speedycache_toggle_settings_link(jQuery(this));
			return;
		}

		speedycache_toggle_settings_link(jQuery(this));
		speedycache_open_modal(jQuery(this));
	});
	
	// Prelaod Resource
	jQuery('#speedycache_preload_resources').change(function() {
		if(!jQuery(this).is(':checked')){
			speedycache_toggle_settings_link(jQuery(this));
			return;
		}

		speedycache_toggle_settings_link(jQuery(this));
		speedycache_open_modal(jQuery(this));
	});

	// Preconnect
	jQuery('#speedycache_pre_connect').change(function() {
		if(!jQuery(this).is(':checked')){
			speedycache_toggle_settings_link(jQuery(this));
			return;
		}

		speedycache_toggle_settings_link(jQuery(this));
		speedycache_open_modal(jQuery(this));
	});
	
	// Lazy Load HTML element
	jQuery('#speedycache_lazy_load_html').change(function() {
		if(!jQuery(this).is(':checked')){
			speedycache_toggle_settings_link(jQuery(this));
			return;
		}

		speedycache_toggle_settings_link(jQuery(this));
		speedycache_open_modal(jQuery(this));
	});

	// Manage Cache Tab
	jQuery('#speedycache-manage-cache').change(function() {
		if(!jQuery(this).is(':checked')) {
			return;
		}
		
		speedycache_update_url(jQuery(this));
		speedycache_delete_cache_tab(jQuery(this));
		speedycache_cache_timeout();
	});
	
	//Exclude Tab
	jQuery('#speedycache-exclude').change(function() {
		if(!jQuery(this).is(':checked')) {
			return;
		}
		
		speedycache_update_url(jQuery(this));
		speedycache_exclude_rules();
	});

	//CDN Tab
	jQuery('#speedycache-cdn').change(function() {
		if(!jQuery(this).is(':checked')) {
			return;
		}
		
		speedycache_update_url(jQuery(this));
		speedycache_cdn_settings();
	});
	
	jQuery('#speedycache-options').change(function() {
		if(!jQuery(this).is(':checked')) {
			return;
		}
		
		speedycache_update_url(jQuery(this));
	});
	
	jQuery('#speedycache-image-optimisation').change(function() {
		if(!jQuery(this).is(':checked')) {
      return;
		}
		
		speedycache_update_url(jQuery(this));
	});
	
	jQuery('#speedycache-bloat').change(function() {
		if(!jQuery(this).is(':checked')) {
			return;
		}
		
		speedycache_update_url(jQuery(this));
	});
	
	jQuery('#speedycache-support').change(function() {
		if(!jQuery(this).is(':checked')) {
			return;
		}
		
		speedycache_update_url(jQuery(this));
	});
	
	//Database Cleanup Tab
	jQuery('#speedycache-db').change(function() {
		if(!jQuery(this).is(':checked')) {
			return;
		}
		
		speedycache_update_url(jQuery(this));
		speedycache_db_cleanup();
	});
	
	// Object Cache Tab
	jQuery('#speedycache-object').change(function() {
		if(!jQuery(this).is(':checked')) {
			return;
		}
		
		speedycache_update_url(jQuery(this));
	});
	
	jQuery('.speedycache-flush-db').click(function(e) {
		e.preventDefault();
		
		let loader = jQuery('.speedycache-btn-loader');
		loader.show();
		
		jQuery.ajax({
			'method' : 'GET',
			'url' : speedycache_ajax.url + '?action=speedycache_flush_objects&security='+speedycache_ajax.nonce,
			'success' : function(res){
				loader.hide();

				if(res.success){
					alert(res.data.message);
					return;
				}
				
				alert(res.data.message);
			}
		});
	});
	
	jQuery('.speedycache_preloading_add').click(function(e) {
		e.preventDefault();
		
		let ele = jQuery(this),
		loader = ele.find('.speedycache-btn-loader'),
		form = ele.closest('form'),
		error = false;

		if(!form){
			alert('Unable to get the form details!');
			return;
		}
		
		let form_type = form.data('type');

		// Disabling Add Button
		ele.prop('disabled', true);

		loader.show();
		
		let form_val = {};
		
		form_data = form.serializeArray();
		
		form_data.forEach((field) => {
			form_val[field.name] = field.value;
			
			if(!field.value){
				error = true;
			}
		});

		if(error){
			alert('Fill all the fields before adding');
			loader.hide();
			ele.prop('disabled', false);
			return;
		}

		jQuery.ajax({
			'method' : 'POST',
			'url' : speedycache_ajax.url,
			'data' : {
				action : 'speedycache_preloading_add_settings',
				settings : form_val,
				type : form_type,
				security : speedycache_ajax.nonce
			},
			'success' : function(res){
				loader.hide();
				ele.prop('disabled', false);
				
				if(!res){
					alert('Something went wrong, the response returned is empty');
					return;
				}
				
				if(!res.success){
					alert(res.data);
					return;
				}
				
				let table = ele.closest('.speedycache-modal-content').find('table');
				
				html = `<td>${form_val.resource}</td>
					
					${form_type != 'pre_connect_list' ? '<td>'+form_val.type+'</td>' : ''} 
					<td>${form_val.crossorigin ? 'Yes' : 'No'}</td>
					<td data-key="${res.data}"><span class="dashicons dashicons-trash"></span></td>`;
				
				
				if(table.find('.speedycache-preloading-empty').length  > 0){
					let tr = table.find('.speedycache-preloading-empty').closest('tr');
					table.find('.speedycache-preloading-empty').remove();
					
					tr.append(html);
				} else {
					let tbody = table.find('tbody');

					tbody.append('<tr>'+html+'</tr>');
				}
				
				// Resetting the form
				form.find('input, select').map(function(){
					let type = jQuery(this).prop('type');
					
					if(type == 'checkbox'){
						jQuery(this).prop('checked', false);
						return;
					} else 
					
					jQuery(this).val('');
					
				});

				alert('Settings Saved Successfully');
			}
		});
	});
	
	jQuery('.speedycache-preloading-table').on('click', '.dashicons-trash', function(){
		let ele = jQuery(this),
		key = ele.closest('td').data('key'),
		type = ele.closest('table').data('type');
		
		
		jQuery.ajax({
			'method' : 'POST',
			'url' : speedycache_ajax.url,
			'data' : {
				action : 'speedycache_preloading_delete_resource',
				type : type,
				key : key,
				security : speedycache_ajax.nonce
			},
			success : function(res){
				if(!res || !res.success){
					alert(res.data ? res.data : 'Unable to delete this resource');
					return;
				}
				
				ele.closest('tr').remove();
			}
		});
		
	});
	
	//if "Mobile Theme" has been selected, "Mobile" option cannot be changed
	jQuery('#speedycache_mobile').click(function(e) {	
		if(jQuery(this).is(':checked')) {
			return;
		}

		if(jQuery('#speedycache_mobile_theme').is(':checked')) {
			jQuery('#speedycache_mobile').prop('checked', true);
			
			speedycache_tooltip({
				jEle : jQuery('#speedycache_mobile_theme').closest('label'),
				html : 'Turn This off first',
			});
		}
	});
	
	//Mobile There Option
	jQuery('#speedycache_mobile_theme').click(function() {
		if(!jQuery(this).is(':checked')){
			return;
		}
		
		/* For Mobile theme to work Mobile option should be enabled
		* Mobile option prevents desktop cache version to show on mobile
		*/
		jQuery('#speedycache_mobile').prop('checked', true);
	});
	
	jQuery('#speedycache_automatic_cache').change(function() {
		if(!jQuery(this).is(':checked')) {
			speedycache_toggle_settings_link(jQuery(this));
			return;
		}
		
		speedycache_toggle_settings_link(jQuery(this));
		speedycache_open_modal(jQuery(this));
	});
	
	jQuery('#speedycache_minify_css').change(function() {
		if(jQuery(this).is(':checked')) {
			return;
		}
		
		if(jQuery('#speedycache_minify_css_enhanced').is(':checked')) {
			jQuery('#speedycache_minify_css').prop('checked', true);
			speedycache_tooltip({
				jEle : jQuery('#speedycache_minify_css_enhanced').closest('label'),
				html : 'Turn This off first',
			});
		}
	});
	
	jQuery('#speedycache_minify_css_enhanced').change(function() {
		if(!jQuery(this).is(':checked')) {
			return;
		}
		
		jQuery('#speedycache_minify_css').prop('checked', true);
	});
	
	jQuery('#speedycache_render_blocking').change(function() {
		if(jQuery(this).is(':checked')) {
			return;
		}
		
		if(jQuery('#speedycache_google_fonts').is(':checked')) {
			jQuery('#speedycache_render_blocking').prop('checked', true);
			speedycache_tooltip({
				jEle : jQuery('#speedycache_google_fonts').closest('label'),
				html : 'Turn This off first',
			});
		}
	});
	
	jQuery('#speedycache_google_fonts').change(function() {
		if(!jQuery(this).is(':checked')) {
			return;
		}
		
		jQuery('#speedycache_render_blocking').prop('checked', true);
	});
	
	//Lazy Load Option
	jQuery('#speedycache_lazy_load').change( function() {
		if(!jQuery(this).is(':checked')){
			speedycache_toggle_settings_link(jQuery(this));
			return;
		}
		
		speedycache_toggle_settings_link(jQuery(this));
		
		jQuery('#speedycache_mobile').prop('checked', true);
		jQuery('#speedycache_mobile_theme').prop('checked', true);
		
		speedycache_open_modal(jQuery(this));
		speedycache_lazy_load_settings(jQuery(this));
	});
	
	//Toggle Delete Log
	jQuery('#speedycache-show-delete-log, #speedycache-hide-delete-log').click(function(e){
		if(e.target.id == 'speedycache-show-delete-log'){
			jQuery(e.target).hide();
			jQuery('#speedycache-hide-delete-log').show();
			jQuery('#speedycache-delete-logs').show();
			$blocks = jQuery('#speedycache-cache-statics').closest('.speedycache-tab-delete-cache').children('.speedycache-block');
			$blocks.hide();
		}else if(e.target.id == 'speedycache-hide-delete-log'){
			jQuery(e.target).hide();
			jQuery('#speedycache-delete-logs').hide();
			jQuery('#speedycache-show-delete-log').show();
			$blocks = jQuery('#speedycache-cache-statics').closest('.speedycache-tab-delete-cache').children('.speedycache-block');
			$blocks.show();
		}
	});
	
	
	// Test Optimization button listener
	jQuery('#speedycache-test-btn').click(speedycache_test_optimization);

	// Save test settings listener
	jQuery('.speedycache-copy-test-settings').click(function(e){
		e.preventDefault();
		
		if(confirm('It will overwrite any already saved settings!\nDo you want to continue?') !== true){
			return;
		}
		
		jQuery.ajax({
			method : 'GET',
			url : speedycache_ajax.url + '?action=speedycache_copy_test_settings&security='+speedycache_ajax.nonce,
			success: function(res){
				if(res.success){
					alert('The settings has been successfully saved!');
					return;
				}
				
				if(res.data){
					alert(res.data);
				}
			}
		});
	});
	
	
	//speedycache_update_url();
});

// Update URL when the tab is clicked
function speedycache_update_url(jEle = null) {
	if(jEle) {
		let tab_id = jEle.attr('id'),
		url = new URL(window.location);
		
		if(tab_id == 'speedycache-options'){
			tab_id = 'speedycache';
		}
		
		url.searchParams.set('page', tab_id);
		window.history.pushState({}, '', url);
		return;
	}
	
}

//Close SpeedyCache Modal
function speedycache_close_modal() {
	jQuery('.speedycache-modal-footer > button, .speedycache-close-modal').on('click', function() {
		jQuery(this).closest('.speedycache-modal').find('form').trigger('reset');
		jQuery(this).closest('.speedycache-modal *').off();
		jQuery(this).closest('.speedycache-modal').css('visibility','hidden');
	});
}

function speedycache_add_loader() {
	jQuery('.speedycache-loader').css('display','flex');
}

function speedycache_hide_loader() {
	jQuery('.speedycache-loader').hide();
}

function speedycache_open_modal(jEle, prevent_open) {
	var id_attr = 'id';
	
	if(prevent_open){
		return;
	}
	
	if(jEle.attr('modal-id')) {
		id_attr = 'modal-id'
	}
	
	//For Settings Link
	if(jEle.attr('setting-id')) {
		id_attr = 'setting-id';
	}
	
	var modal_id = jEle.attr(id_attr),
	speedycache_modal = jQuery("div[modal-id='"+modal_id+"']");
	
	if(speedycache_modal && speedycache_modal.css('visibility') === 'hidden') {
		speedycache_modal.css('visibility','visible');
		speedycache_close_modal();
	}
}

//Fills the input with a string of preload page order seperated by Comma
function speedycache_sort_preload(event, ui) {
	var target= jQuery(event.target),
	sortables = target.closest('.speedycache-sortable').find('.ui-sortable-handle'),
	
	sorted_string = sortables.map(function() {
		return jQuery(this).data('type');
	}).get().join(',');
	
	target.closest('.speedycache-modal').find('#speedycache_preload_order').val(sorted_string);
}

function speedycache_delete_cache_tab(jEle) {
	if(!jEle.is(':checked')){
		return;
	}
	
	speedycache_update_cache_stats();
}

//Updates Cache stats present on the Delete Cache Tab
function speedycache_update_cache_stats() {
	var stats_holder = jQuery('#speedycache-cache-statics');
	
	if(!speedycache_ajax.premium){
		return;
	}
	
	var size_string = function(size) {
		if(size > 1000) {
			size = size / 1000;
			return size.toFixed(2) + ' MB';
		}
		
		return size + ' KB';
	}
	
	jQuery.ajax({
		method: 'GET',
		url: speedycache_ajax.url+ '?action=speedycache_cache_statics_get',
		data : {
			'security' : speedycache_ajax.nonce
		},
		beforeSend: function() {
			speedycache_add_loader();
		},
		success : function(data) {
			for(var type in data) {
				var data_wrapper = stats_holder.find('#speedycache-cache-statics-'+type+'-data'),
				size_text = data[type].size ? size_string(data[type].size) : '0 KB',
				file_text = data[type].file ? 'of ' + data[type].file + ' Items' : 'of 0 Items';
				
				data_wrapper.find('.speedycache-size').text(size_text);
				data_wrapper.find('.speedycache-files').text(file_text);
			}
			
			speedycache_hide_loader();
		}
	})
}

//speedycache Timeout
function speedycache_cache_timeout() {
	var timeout_list = jQuery('.speedycache-timeout-list'),
	lang = {
		'startwith' : { 'en' : 'Starts With' },
		'homepage' : { 'en' : 'Homepage' },
		'exact' : { 'en' : 'Is Equal To' },
		'contain' : { 'en' : 'Contains' },
	},
	interval_id = '';
	
	var init = function() {	
		jQuery('#speedycache-timeout').click(function() {
			speedycache_open_modal(jQuery(this));
			var modal_id = jQuery(this).attr('id');
			speedycache_modal = jQuery('div[modal-id="'+modal_id+'"]');
			
			timeout_listeners();
		});
		
		if(timeout_list.length) {
			load_timeout_rules();
		}
		
		timeout_list.find('.dashicons-trash').on('click', delete_tile);
		timeout_list.find('.dashicons-edit').on('click', edit_tile);	
	}
	
	//Toggles Rule Line input based on prefix
	var prefix_input_listener = function() {
		var rule_prefix = jQuery(this),
		rule_content = rule_prefix.parent().next('.speedycache-timeout-rule-line-middle');
	
		//Hides input if value is not homepage
		//This regex matches if these options dosent matches
		if(rule_prefix.val().match(/^(?!(all|homepage))/i)) {
			if(rule_content.hasClass('speedycache-hidden')) {
				rule_content.removeClass('speedycache-hidden');
			}
			
			return;
		}
		
		rule_content.addClass('speedycache-hidden');
		rule_content.val('');
	}
	
	//Toggles Hour and minute input if the time is set to 'Once a day'
	var schedule_input_listener = function() {
		var at_text = speedycache_modal.find('.speedycache-timeout-at-text'),
		at_hour = speedycache_modal.find('[name="speedycache-timeout-rule-hour"]'),
		at_min = speedycache_modal.find('[name="speedycache-timeout-rule-minute"]');
		
		if(jQuery(this).val().match(/^(onceaday|daily)/i)) {
			at_text.show();
			at_hour.show();
			at_min.show();
			
			return;
		}
		
		at_text.hide();
		at_hour.hide();
		at_min.hide();
	};
	
	var timeout_listeners = function() {
		speedycache_modal.find('[name="speedycache-timeout-rule-prefix"]').on('change.modal', prefix_input_listener);
		speedycache_modal.find('[name="speedycache-timeout-rule-schedule"]').on('change.modal', schedule_input_listener);
		speedycache_modal.find('.speedycache-modal-footer > button').off('click').on('click.modal', add_timeout_tile);
	}
	
	//Renders the tile
	var render_tile = function(tile_num, rule) {
		var data_attrs = '';
		
		//Converting all the rule attributes to a single string to append to the parent
		for(var input in rule) {
			data_attrs += ` data-${input}="${rule[input]}"`;
		}
		
		var tile_html = `<div class="speedycache-card speedycache-timeout-rule" ${data_attrs} data-tile-num="${tile_num}">`;
		
		tile_html += `
				<div class="speedycache-card-body speedycache-timeout-wrap">
				<div>
				<div class="speedycache-timeout-title">
				<strong>`+
				ln(lang, rule['prefix'])+ ' :</strong> ' + rule['content']+
				'<span class="speedycache-timeout-deletes"><strong>Delete Files</strong> ' + (speedycache_ajax.schedules[rule['schedule']] ? speedycache_ajax.schedules[rule['schedule']].display : rule['schedule']);

		if(rule['schedule'] == 'onceaday') {
			tile_html += ' <span class="speedycache-timeout-time-clock">at ';
			tile_html += rule['hour'] ? rule['hour'] + ' hours : ' : '0';
			tile_html += rule['minute'] ? rule['minute'] + ' minutes': '0';
			tile_html += '</span>'
		}
	
		tile_html += `</span></div>
					<div class="speedycache-timeout-url">`;
		tile_html += create_url_description(rule['prefix'], rule['content']);			
		tile_html += '</div></div>';
		tile_html += '<div class="speedycache-tile-action"><span class="dashicons dashicons-edit" title="Edit"></span><span class="dashicons dashicons-trash" title="Delete"></span></div>'
		tile_html += '</div></div>';
		
		timeout_list.append(tile_html);	
	}
	
	var speedycache_executed_delete = false;
	var delete_tile = function() {
		jQuery(this).closest('.speedycache-timeout-rule').remove();
		
		if(speedycache_executed_delete) {
			return;
		}
		
		speedycache_executed_delete = true;
		
		setTimeout( function() {
			save_timeout();
			speedycache_executed_delete = false;
		}, 1000);
	}
	
	//Adds already listed rules on load
	var load_timeout_rules = function () {
		if(!speedycache_ajax.timeout_rules) {
			return;
		}
		
		timeout_list.empty();
		
		for(var [index, rule] of speedycache_ajax.timeout_rules.entries()) {
			render_tile(index+1, rule);
		}
	}
	
	//Opens up Modal with populated inputs
	var edit_tile = function() {
		var tile = jQuery(this).closest('.speedycache-timeout-rule');
		jQuery('#speedycache-timeout').trigger('click');
		
		var modal = jQuery('[modal-id="speedycache-timeout"]'),
		el_prefix = modal.find('[name="speedycache-timeout-rule-prefix"]'),
		el_content = modal.find('[name="speedycache-timeout-rule-content"]'),
		el_schedule = modal.find('[name="speedycache-timeout-rule-schedule"]'),
		el_hour = modal.find('[name="speedycache-timeout-rule-hour"]'),
		el_minute = modal.find('[name="speedycache-timeout-rule-minute"]');
		
		el_prefix.val(tile.data('prefix'));
		el_prefix.trigger('change');
		
		if(tile.data('prefix') != 'homepage') {
			el_content.val(tile.data('content'));
		}
		
		el_schedule.val(tile.data('schedule'));
		el_schedule.trigger('change');
		
		if(tile.data('schedule')) {
			el_hour.val(tile.data('hour'));
			el_minute.val(tile.data('minute'));
		}
		
		modal.find('.speedycache-modal-footer > button').off('click').on('click', function() {
			update_tile(jQuery(this), tile);
		});
	}
	
	var update_tile = function(jEle, tile) {
		var modal = jEle.closest('.speedycache-modal');
		
		var prefix = modal.find('[name="speedycache-timeout-rule-prefix"]').val();
		tile.data('prefix', prefix);
		
		if(prefix != 'homepage') {
			var content = modal.find('[name="speedycache-timeout-rule-content"]').val();
			tile.data('content', content);
		}
		
		var schedule = modal.find('[name="speedycache-timeout-rule-schedule"]').val();
		tile.data('schedule', schedule);
		
		if(schedule) {
			var hour = modal.find('[name="speedycache-timeout-rule-hour"]').val(),
			minute = modal.find('[name="speedycache-timeout-rule-minute"]').val();
			
			tile.data('hour', hour);
			tile.data('minute', minute);
		}
	
		content = content ? content : '';
		
		var title = '<strong>'+ln(lang, prefix)+ ' :</strong> ' + content,
		time_str = '<span class="speedycache-timeout-deletes"><strong>Delete Files</strong> '+speedycache_ajax.schedules[schedule].display+'</span>';
		
		if(schedule == 'onceaday') {
			time_str += ' <span class="speedycache-timeout-time-clock">at ';
			time_str += hour ? hour + 'hours : ' : '0';
			time_str += minute ? minute + ' minutes': '0';
			time_str += '</span>';
		}
	
		var edit_tile = timeout_list.find('[data-tile-num='+tile.data('tile-num')+']');

		edit_tile.find('.speedycache-timeout-title').empty();
		edit_tile.find('.speedycache-timeout-title').append(title);
		edit_tile.find('.speedycache-timeout-title').append(time_str);
		edit_tile.find('.speedycache-timeout-url').empty();
		edit_tile.find('.speedycache-timeout-url').append(create_url_description(prefix, content));
		
		save_timeout();
		speedycache_modal.find('form').trigger('reset');
		//speedycache_modal.find('.speedycache-close-modal').trigger('click');
		jQuery.when(speedycache_modal.find('.speedycache-close-modal').trigger('click')).done(function() {
			clearInterval(interval_id);
		});
	}

	//Returns corrosponding language if available
	var ln = function(lang, word) {
		return lang[word] ? lang[word].en : word.toUpperCase();
	}
	
	//Contructs URL based on Prefix
	var create_url_description = function(prefix, content){
		var request_uri = content;
		var b_start = "<b style='font-size:11px;color:var(--speedycache-color);'>";
		var b_end = "</b>"

		if(prefix == 'exact'){
			request_uri = b_start + content + b_end;
		}else if(prefix == 'startwith'){
			request_uri = b_start + content + b_end + '(.*)';
		}else if(prefix == 'contain'){
			request_uri = '(.*)' + b_start + content + b_end + '(.*)';
		}else if(prefix == 'homepage' || prefix == 'all'){
			request_uri = "";
		}

		return speedycache_ajax.home_url + '/' + request_uri;
	}
	
	//Saving Timeout data
	var save_timeout = function() {
		var eleRules = timeout_list.find('.speedycache-timeout-rule');
		rules = [];
		
		eleRules.each( function() {
			var rule = jQuery(this),
			prefix = rule.data('prefix'),
			content = rule.data('content'),
			schedule = rule.data('schedule'),
			hour = rule.data('hour'),
			minute = rule.data('minute');
			
			rules.push({'prefix':prefix, 'content':content, 'schedule':schedule, 'hour':hour, 'minute':minute});
		});
		
		jQuery.ajax({
			type :'POST',
			url: speedycache_ajax.url + '?action=speedycache_save_timeout_pages',
			data : {
				'rules' : rules,
				'security' : speedycache_ajax.nonce
			},
			success : function(res) {

				if(res.success){
					speedycache_ajax.timeout_rules = rules;
					return;
				}
				
				alert('This rule can\'t be added');
			}
		})
	}
	
	var add_timeout_tile = function() {
		var formData = speedycache_modal.find('form').serializeArray();
		
		//removing prefixes to the names
		for(var input of formData) {
			input.name = input.name.replace('speedycache-timeout-rule-', '');
		}
		
		formData = speedycache_convert_serialized(formData);
		
		render_tile(timeout_list.length+1, formData);
		save_timeout();
		speedycache_modal.find('form').trigger('reset');
		timeout_list.find('.dashicons-trash').on('click', delete_tile);
		timeout_list.find('.dashicons-edit').on('click', edit_tile);	
		//speedycache_modal.find('.speedycache-close-modal').trigger('click');
		jQuery.when(speedycache_modal.find('.speedycache-close-modal').trigger('click')).done(function() {
			clearInterval(interval_id);
		});
	}
	
	init();
}

function speedycache_lazy_load_settings(jEle) {
	var modal_id = jEle.prop('id'),
	speedycache_modal = jQuery('div[modal-id="'+modal_id+'"]'),
	ll_palceholder = speedycache_modal.find('.speedycache_lazy_load_placeholder'),
	keyword_input = speedycache_modal.find('.speedycache-exclude-source-keyword'),
	ll_keywords = speedycache_modal.find('#speedycache_lazy_load_keywords');
	
	//Creates the Keyword tags
	var populate_keywords = function() {
		
		if(ll_keywords.val() == '') {
			return;
		}
		
		var keywords = ll_keywords.val().split(','),
		tag_holder = speedycache_modal.find('.speedycache-tags-holder');
		
		tag_holder.empty();
		
		for(var i in keywords ) {
			tagHTML = `<div class="speedycache-tag">
				<div class="speedycache-tag-text">${keywords[i]}</div>
				<div class="speedycache-tag-remove">&#10006;</div>
			</div>`;
			
			tag_holder.append(tagHTML);
		}
	}
	
	populate_keywords();
	
	//Toggels visibility of custom Placeholder Input
	ll_palceholder.on('change.modal', function() {
		var custom_url = speedycache_modal.find('[name="speedycache_lazy_load_placeholder_custom_url"]');
		
		if( ll_palceholder.val() != 'custom' ) {
			if(custom_url.length) {
				custom_url.val(jQuery(this).val());
				custom_url.addClass('speedycache-hidden');
			}
			
			return;
		}
		
		custom_url.removeClass('speedycache-hidden');
		custom_url.val('');
	});
	
	//Event Listener for keywords when "," is used
	keyword_input.on('keyup.modal', function(e) {		
		if(e.code == 'Enter' || e.code == 'Comma') {
			e.preventDefault();
			
			var tag_holder = speedycache_modal.find('.speedycache-tags-holder'),
			tag_text = e.target.value.replace(/[$\,]/gi, '');
			e.target.value = '';
			
			if(tag_text == '') {
				return;
			}
			
			tagHTML = `<div class="speedycache-tag">
				<div class="speedycache-tag-text">${tag_text}</div>
				<div class="speedycache-tag-remove">&#10006;</div>
			</div>`;
			
			tag_holder.append(tagHTML);
			
			//Combines a array of values to a string seperated with commas
			var combined_keywords = tag_holder.find('.speedycache-tag .speedycache-tag-text').map( function() {
				return jQuery(this).text();
			}).get().join(',');
			
			ll_keywords.val(combined_keywords);			
		}
	});
	
	speedycache_modal.on('click.modal', '.speedycache-tag-remove', function() {
		var tag_holder = speedycache_modal.find('.speedycache-tags-holder');
	
		jQuery(this).closest('.speedycache-tag').remove();
		
		//Combines a array of values to a string seperated with commas
		var combined_keywords = tag_holder.find('.speedycache-tag .speedycache-tag-text').map( function() {
			return jQuery(this).text();
		}).get().join(',');
		
		ll_keywords.val(combined_keywords);
	});	
}

function speedycache_exclude_rules() {
	var type = '';
	
	var prefix_input_listener = function(e) {
		var target = jQuery(e.target),
			prefix = target.val();
		
		if(prefix.match(/^(homepage|category|tag|archive|post|page|attachment|googleanalytics|woocommerce_items_in_cart)$/)){
			target.closest('form').find('.speedycache-exclude-rule-line-middle').hide();
			return;
		}
	
		target.closest('form').find('.speedycache-exclude-rule-line-middle').show();
	}
	
	//Returns corrosponding language if available
	var ln = function(lang, word) {
		return lang[word] ? lang[word].en : word.toUpperCase();
	}
	
	var lang = {
		'startwith' : { 'en' : 'Starts With'},
		'homepage' : { 'en' : 'Home Page' },
		'exact' : { 'en' : 'Is Equal To' },
		'contain' : { 'en' : 'Contains' },
		'cookie' : { 'en' : 'Cookie' },
		'woocommerce_items_in_cart' : { 'en' : 'Woocommerce Items in Cart' },
		'page' : { 'en' : 'Pages' },
		'tag' : { 'en' : 'Tags' },
		'category' : { 'en' : 'Categories' },
		'archive' : { 'en' : 'Archive' },
		'attachment' : { 'en' : 'Attachment' },
		'googleanalytics' : { 'en' : 'has Google Analytics Parameters' },
	}
	
	//Loads already save exclude rules on load of the tab
	var load_exclude_tiles = function() {
		var logintxt = speedycache_ajax.sitepad ? 'login.php' : 'wp-login.php',
			admintxt = speedycache_ajax.sitepad ? 'site-admin' : 'wp-admin';
		
		//constant rules
		var constant_rules = [
			{'type':'page', 'prefix':'exact', 'content': logintxt, 'editable':false, 'id':new Date().getTime()},
			{'type':'page', 'prefix':'startwith', 'content':admintxt, 'editable':false, 'id':new Date().getTime()},
			{'type':'useragent', 'prefix':'contain', 'content':'facebookexternalhit', 'editable':false, 'id':new Date().getTime()},
			{'type':'useragent', 'prefix':'contain', 'content':'LinkedInBot', 'editable':false,'id':new Date().getTime()},
			{'type':'useragent', 'prefix':'contain', 'content':'WhatsApp', 'editable': false, 'id':new Date().getTime()},
			{"type" : 'useragent', 'prefix' : 'contain', 'content' : 'Twitterbot', 'editable' : false, 'id':new Date().getTime()},
			{'type':'cookie', 'prefix':'contain', 'content':'Admin', 'editable':false, 'id':new Date().getTime()},	
		];
		
		for(var rule of constant_rules) {
			jQuery('.speedycache-exclude-' + rule['type'] + '-list').empty();	
		}
		
		for(var rule of constant_rules) {
			render_tile(rule);
		}
		
		jQuery('.speedycache-exclude-rule[data-editable="true"').remove();
		
		if(speedycache_ajax.exclude_rules.length) {
			for(var [i, rule] of speedycache_ajax.exclude_rules.entries()) {
				rule['id'] = i+1;
				rule['editable'] = true;
				
				render_tile(rule);
			}			
		}
	}
	
	//Contructs URL based on Prefix
	var create_description = function(prefix, content){
		var request_uri = content;
		var b_start = "<b style='font-size:11px;color:var(--speedycache-color);'>";
		var b_end = '</b>'
		
		if(prefix.match(/^(homepage|category|tag|archive|post|page|attachment|googleanalytics|woocommerce_items_in_cart)$/)){
			if(prefix == 'homepage'){
				return 'The' + b_start +' '+ ln(lang, prefix) +' '+ b_end + 'has been excluded';
			} else if(prefix == 'woocommerce_items_in_cart') {
				return '<strong>Cookie : </strong>' + prefix; 
			}
			
			else{
				return 'All' + ' ' + b_start + ' '+ ln(lang,prefix).toLowerCase() + b_end + ' ' + 'have been excluded';
			}
		}
		
		if(prefix == 'exact'){
			request_uri = b_start + content + b_end;
		}else if(prefix == 'startwith'){
			request_uri = b_start + content + b_end + '(.*)';
		}else if(prefix == 'contain'){
			request_uri = '(.*)' + b_start + content + b_end + '(.*)';
		}

		return speedycache_ajax.home_url + '/' + request_uri;
	}
	
	//adds the html to the dom
	var render_tile = function(rule) {		
		var data_attrs = '';
		
		//Converting all the rule attributes to a single string to append to the parent
		for(var input in rule) {
			data_attrs += ` data-${input}="${rule[input]}"`;
		}
		
		var tile_html = `<div class="speedycache-card speedycache-exclude-rule" ${data_attrs}">`;
		
		tile_html += `
				<div class="speedycache-card-body speedycache-exclude-wrap">
				<div>
				<div class="speedycache-exclude-title">
				<strong>`+
				ln(lang, rule['prefix'])+ ' :</strong> ' + rule['content'];
	
		tile_html += `</div>
					<div class="speedycache-exclude-url">`;
		tile_html += create_description(rule['prefix'], rule['content']);			
		tile_html += '</div></div>';
		
		if(rule['editable']) {
			tile_html += '<div class="speedycache-tile-action"><span class="dashicons dashicons-edit" title="Edit"></span><span class="dashicons dashicons-trash" title="Delete"></span></div>'
		}
		
		tile_html += '</div></div>';
		
		
		jQuery('.speedycache-exclude-' + rule['type'] + '-list').append(tile_html);	
	}
	
	//Updates the text in the modal header and the condition based on type
	var update_text = function(type) {
		var modal_header = speedycache_modal.find('.speedycache-modal-header > div:first-child'),
		condition_text = speedycache_modal.find('.speedycache-condition-text');
		modal_header.empty();
		
		if(type == 'page') {
			modal_header.text('Exclude Page');
			condition_text.text('If REQUEST URI');
		} else if(type == 'useragent') {
			modal_header.text('Exclude UserAgent');
			condition_text.text('If User-Agent');
		} else if(type == 'cookie') {
			modal_header.text('Exclude Cookie');
			condition_text.text('If Cookie');
		} else if(type == 'css') {
			modal_header.text('Exclude CSS');
			condition_text.text('If CSS Url');
		} else if(type == 'js') {
			modal_header.text('Exclude JS');
			condition_text.text('If JS Url');
		} else{
			modal_header.text('Exclude');
		}
	}
	
	var update_prefix_options = function(type) {
		var select = speedycache_modal.find('select[name="speedycache-exclude-rule-prefix"]'),
		options = select.find('option');
		
		options.each(function() {
			jQuery(this).hide();
			
			if(type != 'page' && jQuery(this).val() != 'contain') {
				jQuery(this).hide();
			} else if(jQuery(this).val() != 'woocommerce_items_in_cart') {
				jQuery(this).show();
			}
			
			if(type == 'cookie' && jQuery(this).val() == 'woocommerce_items_in_cart') {
				jQuery(this).show();
			}
		});
		
		var result = options.filter((_, option) => {
			if(jQuery(option).css('display') == 'block') {
				return option;
			}
		});
		
		if(result.length == 1) {
			jQuery(result).attr('selected', true);
			select.trigger('change');
		}
	}
	
	var add_exclude_tile = function() {
		var formData = speedycache_modal.find('form').serializeArray(),
		exclude_list = jQuery('.speedycache-exclude-'+type+'-list [data-editable="true"]');
		
		// removing prefixes to the names
		for(var input of formData) {
			input.name = input.name.replace('speedycache-exclude-rule-', '');
		}
		
		formData = speedycache_convert_serialized(formData);
		formData['id'] = exclude_list.length ? exclude_list.length + 1 : new Date().getTime();
		formData['editable'] = true;
		render_tile(formData);
		
		//Action eventlisteners
		jQuery('[class*="speedycache-exclude-"] .dashicons-trash').on('click', delete_tile);
		jQuery('[class*="speedycache-exclude-"] .dashicons-edit').on('click', edit_tile);
		
		save_exclude();
		speedycache_modal.find('.speedycache-close-modal').trigger('click');
	}
	
	var save_exclude = function() {
		var eleRules = jQuery('[data-editable="true"]');
		rules = [];
		
		eleRules.each( function() {
			var rule = jQuery(this),
			prefix = rule.data('prefix'),
			content = rule.data('content'),
			type = rule.data('type');
			
			rules.push({'prefix':prefix, 'content':content, 'type':type});
		});
		
		jQuery.ajax({
			type :'POST',
			url: speedycache_ajax.url + '?action=speedycache_save_exclude_pages',
			data : {
				'rules' : rules,
				'security' : speedycache_ajax.nonce
			},
			success : function(res) {
				
				if(res.success) {
					speedycache_ajax.exclude_rules = rules;
					
					return;
				}
				
				alert('This rule can\'t be added');
			}
		})
	}
	
	var speedycache_executed_delete = false;
	var delete_tile = function() {
		jQuery(this).closest('.speedycache-exclude-rule').remove();
		
		if(speedycache_executed_delete) {
			return;
		}
		
		speedycache_executed_delete = true;
		
		setTimeout( function() {
			save_exclude();
			speedycache_executed_delete = false;
		}, 1000);
	}
	
	var edit_tile = function() {
		var tile = jQuery(this).closest('.speedycache-exclude-rule');
		jQuery(this).closest('.speedycache-block').find('.speedycache-add-new-exclude-button').trigger('click');
		
		var modal = jQuery('[modal-id="speedycache-exclude"]'),
		el_prefix = modal.find('[name="speedycache-exclude-rule-prefix"]'),
		el_content = modal.find('[name="speedycache-exclude-rule-content"]'),
		el_type = modal.find('[name="speedycache-exclude-rule-type"]');
		
		el_prefix.val(tile.data('prefix'));
		el_prefix.trigger('change'); //to hide content input if prefix dosent require the content input
		
		el_content.val(tile.data('content'));
		el_type.val(tile.data('type'));
		
		modal.find('.speedycache-modal-footer > button').off('click').on('click', function() {
			update_tile(jQuery(this), tile);
		});
	}
	
	var update_tile = function(jEle, tile) {
		var modal = jEle.closest('.speedycache-modal');
		
		var prefix = modal.find('[name="speedycache-exclude-rule-prefix"]').val();
		tile.data('prefix', prefix);
		
		var content = modal.find('[name="speedycache-exclude-rule-content"]').val();
		tile.data('content', content);
		
		
		var type = modal.find('[name="speedycache-exclude-rule-type"]').val();
		tile.data('schedule', type);
		
		content = content ? content : '';
		
		var title = '<strong>'+ln(lang, prefix)+ ' :</strong> ' + content;
		
		var edit_tile = jQuery('.speedycache-exclude-'+type+'-list').find('[data-id='+tile.data('id')+']');

		edit_tile.find('.speedycache-exclude-title').empty();
		edit_tile.find('.speedycache-exclude-title').append(title);
		edit_tile.find('.speedycache-exclude-url').empty();
		edit_tile.find('.speedycache-exclude-url').append(create_description(prefix, content));
		
		save_exclude();
		speedycache_modal.find('.speedycache-close-modal').trigger('click');
	}
	
	jQuery('.speedycache-add-new-exclude-button').click(function() {
		type = jQuery(this).data('type');
		speedycache_modal = jQuery('div[modal-id="speedycache-exclude"]');

		if(speedycache_modal && speedycache_modal.css('visibility') === 'hidden'){
			speedycache_modal.css('visibility','visible');
			speedycache_close_modal();
			
			update_text(type);
			update_prefix_options(type);
			
			speedycache_modal.find('.speedycache-modal-footer > button').off('click').on('click.modal', add_exclude_tile);
			speedycache_modal.find('[name="speedycache-exclude-rule-type"]').val(type);
			speedycache_modal.find('[name="speedycache-exclude-rule-prefix"]').off('change').on('change.modal', prefix_input_listener);
		}
	});
	
	load_exclude_tiles();
	jQuery('[class*="speedycache-exclude-"] .dashicons-trash').on('click', delete_tile);
	jQuery('[class*="speedycache-exclude-"] .dashicons-edit').on('click', edit_tile);
}

function speedycache_db_cleanup() {
	var isOnLoad = false;
	
	if(!jQuery('#speedycache-db').is(':checked')){
		isOnLoad = true;
	}
	
	var db_section = jQuery('.speedycache-tab-db .speedycache-db-page'),
	db_cards = db_section.find('.speedycache-card');
	
	var update_data = function() {
		jQuery.ajax({
			type:'POST',
			url: speedycache_ajax.url + '?action=speedycache_db_statics',
			data : {
				'security' : speedycache_ajax.nonce
			},
			beforeSend: function() {
				if(!isOnLoad && speedycache_ajax.premium){
					speedycache_add_loader();
				}
			},
			success: function(res) {
				
				if(res.all_warnings > 0){
					jQuery('label[for="speedycache-db"]').text('DB (' + res.all_warnings + ')');
				}else{
					jQuery('label[for="speedycache-db"]').text('DB');
				}
				
				if(isOnLoad){
					return;
				}
				
				for(var type in res) {
					
					if(res[type] > 0) {
						db_section.find('[speedycache-db-name="'+type+'"] .speedycache-db-clean').addClass('speedycache-db-dirty');
						db_section.find('[speedycache-db-name="'+type+'"] .speedycache-db-number').css('color','red');
						db_section.find('[speedycache-db-name="'+type+'"] .speedycache-db-number').text('('+res[type]+')');
					} else{
						db_section.find('[speedycache-db-name="'+type+'"] .speedycache-db-dirty').removeClass('speedycache-db-dirty');
						db_section.find('[speedycache-db-name="'+type+'"] .speedycache-db-number').css('color','#1ACDA6');
						db_section.find('[speedycache-db-name="'+type+'"] .speedycache-db-number').text('(0)');
					}
				}
				
				speedycache_hide_loader();
			}
		});	
	}
	
	var database_action = function(e) {
		
		var confirm_modal = jQuery('[modal-id="speedycache-modal-db-confirmation"]'),
		db_type = jQuery(this);
		
		if(confirm_modal.length == 0) {
			return;
		}
		
		speedycache_open_modal(confirm_modal);
		
		confirm_modal.find('.speedycache-db-confirm-yes').off().on('click', function() {
			jQuery.ajax({
				type: 'GET', 
				url: speedycache_ajax.url + '?action=speedycache_db_fix',
				data : {
					'type' : db_type.attr('speedycache-db-name'),
					'security' : speedycache_ajax.nonce
				},
				success: function(res){
					update_data();
				}
			});
			
			confirm_modal.css('visibility','hidden');
			return;
		});
		
		confirm_modal.find('.speedycache-db-confirm-no').off().on('click', function() {
			confirm_modal.css('visibility','hidden');
		});
	}
	
	jQuery(db_cards).off('click').on('click', database_action);
	update_data();
}

function speedycache_cdn_settings() {
	var jEle = jQuery('.speedycache-tab-cdn');
	
	var cdn_errors = true,
	input_changed = false;
	
	jEle.find('.speedycache-keyword-input').on('keyup', function(e) {
		handle_keywords(e) 
	});
	
	var show_loader = function(inputEle) {
		inputEle.find('#speedycache-cdn-url-loading').show();
	}
	
	var hide_loader = function(inputEle) {
		inputEle.find('#speedycache-cdn-url-loading').hide();
	}
	
	var action_listeners = function() {
		jEle.find('button.speedycache-cdn-pause').off('click').on('click', function(e) {
			pause_cdn(e);
		});
		
		jEle.find('button.speedycache-cdn-start').off('click').on('click', function(e) {
			start_cdn(e);
		});
	
		jEle.find('button.speedycache-cdn-stop').off('click').on('click', function(e) {
			stop_cdn(e);
		});
	}
	
	var verify_cdn_url = function() {
		var cdnEle =  jQuery(this),
		form = cdnEle.closest('form')
		cdn_url = form.find('[name="cdn_url"]').val(),
		origin_url = form.find('input[name="origin_url"]').val(),
		type = form.find('input[name="id"]').val();
		
		if(type == 'cloudflare') {
			cdn_url = 'speedycache';
		}
		
		input_changed = true;
		
		jQuery.ajax({
			type: 'GET',
			url: speedycache_ajax.url + '?action=speedycache_check_url',
			data: {
				url : cdn_url,
				origin_url : origin_url,
				type : type,
				security : speedycache_ajax.nonce
			},
			beforeSend: function() {
				show_loader(cdnEle.closest('.speedycache-form-input'));
			},
			success: function(res) {
				hide_loader(cdnEle.closest('.speedycache-form-input'));
				
				if(res.success){
					form.find('.speedycache-error-msg').empty();
					cdn_errors = false;
					return;
				}
				
				cdn_errors = true;
				form.find('.speedycache-error-msg').empty();
				form.find('.speedycache-error-msg').append(res.error_message);
			}
		});
	}
	
	jEle.find('input[name="cdn_url"],select[name="cdn_url"]').on('change', verify_cdn_url);
	jEle.find('.speedycache-cloudflare-settings input[name="origin_url"]').on('change', verify_cdn_url);
	
	jEle.find('.speedycache-cdn-save > button').off('click').on('click', function(e) {
		save_cdn_settings(e);
	});
	
	var save_cdn_settings = function(e) {
		e.preventDefault();
		
		var target = jQuery(e.target),
		form = target.closest('form'),
		formData = form.serializeArray(),
		ele_file_type = form.find('.speedycache-checkbox-list input:checked'),
		access_key = form.find('[name="bunny_access_key"');
		
		var file_type = ele_file_type.map(function(){
			return jQuery(this).val();
		}).get().join(',');
		
		var cdn_data = speedycache_convert_serialized(formData);
		cdn_data['file_types'] = file_type;
		
		if(!input_changed && cdn_data['cdn_url']) {
			cdn_errors = false;
		}
		
		if(!cdn_data['cdn_url'] || !cdn_data['origin_url']) {
			cdn_errors = true;
			show_snack_bar('error', 'Fix the issues above!');
			return;
		}
		
		if(access_key.length && access_key.val()){
			cdn_data['bunny_access_key'] = access_key.val();
		}
		
		if(cdn_errors) {
			show_snack_bar('error', 'Fix the issues above!');
			return;
		}

		jQuery.ajax({
			type:'POST',
			url: speedycache_ajax.url + '?action=speedycache_save_cdn_integration',
			data: {
				values : cdn_data,
				security : speedycache_ajax.nonce
			},
			success: function(res) {

				if(!res.success) {
					return;
				}
				
				show_snack_bar('success', 'CDN Saved Successfully');
				update_status(cdn_data['id'], 'start');
				
				if(form.find('.speedycache-cdn-actions').length){
					return;
				}

				var action_html = '<div class="speedycache-cdn-actions">';
				action_html += '<button class="speedycache-cdn-pause" title="Pause CDN">Pause</button>';
				action_html += '<button class="speedycache-cdn-stop" title="Stop CDN">Stop</button>';
				action_html += '</div>';
				
				var hr = form.find('hr').eq(0);
			
				hr.before(action_html);
				action_listeners();
			}
		});
	}
	
	var show_snack_bar = function(type, msg) {
		var class_name = '';
		
		switch(type) {
			case 'success':
				class_name = 'speedycache-snack-success';
				break;
				
			case 'error': 
				class_name = 'speedycache-snack-danger';
				break;
				
			case 'info':
				class_name = 'speedycache-snack-info';
				break;
		}
		
		var snack_bar = jEle.find('.speedycache-snack-bar');
		snack_bar.addClass(class_name).fadeIn('slow');
		snack_bar.find('.speedycache-snack-bar-msg').text(msg);
		
		setTimeout(function() {
			snack_bar.hide().removeClass(class_name);
		},5000);
	}
	
	var pause_cdn = function(e) {
		e.preventDefault();
		var target = jQuery(e.target);
		
		var cdn_id = target.closest('form').find('[name="id"]').val();

		if(!cdn_id) {
			return;
		}
		
		jQuery.ajax({
			type: 'POST',
			url: speedycache_ajax.url + '?action=speedycache_pause_cdn_integration',
			data: {
				id : cdn_id,
				security: speedycache_ajax.nonce
			},
			success: function(res) {
				if(!res.success) {
					return;
				}
				
				var action_holder = target.parent();
				
				action_holder.prepend('<button class="speedycache-cdn-start" title="Start CDN">Start</button>');
				target.remove();
				action_listeners();
				update_status(cdn_id, 'pause');
				show_snack_bar('info', 'CDN has been Paused');
			}
		});
	}
	
	var start_cdn = function(e) {
		e.preventDefault();
		var target = jQuery(e.target);
		
		var cdn_id = target.closest('form').find('[name="id"]').val();

		if(!cdn_id) {
			return;
		}
		
		jQuery.ajax({
			type: 'POST',
			url: speedycache_ajax.url + '?action=speedycache_start_cdn_integration',
			data: {
				id : cdn_id,
				security: speedycache_ajax.nonce
			},
			success: function(res) {

				if(!res.success) {
					return;
				}
				
				var action_holder = target.parent();
				
				action_holder.prepend('<button class="speedycache-cdn-pause" title="Start CDN">Pause</button>');
				target.remove();
				action_listeners();
				update_status(cdn_id, 'start');
				show_snack_bar('success', 'CDN has been resumed');
			}
		});
	}
	
	var stop_cdn = function(e) {
		e.preventDefault();
		var target = jQuery(e.target);
		
		var cdn_id = target.closest('form').find('[name="id"]').val();

		if(!cdn_id) {
			return;
		}
		
		jQuery.ajax({
			type: 'POST',
			url: speedycache_ajax.url + '?action=speedycache_remove_cdn_integration',
			data: {
				id: cdn_id,
				security: speedycache_ajax.nonce
			},
			success: function(res) {

				if(!res.success) {
					return;
				}
				
				
				if(speedycache_ajax.cdn && speedycache_ajax.cdn.length) {
					//removes the from array which has been stopped
					speedycache_ajax.cdn = speedycache_ajax.cdn.filter(cdn => cdn.id != cdn_id);
				}
				
				target.closest('form').find('[name="cdn_url"]').val('');
				target.closest('form').find('[name="origin_url"]').val('');
				target.closest('.speedycache-cdn-actions').remove(); //removing all action
				update_status(cdn_id, 'stop');
				show_snack_bar('success', 'The CDN has been stopped successfully');
			}
		});
	}
	
	var update_status = function(id = '', type = '') {	
		if(!type || !id) {
			for(var i in speedycache_ajax.cdn) {
				var cdn = speedycache_ajax.cdn[i];
				if(cdn.id == 'maxcdn') {
					cdn.id = 'stackpath';
				}
				
				var target = jEle.find('[for="speedycache-cdn-tab-'+cdn.id+'-input'),
				cdn_icon = target.find('.speedycache-cdn-tab-icon');
				
				if(cdn['status'] == 'pause') {
					cdn_icon.removeClass('speedycache-cdn-running-icon');
					cdn_icon.addClass('speedycache-cdn-pause-icon');
					continue;
				}
				
				cdn_icon.addClass('speedycache-cdn-running-icon');
				cdn_icon.removeClass('speedycache-cdn-pause-icon');
			}
			
			return;
		}
		
		if(id == 'maxcdn') {
			id = 'stackpath';
		}
		
		var target = jEle.find('[for="speedycache-cdn-tab-'+id+'-input'),
		cdn_icon = target.find('.speedycache-cdn-tab-icon');
	
		switch(type) {
			case 'pause':
				cdn_icon.removeClass('speedycache-cdn-running-icon');
				cdn_icon.addClass('speedycache-cdn-pause-icon');
				break;
			
			case 'start':
				cdn_icon.addClass('speedycache-cdn-running-icon');
				cdn_icon.removeClass('speedycache-cdn-pause-icon');
				break;
				
			default:
				cdn_icon.removeClass('speedycache-cdn-running-icon');
				cdn_icon.removeClass('speedycache-cdn-pause-icon');
				break;
		}	
	}
	
	//removes if checked and then checks the input name from the array
	var mark_checkboxes = function(file_type, jEle) {
		file_type = file_type.split(',');
		
		jEle.find('.speedycache-checkbox-list input').attr('checked', false);
		
		for(var type of file_type) {
			jEle.find('#file-type-'+type).attr('checked', true);
		} 
	} 
	
	var add_keywords = function(type, data, cdnEle) {
		var tag_input = cdnEle.find('[name="'+type+'"]'),
		tag_holder = tag_input.closest('label').find('.speedycache-tags-holder');
		tag_input.val(data);
		
		var tag_arr = data.split(','),
		tagHTML = '';
		
		tag_holder.empty();
		
		for(var tag_text of tag_arr) {
			tagHTML += `<div class="speedycache-tag">
				<div class="speedycache-tag-text">${tag_text}</div>
				<div class="speedycache-tag-remove">&#10006;</div>
			</div>`;
		}
		
		tag_holder.append(tagHTML);
	}
	
	var populate_fields = function() {
		
		for(var i in speedycache_ajax.cdn) {
			var cdn = speedycache_ajax.cdn[i];
			if(cdn.id == 'maxcdn') {
				cdn.id = 'stackpath';
			}

			var settings = jEle.find('.speedycache-'+cdn.id+'-settings');
			
			if(cdn.hasOwnProperty('cdn_url') && cdn.cdn_url) {
				settings.find('[name="cdn_url"]').val(cdn.cdn_url);
			}
			
			if(cdn.hasOwnProperty('origin_url') && cdn.origin_url) {
				settings.find('[name="origin_url"]').val(cdn.origin_url);
			}
			
			if(cdn.hasOwnProperty('bunny_access_key') && cdn.bunny_access_key) {
				settings.find('[name="bunny_access_key"]').val(cdn.bunny_access_key);
			}
			
			if(cdn.hasOwnProperty('keywords') && cdn.keywords) {
				add_keywords('keywords', cdn.keywords, settings)
			}
			
			if(cdn.hasOwnProperty('excludekeywords') && cdn.excludekeywords) {
				add_keywords('excludekeywords', cdn.excludekeywords, settings)
			}
			
			if(cdn.hasOwnProperty('file_type') && cdn.file_type) {
				mark_checkboxes(cdn.file_type, settings);
			}
			
		}
	}
	
	var handle_keywords = function(e) {	
		e.preventDefault();
		
		if(e.code != 'Comma'){
			return;
		}
		
		var target = jQuery(e.target),
		keyword_input_id = target.data('target'),
		keyword_input = target.closest('label').find('#'+keyword_input_id),
		tag_holder = target.closest('label').find('.speedycache-tags-holder');
		
		var tag_text = target.val().replace(/[$\,]/gi, '');
		
		target.val('');
		
		if(!tag_text) {
			return;
		}
		
		tagHTML = `<div class="speedycache-tag">
			<div class="speedycache-tag-text">${tag_text}</div>
			<div class="speedycache-tag-remove">&#10006;</div>
		</div>`;
		
		tag_holder.append(tagHTML);
		
		//Combines a array of values to a string seperated with commas
		var combined_keywords = tag_holder.find('.speedycache-tag .speedycache-tag-text').map( function() {
			return jQuery(this).text();
		}).get().join(',');
		
		keyword_input.val(combined_keywords);			
	}
	
	jEle.on('click', '.speedycache-tag-remove', function() {
		var label = jQuery(this).closest('label'),
		tag_holder = label.find('.speedycache-tags-holder'),
		keyword_input = label.find('.speedycache-keyword-input').data('target'),
		keyword_save = label.find('#'+keyword_input);
		
		//removing html tag Node
		jQuery(this).closest('.speedycache-tag').remove();
			
		//updating the keyword input
		var combined_keywords = tag_holder.find('.speedycache-tag .speedycache-tag-text').map( function() {
			return jQuery(this).text();
		}).get().join(',');
		
		keyword_save.val(combined_keywords);	
	});	
	
	populate_fields();
	action_listeners();
	update_status();
}

/*
	Converts the format of jQuery serializeArray
	i.e, [ 0:{name:someName, value:expectedvalue} ] to
	{ someName:expectedvalue }
*/
function speedycache_convert_serialized(arr) {
	var converted_obj = {};
	
	for(var i of arr) {
		converted_obj[i.name] = i.value;
	}
	
	return converted_obj;
}

function speedycache_tooltip(args) {
	var jEle = args.jEle;
	
	var tool_tip_html = `<div class="speedycache-tool-tip">${args.html}</div>`;
		
	if(jEle.find('.speedycache-tool-tip').length) {
		return;
	}
	
	var removeRel = false;
	
	if(jEle.css('position') != 'relative') {
		removeRel = true;
		jEle.css('position', 'relative');
	}
	
	jEle.append(tool_tip_html);
	
	setTimeout(() => {
		jEle.find('.speedycache-tool-tip').remove();
	},2500);	
}

function speedycache_premium_tool_tip(jEle) {
	
	var tool_tip_html = `<div class="speedycache-tool-tip">
		<span class="speedycache-premium-tip-text"><a href="https://speedycache.com/pricing" target="_blank"><i class="fas fa-shopping-cart"></i> Buy Pro Version Now</a></span>
		</div>`;
		
	if(jEle.find('.speedycache-tool-tip').length) {
		return;
	}
	
	jEle.append(tool_tip_html);
	
	setTimeout(() => {
		jEle.find('.speedycache-tool-tip').remove();
	},2500);
}

function speedycache_toggle_settings_link(jEle) {
	var wrap = jEle.closest('.speedycache-option-wrap'),
	$setting = wrap.find('.speedycache-modal-settings-link, .speedycache-action-link');
	
	if(jEle.is(':checked')) {
		$setting.show();
		return;
	}
	
	$setting.hide();
}

function speedycache_image_optimization() {
	var stats,
		total_page = {
			value: 0,
			set: function (value) {
				this.value = value;
				this.update_num();
				disabling_paging_btn(jQuery('#speedycache-image-list'));
			},
			update_num : function(){
				jQuery('.speedycache-total-pages').text(this.value);
			}
		},
		current_page = {
			value: 0,
			set: function (value) {
				this.value = value;
				this.update_num();
				disabling_paging_btn(jQuery('#speedycache-image-list'));
			},
			update_num : function(){
				jQuery('.speedycache-current-page').text(this.value+1);
			}
		};
	
	//Gets Stats	
	var get_stats = function(onload = false) {
		jQuery.ajax({
			type : 'GET',
			url : speedycache_ajax.url + '?action=speedycache_statics_ajax_request',
			cache : false,
			data : {
				'security' : speedycache_ajax.nonce
			},
			success : function(res){
				stats = res;
			
				//For pagination
				var $total_page = jQuery('.speedycache-total-pages'),
				optimized = res.optimized
				$total_page.text(Math.ceil(optimized/5));
				total_page.set($total_page.text());
				
				if(total_page == '1') {
					jQuery('.speedycache-image-list-next-page').addClass('disabled');
					jQuery('.speedycache-image-list-last-page').addClass('disabled');
				}
			
				if(!onload) {
					optm_count = `${optimized}/${stats.total_image_number}`;
					jQuery('.speedycache-img-optm-count').text(optm_count);
					
					reduction = res.reduction > 10000 ? (res.reduction/1000).toFixed(2) + 'MB' : res.reduction.toFixed(2) + 'KB';
					
					var stat_block = jQuery('.speedycache-img-stats');
					
					stat_block.find('.speedycache-img-reduced-size').text(reduction);
					stat_block.find('.speedycache-donut-percent').text(res.percent + '%');
					stat_block.find('.speedycache-img-success-per').text(res.percent + '%');
					stat_block.find('.speedycache-img-error-count').text(res.error);
					
					var sub = 100 - parseInt(res.percent);
					
					stat_block.find('.speedycache-donut-segment-2').attr('stroke-dasharray', res.percent+' '+sub);
					var donut_style = stat_block.closest('.speedycache-tab-image').find('style').eq(0);
					
					//this regex wont work in PHP as it dosent supports look behind without fixed size
					var dash_array = donut_style.text();
					
					//(?<=100%\s*{(?:\s*|\n)stroke-dasharray\s*:\s*)([\d]+\s*[\d]+[^;]) this reg ex can be used too its more precise and gets just numbers but need to update it to handle floats
					dash_array = dash_array.replace(/100%.*(?:[\d]|[\d]+\.[\d]+)[^;]/, `100%{stroke-dasharray:${res.percent}, ${sub}`);
				
					var segment = stat_block.find('.speedycache-donut-segment-2');
					segment.removeClass('speedycache-donut-segment-2');
					segment.addClass('speedycache-donut-segment-2');
					
					donut_style.text(dash_array);
				}
			
				if(res.uncompressed > 0) {
					jQuery('.speedycache_img_optm_status').css('backgroundColor', '#EED202');
					jQuery('.speedycache_img_optm_status').next().text(`${res.uncompressed} File(s) needed to be optimized`);
				}else {
					jQuery('.speedycache_img_optm_status').css('backgroundColor', '#90ee90');
					jQuery('.speedycache_img_optm_status').next().text(`All images are optimized`);
				}
			}
		});
	}
	
	//Updates Image Optimization Stats on load
	get_stats(true);
	
	jQuery('.speedycache-img-opt-settings input').on('change', function() {
		
		var settings = jQuery('.speedycache-img-opt-settings').serializeArray();
		settings = speedycache_convert_serialized(settings);
		
		jQuery.ajax({
			type: 'POST',
			url : speedycache_ajax.url + '?action=speedycache_update_image_settings',
			data : {
				'security' : speedycache_ajax.nonce,
				'settings' : settings
			},
			success: function(res) {
				//Succeed or Fail silently
			}
		});
	});
	
	var file_counter = 1,
	optm_stopped = false,
	optm_ajax;
	
	jQuery('.speedycache-img-optm-btn').on('click', function() {
		if(optm_ajax && optm_stopped) {
			optm_ajax.abort();
			optm_stopped = false;
			file_counter = 1;

			return;
		}
		
		var inner_content = `
			<div class="speedycache-img-optm-counter">${file_counter - 1}/${stats.uncompressed}</div>
			<div class="speedycache-progress">
				<div class="speedycache-progress-value"></div>
			</div>
			<div class="speedycache-optm-close">
				<button class="speedycache-image-optm-stop speedycache-btn speedycache-btn-danger">Stop</button>
				<button class="speedycache-btn speedycache-btn-success speedycache-img-optm-close">Close</button></div>
			</div>`;
		
		
		//If all images are optimized
		if(stats.uncompressed == 0) {
			inner_content = `
			<div class="speedycache-already-optm">
				<i class="fas fa-check-circle"></i>
				<span>All images are Optimized</span>
			</div>
			<div class="speedycache-optm-close">
				<button class="speedycache-btn speedycache-btn-success speedycache-img-optm-close" style="display:block;">Close</button></div>
			</div>
			`;
		}
		
		var inc_per = parseInt(100/stats.uncompressed),
		modal_html = `<div modal-id="speedycache-modal-optimize-all" class="speedycache-modal">
			<div class="speedycache-modal-wrap" style="padding:10px;">
				<div style="text-align:center;"><h2>Optimizing Images</h2></div>
					<div class="speedycache-optm-prog-list">
					</div>
					${inner_content}
			</div>
		</div>`;
		
		var optm_modal = jQuery('[modal-id="speedycache-modal-optimize-all"]');
		
		if(optm_modal.length == 0) {
			jQuery('body').append(modal_html);
			speedycache_open_modal(jQuery(this));
			optm_modal = jQuery('[modal-id="speedycache-modal-optimize-all"]');
		}
		
		optm_modal.find('.speedycache-optm-close button').off('click').on('click', function() {
			optm_modal.remove();
			speedycache_update_list();
			get_stats();
			
			if(stats.uncompressed != 0) {
				optm_stopped = true;
			}
			
			file_counter++;
		});
		
		optm_ajax = jQuery.ajax({
			type : 'POST',
			url : speedycache_ajax.url + '?action=speedycache_optimize_image_ajax_request',
			data : {
				'id' : null,
				'security' : speedycache_ajax.nonce
			},
			success: function(res) {
				var progress = jQuery('[modal-id="speedycache-modal-optimize-all"] .speedycache-progress-value'),
				new_per = file_counter * inc_per;
				progress.css('width', `${new_per}%`);
				
				file_counter++
				
				var modal = progress.closest('.speedycache-modal-wrap');
				
				if(!res.id && res.message != 'finish') {
					var error_html = `<div class="speedycache-img-optm-error">
						<i class="fas fa-times-circle"></i>
						<p>Something Went Wrong<br/>
							${res.message}
						</p>
					</div>`;
					
					progress.parent().before(error_html);
					progress.css({'width': '100%', 'backgroundColor' : 'var(--speedycache-red)'});
					
					setTimeout( () => {
						optm_modal.find('.speedycache-img-optm-close').show();
						optm_modal.find('.speedycache-image-optm-stop').hide();
					},700);
					
					return;
				} 
	
				if(res.message != 'finish' && file_counter <= stats.uncompressed + 1) {
					modal.find('.speedycache-img-optm-counter').text((file_counter) - 1 +'/'+stats.uncompressed);
					
					jQuery('.speedycache-img-optm-btn').trigger('click');
					return;
				}
				
				progress.css('width', '100%');
				
				//To show when Optimization completes
				var success_html = `
				<div class="speedycache-already-optm" style="display:none;">
					<i class="fas fa-check-circle"></i>
					<span>Images optimized Successfully</span>
				</div>
				`;
				
				progress.parent().before(success_html);
				modal.find('.speedycache-img-optm-counter').hide('slow');
				modal.find('.speedycache-already-optm').show('slow');
				
				setTimeout( () => {
					optm_modal.find('.speedycache-img-optm-close').show();
					optm_modal.find('.speedycache-image-optm-stop').hide();
				},700);
			}
		});
	});
	
	//revert Image
	var revert_image = function() {
		var jEle = jQuery(this),
		post_id = jEle.find('input').val();
	
		if(!post_id) {
			return;
		}
		
		speedycache_add_loader();
		
		jQuery.ajax({
			type : 'GET',
			url : speedycache_ajax.url + '?action=speedycache_revert_image_ajax_request&id='+post_id,
			data : {
				'security' : speedycache_ajax.nonce,
			},
			beforeSend : function(){
				jEle.closest('tr').css('backgroundColor', 'rgba(255,0,0,0.2)');
			},
			success : function(res) {
				speedycache_update_list(jEle);
				get_stats();
				speedycache_hide_loader();
			},
			error: function(err) {
				speedycache_hide_loader();
				jEle.closest('tr').css('backgroundColor', 'rgb(255,255,255)');
			}
		});
	}
	
	//Revert the image conversion listener
	jQuery('.speedycache-revert').on('click', revert_image);
	
	jQuery('.speedycache-img-delete-all-conv').on('click', function(e) {
		e.preventDefault();
		
		var confirm_modal = jQuery('[modal-id="speedycache-modal-all-img-revert"]');
		
		if(confirm_modal.length == 0) {
			return;
		}
		
		speedycache_open_modal(confirm_modal);
		
		confirm_modal.find('.speedycache-db-confirm-yes').off().on('click', function() {
			speedycache_add_loader();
			confirm_modal.css('visibility','hidden');
			
			jQuery.ajax({
				type : 'GET',
				url : speedycache_ajax.url + '?action=speedycache_img_revert_all',
				data : {
					'security' : speedycache_ajax.nonce
				},
				success : function(res) {
					
					if(res.success) {
						speedycache_hide_loader();
						speedycache_update_list();
						get_stats();
						return;
					}
				
					speedycache_hide_loader();
					alert(res.message);
				}
			});
		});
		
		confirm_modal.find('.speedycache-db-confirm-no').off().on('click', function() {
			confirm_modal.css('visibility','hidden');
		});
	});	
	
	var speedycache_update_list = function(jEle = null) {
		var img_list = jQuery('#speedycache-image-list'),
			search = img_list.find('#speedycache-image-search-input'),
			per_page = img_list.find('#speedycache-image-per-page'),
			per_page_val = per_page.val() ? per_page.val() : 5,
			filter = img_list.find('#speedycache-image-list-filter'),
			page = 0;
			
		if(jEle) {	
			if(jEle.hasClass('disabled')) {
				return;
			}	
			
			if(jEle.data('page-action')) {
				switch(jEle.data('page-action')) {
					case 'last-page':
						current_page.set(total_page.value - 1);
						break;
						
					case 'next-page':
						current_page.set(current_page.value + 1);
						break;
					
					case 'first-page':
						current_page.set(0);
						break;
					
					case 'prev-page':
						current_page.set(current_page.value > 0 ? current_page.value - 1 : 0);
						break;
				}
			}
		}
		
		var optimized = stats.total_image_number - stats.uncompressed;
		
		if(optimized <= per_page_val) {
			current_page.set(0);
		}
		
		jQuery.ajax({
			type : 'GET',
			url : speedycache_ajax.url + '?action=speedycache_update_image_list_ajax_request',
			data : {
				'search' : search.val(),
				'per_page' : per_page_val,
				'filter' : filter.val(),
				'page' : current_page.value,
				'security' : speedycache_ajax.nonce
			},
			success: function(res) {
				if(!res.content) {
					return;
				}
				
				total_page.set(Math.ceil(res.result_count/per_page_val));
				
				if(total_page.value - 1 == current_page.value) {
					img_list.find('.speedycache-image-list-next-page').addClass('disabled');
					img_list.find('.speedycache-image-list-last-page').addClass('disabled');
				}
				
				jQuery('#speedycache-image-list tbody').empty();
				jQuery('#speedycache-image-list tbody').append(res.content);
				jQuery('.speedycache-revert').on('click', revert_image);
				jQuery('.speedycache-open-image-details').on('click', open_img_details);
			}
		});
	}
	
	var disabling_paging_btn = function(img_list) {
		if(current_page.value == 0 && total_page.value - 1 == 0) {
			img_list.find('.speedycache-image-list-first-page').addClass('disabled');
			img_list.find('.speedycache-image-list-prev-page').addClass('disabled');
			img_list.find('.speedycache-image-list-last-page').addClass('disabled');
			img_list.find('.speedycache-image-list-next-page').addClass('disabled');
		}else if(current_page.value == 0) {
			img_list.find('.speedycache-image-list-first-page').addClass('disabled');
			img_list.find('.speedycache-image-list-prev-page').addClass('disabled');
			img_list.find('.speedycache-image-list-last-page').removeClass('disabled');
			img_list.find('.speedycache-image-list-next-page').removeClass('disabled');
		} else if(current_page.value == total_page.value - 1) {
			img_list.find('.speedycache-image-list-first-page').removeClass('disabled');
			img_list.find('.speedycache-image-list-prev-page').removeClass('disabled');
			img_list.find('.speedycache-image-list-last-page').addClass('disabled');
			img_list.find('.speedycache-image-list-next-page').addClass('disabled');
		} else {
			img_list.find('.speedycache-image-list-first-page').removeClass('disabled');
			img_list.find('.speedycache-image-list-prev-page').removeClass('disabled');
			img_list.find('.speedycache-image-list-last-page').removeClass('disabled');
			img_list.find('.speedycache-image-list-next-page').removeClass('disabled');
		}
	}
	
	//Toggles the image variants
	var open_img_details = function() {
		var post_id = jQuery(this).closest('tr').attr('post-id');
		
		if(!post_id) {
			return;
		}
		
		var details = jQuery('tr[post-id="'+post_id+'"][post-type="detail"]');
		
		if(details.is(':hidden')) {
			details.show();
			jQuery(this).find("span").attr('class', 'dashicons dashicons-arrow-up-alt2')
		} else {
			details.hide();
			jQuery(this).find("span").attr('class', 'dashicons dashicons-arrow-down-alt2');
		}
	}
	
	//Downloading cwebp
	jQuery('button.speedycache-webp-download').on('click', function(e) {
		e.preventDefault();
		
		type = jQuery(this).data('type') ? jQuery(this).data('type') : 'cwebp';
		
		jQuery.ajax({
			url : speedycache_ajax.url + '?action=speedycache_download_cwebp',
			type : 'GET',
			data : {
				security : speedycache_ajax.nonce,
				type : type
			},
			beforeSend : function() {
				speedycache_add_loader();
			},
			success : function(res) {
				speedycache_hide_loader();
				
				if(res.success) {
					location.reload();
					return;
				}
				
				if(!res.error_message) {
					alert('Something went wrong try again later!');
				}
				
				alert(res.error_message);
			}
		})
	});
	
	//Listener For Scheduled Count
	jQuery('span.speedycache-scheduled-count').on('click', function() {
		speedycache_open_modal(jQuery(this));
	});
	
	//Listeners Starts here
	
	//Search button listener
	jQuery('#speedycache-image-search-button').on('click', function() {
		speedycache_update_list(jQuery(this));
	});
	
	//All or Error image filter
	jQuery('#speedycache-image-list-filter').on('change', function() {
		speedycache_update_list(jQuery(this));
	});
	
	//Per page listener
	jQuery('#speedycache-image-per-page').on('change', function() {
		speedycache_update_list(jQuery(this));
	});
	
	//Paging Number Listeners
	jQuery('.speedycache-image-list-first-page, .speedycache-image-list-prev-page, .speedycache-image-list-next-page, .speedycache-image-list-last-page').on('click', function() {
		speedycache_update_list(jQuery(this));
	});
	
	//Toggles the image variants Listener
	jQuery('.speedycache-open-image-details').on('click', open_img_details);
}

function speedycache_critical_css(){
	jQuery.ajax({
		type: 'GET',
		url : speedycache_ajax.url + '?action=speedycache_critical_css&security='+speedycache_ajax.nonce,
		success: function(res){
			if(!res.success){
				alert(res.data.message ? res.data.message : 'Something went wrong ! Unable to intitiate Critical CSS!');
				return;
			}
			
			alert(res.data.message);
		}
	})
}

async function speedycache_test_optimization(e){
	e.preventDefault();

	let process = jQuery('.speedycache-test-process'),
	p = jQuery('.speedycache-no-tests'),
	input = jQuery(this).siblings('input'),
	form = jQuery('.speedycache-test-settings form');

	process.find('.is-active').removeClass('is-active');
	process.find('p:first-child span').addClass('is-active');
	jQuery('.speedycache-result').hide();

	url = input.val();
	
	if(!url){
		alert('Please enter a URL to analyse');
		return;
	}
	
	settings = form.serializeArray();
	let settings_val = {};

	if(settings.length < 1){
		alert('You have not enabled any settings, please enable it');
		return;
	}
	
	settings.forEach((field) => {
		settings_val[field.name] = true;
	});

	jQuery(this).attr('disabled', true);
	jQuery(this).off();
	
	process.show();
	p.hide();

	let res,
	is_active = process.find('.is-active');
	
	// Step 1: Checking if domain is valid before sending it and update the test settings
	res = await speedycache_test_check_domain(url, settings_val);

	if(!res){
		alert('The domain you have entered can not be reached!');
		return;
	}
	
	// Updating spinner state
	is_active.removeClass('is-active');
	is_active = is_active.parent().next().find('.spinner'); // updating the is active.
	is_active.addClass('is-active');

	let old_score,
	new_score;
	
	// Step 2: Getting score before optimization.
	old_score = await speedycache_test_get_score(url);

	if(!old_score['score']){
		alert('Unable to get score of your website, please try again later!');
		return;
	}
	
	// Updating spinner state
	is_active.removeClass('is-active');
	is_active = is_active.parent().next().find('.spinner'); // updating the is active.
	is_active.addClass('is-active');
	
	// Step 3: Optimizing the page.
	res = await speedycache_test_optimize(url);

	if(!res){
		alert('Optimization failed, please contact support, or try again later!');
		return;
	}
	
	// Updating spinner state
	is_active.removeClass('is-active');
	is_active = is_active.parent().next().find('.spinner'); // updating the is active.
	is_active.addClass('is-active');

	// Step 4: Getting score of optimized page.
	new_score = await speedycache_test_get_score(url+'&test_speedycache=1');

	if(!new_score['score']){
		alert('Unable to get scores for the optimized page!');
		return;
	}

	speedycache_updating_test_results(old_score, new_score);

	// Enabling the Button again.
	jQuery(this).attr('disabled', false);
	jQuery(this).on('click', speedycache_test_optimization);
	process.hide();

}

async function speedycache_test_check_domain(url, settings){

	let response = false;

	await jQuery.ajax({
		type : 'POST',
		url : speedycache_ajax.url,
		data : {
			url : url,
			settings : settings,
			security : speedycache_ajax.nonce,
			action : 'speedycache_check_domain',
		},
		success : function(res){
			if(!res.success){
				return;
			}

			response = true;
		}
	})

	return response; 
}


// Send request to SpeedyCache API to get PageSpeed Data.
async function speedycache_test_get_score(url){
	
	let response = false;
	
	await jQuery.ajax({
		type : 'GET',
		url : speedycache_ajax.url + '?action=speedycache_test_score&security=' + speedycache_ajax.nonce + '&url=' + url,
		success : function(res){
			if(!res.success){
				return;
			}

			response = res.data;
		}
	})
	
	return response;
}

// Sends request to page to get it optimized.
async function speedycache_test_optimize(url){
	
	let response = false;

	await jQuery.ajax({
		type : 'GET',
		url : speedycache_ajax.url + '?action=speedycache_create_test_cache&security=' + speedycache_ajax.nonce + '&url=' + url,
		success : function(res){
			if(!res){
				return;
			}

			response = true;
		}
	})
	
	return response;
}

function speedycache_updating_test_results(old_score, new_score){
	jQuery('.speedycache-result').show();

	let increased_points = new_score['score'] - old_score['score'];
	if(increased_points < 0){
		increased_points = 0;
	}
	
	let before_optimization = jQuery('.speedycache-before-optimization'),
	after_optimization = jQuery('.speedycache-after-optimization'),
	before_stroke = before_optimization.find("[stroke-dasharray]"),
	after_stroke = after_optimization.find("[stroke-dasharray]");

	let dash_array1 = 100 - new_score['score'],
	dash_array2 = 100 - old_score['score'],
	percent_of_change = (new_score['score'] - old_score['score']) * 100 / old_score['score'];
	
	// Updating the Stroke values
	before_stroke.attr('stroke-dasharray', old_score['score'].toString()  + ' ' + dash_array2);
	after_stroke.attr('stroke-dasharray', new_score['score'].toString()  + ' ' + dash_array1);
	
	// Updating the Text value
	before_optimization.find('tspan').text(old_score['score']);
	after_optimization.find('tspan').text(new_score['score']);
		
	// Updating the betterness value.
	jQuery('.speedycache-test-improvement').text(Math.floor(percent_of_change) + '%');
	
	// Updating Before metric Info
	jQuery('.speedycache-first-contentful-paint .speedycache-metric-before').text('Before: ' + old_score['first-contentful-paint']);
	jQuery('.speedycache-total-blocking-time .speedycache-metric-before').text('Before: ' + old_score['total-blocking-time']);
	jQuery('.speedycache-layout-shift .speedycache-metric-before').text('Before: ' + old_score['cumulative-layout-shift']);
	jQuery('.speedycache-speed-index .speedycache-metric-before').text('Before: ' + old_score['speed-index']);

	// Updating After metric Info
	jQuery('.speedycache-first-contentful-paint .speedycache-metric-after').text('After: ' + new_score['first-contentful-paint']);
	jQuery('.speedycache-total-blocking-time .speedycache-metric-after').text('After: ' + new_score['total-blocking-time']);
	jQuery('.speedycache-layout-shift .speedycache-metric-after').text('After: ' + new_score['cumulative-layout-shift']);
	jQuery('.speedycache-speed-index .speedycache-metric-after').text('After: ' + new_score['speed-index']);

	old_chart_color = speedycache_get_test_color(old_score['score']);
	new_chart_color = speedycache_get_test_color(new_score['score']);
	
	before_optimization.find('.speedycache-donut-segment').css('stroke', old_chart_color[0]);
	before_optimization.find('.donut-hole').attr('fill', old_chart_color[1]);
	before_optimization.find('.speedycache-donut-percent').css('fill', old_chart_color[2]);
	
	after_optimization.find('.speedycache-donut-segment').css('stroke', new_chart_color[0]);
	after_optimization.find('.donut-hole').attr('fill', new_chart_color[1]);
	after_optimization.find('.speedycache-donut-percent').css('fill', new_chart_color[2]);

	
}

function speedycache_get_test_color(score){

	// The structure of this array is 0 => [Stroke Color, Background Color, Text Color]
	score_color_map = {
		0 : ['#c00', '#c003', '#c00'], // Red
		50 : ['#fa3', '#ffa50036', '#fa3'], // Orange
		90 : ['#0c6', '#00cc663b', '#080'], // Green
	};
	if(score >= 0 && score < 50){
		return score_color_map[0];
	}

	if(score >= 50  && score < 90){
		return score_color_map[50];
	}

	return score_color_map[90];
}
