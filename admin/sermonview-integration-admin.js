(function ($) {
    // https://wordpress.stackexchange.com/questions/235406/how-do-i-select-an-image-from-media-library-in-my-plugin
    jQuery('.media-picker').click(function(e) {
        var target_id = $(this).data('target-id');
        var target_img_id = $(this).parents('.image-preview-block').find('img').attr('id');

        console.log(target_id+'|'+target_img_id);

        e.preventDefault();
        var image_frame;
        if(image_frame){
            image_frame.open();
        }
        // Define image_frame as wp.media object
        image_frame = wp.media({
            title: 'Select Media',
            multiple : false,
            library : {
                type : 'image',
            }
        });

        image_frame.on('close',function() {
            // On close, get selections and save to the hidden input
            // plus other AJAX stuff to refresh the image preview
            var selection =  image_frame.state().get('selection');
            var gallery_ids = new Array();
            var my_index = 0;
            selection.each(function(attachment) {
                gallery_ids[my_index] = attachment['id'];
                my_index++;
            });
            var ids = gallery_ids.join(",");
            jQuery('input#'+target_id).val(ids);
            jQuery('#'+target_id).parents('.image-preview-block').find('.reset-default-span').removeClass('hidden');
            jQuery('#'+target_id).parents('.image-preview-block').find('.default-image-span').addClass('hidden');
            Refresh_Image(ids,target_img_id);
        });

        image_frame.on('open',function() {
            // On open, get the id from the hidden input
            // and select the appropiate images in the media manager
            var selection =  image_frame.state().get('selection');
            var ids = jQuery('input#'+target_id).val().split(',');
            ids.forEach(function(id) {
                var attachment = wp.media.attachment(id);
                attachment.fetch();
                selection.add( attachment ? [ attachment ] : [] );
            });
        });

        image_frame.open();
    });
    jQuery('.media-reset').click(function(e) {
        e.preventDefault();
        var target_id = $(this).data('target-id');
        var target_img_id = $(this).parents('.image-preview-block').find('img').attr('id');
        jQuery('input#'+target_id).val('');
        Refresh_Image('',target_img_id);
    });

})(jQuery);
// Ajax request to refresh the image preview
function Refresh_Image(the_id,target_img_id) {
    var data = {
        action: 'svi_setting_get_image',
        target_id: target_img_id,
        img_id: the_id
    };
    jQuery('#'+target_img_id).parents('.image-preview-block').find('.spinner').addClass('is-active');

    jQuery.get(ajaxurl, data, function (response) {
        if (response.success === true) {
            jQuery('#' + response.data.target_id).replaceWith(response.data.image);
            if(the_id) {
                jQuery('#'+target_img_id).parents('.image-preview-block').find('.reset-default-span').removeClass('hidden');
                jQuery('#'+target_img_id).parents('.image-preview-block').find('.default-image-span').addClass('hidden');
            } else {
                jQuery('#'+target_img_id).parents('.image-preview-block').find('.reset-default-span').addClass('hidden');
                jQuery('#'+target_img_id).parents('.image-preview-block').find('.default-image-span').removeClass('hidden');
            }
        }
        jQuery('#'+target_img_id).parents('.image-preview-block').find('.spinner').removeClass('is-active');
    });
}
