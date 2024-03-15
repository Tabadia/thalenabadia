(function($){
	let url = window.location.href;
	
	$('#backuply-cloud-trial').click(function(){
		$('#bcloud-dialog').dialog();
	});
	
	if(url.includes('token=') && url.includes('license=BAKLY')){
		let display = $('.backuply-bcloud-trial-verify').css('display');
		
		if(display != 'none'){
			$('#backuply-cloud-trial').click();
		}

	}
	
	$('#backuply_free-trial .backuply_promo-close').click(function(){
		var data;

		// Hide it
		$('#backuply_free-trial').hide();
		
		// Save this preference
		$.post(backuply_promo.ajax + '?backuply_trial_promo=0&security='+backuply_promo.nonce, data, function(response) {
			//alert(response);
		});
	});
	
	$('.backuply-cloud-state').on('click', '#backuply_has_license', function(){
		$(this).closest('div').hide();
		$('.bcloud-trial-license').show();		
	});

	$('.backuply-cloud-state').on('click', '#backuply_no_license', function(){
		$(this).closest('div').hide();
		$('.bcloud-trial-email').show();
	});

	// License Linking to Backuply Cloud
	$('.backuply-cloud-state').on('click', 'button.backuply-license-link', function(){
		let wrapper = $(this).closest('div'),
		input = wrapper.find('input'),
		input_name = input.attr('name'),
		value = input.val();

		if(!value){
			alert('Please Fill the form to proceed');
			return false;
		}

		let spinner = $(this).next();
		spinner.addClass('is-active');

		$.ajax({
			method : 'POST',
			url : backuply_promo.ajax,
			data : {
				action : 'bcloud_trial',
				security : backuply_promo.nonce,
				form_action : input_name,
				value : value,
			},
			success : function(res){
				spinner.removeClass('is-active');
				
				if(res.success){
					let state = wrapper.closest('.backuply-cloud-state');
					state.hide();
					state.next().show();

					return;
				}

				alert(res.data ? res.data : 'Something went wrong, check if you already have Backuply Cloud added');
			}
		})
	});

	$('button.backuply-default-yes').on('click', function(){
		let wrapper = $(this).closest('.backuply-cloud-trial-settings');

		$.ajax({
			method : 'POST',
			url : backuply_promo.ajax,
			data : {
				action : 'backuply_trial_settings',
				security : backuply_promo.nonce
			},
			success : function(res){
				if(!res.success){
					alert('Unable to set Backuply settings')
				}

				wrapper.hide();
				wrapper.next().show();				
			}
		});
	});
	
	$('button.backuply-default-no').on('click', function(){
		let wrapper = $(this).closest('.backuply-cloud-trial-settings');

		wrapper.hide();
		wrapper.next().show();
	});
	
	
	function backuply_trail_confirmation(){
		let wrapper = $(this).closest('div');

		let spinner = $(this).next();
		spinner.addClass('is-active');

		$.ajax({
			method : 'POST',
			url : backuply_promo.ajax,
			data : {
				action : 'backuply_verify_trial',
				security : backuply_promo.nonce,
			},
			success : function(res){
				spinner.removeClass('is-active');

				//if(res.success){
					let state = wrapper.closest('div');
					state.hide();
					state.next().show();
					
					// Add license to it and It will create the keys
					state.next().find('input').val(res.data['license']);
					state.next().find('button').click();

					return;
				//}

				//alert(res.data ? res.data : 'Something went wrong!');
			}
		})
	};

	// Confirming Email Verification
	$('#backuply-verify-checkbox').on('change', function(e){
		if($(this).is(':checked')){
			$('.backuply-cloud-state button.backuply-verify-email').attr('disabled', false);
			$('.backuply-cloud-state').on('click', 'button.backuply-verify-email', backuply_trail_confirmation);
		} else {
			$('.backuply-cloud-state').off('click');
			$('.backuply-cloud-state button.backuply-verify-email').attr('disabled', true);
		}
	});

})(jQuery);