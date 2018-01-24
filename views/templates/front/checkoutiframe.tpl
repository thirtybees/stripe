<!doctype html>
<html>
<head>
  {if !empty($stripe_checkout_font_family)}<link rel="stylesheet" href="//fonts.googleapis.com/css?family={$stripe_checkout_font_family|replace:' ':'+'|escape:'htmlall':'UTF-8'}:300,400,600,700&amp;lang=en" />{/if}
  {include file="./assets.tpl"}
  <style>
    body {
      font-family: {if !empty($stripe_checkout_font_family)}{$stripe_checkout_font_family|escape:'htmlall':'UTF-8'}, {/if}Inter UI, Open Sans, Segoe UI, sans-serif;
      font-size: {if !empty($stripe_checkout_font_size)}{$stripe_checkout_font_size|escape:'htmlall':'UTF-8'}{else}15px{/if};
      margin: 0;
      padding: 0;
    }

    .thirtybees.thirtybees-stripe {
      background-color: transparent;
    }

    .thirtybees.thirtybees-stripe * {
      font-family: {if !empty($stripe_checkout_font_family)}{$stripe_checkout_font_family|escape:'htmlall':'UTF-8'}, {/if}Inter UI, Open Sans, Segoe UI, sans-serif;
      font-size: {if !empty($stripe_checkout_font_size)}{$stripe_checkout_font_size|escape:'htmlall':'UTF-8'}{else}15px{/if};
      font-weight: 500;
    }

    .thirtybees.thirtybees-stripe form {
      max-width: 496px !important;
      padding: 0 15px;
    }

    .thirtybees.thirtybees-stripe form > * + * {
      margin-top: 20px;
    }

    .thirtybees.thirtybees-stripe .container {
      background-color: #fff;
      box-shadow: 0 4px 6px rgba(50, 50, 93, 0.11), 0 1px 3px rgba(0, 0, 0, 0.08);
      border-radius: 4px;
      padding: 3px;
    }

    .thirtybees.thirtybees-stripe fieldset {
      border-style: none;
      padding: 5px;
      margin-left: -5px;
      margin-right: -5px;
      background: rgba(18, 91, 152, 0.05);
      border-radius: 8px;
    }

    .thirtybees.thirtybees-stripe fieldset legend {
      float: left;
      width: 100%;
      text-align: center;
      font-size: 13px;
      color: #8898aa;
      padding: 3px 10px 7px;
    }

    .thirtybees.thirtybees-stripe .card-only {
      display: block;
    }

    .thirtybees.thirtybees-stripe .payment-request-available {
      display: none;
    }

    .thirtybees.thirtybees-stripe fieldset legend + * {
      clear: both;
    }

    .thirtybees.thirtybees-stripe input, .thirtybees.thirtybees-stripe button {
      -webkit-appearance: none;
      -moz-appearance: none;
      appearance: none;
      outline: none;
      border-style: none;
      color: #fff;
    }

    .thirtybees.thirtybees-stripe input:-webkit-autofill {
      transition: background-color 100000000s;
      -webkit-animation: 1ms void-animation-out;
    }

    .thirtybees.thirtybees-stripe #thirtybees-stripe-card {
      padding: 10px;
      margin-bottom: 2px;
    }

    .thirtybees.thirtybees-stripe input {
      -webkit-animation: 1ms void-animation-out;
    }

    .thirtybees.thirtybees-stripe input::-webkit-input-placeholder {
      color: {if !empty($stripe_input_placeholder_color)}{$stripe_input_placeholder_color|escape:'htmlall':'UTF-8'}{else}#9bacc8{/if};
    }

    .thirtybees.thirtybees-stripe input::-moz-placeholder {
      color: {if !empty($stripe_input_placeholder_color)}{$stripe_input_placeholder_color|escape:'htmlall':'UTF-8'}{else}#9bacc8{/if};
    }

    .thirtybees.thirtybees-stripe input:-ms-input-placeholder {
      color: {if !empty($stripe_input_placeholder_color)}{$stripe_input_placeholder_color|escape:'htmlall':'UTF-8'}{else}#9bacc8{/if};
    }

    .thirtybees.thirtybees-stripe button {
      display: block;
      width: 100%;
      height: 37px;
      background-color: {if !empty($stripe_button_background_color)}{$stripe_button_background_color|escape:'htmlall':'UTF-8'}{else}#d782d9{/if};
      border-radius: 2px;
      color: {if !empty($stripe_button_foreground_color)}{$stripe_button_foreground_color|escape:'htmlall':'UTF-8'}{else}#ffffff{/if};
      cursor: pointer;
    }

    .thirtybees.thirtybees-stripe button:active {
      background-color: {if !empty($stripe_highlight_color)}{$stripe_highlight_color|escape:'htmlall':'UTF-8'}{else}#b76ac4{/if};
    }

    .thirtybees.thirtybees-stripe .error svg .base {
      fill: {if !empty($stripe_error_color)}{$stripe_error_color|escape:'htmlall':'UTF-8'}{else}#e25950{/if};
    }

    .thirtybees.thirtybees-stripe .error svg .glyph {
      fill: {if !empty($stripe_error_glyph_color)}{$stripe_error_glyph_color|escape:'htmlall':'UTF-8'}{else}#f6f9fc{/if};
    }

    .thirtybees.thirtybees-stripe .error .message {
      color: {if !empty($stripe_error_color)}{$stripe_error_color|escape:'htmlall':'UTF-8'}{else}#e25950{/if};
    }
    #tb-stripe-elements {
      width: 1px;
      min-width: 100%;
      *width: 100%;
    }
  </style>
