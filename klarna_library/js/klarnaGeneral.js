if(typeof $ == 'undefined') {
    $ = jQuery;
}

var klarnaGeneralLoaded = true;
var red_baloon_busy = false;
var blue_baloon_busy = false;
var address_busy = false;
var baloons_moved = false;
var flagChange_active = false;
var changeLanguage_busy = false;
var openBox_busy = false;
var showing_companyNotAlowed_box = false;
var klarnaSelectedPayment;

var klarna_js_loaded = true;

var klarna = {
	errorHandler: {
		show: function(parent, message, code, type) {
			var errorHTML = '<div class="klarna_errMsg"><span>'+message+'</span></div>';
			errorHTML += '<div class="klarna_errDetails">'
			errorHTML += '<span class="klarna_errType">'+type+'</span>';
			errorHTML += '<span class="klarna_errCode">#'+code+'</span></div>';

			jQuery('#klarna_red_baloon_content div').html(errorHTML);
			showRedBaloon(parent);
		}
	}
};

//Load when document finished loading
$(document).ready(function (){
    jQuery(document).delegate('input.Klarna_radio', 'change', function() {
        gender = jQuery(this).attr('value');
    });

    if (global_countryCode == "de" || global_countryCode == "nl") {
        jQuery(document).delegate('.klarna_select_bday', 'change', function() {
            klarna_select_bday = jQuery(this).val();
        });
        jQuery(document).delegate('.klarna_select_bmonth', 'change', function() {
            klarna_select_bmonth = jQuery(this).val();
        });
        jQuery(document).delegate('.klarna_select_byear', 'change', function() {
            klarna_select_byear = jQuery(this).val();
        });
    }

    var baloon = $('#klarna_baloon').clone();
    $(document).find('#klarna_baloon').each(function () {
        $(this).remove();
    });

    var baloon2 = $('#klarna_red_baloon').clone();
    $(document).find('#klarna_red_baloon').each(function () {
        $(this).remove();
    });

    var baloon3 = $('#klarna_blue_baloon').clone();
    $(document).find('#klarna_blue_baloon').each(function () {
        $(this).remove();
    });

    $('body').append(baloon);
    $('body').append(baloon2);
    $('body').append(baloon3);

    doDocumentIsReady();

    if($('input[type=radio][name=custom_gateway]').length > 0)
        var choice = $('input[type=radio][name=custom_gateway]:checked').val();
    else
        var choice = $('input[type=hidden][name=custom_gateway]').val();

    klarnaSelectedPayment = choice;

    if (choice != "wpsc_merchant_klarna_invoice")
    {
        $('#klarna_box_invoice_top_right').css({"display": "none"});
        $('#klarna_box_invoice').animate({"min-height": "55px", "height": "55px"}, 200);
        $('#klarna_box_invoice').find('.klarna_box_bottom').css({"display": "none"});
        showHideIlt($('#klarna_box_invoice .klarna_box_ilt'), false, false);
    }
    else {
        $('#klarna_box_invoice').find('.klarna_box_bottom').fadeIn('fast');
        showHideIlt($('#klarna_box_invoice .klarna_box_ilt'), true, false);
    }

    if (choice != "wpsc_merchant_klarna_part")
    {
        $('#klarna_box_part_top_right').css({"display": "none"});
        $('#klarna_box_part').animate({"min-height": "55px", "height": "55px"}, 200);
        $('#klarna_box_part').find('.klarna_box_bottom').css({"display": "none"});
        showHideIlt($('#klarna_box_part .klarna_box_ilt'), false, false);
    }
    else {
        $('#klarna_box_part').find('.klarna_box_bottom').fadeIn('fast');
        showHideIlt($('#klarna_box_part .klarna_box_ilt'), true, false);
    }

    if (choice != "wpsc_merchant_klarna_spec")
    {
        $('#klarna_box_spec_top_right').css({"display": "none"});
        $('#klarna_box_spec').animate({"min-height": "55px", "height": "55px"}, 200);
        $('#klarna_box_spec').find('.klarna_box_bottom').css({"display": "none"});
        showHideIlt($('#klarna_box_spec .klarna_box_ilt'), false, false);
    }
    else {
        $('#klarna_box_spec').find('.klarna_box_bottom').fadeIn('fast');
        showHideIlt($('#klarna_box_spec .klarna_box_ilt'), true, false);
    }

    baloons_moved = true;
});

