var invoice_active = false;
var invoice_different_language = false;

// Load when document finished loading
$(document).ready(function (){
    klarna_invoiceReady();
});

function klarna_invoiceReady ()
{
    var foundBox = false;
    var currentMinHeight_invoice = $('#klarna_box_invoice').height();

    // Chosing the active language
    $('#box_active_language').click(function () {
        $('.klarna_box_top_flag_list').slideToggle('fast', function () {
            if ($(this).is(':visible'))
            {
                $('.klarna_box_top_flag').animate({opacity: 1.0}, 'fast');
            }
            else {
                $('.klarna_box_top_flag').animate({opacity: 0.4}, 'fast');
            }
        });
    });

    $('.klarna_box_bottom_languageInfo').mousemove(function (e) {
        showBlueBaloon(e.pageX, e.pageY, $(this).find('img').attr("alt"));
    });

    $('.klarna_box_bottom_languageInfo').mouseout(function () {
        hideBlueBaloon();
    });

    if(typeof invoice_ITId != "undefined") {
        $('input[name='+invoice_ITId+']').change(function (){
            var val = $(this).val();

            if (val == "private")
            {
                $('#invoice_perOrg_title').text(lang_personNum);
                $('#invoice_box_private').slideDown('fast');
                $('#invoice_box_company').slideUp('fast');
            }
            else if (val == "company")
            {
                $('#invoice_perOrg_title').text(lang_orgNum);
                $('#invoice_box_company').slideDown('fast');
                $('#invoice_box_private').slideUp('fast');
            }
        });
    }
}

function resetListBox ($listBox)
{
    $listBox.find('li').each(function (){
        if ($(this).attr("id") == "click")
        {
            $(this).attr("id", "");
        }

        $(this).find('div').find('img').remove();
    });
}