</head>
<body>
  <div id="tb-stripe-elements">
    <div class="cell thirtybees thirtybees-stripe">
      <form>
        <div id="thirtybees-paymentRequest">
          <!--Stripe paymentRequestButton Element inserted here-->
        </div>
        <fieldset>
          <legend class="card-only" data-tid="elements_thirtybees.form.pay_with_card">
            {l s='Pay with card' mod='stripe'}
          </legend>
          <legend class="payment-request-available" data-tid="elements_thirtybees.form.enter_card_manually">
            {l s='Or enter card details' mod='stripe'}
          </legend>
          <div class="container">
            <div id="thirtybees-stripe-card"></div>
            <button type="submit" data-tid="elements_thirtybees.form.donate_button">{l s='Pay' mod='stripe'}</button>
          </div>
        </fieldset>
        <div class="error" role="alert" style="display: none">
          <svg xmlns="http://www.w3.org/2000/svg" width="17" height="17" viewBox="0 0 17 17">
            <path class="base"
                  fill="#000"
                  d="M8.5,17 C3.80557963,17 0,13.1944204 0,8.5 C0,3.80557963 3.80557963,0 8.5,0 C13.1944204,0 17,3.80557963 17,8.5 C17,13.1944204 13.1944204,17 8.5,17 Z"
            ></path>
            <path class="glyph"
                  fill="#FFF"
                  d="M8.5,7.29791847 L6.12604076,4.92395924 C5.79409512,4.59201359 5.25590488,4.59201359 4.92395924,4.92395924 C4.59201359,5.25590488 4.59201359,5.79409512 4.92395924,6.12604076 L7.29791847,8.5 L4.92395924,10.8739592 C4.59201359,11.2059049 4.59201359,11.7440951 4.92395924,12.0760408 C5.25590488,12.4079864 5.79409512,12.4079864 6.12604076,12.0760408 L8.5,9.70208153 L10.8739592,12.0760408 C11.2059049,12.4079864 11.7440951,12.4079864 12.0760408,12.0760408 C12.4079864,11.7440951 12.4079864,11.2059049 12.0760408,10.8739592 L9.70208153,8.5 L12.0760408,6.12604076 C12.4079864,5.79409512 12.4079864,5.25590488 12.0760408,4.92395924 C11.7440951,4.59201359 11.2059049,4.59201359 10.8739592,4.92395924 L8.5,7.29791847 L8.5,7.29791847 Z"
            ></path>
          </svg>
          <span class="message"></span></div>
      </form>
      <div class="success" style="display: none">
        <div class="icon">
          <svg width="84px" height="84px" viewBox="0 0 84 84" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
            <circle class="border" cx="42" cy="42" r="40" stroke-linecap="round" stroke-width="4" stroke="#000" fill="none"></circle>
            <path class="checkmark" stroke-linecap="round" stroke-linejoin="round" d="M23.375 42.5488281 36.8840688 56.0578969 64.891932 28.0500338" stroke-width="4" stroke="#000" fill="none"></path>
          </svg>
        </div>
        <h3 class="title" data-tid="elements_thirtybees.success.title">{l s='Payment successful' mod='stripe'}</h3>
        <p class="message"><span data-tid="elements_thirtybees.success.message"></p>
        <a class="reset" href="#">
          <svg width="32px" height="32px" viewBox="0 0 32 32" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
            <path fill="#000000"
                  d="M15,7.05492878 C10.5000495,7.55237307 7,11.3674463 7,16 C7,20.9705627 11.0294373,25 16,25 C20.9705627,25 25,20.9705627 25,16 C25,15.3627484 24.4834055,14.8461538 23.8461538,14.8461538 C23.2089022,14.8461538 22.6923077,15.3627484 22.6923077,16 C22.6923077,19.6960595 19.6960595,22.6923077 16,22.6923077 C12.3039405,22.6923077 9.30769231,19.6960595 9.30769231,16 C9.30769231,12.3039405 12.3039405,9.30769231 16,9.30769231 L16,12.0841673 C16,12.1800431 16.0275652,12.2738974 16.0794108,12.354546 C16.2287368,12.5868311 16.5380938,12.6540826 16.7703788,12.5047565 L22.3457501,8.92058924 L22.3457501,8.92058924 C22.4060014,8.88185624 22.4572275,8.83063012 22.4959605,8.7703788 C22.6452866,8.53809377 22.5780351,8.22873685 22.3457501,8.07941076 L22.3457501,8.07941076 L16.7703788,4.49524351 C16.6897301,4.44339794 16.5958758,4.41583275 16.5,4.41583275 C16.2238576,4.41583275 16,4.63969037 16,4.91583275 L16,7 L15,7 L15,7.05492878 Z M16,32 C7.163444,32 0,24.836556 0,16 C0,7.163444 7.163444,0 16,0 C24.836556,0 32,7.163444 32,16 C32,24.836556 24.836556,32 16,32 Z"
            ></path>
          </svg>
        </a>
      </div>
    </div>
  </div>

  <script type="text/javascript">
    (function () {
      function initElements() {
        if (typeof Stripe === 'undefined') {
          setTimeout(initElements, 10);

          return;
        }

        var example = document.querySelector('.thirtybees');
        var form = example.querySelector('form');

        function updateQueryStringParameter(uri, key, value) {
          var re = new RegExp("([?&])" + key + "=.*?(&|$)", "i");
          var separator = uri.indexOf('?') !== -1 ? "&" : "?";
          if (uri.match(re)) {
            return uri.replace(re, '$1' + key + "=" + value + '$2');
          }
          else {
            return uri + separator + key + "=" + value;
          }
        }

        function sendHeight() {
          top.postMessage(JSON.stringify({
            messageOrigin: 'checkoutiframe',
            subject: 'height',
            height: document.querySelector('body').offsetHeight + 20,
          }), '*');
        }

        function sendPaymentSuccess() {
          top.postMessage(JSON.stringify({
            messageOrigin: 'checkoutiframe',
            subject: 'payment-success',
          }), '*');
        }

        function enableInputs() {
          Array.prototype.forEach.call(
            form.querySelectorAll(
              "input[type='text'], input[type='email'], input[type='tel']"
            ),
            function(input) {
              input.removeAttribute('disabled');
            }
          );
        }

        function disableInputs() {
          Array.prototype.forEach.call(
            form.querySelectorAll(
              "input[type='text'], input[type='email'], input[type='tel']"
            ),
            function(input) {
              input.setAttribute('disabled', 'true');
            }
          );
        }

        var stripe = Stripe('{$stripe_publishable_key|escape:'javascript':'UTF-8'}');
        var elements = stripe.elements({
          fonts: [
            {
              cssSrc: 'https://fonts.googleapis.com/css?family={$stripe_input_font_family|replace:' ':'+'|escape:'javascript':'UTF-8'}',
            },
          ],
          locale: 'auto'
        });
        var style = {
          base: {
            color: '{if !empty($stripe_payment_request_background_color)}{$stripe_payment_request_background_color|escape:'javascript':'UTF-8'}{else}#32325D{/if}',
            fontWeight: 500,
            fontFamily: '{if !empty($stripe_input_font_family)}{$stripe_input_font_family|escape:'javascript':'UTF-8'}, {/if}Open Sans, Segoe UI, sans-serif',
            fontSize: '{if !empty($stripe_checkout_font_size)}{$stripe_checkout_font_size|escape:'javascript':'UTF-8'}{else}15px{/if}',
            fontSmoothing: 'antialiased',

            '::placeholder': {
              color: '{if !empty($stripe_payment_request_foreground_color)}{$stripe_payment_request_foreground_color|escape:'javascript':'UTF-8'}{else}#CFD7DF{/if}'
            }
          },
          invalid: {
            color: '{if !empty($stripe_error_color)}{$stripe_error_color|escape:'javascript':'UTF-8'}{else}#e25950{/if}'
          }
        };

        // Create an instance of the card Element
        var card = elements.create('card', {
          style: style
        });

        card.on('ready', sendHeight);

        // Add an instance of the card Element into the `card-element` <div>
        card.mount('#thirtybees-stripe-card');

        {if !empty($stripe_payment_request)}
        /**
         * Payment Request Element
         */
        var paymentRequest = stripe.paymentRequest({
          country: '{$stripe_country|escape:'javascript':'UTF-8'}'.toUpperCase(),
          currency: '{$stripe_currency|escape:'javascript':'UTF-8'}'.toLowerCase(),
          total: {
            amount: {$stripe_amount|escape:'javascript':'UTF-8'},
            label: 'Total'
          }
        });

        paymentRequest.on('source', function(result) {
          console.log(result);
          if (result.source) {
            var a = document.createElement('a');
            a.href = '{$link->getModuleLink('stripe', 'validation', [], true)|escape:'javascript':'UTF-8'}';

            a.search = updateQueryStringParameter(a.search, 'stripe-token', result.source.id);
            a.search = updateQueryStringParameter(a.search, 'stripe-id_cart', {$id_cart|intval});

            result.complete('success');
            sendPaymentSuccess();
            top.location = a.href;
          } else {
            // Otherwise, un-disable inputs.
            enableInputs();
            result.complete('fail');
          }
        });

        var paymentRequestElement = elements.create('paymentRequestButton', {
          paymentRequest: paymentRequest,
          style: {
            paymentRequestButton: {
              type: 'buy',
              theme: '{if !empty($stripe_payment_request_style)}{$stripe_payment_request_style|escape:'javascript':'UTF-8'}{else}dark{/if}'
            },
            base: {
              color: '{if !empty($stripe_payment_request_background_color)}{$stripe_payment_request_background_color|escape:'javascript':'UTF-8'}{else}#32325D{/if}',
              fontWeight: 500,
              fontFamily: '{if !empty($stripe_input_font_family)}{$stripe_input_font_family|escape:'javascript':'UTF-8'}, {/if}Open Sans, Segoe UI, sans-serif',
              fontSize: '{if !empty($stripe_checkout_font_size)}{$stripe_checkout_font_size|escape:'javascript':'UTF-8'}{else}15px{/if}',
              fontSmoothing: 'antialiased',

              '::placeholder': {
                color: '{if !empty($stripe_payment_request_foreground_color)}{$stripe_payment_request_foreground_color|escape:'javascript':'UTF-8'}{else}#CFD7DF{/if}'
              }
            },
            invalid: {
              color: '{if !empty($stripe_error_color)}{$stripe_error_color|escape:'javascript':'UTF-8'}{else}#e25950{/if}'
            }
          }
        });

        paymentRequestElement.on('ready', sendHeight);

        paymentRequest.canMakePayment().then(function (result) {
          if (result) {
            document.querySelector('.thirtybees .card-only').style.display = 'none';
            document.querySelector('.thirtybees .payment-request-available').style.display = 'block';
            paymentRequestElement.mount('#thirtybees-paymentRequest');
          }
        });
        {/if}

        form.addEventListener('submit', function(e) {
          e.preventDefault();

          // Show a loading screen...
          example.className += ' submitting';

          // Disable all inputs.
          disableInputs();

          // Gather additional customer data we may have collected in our form.
          var name = form.querySelector('#thirtybees-name');
          var address1 = form.querySelector('#thirtybees-address');
          var city = form.querySelector('#thirtybees-city');
          var state = form.querySelector('#thirtybees-state');
          var zip = form.querySelector('#thirtybees-zip');
          var additionalData = {
            name: name ? name.value : undefined,
            address_line1: address1 ? address1.value : undefined,
            address_city: city ? city.value : undefined,
            address_state: state ? state.value : undefined,
            address_zip: zip ? zip.value : undefined,
          };

          // Use Stripe.js to create a source. We only need to pass in one Element
          // from the Element group in order to create a source. We can also pass
          // in the additional customer data we collected in our form.
          stripe.createSource(card, additionalData).then(function(result) {
            // Stop loading!
            example.className.replace(' submitting', '');

            if (result.source) {
              var a = document.createElement('a');
              a.href = '{$link->getModuleLink('stripe', 'validation', [], true)|escape:'javascript':'UTF-8'}';

              a.search = updateQueryStringParameter(a.search, 'stripe-token', result.source.id);
              a.search = updateQueryStringParameter(a.search, 'stripe-id_cart', {$id_cart|intval});
              sendPaymentSuccess();
              top.location = a.href;
            } else {
              // Otherwise, un-disable inputs.
              enableInputs();
            }
          });
        });
      }

      initElements();
    })();
  </script>
</body>
</html>
