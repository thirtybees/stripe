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
          {* Grab the form: *}
        var $form = $('#stripe-cc-form');

        if (response.error) {
            {* Show the errors on the form: *}
          $form.find('.payment-errors').text(response.error.message);
          $form.find('.submit').prop('disabled', false); // Re-enable submission
          $form.find('.stripe-loader').hide();
        } else {
            {* Token was created! *}
            {* Get the token ID: *}
          var token = response.id;

            {* Insert the token ID into the form so it gets submitted to the server: *}
          $form.append($('<input type="hidden" name="stripe-token">').val(token));

            {* Submit the form: *}
          $form.get(0).submit();
        }
      }

      function initStripeCC() {
        if (typeof Stripe === 'undefined' || typeof Card === 'undefined') {
          console.log('try again');
          setTimeout(initStripeCC, 100);
          return;
        }

        var $form = $('#stripe-cc-form');
        $form.card({
          container: '#stripe-card-wrapper',
          formSelectors: {
            numberInput: '#stripeCardNumber',
            expiryInput: '#stripeCardExpiry',
            cvcInput: '#stripeCardCVC',
          },
          placeholders: {
            name: '{$stripe_name|escape:'javascript':'UTF-8'}',
          },
        });

        Stripe.setPublishableKey('{$stripe_publishable_key|escape:'javascript':'UTF-8'}');

        $form.submit(function (event) {
          event.preventDefault();
          {* Disable the submit button to prevent repeated clicks: *}
          $form.find('.submit').prop('disabled', true);
          $form.find('.stripe-loader').show();

          {* Request a token from ThirtybeesStripe: *}
          var expiry = $('#stripeCardExpiry').val().split('/', 2);
          Stripe.source.create({
              type: 'card',
              card: {
                number: $('#stripeCardNumber').val(),
                cvc: $('#stripeCardCVC').val(),
                exp_month: parseInt(expiry[0]),
                exp_year: parseInt(expiry[1]),
              },
              owner: {
                address: {
                  postal_code: $('#stripeCardZip').val(),
                }
              }
            }, stripeResponseHandler);
            {* Prevent the form from being submitted: *}
          return false;
        });
      }

      initStripeCC();
    }

    initEverything();
  })();
</script>
<br/>
<div id="stripe-bootstrap">
    <div class="row clearfix">
        <form action="{$stripe_confirmation_page|escape:'htmlall':'UTF-8'}" method="POST" id="stripe-cc-form" class="col-md-5 form-inline">
            <input type="hidden" name="stripe-id_cart" value="{$id_cart|escape:'htmlall':'UTF-8'}">
            <div class="panel panel-default credit-card-box">
                <div class="panel-heading">
                    <div class="row display-tr">
                        <h3 class="panel-title display-td">{l s='Payment Details' mod='stripe'}</h3>
                        <div class="display-td">
                            <img class="img-responsive pull-right" src="{$module_dir|escape:'htmlall':'UTF-8'}views/img/maincreditcards.png">
                        </div>
                    </div>
                </div>
                <div class="panel-body">
                    <div class="row">
                        <div class="col-md-12 form-group">
                            <label for="stripeCardNumber">{l s='CARD NUMBER' mod='stripe'}</label>
                            <div class="input-group col-md-12">
                                <input
                                        type="tel"
                                        class="form-control"
                                        id="stripeCardNumber"
                                        placeholder="{l s='Valid Card Number' mod='stripe'}"
                                        required
                                />
                                <span class="input-group-addon"><i class="customicon-credit-card"></i></span>
                            </div>
                        </div>
                    </div>
                    <br/>
                    <div class="row">
                        <div class="col-xs-7 col-md-7 col-lg-7 form-group">
                            <label for="stripeCardExpiry">{l s='EXP. DATE' mod='stripe'}</label>
                            <input
                                    type="tel"
                                    class="form-control"
                                    id="stripeCardExpiry"
                                    placeholder="{l s='MM / YY' mod='stripe'}"
                                    required
                            />
                        </div>
                        <div class="col-xs-5 col-md-5 col-lg-5 form-group">
                            <label for="stripeCardCVC">{l s='CVC CODE' mod='stripe'}</label>
                            <input
                                    type="tel"
                                    class="form-control"
                                    id="stripeCardCVC"
                                    placeholder="{l s='CVC' mod='stripe'}"
                                    required
                            />
                        </div>
                    </div>
                    {if $stripecc_zipcode}
                        <br/>
                        <div class="row">
                            <div class="col-xs-5 col-md-5 col-lg-5 form-group">
                                <label for="stripeCardZip">{l s='ZIPCODE' mod='stripe'}</label>
                                <input
                                        type="text"
                                        class="form-control"
                                        id="stripeCardZip"
                                        placeholder="{l s='Zipcode' mod='stripe'}"
                                />
                            </div>
                        </div>
                    {/if}
                    <br/>
                    <div class="row">
                        <div class="col-xs-12 clearfix">
                            <button class="submit subscribe btn btn-success btn-lg btn-block" type="submit">{l s='Pay' mod='stripe'} {$stripe_amount_formatted|escape:'htmlall':'UTF-8'}
                                <div class="stripe-loader pull-right" style="display: none;"></div>
                            </button>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-xs-12 clearfix">
                            <p class="payment-errors"></p>
                        </div>
                    </div>
                </div>
            </div>
        </form>
        {if $stripe_cc_animation}
            <div id="stripe-card-wrapper" class="col-md-6 col-lg col-xl-6 hidden-xs hidden-sm"></div>
        {/if}
    </div>
</div>

{* Dummy placeholder if animation is disabled *}
{if !$stripe_cc_animation}
    <div id="stripe-card-wrapper" style="display:none"></div>
{/if}

