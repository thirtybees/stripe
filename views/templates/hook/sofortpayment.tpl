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
    var initTime = 0;

    window.stripeBlockerOpen = window.stripeBlockerOpen || {if isset($stripe_blocker_info) && !$stripe_blocker_info}true{else}false{/if};

    function showBlockerInfo() {
      if (window.stripeBlockerOpen) {
        return;
      }

      window.stripeBlockerOpen = true;

      $.fancybox({
        height: 800,
        content: '<h2 style="color: red"><i class="icon icon-times-circle-o"></i> {l s='Unable to initialize checkout' js=1}</h2><strong>{l s='Please enable Stripe in Ghostery as follows:' js=1}</strong><br /><img class="responsive" src="{$module_dir|escape:'javascript'}views/img/fixstripeghostery.gif">',
        afterClose: function () {
          window.stripeBlockerOpen = false;
        },
        helpers: {
          overlay: {
            closeClick: false
          }
        }
      });
    }

    function initEverything() {
      if (typeof $ === 'undefined') {
        setTimeout(initEverything, '100');
        return;
      }

      function stripeResponseHandler(status, response) {
        $('#stripe_sofort_payment_link').click(function () {
          window.location = response.redirect.url;
        });
      }

      function initStripeSofort() {
        if (initTime > 5000) {
          showBlockerInfo();

          return;
        }

        if (typeof Stripe === 'undefined') {
          setTimeout(initStripeSofort, 100);
          initTime += 100;

          return;
        }

        Stripe.setPublishableKey('{$stripe_publishable_key|escape:'javascript':'UTF-8'}');

        Stripe.source.create({
          type: 'sofort',
          amount: {$stripe_amount|intval},
          currency: '{$stripe_currency|escape:'javascript':'UTF-8'}',
          owner: {
            name: '{$stripe_name|escape:'javascript':'UTF-8'}'
          },
          redirect: {
            return_url: '{$link->getModuleLink('stripe', 'sourcevalidation', ['stripe-id_cart' => $id_cart, 'type' => 'sofort'], true)|escape:'javascript':'UTF-8'}'
          },
          sofort: {
            country: '{$stripe_country|escape:'javascript':'UTF-8'}'
          }
        }, stripeResponseHandler);
      }

      initStripeSofort();
    }

    initEverything();
  })();
</script>
<p class="payment_module stripe_payment_button">
    <a id="stripe_sofort_payment_link" style="cursor:pointer" title="{l s='Pay with Sofort Banking' mod='stripe'}">
        <img src="{$module_dir|escape:'htmlall':'UTF-8'}/views/img/sofort.png" alt="{l s='Pay with Sofort Banking' mod='stripe'}" width="64" height="64"/>
        {l s='Pay with Sofort Banking' mod='stripe'}
    </a>
</p>

