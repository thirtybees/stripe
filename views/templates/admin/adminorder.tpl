{*
 * Copyright (C) 2017 thirty bees
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.md
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@thirtybees.com so we can send you a copy immediately.
 *
 *  @author    thirty bees <modules@thirtybees.com>
 *  @copyright 2017 thirty bees
 *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*}
<script type="text/javascript">
	var stripe_total_amount = '{$stripe_total_amount|escape:'javascript':'UTF-8'}';

	$(document).ready(function() {
		$('#stripe_partial_refund_button').click(function () {
			stripeConfirmation();
		});

		$('#stripe_full_refund_button').click(function () {
			$('#stripe_refund_amount').val(stripe_total_amount);
			stripeConfirmation();
		});

		function stripeConfirmation() {
			swal({
				title: '{l s='Are you sure?' mod='stripe' js=1}',
				text: '{l s='Do you want to refund this order?' mod='stripe' js=1}',
				type: 'warning',
				showCancelButton: true,
				confirmButtonColor: '#DD6B55',
				confirmButtonText: '{l s='Yes' mod='stripe' js=1}',
				cancelButtonText: '{l s='No' mod='stripe' js=1}',
				closeOnConfirm: false
			}, function () {
				$form = $('#stripe_refund');
				$form.get(0).submit();
			});
		}
	});
</script>


<div class="panel">
	<div class="panel-heading">
		<i class="icon icon-credit-card"></i> <span>{l s='Stripe' mod='stripe'}</span>
	</div>
	{$stripe_transaction_list}
	<br />
	<div class="row-margin-bottom row-margin-top order_action row clearfix">
		<div class="fixed-width-xl pull-left">
			<button id="stripe_full_refund_button" type="button" class="btn btn-default"><i class="icon icon-undo"></i> {l s='Full refund' mod='stripe'}</button>
		</div>
		<form id="stripe_refund" action="{$stripe_module_refund_action|escape:'htmlall':'UTF-8'}&id_order={$id_order|escape:'htmlall':'UTF-8'}" method="post">
			<div class="input-group pull-left" style="width: 400px;">
				<input type="hidden" id="stripe_refund_order" name="stripe_refund_order" value="{$id_order|escape:'htmlall':'UTF-8'}">
				<div class="input-group-addon">
					{$stripe_currency_symbol}
				</div>
				<input type="text" id="stripe_refund_amount" name="stripe_refund_amount" class="form-control" placeholder="{l s='Remaining: ' mod='stripe'} {$stripe_total_amount|escape:'htmlall':'UTF-8'}">
				<div class="input-group-btn">
					<button id="stripe_partial_refund_button" class="btn btn-default" type="button"><i class="icon icon-undo"></i> {l s='Partial Refund' mod='stripe'}</button>
				</div>
			</div>
		</form>
	</div>
</div>

