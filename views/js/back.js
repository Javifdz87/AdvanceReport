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
*  @version   1.0.0
*  @copyright 2016 idnovate
*  @license   See above
*/
$(document).ready(function() {
	if($("div.form-group select[name='frequency']").val() == 1) {
		$("div.form-group select[name='frequency_week']").parent().parent().show();
	} else {
		$("div.form-group select[name='frequency_week']").parent().parent().hide();
	}
	if($("div.form-group select[name='frequency']").val() == 2) {
		$("div.form-group select[name='frequency_month']").parent().parent().show();
	} else {
		$("div.form-group select[name='frequency_month']").parent().parent().hide();
	}
	if($("div.form-group select[name='frequency']").val() == 3) {
		$("div.form-group input[name='frequency_year']").parent().parent().parent().parent().show();
	} else {
		$("div.form-group input[name='frequency_year']").parent().parent().parent().parent().hide();
	}
	$('div.bootstrap div.alert-info').detach().appendTo('#form-advancedreports');
	$('#form-advancedreports_fields').append('<input type="hidden" name="id_report" value="' + GetQueryStringParams('id_report') + '">');
	$("div.form-group input[name='active']").change(function() {
		if($("div.form-group input[name='active']:checked").val() == 1) {
			$("div.form-group select[name='frequency']").prop('disabled',false);
			$("div.form-group select[name='frequency_week']").prop('disabled',false);
			$("div.form-group select[name='frequency_month']").prop('disabled',false);
			$("div.form-group input[name='frequency_year']").prop('disabled',false);
			$("div.form-group input[name='email']").prop('disabled',false);
		} else {
			$("div.form-group select[name='frequency']").prop('disabled','disabled');
			$("div.form-group select[name='frequency_week']").prop('disabled','disabled');
			$("div.form-group select[name='frequency_month']").prop('disabled','disabled');
			$("div.form-group input[name='frequency_year']").prop('disabled','disabled');
			$("div.form-group input[name='email']").prop('disabled','disabled');
		}
	});
	$("div.form-group select[name='frequency']").change(function() {
		if($("div.form-group select[name='frequency']").val() == 1) {
			$("div.form-group select[name='frequency_week']").parent().parent().show();
		} else {
			$("div.form-group select[name='frequency_week']").parent().parent().hide();
		}
		if($("div.form-group select[name='frequency']").val() == 2) {
			$("div.form-group select[name='frequency_month']").parent().parent().show();
		} else {
			$("div.form-group select[name='frequency_month']").parent().parent().hide();
		}
		if($("div.form-group select[name='frequency']").val() == 3) {
			$("div.form-group input[name='frequency_year']").parent().parent().parent().parent().show();
		} else {
			$("div.form-group input[name='frequency_year']").parent().parent().parent().parent().hide();
		}
	});	
	$('a#page-header-desc-advancedreports_fields-desc-module-cancel').detach().appendTo('div.panel-footer').removeClass().addClass('btn btn-default');
	$('a#page-header-desc-advancedreports_fields-desc-module-save').detach().appendTo('div.panel-footer').removeClass().addClass('btn btn-default pull-right');
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