/**
* Advanced Reports
*
* NOTICE OF LICENSE
*
* This product is licensed for one customer to use on one installation (test stores and multishop included).
* Site developer has the right to modify this module to suit their needs, but can not redistribute the module in
* whole or in part. Any other use of this module constitues a violation of the user agreement.
*
* DISCLAIMER
*
* NO WARRANTIES OF DATA SAFETY OR MODULE SECURITY
* ARE EXPRESSED OR IMPLIED. USE THIS MODULE IN ACCORDANCE
* WITH YOUR MERCHANT AGREEMENT, KNOWING THAT VIOLATIONS OF
* PCI COMPLIANCY OR A DATA BREACH CAN COST THOUSANDS OF DOLLARS
* IN FINES AND DAMAGE A STORES REPUTATION. USE AT YOUR OWN RISK.
*
*  @author    idnovate
*  @version   1.4.5
*  @copyright 2020 idnovate
*  @license   See above
*/
$(document).ready(function() {
	if($("form#advancedreports_form select[name='frequency']").val() == 0) {
		$("form#advancedreports_form select[name='frequency_week']").parent().hide();
		$("form#advancedreports_form select[name='frequency_week']").parent().prev().hide();
		$("form#advancedreports_form select[name='frequency_month']").parent().hide();
		$("form#advancedreports_form select[name='frequency_month']").parent().prev().hide();
		$("form#advancedreports_form input[name='frequency_year']").parent().hide();
		$("form#advancedreports_form input[name='frequency_year']").parent().prev().hide();
	}
	if($("form#advancedreports_form select[name='frequency']").val() == 1) {
		$("form#advancedreports_form select[name='frequency_week']").parent().show();
		$("form#advancedreports_form select[name='frequency_week']").parent().prev().show();
	} else {
		$("form#advancedreports_form select[name='frequency_week']").parent().hide();
		$("form#advancedreports_form select[name='frequency_week']").parent().prev().hide();
	}
	if($("form#advancedreports_form select[name='frequency']").val() == 2) {
		$("form#advancedreports_form select[name='frequency_month']").parent().show();
		$("form#advancedreports_form select[name='frequency_month']").parent().prev().show();
	} else {
		$("form#advancedreports_form select[name='frequency_month']").parent().hide();
		$("form#advancedreports_form select[name='frequency_month']").parent().prev().hide();
	}
	if($("form#advancedreports_form select[name='frequency']").val() == 3) {
		$("form#advancedreports_form input[name='frequency_year']").parent().show();
		$("form#advancedreports_form input[name='frequency_year']").parent().prev().show();
	} else {
		$("form#advancedreports_form input[name='frequency_year']").parent().hide();
		$("form#advancedreports_form input[name='frequency_year']").parent().prev().hide();
	}
	//$('div#content div.hint').detach().appendTo('form#advancedreports_form');
	$('table#advancedreports_fields').append('<input type="hidden" name="id_report" value="' + GetQueryStringParams('id_report') + '">');
	$("form#advancedreports_form input[name='active']").change(function() {
		if($("form#advancedreports_form input[name='active']:checked").val() == 1) {
			$("form#advancedreports_form select[name='frequency']").prop('disabled',false);
			$("form#advancedreports_form select[name='frequency_week']").prop('disabled',false);
			$("form#advancedreports_form select[name='frequency_month']").prop('disabled',false);
			$("form#advancedreports_form input[name='frequency_year']").prop('disabled',false);
			$("form#advancedreports_form input[name='email']").prop('disabled',false);
		} else {
			$("form#advancedreports_form select[name='frequency']").prop('disabled','disabled');
			$("form#advancedreports_form select[name='frequency_week']").prop('disabled','disabled');
			$("form#advancedreports_form select[name='frequency_month']").prop('disabled','disabled');
			$("form#advancedreports_form input[name='frequency_year']").prop('disabled','disabled');
			$("form#advancedreports_form input[name='email']").prop('disabled','disabled');
		}
	});
	$("form#advancedreports_form select[name='frequency']").change(function() {
		if($("form#advancedreports_form select[name='frequency']").val() == 1) {
			$("form#advancedreports_form select[name='frequency_week']").parent().show();
			$("form#advancedreports_form select[name='frequency_week']").parent().prev().show();
		} else {
			$("form#advancedreports_form select[name='frequency_week']").parent().hide();
			$("form#advancedreports_form select[name='frequency_week']").parent().prev().hide();
		}
		if($("form#advancedreports_form select[name='frequency']").val() == 2) {
			$("form#advancedreports_form select[name='frequency_month']").parent().show();
			$("form#advancedreports_form select[name='frequency_month']").parent().prev().show();
		} else {
			$("form#advancedreports_form select[name='frequency_month']").parent().hide();
			$("form#advancedreports_form select[name='frequency_month']").parent().prev().hide();
		}
		if($("form#advancedreports_form select[name='frequency']").val() == 3) {
			$("form#advancedreports_form input[name='frequency_year']").parent().show();
			$("form#advancedreports_form input[name='frequency_year']").parent().prev().show();
		} else {
			$("form#advancedreports_form input[name='frequency_year']").parent().hide();
			$("form#advancedreports_form input[name='frequency_year']").parent().prev().hide();
		}
	});
	//$('a#desc-advancedreports_fields-cancel').detach().appendTo('form#advancedreports_fields_form').removeClass().addClass('btn btn-default');
	//$('a#desc-advancedreports_fields-save').detach().appendTo('form#advancedreports_fields_form').removeClass().addClass('btn btn-default pull-right');
});

function getFields() {
	$.ajax({
	    type: "GET",
	    url:location.href + '&getfields=' + $('#table').val(),
	    dataType: "json",
	    success: function (data) {
	    	$('#field').html('');
			$.each(data.fields, function(field, objField) {
			    $('#field').append(
			        $('<option></option>').val(field).html(field)
			    );
			});
		}
	});
}

function submitFieldForm(type) {
	if (type == 0) {
		$('input#addotherfield').val('0');
		$("form#advancedreports_fields_form").submit();
	} else if (type == 1) {
		$('input#addotherfield').val('1');
		$("form#advancedreports_fields_form").submit();
	}
}

function GetQueryStringParams(sParam) {
    var sPageURL = window.location.search.substring(1);
    var sURLVariables = sPageURL.split('&');
    for (var i = 0; i < sURLVariables.length; i++) 
    {
        var sParameterName = sURLVariables[i].split('=');
        if (sParameterName[0] == sParam) 
        {
            return sParameterName[1];
        }
    }
}