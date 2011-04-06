jQuery(document).ready(function(){
	jQuery('.bwp-syntax-block div.bwp-syntax-toolbar').css('display', 'none');
								
	jQuery('.bwp-syntax-block').hover(
		function(){
			jQuery(this).find('div.bwp-syntax-toolbar').fadeIn('fast');
		},
		function(){
			jQuery(this).find('div.bwp-syntax-toolbar').fadeOut('fast');
		}
	);

	jQuery('.bwp-syntax-block .bwp-syntax-block-handle a').click(function(){
		if (jQuery(this).hasClass('toggled'))
		{
			jQuery(this).removeClass('toggled');
			jQuery(this).parents('.bwp-syntax-block').addClass('bwp-syntax-has-border');
		}
		else
		{
			jQuery(this).addClass('toggled');
			jQuery(this).parents('.bwp-syntax-block').removeClass('bwp-syntax-has-border');
		}
		jQuery(this).parents('.bwp-syntax-block').find('.bwp-syntax-wrapper').toggle();
		return false;
	});

	jQuery('.bwp-syntax-block a.bwp-syntax-source-switch').click(function(){
		newwindow = window.open("", "SourceCode", "height=400,width=700,top=200,left=150,scrollbars=1");
		var container = jQuery(this).parents('.bwp-syntax-block');
		var tmp = newwindow.document;
		tmp.write(container.find('.bwp-syntax-source').html());
		tmp.close();
		return false;
	});
});