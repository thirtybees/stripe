{*
 * 2016 Michael Dekker
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@michaeldekker.com so we can send you a copy immediately.
 *
 *  @author    Michael Dekker <prestashop@michaeldekker.com>
 *  @copyright 2016 Michael Dekker
 *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*}
<!-- stripe views/templates/front/eupayment.tpl -->
<div class="row">
	<form id="stripe-form" action="{$stripe_confirmation_page|escape:'htmlall':'UTF-8'}" method="POST">
		<input type="hidden" name="stripe-id_cart" value="{$id_cart|escape:'htmlall':'UTF-8'}">
	</form>
	<a id="stripe_payment_link" href="#" title="{l s='Pay with Stripe' mod='stripe'}" class="btn btn-default">
		{l s='Pay with Stripe' mod='stripe'}
	</a>
	<script type="text/javascript">
		(function() {
			var handler = null;

			function initEuStripe() {
				if (typeof $ === 'undefined') {
					setTimeout(initEuStripe, 100);
					return;
				}

				$(document).ready(function () {

					function openStripeHandler(e) {
						if (!handler) {
							return;
						}

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
						if (typeof e !== 'undefined' && typeof e !== 'function') {
							e.preventDefault();
						}
					}

					function initStripe() {
						if (typeof StripeCheckout === 'undefined' || typeof $ === 'undefined') {
							setTimeout(initStripe, 100);
							return;
						}

						handler = StripeCheckout.configure({
							key: '{$stripe_publishable_key|escape:'javascript':'UTF-8'}',
							image: '{$stripeShopThumb|escape:'javascript':'UTF-8'}',
							locale: '{$stripe_locale|escape:'javascript':'UTF-8'}',
							token: function (token) {
								var $form = $('#stripe-form');
								{* Insert the token into the form so it gets submitted to the server: *}
								$form.append($('<input type="hidden" name="stripe-token" />').val(token.id));

								{* Submit the form: *}
								$form.get(0).submit();
							}
						});

						$('#stripe_payment_link').click(openStripeHandler);
						{if $autoplay}
						openStripeHandler();
						{/if}
					}

					initStripe();
				});
			}

			initEuStripe();
		})();
	</script>
</div>
<!-- /stripe views/templates/front/eupayment.tpl -->