function doDocumentIsReady ()
{
    var foundBox = false;
    var currentMinHeight_invoice = $('#klarna_box_invoice').height();
    var currentMinHeight_part = $('#klarna_box_part').height();
    var currentMinHeight_spec = $('#klarna_box_spec').height();

    if(typeof InitKlarnaSpecialPaymentElements != 'undefined')
        InitKlarnaSpecialPaymentElements('specialCampaignPopupLink', global_spec_eid, global_countryCode);

    if (global_countryCode == "de" || global_countryCode == "nl") {
        if (typeof klarna_select_bday != "undefined") {
            $('.klarna_select_bday').val(klarna_select_bday);
        }
        if (typeof klarna_select_bmonth != "undefined") {
            $('.klarna_select_bmonth').val(klarna_select_bmonth);
        }
        if (typeof klarna_select_byear != "undefined") {
            // Years box
            var date = new Date();
            for (i = date.getFullYear(); i >= 1900; i--) {
                $('<option/>').val(i).text(i).appendTo('.klarna_select_byear');
            }
            $('.klarna_select_byear').val(klarna_select_byear);
        }
    }

    $(document).bind('triggerChoosePaymentOption', function (e, choice){
        if (openBox_busy == false)
        {
            hideRedBaloon();
            openBox_busy = true;
            if (choice == "wpsc_merchant_klarna_invoice")
            {
                $('#klarna_box_part_top_right').fadeOut('fast');
                $('#klarna_box_part').animate({"min-height": "55px"}, 200);
                $('#klarna_box_part').find('.klarna_box_bottom').fadeOut('fast');
                showHideIlt($('#klarna_box_part .klarna_box_ilt'), false);

                $('#klarna_box_spec_top_right').fadeOut('fast');
                $('#klarna_box_spec').animate({"min-height": "55px"}, 200);
                $('#klarna_box_spec').find('.klarna_box_bottom').fadeOut('fast');
                showHideIlt($('#klarna_box_spec .klarna_box_ilt'), false);

                $('#klarna_box_invoice').animate({"min-height": currentMinHeight_invoice}, 200, function () {
                	showHideIlt($(this).find('.klarna_box_ilt'), true);
                    $(this).find('.klarna_box_bottom').fadeIn('fast', function () {
                        $('.klarna_box_bottom_content_loader').fadeOut();

                        if (showing_companyNotAlowed_box)
                        {
                            hideRedBaloon();
                        }
                    });
                    $('#klarna_box_invoice_top_right').fadeIn('fast');

                    if (invoice_different_language)
                        $('.klarna_box_bottom_languageInfo').fadeIn('fast');

                    invoice_active = true;
                    openBox_busy = false;
                });
            }
            else if (choice == "wpsc_merchant_klarna_part")
            {
                $('#klarna_box_invoice_top_right').fadeOut('fast');
                $('#klarna_box_invoice').animate({"min-height": "55px"}, 200);
                $('#klarna_box_invoice').find('.klarna_box_bottom').fadeOut('fast');
                showHideIlt($('#klarna_box_invoice .klarna_box_ilt'), false);

                $('#klarna_box_spec_top_right').fadeOut('fast');
                $('#klarna_box_spec').animate({"min-height": "55px"}, 200);
                $('#klarna_box_spec').find('.klarna_box_bottom').fadeOut('fast');
                showHideIlt($('#klarna_box_spec .klarna_box_ilt'), false);

                $('#klarna_box_part').animate({"min-height": currentMinHeight_part}, 200, function () {
                	showHideIlt($(this).find('.klarna_box_ilt'), true);
                    $(this).find('.klarna_box_bottom').fadeIn('fast', function () {
                        $('.klarna_box_bottom_content_loader').fadeOut();

                        if (showing_companyNotAlowed_box)
                        {
                            hideRedBaloon();
                        }
                    });
                    $('#klarna_box_part_top_right').fadeIn('fast');

                    if (part_different_language)
                        $('.klarna_box_bottom_languageInfo').fadeIn('fast');

                    part_active = true;
                    openBox_busy = false;
                });
            }
            else if (choice == "wpsc_merchant_klarna_spec")
            {
                $('#klarna_box_invoice_top_right').fadeOut('fast');
                $('#klarna_box_invoice').animate({"min-height": "55px"}, 200);
                $('#klarna_box_invoice').find('.klarna_box_bottom').fadeOut('fast');
                showHideIlt($('#klarna_box_invoice .klarna_box_ilt'), false);


                $('#klarna_box_part_top_right').fadeOut('fast');
                $('#klarna_box_part').animate({"min-height": "55px"}, 200);
                $('#klarna_box_part').find('.klarna_box_bottom').fadeOut('fast');
                showHideIlt($('#klarna_box_part .klarna_box_ilt'), false);

                $('#klarna_box_spec').animate({"min-height": currentMinHeight_spec}, 200, function () {
                	showHideIlt($(this).find('.klarna_box_ilt'), true);
                    $(this).find('.klarna_box_bottom').fadeIn('fast', function () {
                        $('.klarna_box_bottom_content_loader').fadeOut();

                        if (showing_companyNotAlowed_box)
                        {
                            hideRedBaloon();
                        }
                    });
                    $('#klarna_box_spec_top_right').fadeIn('fast');

                    if (spec_different_language)
                        $('.klarna_box_bottom_languageInfo').fadeIn('fast');

                    spec_active = true;
                    openBox_busy = false;
                });
            }
            else {
                $('#klarna_box_part_top_right').fadeOut('fast');
                $('#klarna_box_invoice_top_right').fadeOut('fast');
                $('#klarna_box_spec_top_right').fadeOut('fast');

                $('.klarna_box_bottom').fadeOut('fast', function () {
                	$(this).find('.klarna_box_ilt').fadeOut('fast');
                    $('#klarna_box_invoice').animate({"min-height": "55px"}, 200);
                    $('#klarna_box_part').animate({"min-height": "55px"}, 200);
                    $('#klarna_box_spec').animate({"min-height": "55px"}, 200);

                    $('.klarna_box_bottom_languageInfo').fadeOut('fast');

                    invoice_active = false;
                    openBox_busy = false;
                });
            }
        }
        klarnaSelectedPayment = choice;
    });

    // P-Classes box actions
    $('.klarna_box').find('ol').find('li').mouseover(function (){
        if ($(this).attr("id") != "click")
            $(this).attr("id", "over");
    }).mouseout(function (){
        if ($(this).attr("id") != "click")
            $(this).attr("id", "");
    }).click(function (){
        // Reset list and move chosen icon to newly selected pclass
        chosen = $(this).parent("ol").find('img')
        resetListBox($(this).parent("ol"));
        chosen.appendTo($(this).find('div'));
        $(this).attr("id", "click");

        // Update input field with pclass id
        var value = $(this).find('span').html();
        var name = $(this).parent("ol").attr("id");

        $("input:hidden[name="+name+"]").attr("value", value);
    });

    $(document).find('input[type=radio][name=custom_gateway]').each(function () {
        var value = $(this).val();

        $(this).click(function (){
            $(this).trigger("triggerChoosePaymentOption", [ value ]);
        });

        $(this).bind("keyup blur focus change", function (){
            $(this).trigger("triggerChoosePaymentOption", [ value ]);
        });
    });

    if (global_countryCode == "de" || global_countryCode == "nl")
    {
        if (gender == 'm' || gender == '1')
        {
            $('.Klarna_radio[value=1]').attr('checked', 'checked');
        }
        else if (gender == 'f' || gender == '0')
        {
            $('.Klarna_radio[value=0]').attr('checked', 'checked');
        }
    }

    // Input field on focus
    $('.klarna_box').find('input').focusin(function () {
        setBaloonInPosition($(this), false);
    }).focusout(function () {
        hideBaloon();
    });

    // Chosing the active language
    $('.box_active_language').click(function () {
        if (flagChange_active == false)
        {
            flagChange_active = true;

            $(this).parent().find('.klarna_box_top_flag_list').slideToggle('fast', function () {
                if ($(this).is(':visible'))
                {
                    $(this).parent('.klarna_box_top_flag').animate({opacity: 1.0}, 'fast');
                }
                else {
                    $(this).parent('.klarna_box_top_flag').animate({opacity: 0.4}, 'fast');
                }

                flagChange_active = false;
            });
        }
    });

    $('.klarna_box_top_flag_list img').click(function (){
        if (changeLanguage_busy == false)
        {
            changeLanguage_busy = true;

            var newIso = $(this).attr("alt");

            $('#box_active_language').attr("src", $(this).attr("src"));

            var box = $(this).parents('.klarna_box_container');
            var params;
            var values;
            var type;

            if (box.find('.klarna_box').attr("id") == "klarna_box_invoice")
            {
                params = paramValues_invoice;
                values = paramNames_invoice;
                type = "invoice";
            }
            else if (box.find('.klarna_box').attr("id") == "klarna_box_part")
            {
                params = paramValues_part;
                values = paramNames_part;
                type = "part";
            }
            else if (box.find('.klarna_box').attr("id") == "klarna_box_spec")
            {
                params = paramValues_spec;
                values = paramNames_spec;
                type = "spec";
            }
            else {
                return ;
            }

            changeLanguage(box, params, values, newIso, global_countryCode, type);
        }
    });

    setTimeout('prepareRedBaloon()', 2000);

    $('#klarna_red_baloon').mouseover(function (){
        showRedBaloonShowAgain();
    });

    $('#klarna_red_baloon').bind("mouseout blur", function (){
        showRedBaloonHidden();
    });

    $('.klarna_box_bottom_languageInfo').mousemove(function (e) {
        showBlueBaloon(e.pageX, e.pageY, $(this).find('img').attr("alt"));
    });

    $('.klarna_box_bottom_languageInfo').mouseout(function () {
        hideBlueBaloon();
    });

    $(document).find('.Klarna_pnoInputField').each(function (){
        var pnoField = $(this);

        $(this).bind("keyup change blur focus", function (){
            getAddress($(this), ($(this).parents('.klarna_box').attr("id") == "klarna_box_invoice"), ($(this).parents('.klarna_box').attr("id") == "klarna_box_invoice" ? pnoField : null));
        });
    });
}

