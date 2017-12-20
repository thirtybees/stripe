{*
 * Copyright (C) 2017-2018 thirty bees
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
 *  @copyright 2017-2018 thirty bees
 *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*}
<script type="text/javascript">
  (function () {
    function initStripeAssets() {
      if (typeof $ === 'undefined') {
        setTimeout(initStripeAssets, 100);
        return;
      }

      {if $stripe_checkout}
      if (typeof StripeCheckout === 'undefined') {
        $.getScript('https://checkout.stripe.com/checkout.js');
      }
      {/if}

      {if $stripe_cc_form || $stripe_apple_pay || $stripe_ideal || $stripe_bancontact || $stripe_giropay || $stripe_sofort || $stripe_alipay}
      if (typeof Stripe === 'undefined') {
        $.getScript('https://js.stripe.com/v2/');
      }
      {/if}

      {if $stripe_cc_form}
      if (typeof Card === 'undefined') {
        $.getScript('{$module_dir|escape:'javascript':'UTF-8'}views/js/jquery.card.js');
      }
      {if (Configuration::get(Stripe::INCLUDE_STRIPE_BOOTSTRAP))}
      if (!$("link[href='{$module_dir|escape:'javascript':'UTF-8'}views/css/stripe-bootstrap.css']").length) {
        $('<link href="{$module_dir|escape:'javascript':'UTF-8'}views/css/stripe-bootstrap.css" rel="stylesheet">').appendTo('head');
      }
      {/if}
      if (!$("link[href='{$module_dir|escape:'javascript':'UTF-8'}views/css/creditcard-embedded.css']").length) {
        $('<link href="{$module_dir|escape:'javascript':'UTF-8'}views/css/creditcard-embedded.css" rel="stylesheet">').appendTo('head');
      }
      if (!$("link[href='{$module_dir|escape:'javascript':'UTF-8'}views/css/simplespinner.css']").length) {
        $('<link href="{$module_dir|escape:'javascript':'UTF-8'}views/css/simplespinner.css" rel="stylesheet">').appendTo('head');
      }
      {/if}

      {if $stripe_checkout}
      if (!$("link[href='{$module_dir|escape:'javascript':'UTF-8'}views/css/front.css']").length) {
        $('<link href="{$module_dir|escape:'javascript':'UTF-8'}views/css/front.css" rel="stylesheet">').appendTo('head');
      }
      {/if}
    }

    initStripeAssets();
  })();
</script>
