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
<style>
    #stripe-apple-pay-method {
        display: none;
    }

    #stripe-apple-pay-logo {
        background-color: black !important;
        background-image: url('{$module_dir|escape:'htmlall':'UTF-8'}views/img/apple_pay_logo_black.png');
        background-position: center;
        background-size: auto 44px;
        background-origin: content-box;
        background-repeat: no-repeat;
        width: 140px;
        height: 44px;
        padding: 0;
        border-radius: 10px;
        border: none;
    }
</style>
<div class="row" id="stripe-apple-pay-method">
    <div class="col-xs-12">
        <p id="stripe_apple_payment_button" class="payment_module">
            <a id="stripe-apple-pay-button" href="#" class="stripeapplepay" title="Pay with Apple Pay">
                <img id="stripe-apple-pay-logo" src="{$module_dir|escape:'htmlall':'UTF-8'}/views/img/apple_pay_logo_black.png" alt="{l s='Pay with Apple Pay' mod='stripe'}"/>
                Pay with Apple Pay
            </a>
        </p>
    </div>
</div>
<script type="text/javascript">
  (function () {
    function initApplePay() {
      if (typeof $ === 'undefined' || typeof Stripe === 'undefined' || Stripe.setPublishableKey === 'undefined') {
        setTimeout(initApplePay, 100);
        return;
      }

      Stripe.setPublishableKey('{$stripe_publishable_key|escape:'javascript':'UTF-8'}');

      Stripe.applePay.checkAvailability(function (available) {
        if (available) {
          $('#stripe-apple-pay-method').show();
          $('#stripe-apple-pay-button').on('click', function () {
            var paymentRequest = {
              countryCode: '{$stripe_country|escape:'javascript':'UTF-8'}',
              currencyCode: '{$stripe_currency|escape:'javascript':'UTF-8'}',
              total: {
                label: '{$stripe_shopname|escape:'javascript':'UTF-8'}',
                amount: '{$stripe_amount_string|escape:'javascript':'UTF-8'}'
              }
            };

            var session = Stripe.applePay.buildSession(paymentRequest,
              function (result, completion) {

                $.post('{$stripe_ajax_validation|escape:'javascript':'UTF-8'}', {
                  'stripe-token': result.token.id,
                  'stripe-id_cart': '{$id_cart|escape:'javascript':'UTF-8'}',
                }).done(function (result) {
                  completion(ApplePaySession.STATUS_SUCCESS);
                    {* You can now redirect the user to a receipt page, etc. *}
                  window.location.href = '{$stripe_ajax_confirmation_page|escape:'javascript':'UTF-8'}' + '&id_order=' + result.idOrder;
                }).fail(function () {
                  completion(ApplePaySession.STATUS_FAILURE);
                });

              }, function (error) {
                console.log(error.message);
              });

            session.begin();
          });
        }
      });
    }

    initApplePay();
  })();
</script>

