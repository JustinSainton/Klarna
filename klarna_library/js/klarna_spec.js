var spec_active = false;
var spec_different_language = false;

// Load when document finished loading
$(document).ready(function (){
    klarna_specReady();
});

function klarna_specReady ()
{
    var foundBox = false;
    var currentMinHeight_spec = $('#klarna_box_spec').height();

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

    if ($('#klarna_red_baloon_content div').html() != "")
    {
        setTimeout('showRedBaloon()', 500);
        setTimeout('showRedBaloonHidden()', 3000);

        $('#klarna_red_baloon').mouseover(function (){
            showRedBaloonShowAgain();
        });

        $('#klarna_red_baloon').bind("mouseout", function (){
            showRedBaloonHidden();
        });
    }

    $('.klarna_box_bottom_languageInfo').mousemove(function (e) {
        showBlueBaloon(e.pageX, e.pageY, $(this).find('img').attr("alt"));
    });

    $('.klarna_box_bottom_languageInfo').mouseout(function () {
        hideBlueBaloon();
    });

    if ($('#getAddressUpdater').val() != "" && global_countryCode == "se")
    {
        getAddress();
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