/**
 * Showing and hiding the ILT questions
 * 
 * @param field
 * @param show
 * @param animate
 */
function showHideIlt (field, show, animate)
{
	if (show == false)
	{
		if (animate == true)
			field.slideUp('fast');
		else
			field.hide();
	}
	else {
		var length = field.find('.klarna_box_iltContents').find('.klarna_box_ilt_question').length;
		
		if (length > 0)
		{
			if (animate == true)
				field.slideDown('fast');
			else 
				field.show();
		}
	
	}
}

function prepareRedBaloon ()
{
    if ($('#klarna_red_baloon_content div').html() != "")
    {
        showRedBaloonShowAgain();
        setTimeout('showRedBaloonHidden()', 3000);
    }
}

function getAddress (box, companyAllowed, field)
{
    var pno_value = $(box).val();

    // Set the PNO to the other fields
    $(document).find('.Klarna_pnoInputField').each(function () {
        $(this).val(pno_value);
    });

    // Do check
    if (pno_value != "")
    {
        $(document).find('.klarna_box_bottom_content_loader').each(function () {
            if (!$(this).is(":visible"))
                $(this).fadeIn('fast');
        });

        if (!validateSocialSecurity(pno_value))
        {
            $(document).find('.klarna_box_bottom_content_loader').each(function () {
                $(this).fadeOut('fast');
            });

            if ($('.klarna_box_bottom_address').is(":visible"))
                $('.klarna_box_bottom_address').slideUp('fast');
        }
        else
        {
            if (!address_busy)
            {
                address_busy = true;

                paymentMethod = '';
                if(klarnaSelectedPayment.indexOf('invoice') != -1)
                    paymentMethod = 'invoice';
                else if(klarnaSelectedPayment.indexOf('part') != -1)
                    paymentMethod = 'part';
                else if(klarnaSelectedPayment.indexOf('spec') != -1)
                    paymentMethod = 'spec';

                var data_obj = {
                        action: 'get_klarna_address',
                        country: global_countryCode,
                        pno: pno_value,
                        type: paymentMethod
                };
                var success_callback =  function(xml){
                        $(xml).find('error').each(function() {
                            var msg = $(this).find('message').text();
                            var code = $(this).find('code').text();
                            var type = $(this).find('type').text();
                            $('.klarna_box_bottom_content_loader').fadeOut('fast', function () {
                                address_busy = false;
                            });
                            klarna.errorHandler.show($(box).closest('.klarna_box'), msg, code, type);
                        });
                        $(xml).find('getAddress').each(function() {
                            var selectBox = ($('address', this).length > 1);

                            var inputInvoice;
                            var inputPart;
                            var inputSpec;

                            var string = "";

                            $(this).find('address').each(function () {
                                var isCompany = ($('companyName', this).length > 0);

                                if (!selectBox)
                                {
                                    var inputValue = (isCompany ? $(this).find('companyName').text() : $(this).find('first_name').text() + " " + $(this).find('last_name').text());
                                    inputValue += "|"+$(this).find('street').text();
                                    inputValue += "|"+$(this).find('zip').text()+"|"+$(this).find('city').text();
                                    inputValue += "|"+$(this).find('countryCode').text();

                                    if (typeof shipmentAddressInput_invoice != "undefined")
                                        inputInvoice = '<input type="hidden" name="'+shipmentAddressInput_invoice+'" value="'+inputValue+'" />';

                                    if (typeof shipmentAddressInput_part != "undefined")
                                        inputPart = '<input type="hidden" name="'+shipmentAddressInput_part+'" value="'+inputValue+'" />';

                                    if (typeof shipmentAddressInput_spec != "undefined")
                                        inputSpec = '<input type="hidden" name="'+shipmentAddressInput_spec+'" value="'+inputValue+'" />';

                                    string += "<p>"+(isCompany ? $(this).find('companyName').text() : $(this).find('first_name').text() + " " + $(xml).find('last_name').text())+"</p>";
                                    string += "<p>"+$(this).find('street').text()+"</p>";
                                    string += "<p>"+$(this).find('zip').text()+" "+$(this).find('city').text()+"</p>";

                                    inputInvoice += string;
                                    inputPart += string;
                                    inputSpec += string;
                                }
                                else {
                                    var inputValue = (isCompany ? $(this).find('companyName').text() : $(this).find('first_name').text() + " " + $(this).find('last_name').text());
                                    inputValue += "|"+$(this).find('street').text();
                                    inputValue += "|"+$(this).find('zip').text()+"|"+$(this).find('city').text();
                                    inputValue += "|"+$(this).find('countryCode').text();

                                    string += '<option value="'+inputValue+'">';
                                    string += (isCompany ? $(this).find('companyName').text() : $(this).find('first_name').text() + " " + $(xml).find('last_name').text());
                                    string += ", "+$(this).find('street').text();
                                    string += ", "+$(this).find('zip').text()+" "+$(this).find('city').text();
                                    string += ", "+$(this).find('countryCode').text();
                                    string += '</option>';
                                }

                                if (isCompany)
                                {
                                    $('#invoiceType').val("company");
                                    $('.referenceDiv').slideDown('fast');

                                    if (!selectBox)
                                    {
                                        $('.klarna_box_bottom').animate({"min-height": "300px"},'fast');
                                    }

                                    if (companyAllowed == false && typeof lang_companyNotAllowed != "undefined")
                                    {
                                        showRedBaloon($(box));
                                        $('#klarna_red_baloon_content div').html(lang_companyNotAllowed);
                                        showing_companyNotAlowed_box = true;
                                    }
                                    else {
                                        hideRedBaloon();
                                    }
                                }
                                else
                                {
                                    $('#invoiceType').val("private");
                                    $(document).find('.referenceDiv').slideUp('fast');

                                    $('.klarna_box_bottom').animate({"min-height": "250px"},'fast');

                                    if (showing_companyNotAlowed_box)
                                        hideRedBaloon();
                                }
                            });

                            if (selectBox)
                                string += "</select>";

                            var selectInvoice;
                            var selectPart;
                            var selectSpec;

                            if (selectBox)
                            {
                                if (typeof shipmentAddressInput_invoice != "undefined")
                                    selectInvoice = '<select name="'+shipmentAddressInput_invoice+'">'+string;

                                if (typeof shipmentAddressInput_part != "undefined")
                                    selectPart = '<select name="'+shipmentAddressInput_part+'">'+string;

                                if (typeof shipmentAddressInput_spec != "undefined")
                                    selectSpec = '<select name="'+shipmentAddressInput_spec+'">'+string;
                            }

                            $('#klarna_box_invoice').find('.klarna_box_bottom_address_content').html((selectBox ? selectInvoice : inputInvoice));

                            $('#klarna_box_part').find('.klarna_box_bottom_address_content').html((selectBox ? selectPart : inputPart));

                            $('#klarna_box_spec').find('.klarna_box_bottom_address_content').html((selectBox ? selectSpec : inputSpec));

                            $('.klarna_box_bottom_address').slideDown('fast');
                            $('.klarna_box_bottom_content_loader').fadeOut('fast', function () {
                                address_busy = false;
                                hideRedBaloon();
                            });
                        });
                        address_busy = false;
                };
                // Get the new klarna_box
                $.post( wpsc_ajax.ajaxurl, data_obj, success_callback );
            }
        }
    }
    else {
        $(document).find('.referenceDiv').each(function (){
            if ($(this).is(":visible"))
            {
                $(this).slideUp('fast');
            }
            else {
                $(this).css({"display":"none"});
            }
        });

        $('.klarna_box_bottom_content_loader').fadeOut('fast');

        $(document).find('.klarna_box_bottom_address').each(function () {
            if ($(this).is(":visible"))
            {
                $(this).slideUp('fast');
            }
            else {
                $(this).css({"display":"none"});
            }
        });
    }
}

