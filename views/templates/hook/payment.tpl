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
{if $stripe_checkout}
    <div class="row">
        <div class="col-xs-12 col-md-12">
            <p class="payment_module" id="stripe_payment_button">
                <a id="stripe_payment_link" href="#" title="{l s='Pay by Credit Card' mod='stripe'}">
                    <img src="{$module_dir|escape:'htmlall'}/views/img/stripebtnlogo.png"
                         alt="{l s='Pay by Credit Card' mod='stripe'}" width="64" height="64"/>
                    {l s='Pay by Credit Card' mod='stripe'}
                    {if $showPaymentLogos}
                        <img src="{$module_dir|escape:'htmlall'}/views/img/creditcards.png"
                             alt="{l s='Credit cards' mod='stripe'}"/>
                    {/if}
                </a>
            </p>
        </div>
    </div>
    <script type="text/javascript" data-cookieconsent="necessary">
        (function () {
            function initStripe() {
                if (typeof Stripe === 'undefined') {
                    return setTimeout(initStripe, 100);
                }

                var stripe = Stripe('{$stripe_publishable_key|escape:'javascript'}');
                var element = document.getElementById('stripe_payment_link');
                element.addEventListener('click', function(e) {
                    e.preventDefault();
                    stripe
                        .redirectToCheckout({ sessionId: '{$stripe_session_id|escape:'javascript' }'})
                        .then(function(result) {
                            console.log(result);
                        });
                });
            }
            initStripe();
        })();
    </script>
{/if}
