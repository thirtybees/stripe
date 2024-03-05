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
<div class="row">
    <a id="stripe_payment_link" href="#" title="{l s='Pay with Stripe' mod='stripe'}" class="btn btn-default">
        {l s='Pay with Stripe' mod='stripe'}
    </a>
    <script type="text/javascript" data-cookieconsent="necessary">
        (function () {
            function documentReady(fn) {
                if (document.readyState !== 'loading') {
                    fn();
                } else if (document.addEventListener) {
                    document.addEventListener('DOMContentLoaded', fn);
                } else {
                    document.attachEvent('onreadystatechange', function () {
                        if (document.readyState !== 'loading') {
                            fn();
                        }
                    });
                }
            }

            function initStripe() {
                if (typeof Stripe === 'undefined') {
                    return setTimeout(initStripe, 100);
                }

                var stripe = Stripe('{$stripePublishableKey|escape:'javascript'}');
                var element = document.getElementById('stripe_payment_link');
                var triggerStripe = function() {
                    stripe
                        .redirectToCheckout({ sessionId: '{$sessionId|escape:'javascript' }'})
                        .then(function(result) {
                            console.log(result);
                        });
                };
                element.addEventListener('click', function() {
                    e.preventDefault();
                    triggerStripe();
                });
                triggerStripe();
            }

            documentReady(initStripe);
        })();
    </script>
</div>
