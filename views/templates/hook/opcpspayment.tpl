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
<div class="row">
	<form id="stripe-form" action="{$stripe_confirmation_page|escape:'htmlall':'UTF-8'}" method="POST">
		<input type="hidden" name="stripe-id_cart" value="{$id_cart|escape:'htmlall':'UTF-8'}">
	</form>
	<div class="col-xs-12 col-md-12">
		<p class="payment_module" id="stripe_payment_button">
			{if $cart->getOrderTotal() < 2}
				<a href="">
					<img src="{$domain|cat:$payment_button|escape:'html':'UTF-8'}" alt="{l s='Pay with ThirtybeesStripe' mod='stripe'}" />
					{l s='Minimum amount required in order to pay with this payment module:' mod='stripe'} {convertPrice price=2}
				</a>
			{else}
				<a id="stripe_payment_link" href="#" title="{l s='Pay with ThirtybeesStripe' mod='stripe'}">
					<img src="{$module_dir|escape:'htmlall':'UTF-8'}/views/img/stripebtnlogo.png" alt="{l s='Pay with ThirtybeesStripe' mod='stripe'}" width="64" height="64" />
					{l s='Pay with ThirtybeesStripe' mod='stripe'}
				</a>
			{/if}
		</p>
	</div>
	<script type="text/javascript">
		(function() {
			function initStripe() {
				if (typeof StripeCheckout !== 'undefined') {
					setTimeout(initStripe, 100);
					return;
				}

				var handler = StripeCheckout.configure({
					key: '{$stripe_publishable_key|escape:'javascript':'UTF-8'}',
					image: '/img/logo.jpg',
					locale: 'auto',
					token: function (token) {
						var $form = $('#stripe-form');
						{* Insert the token into the form so it gets submitted to the server: *}
						$form.append($('<input type="hidden" name="stripe-token" />').val(token.id));

						{* Submit the form: *}
						$form.get(0).submit();
					}
				});

				$('#stripe_payment_link').on('click', function (e) {
					{* Open Checkout with further options: *}
					handler.open({
						name: '{$stripe_shopname|escape:'javascript':'UTF-8'}',
						zipCode: {if $stripe_zipcode}true{else}false{/if},
						bitcoin: {if $stripe_bitcoin}true{else}false{/if},
						alipay: {if $stripe_alipay}true{else}false{/if},
						currency: '{$stripe_currency|escape:'javascript':'UTF-8'}',
						amount: '{$stripe_amount|escape:'javascript':'UTF-8'}',
						email: '{$stripe_email|escape:'javascript':'UTf-8'}',
						billingAddress: {if $stripe_collect_billing}true{else}false{/if},
						shippingAddress: {if $stripe_collect_shipping}true{else}false{/if}
					});
					e.preventDefault();
				});
			}

			initStripe();
		})();
	</script>
</div>
