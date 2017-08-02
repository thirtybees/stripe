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
  (function () {
    function initEverything() {
      if (typeof $ === 'undefined') {
        setTimeout(initEverything, '100');
        return;
      }

      function stripeResponseHandler(status, response) {
        $('#stripe_alipay_payment_link').click(function () {
          window.location = response.redirect.url;
        });
      }

      function initStripeAlipay() {
        if (typeof Stripe === 'undefined') {
          setTimeout(initStripeAlipay, 100);
          return;
        }

        Stripe.setPublishableKey('{$stripe_publishable_key|escape:'javascript':'UTF-8'}');

        Stripe.source.create({
          type: 'alipay',
          amount: {$stripe_amount|intval},
          currency: '{$stripe_currency|escape:'javascript':'UTF-8'}',
          redirect: {
            return_url: '{$link->getModuleLink('stripe', 'sourcevalidation', ['stripe-id_cart' => $id_cart, 'type' => 'alipay'], true)|escape:'javascript':'UTF-8'}'
          }
        }, stripeResponseHandler);
      }

      initStripeAlipay();
    }

    initEverything();
  })();
</script>
<p class="payment_module stripe_payment_button">
    <a id="stripe_alipay_payment_link" style="cursor:pointer" title="{l s='Pay with Alipay' mod='stripe'}">
        <img src="{$module_dir|escape:'htmlall':'UTF-8'}/views/img/alipay.png" alt="{l s='Pay with Alipay' mod='stripe'}" width="64" height="64"/>
        {l s='Pay with Alipay' mod='stripe'}
    </a>
</p>

