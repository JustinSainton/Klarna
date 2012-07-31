var getCodeBusy = false;
var makePurchaseBusy = false;
var reservationNumber = null;

$(document).ready(function () {
    $('.klarnaMobile_boxInputField_left input').bind('keyup change click load', function (){
        showHidePlaceHolder($(this));
    });

    $('.klarnaMobile_boxInputField_left_inputPlaceholder input').bind('keyup change click load', function (){
        showHidePlaceHolder($(this));
    });

    $('.klarnaMobile_boxInputField_left input').bind('blur', function (){
        showHidePlaceHolder($(this));
        fillPlaceHolder($(this));
    });

    $('.klarnaMobile_boxInputField_left_inputPlaceholder input').bind('blur', function (){
        showHidePlaceHolder($(this));
        fillPlaceHolder($(this));
    });

    $('.klarnaMobile_boxInputField_right').click (function () {
        var id = $(this).attr("id");

        if (id == "getCode")
        {
            getCode();
            $('.klarnaMobile_boxInputField_right_send').css({"background-image":"url(klarna/mobile/default/loader.gif)"});
        }
        else if (id == "makePurchase")
        {
            makePurchase();
            $('.klarnaMobile_boxInputField_right_buy').css({"background-image":"url(klarna/mobile/default/loader.gif)"});
        }
    });

    $('.klarnaMobile_errorClose').click (function () {
        $('#klarnaMobile_error').fadeOut('fast');
        $('.klarnaMobile_error_Inner').fadeOut('fast');
    });
});

function getCode (callBack)
{
    $.ajax({
        type: "GET",
        url: "klarnaMobile.php",
        data: "page=ajax&subAction=sendCode&phoneNumber="+$('input[name=mobile_no]').val()+'&productId='+pId,
        success: function(xml){
            var statusCode = $(xml).find('statusCode').text();
            var errorCode    = $(xml).find('errorCode').text();

            if (statusCode < 0 || !IsNumeric(statusCode))
            {
                var text;

                if (IsNumeric(statusCode))
                    text = textToLink($(xml).find('message').text(), (errorCode == '2401'));
                else
                    text = textToLink(xml, (errorCode == '2401'));

                // Whoops, an error!
                $('#klarnaMobile_errorText').html(text);

                $('#klarnaMobile_error').css({'opacity': '0.0', "filter": "alpha(opacity=0)", "display":"block"});
                $('#klarnaMobile_error').animate({'opacity': '0.7', "filter": "alpha(opacity=70)", "display":"block"}, 200, function () {
                    $('.klarnaMobile_error_Inner').fadeIn('fast');
                });

                $('.klarnaMobile_boxInputField_right_send').css({"background-image":"url(klarna/mobile/default/icon_send.png)"});
            }
            else {
                $('input[name=mobile_code]').focus();
                $('.klarnaMobile_boxInputField_right_send').css({"background-image":"url(klarna/mobile/default/done.png)"});

                reservationNumber = statusCode;
            }

            if(typeof callBack == 'function'){
                callBack.call();
            }
        }
    });
}

function makePurchase (callBack)
{
    $.ajax({
        type: "GET",
        url: "klarnaMobile.php",
        data: "page=ajax&subAction=makePurchase&phoneNumber="+$('input[name=mobile_no]').val()+'&productId='+pId+"&pinCode="+$('input[name=mobile_code]').val()+'&reservationNumber='+reservationNumber,
        success: function(xml){
            var statusCode = $(xml).find('statusCode').text();
            var redirectURL = $(xml).find('redirectUrl').html();

            if (statusCode < 0 || !IsNumeric(statusCode))
            {
                // Whoops, an error!
                if (IsNumeric(statusCode))
                    $('#klarnaMobile_errorText').html($(xml).find('message').text());
                else
                    $('#klarnaMobile_errorText').html(xml);

                $('#klarnaMobile_error').css({'opacity': '0.0', "filter": "alpha(opacity=0)", "display":"block"});
                $('#klarnaMobile_error').animate({'opacity': '0.7', "filter": "alpha(opacity=70)", "display":"block"}, 200, function () {
                    $('.klarnaMobile_error_Inner').fadeIn('fast');
                });

                $('.klarnaMobile_boxInputField_right_buy').css({"background-image":"url(klarna/mobile/default/icon_buy.png)"});
            }
            else {
                $('input[name=mobile_code]').focus();
                $('.klarnaMobile_boxInputField_right_buy').css({"background-image":"url(klarna/mobile/default/done.png)"});

                if (typeof redirectURL != "undefined" && redirectURL != "")
                    window.location = redirectURL;
            }

            if(typeof callBack == 'function'){
                callBack.call();
            }
        }
    });
}

function closeErrorBox ()
{

}

function showHidePlaceHolder (field)
{
    var name = $(field).attr("name");
    var ph = placeHolderText[name];

    if (typeof ph != 'undefined')
    {
        if ($(field).val() == "")
        {
            $(field).parent().attr("class", "klarnaMobile_boxInputField_left_inputPlaceholder");
        }
        else if (ph != $(field).val())
        {
            $(field).parent().attr("class", "klarnaMobile_boxInputField_left");
        }
        else {
            $(field).parent().attr("class", "klarnaMobile_boxInputField_left_inputPlaceholder");
            $(field).val("");
        }
    }
}

function fillPlaceHolder (field)
{
    var name = $(field).attr("name");
    var ph = placeHolderText[name];

    if (typeof ph != 'undefined')
    {
        if ($(field).val() =="")
        {
            $(field).parent().attr("class", "klarnaMobile_boxInputField_left_inputPlaceholder");
            $(field).val(ph);
        }
    }
}

function IsNumeric(input)
{
   return (input - 0) == input && input.length > 0;
}

function textToLink(text, regUrl)
{
    if( !text ) return text;

    text = text.replace(/((https?\:\/\/|ftp\:\/\/)|(www\.))(\S+)(\w{2,4})(:[0-9]+)?(\/|\/([\w#!:.?+=&%@!\-\/]))?/gi,function(url){
        nice = url;
        if( url.match('^https?:\/\/') )
        {
            nice = nice.replace(/^https?:\/\//i,'')
        }
        else
            url = 'http://'+url;

        return '<a target="_blank" rel="nofollow" href="'+ url +'">'+ nice.replace(/^www./i,'') +'</a>';
    });

    text = text.replace(/(klarna.se)?/gi,function(url){
        return '<a target="_blank" rel="nofollow" href="'+(regUrl == true ? 'https://klarna.com/sv/privat/tjaenster/mobil/registrera' : 'http://www.klarna.se')+'">'+ url +'</a>';
    });

    return text;
}
