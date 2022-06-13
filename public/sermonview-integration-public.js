(function($){
	$('form div.form-row div.input-holder input.error').change(function(e) {
		$(this).removeClass('error');
		$(this).blur(function(e) {
			$(this).parent().find('.input-instructions.error').hide();
		});
	});
	$('.button.white').hover(function(e) {
		$(this).removeClass('white');
	}, function(e) {
		$(this).addClass('white');
	});
	$('.gf-readonly input').prop('readonly','readonly');
	$('.gf-disabled input').prop('disabled', 'disabled').prop('tabindex', false);

	/* checkout billing accordion */
	$('.svi-accordion .svi-panel .header .custom-checkbox input').click(function (e) {
		var this_body = $(this).parents('.svi-panel').find('.body');
		$('.svi-accordion .svi-panel .body:not(.keep-open)').not(this_body).slideUp().addClass('hidden');
		this_body.removeClass('hidden').slideDown();
	});
	$('.svi-accordion .svi-panel .header .custom-checkbox input').each(function (e) {
		if ($(this).is(':checked')) {
			$(this).parents('.svi-panel').find('.body').removeClass('hidden');
		} else {
			$(this).parents('.svi-panel').find('.body:not(.keep-open)').slideUp();
		}
	});
	$('.svi-accordion .svi-panel .header.disabled').click(function (e) {
		$(this).parents('.svi-panel').find('.click-message').addClass('alert highlight-color');
	});
	$('#svi-payment-methods .svi-panel .body input, #svi-payment-methods .svi-panel .body select, #svi-payment-methods .svi-panel .body textarea').change(function (e) {
		$(this).parents('.svi-panel').find('.header input').prop('checked', true);
	});
})(jQuery);