function showBlueBaloon (x, y, text)
{
    $('#klarna_blue_baloon_content div').html(text);

    var top = (y - $('#klarna_blue_baloon').height())-5;
    var left = (x - ($('#klarna_blue_baloon').width()/2)+5);

    $('#klarna_blue_baloon').animate({"left": left, "top": top}, 10);

    if (!$('#klarna_blue_baloon').is(':visible') && !blue_baloon_busy)
    {
        blue_baloon_busy = true;
        $('#klarna_blue_baloon').fadeIn('fast', function () {
            blue_baloon_busy = false;
        });
    }
}

function hideBlueBaloon ()
{
    if ($('#klarna_blue_baloon').is(':visible') && !blue_baloon_busy)
    {
        $('#klarna_blue_baloon').fadeOut('fast', function () {
            blue_baloon_busy = false;
        });
    }
}

function showRedBaloon (box) {
    if (red_baloon_busy)
        return;

    red_baloon_busy = true;
    var field;
    if(typeof box != undefined) {
        field = box.find('.klarna_logo').first();
    } else {
        field = $('#klarna_logo_' + chosen);
    }

    var position = field.offset();
    var top = (position.top - $('#klarna_red_baloon').height()) + ($('#klarna_red_baloon').height() / 6);
    if (top < 0) top = 10;
    position.top = top;

    var left = (position.left + field.width()) - ($('#klarna_red_baloon').width() / 2);

    position.left = left;

    $('#klarna_red_baloon').css(position);

    $('#klarna_red_baloon').fadeIn('slow', function () {
        red_baloon_busy = false;

        setTimeout('showRedBaloonHidden()', 3000);
    });
}

