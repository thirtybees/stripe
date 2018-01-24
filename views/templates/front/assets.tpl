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
    var script;

    {if $stripe_checkout}
    if (typeof StripeCheckout === 'undefined') {
      script = document.createElement('script');
      script.type = 'text/javascript';
      script.src = 'https://checkout.stripe.com/checkout.js';
      document.querySelector('head').appendChild(script);
    }
    {/if}

    {if $stripe_cc_form}
    if (typeof Stripe === 'undefined') {
      script = document.createElement('script');
      script.type = 'text/javascript';
      script.src = 'https://js.stripe.com/v3/';
      document.querySelector('head').appendChild(script);
    }
    {/if}

    {if $stripe_checkout}
    var found = false;
    [].slice.call(document.querySelectorAll('link')).forEach(function (link) {
      if (link.href === '{$module_dir|escape:'javascript':'UTF-8'}views/css/front.css') {
        found = true;

        return false;
      }
    });
    if (!found) {
      var link = document.createElement('link');
      link.href = '{$module_dir|escape:'javascript':'UTF-8'}views/css/front.css';
      link.rel = 'stylesheet';
      document.querySelector('head').appendChild(link);
    }
    {/if}
  })();
</script>
