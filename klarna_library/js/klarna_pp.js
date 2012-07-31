jQuery(document).ready(function () {
    // If single product
    jQuery('.wpsc-product .klarna_PPBox').detach().appendTo('.wpsc_product_price').show();

    // If product gallery view
    jQuery('.default_product_display').each(function() {
        jQuery(this).find('.klarna_PPBox').detach().appendTo(jQuery(this).find('.wpsc_product_price')).show();
    });

    jQuery('.klarna_PPBox_top').unbind('click').click(function () {
        jQuery(this).next().slideToggle('fast');
    });
});