function showRedBaloonHidden ()
{
    if (red_baloon_busy)
        return;

    red_baloon_busy = true;

    $('#klarna_red_baloon').animate({ "opacity": 0.2}, 500, function () {
        red_baloon_busy = false;
    });
}

function showRedBaloonShowAgain ()
{
    if (red_baloon_busy)
        return;

    red_baloon_busy = true;

    $('#klarna_red_baloon').animate({ "opacity": 1.0}, 500, function () {
        red_baloon_busy = false;
    });
}

function hideRedBaloon ()
{
    if (red_baloon_busy)
        return;

    if ($('#klarna_red_baloon').is(':visible') && !red_baloon_busy)
    {
        $('#klarna_red_baloon').fadeOut('fast', function () {
            red_baloon_busy = false;
            showing_companyNotAlowed_box = false;
        });
    }
}

/**
 * This function is only available for swedish social security numbers
 */
function validateSocialSecurity (vPNO)
{
    if (typeof vPNO == 'undefined')
        return false;

    return vPNO.match(/^([1-9]{2})?[0-9]{6}[-\+]?[0-9]{4}$/)
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

function hideBaloon (callback)
{
    if ($('#klarna_baloon').is(":visible"))
    {
        $('#klarna_baloon').fadeOut('fast', function (){
            if( callback ) callback();

            return true;
        });
    }
    else {
        if( callback ) callback();
        return true;
    }
}

function setBaloonInPosition ($field, red_baloon)
{
    hideBaloon(function (){
        var position = $field.offset();
        var name = $field.attr('name');
        var value = $field.attr('alt');

        if (!value && !red_baloon)
        {
            return false;
        }

        if (!red_baloon)
        {
            $('#klarna_baloon_content div').html(value);

            var top = position.top - $('#klarna_baloon').height();
            if (top < 0) top = 10;
            position.top = top;

            var left = (position.left + $field.width()) - ($('#klarna_baloon').width() - 50);

            position.left = left;

            $('#klarna_baloon').css(position);

            $('#klarna_baloon').fadeIn('fast');
        }
        else {
            var top = position.top - $('#klarna_red_baloon').height();
            if (top < 0) top = 10;
            position.top = top;

            var left = (position.left + $field.width()) - ($('#klarna_red_baloon').width() - 50);

            position.left = left;

            $('#klarna_red_baloon').css(position);

            $('#klarna_red_baloon').fadeIn('fast');
        }
    });
}

function changeLanguage (replaceBox, paramNames, paramValues, newIso, country, type)
{
    var paramString    = "";
    var valueString = "";

    for (var i = 0; i < paramNames.length; i++)
    {
        paramString += "&params["+paramValues[i]+"]="+paramNames[i];

        var inputValue = $("input[name="+paramNames[i]+"]").val();

        if((typeof(inputValue) != "undefined"))
            valueString += "&values["+paramValues[i]+"]="+inputValue;
    }
    $.ajax({
        type: "GET",
        url: global_ajax_file,
        data: 'action=languagepack&subAction=klarna_box&type='+type+'&newIso='+newIso+'&country='+country+'&sum='+global_sum+'&flag='+global_flag+valueString+paramString,
        success: function(response){
            hideRedBaloon();
            if ($(response).find('.klarna_box'))
            {
                replaceBox.find('.klarna_box').remove();
                replaceBox.append($(response).find('.klarna_box'));
                if (type == "invoice")
                {
                    if (newIso != global_language_invoice)
                        replaceBox.find('.klarna_box_bottom_languageInfo').fadeIn('slow', function () {
                            changeLanguage_busy = false;
                        });
                    else
                        replaceBox.find('.klarna_box_bottom_languageInfo').fadeOut('slow', function () {
                            changeLanguage_busy = false;
                        });

                    klarna_invoiceReady();
                }
                if (type == "part")
                {
                    if(newIso != global_language_part)
                        replaceBox.find('.klarna_box_bottom_languageInfo').fadeIn('slow', function () {
                            changeLanguage_busy = false;
                        });
                    else
                        replaceBox.find('.klarna_box_bottom_languageInfo').fadeOut('slow', function () {
                            changeLanguage_busy = false;
                        });
                    klarna_partReady();
                }

                if (type == "spec")
                {
                    if(newIso != global_language_spec)
                        replaceBox.find('.klarna_box_bottom_languageInfo').fadeIn('slow', function () {
                            changeLanguage_busy = false;
                        });
                    else
                        replaceBox.find('.klarna_box_bottom_languageInfo').fadeOut('slow', function () {
                            changeLanguage_busy = false;
                        });

                    klarna_specReady();
                }

                doDocumentIsReady();
            }
            else {
                alert("Error, block not found. Response:\n\n"+response);
            }
        }
    });
}
