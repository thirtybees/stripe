/**
 * 2017-2018 DM Productions B.V.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.md
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to info@dmp.nl so we can send you a copy immediately.
 *
 * @author     Michael Dekker <info@mijnpresta.nl>
 * @copyright  2010-2018 DM Productions B.V.
 * @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */
(function () {
  function ready(fn) {
    if (document.readyState !== 'loading') {
      fn();
    } else if (document.addEventListener) {
      window.addEventListener('DOMContentLoaded', fn);
    } else {
      document.attachEvent('onreadystatechange', function () {
        if (document.readyState !== 'loading')
          fn();
      });
    }
  }

  var currentDesign = {
    stripe_input_placeholder_color: window.stripe_input_placeholder_color,
    stripe_button_background_color: window.stripe_button_background_color,
    stripe_button_foreground_color: window.stripe_button_foreground_color,
    stripe_highlight_color: window.stripe_highlight_color,
    stripe_error_color: window.stripe_error_color,
    stripe_error_glyph_color: window.stripe_error_glyph_color,
    stripe_payment_request_foreground_color:  window.stripe_payment_request_foreground_color,
    stripe_payment_request_background_color:  window.stripe_payment_request_background_color,
    stripe_input_font_family: window.stripe_input_font_family,
    stripe_checkout_font_family: window.stripe_checkout_font_family,
    stripe_checkout_font_size: window.stripe_checkout_font_size,
    stripe_payment_request_style: window.stripe_payment_request_style,
  };

  function handleDemoIframe() {
    var newDesign = {
      stripe_input_placeholder_color: document.querySelector('input[name="STRIPE_INPUT_PLACEHOLDER_COLOR"]').value,
      stripe_button_background_color: document.querySelector('input[name="STRIPE_BUTTON_BACKGROUND_COLOR"]').value,
      stripe_button_foreground_color: document.querySelector('input[name="STRIPE_BUTTON_FOREGROUND_COLOR"]').value,
      stripe_highlight_color: document.querySelector('input[name="STRIPE_HIGHLIGHT_COLOR"]').value,
      stripe_error_color: document.querySelector('input[name="STRIPE_ERROR_COLOR"]').value,
      stripe_error_glyph_color: document.querySelector('input[name="STRIPE_ERROR_GLYPH_COLOR"]').value,
      stripe_payment_request_foreground_color: document.querySelector('input[name="STRIPE_PAYMENT_REQFGC"]').value,
      stripe_payment_request_background_color: document.querySelector('input[name="STRIPE_PAYMENT_REQBGC"]').value,
      stripe_input_font_family: document.getElementById('STRIPE_INPUT_FONT_FAMILY').value,
      stripe_checkout_font_family: document.getElementById('STRIPE_CHECKOUT_FONT_FAMILY').value,
      stripe_checkout_font_size: document.querySelector('input[name="STRIPE_CHECKOUT_FONT_SIZE"]').value,
      stripe_payment_request_style: document.getElementById('STRIPE_PRB_STYLE').value,
    };

    if (JSON.stringify(currentDesign) !== JSON.stringify(newDesign)) {
      currentDesign = newDesign;

      var request = new XMLHttpRequest();
      request.open('POST', window.stripe_color_url, true);

      request.onreadystatechange = function () {
        if (this.readyState === 4) {
          document.getElementById('stripe-demo-iframe').contentWindow.location.reload();
        }
      };

      request.setRequestHeader('Content-Type', 'application/json; charset=UTF-8');
      request.send(JSON.stringify(newDesign));
      request = null;
    }
  }

  ready(function () {
    setInterval(handleDemoIframe, 500);
  });
}());
