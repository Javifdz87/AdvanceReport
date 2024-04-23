{**
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
*  @version   2.0.0
*  @copyright 2016 idnovate
*  @license   See above
*}

{extends file="helpers/view/view.tpl"}

{block name="override_tpl"}
<div class="panel">
	<h3><i class="icon-cog"></i> {l s='SQL query result' mod='advancedreports'}</h3>
	{if isset($view['error'])}
		<div class="alert alert-warning">{l s='This SQL query has no result.' mod='advancedreports'}</div>
	{else}
		<table class="table" id="viewRequestSql">
			<thead>
				<tr>
					{foreach $view['key'] AS $key}
					<th><span class="title_box">{$key|escape:'htmlall':'UTF-8'}</span></th>
					{/foreach}
				</tr>
			</thead>
			<tbody>
			{foreach $view['results'] AS $result}
				<tr>
					{foreach $view['key'] AS $name}
						{if isset($view['attributes'][$name])}
							<td>{$view['attributes'][$name]|escape:'html':'UTF-8'}</td>
						{else}
							<td>{$result[$name]|escape:'html':'UTF-8'}</td>
						{/if}
					{/foreach}
				</tr>
			{/foreach}
			</tbody>
		</table>
	
		<script type="text/javascript">
			$(function(){
				var width = $('#viewRequestSql').width();
				if (width > 990){
					$('#viewRequestSql').css('display','block').css('overflow-x', 'scroll');
				}
			});
		</script>
	{/if}
</div>
{/block}
