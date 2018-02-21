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
<style>
  #stripe-payment-overlay {
    position: fixed; /* Sit on top of the page content */
    display: none; /* Hidden by default */
    width: 100%; /* Full width (cover the whole page) */
    height: 100%; /* Full height (cover the whole page) */
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: rgba(0,0,0,0.5); /* Black background with opacity */
    z-index: 100000; /* Specify a stack order in case you're using a different order for other elements */

    -webkit-box-align: center;
    -ms-flex-align: center;
    align-items: center;
    display: -webkit-box;
    display: -ms-flexbox;
    display: flex;
    -webkit-box-pack: center;
    -ms-flex-pack: center;
    justify-content: center;
  }
  @-webkit-keyframes scaleAnimation {
    0% {
      opacity: 0;
      -webkit-transform: scale(1.5);
      transform: scale(1.5);
    }
    100% {
      opacity: 1;
      -webkit-transform: scale(1);
      transform: scale(1);
    }
  }

  @keyframes scaleAnimation {
    0% {
      opacity: 0;
      -webkit-transform: scale(1.5);
      transform: scale(1.5);
    }
    100% {
      opacity: 1;
      -webkit-transform: scale(1);
      transform: scale(1);
    }
  }
  @-webkit-keyframes drawCircle {
    0% {
      stroke-dashoffset: 151px;
    }
    100% {
      stroke-dashoffset: 0;
    }
  }
  @keyframes drawCircle {
    0% {
      stroke-dashoffset: 151px;
    }
    100% {
      stroke-dashoffset: 0;
    }
  }
  @-webkit-keyframes drawCheck {
    0% {
      stroke-dashoffset: 36px;
    }
    100% {
      stroke-dashoffset: 0;
    }
  }
  @keyframes drawCheck {
    0% {
      stroke-dashoffset: 36px;
    }
    100% {
      stroke-dashoffset: 0;
    }
  }
  @-webkit-keyframes fadeOut {
    0% {
      opacity: 1;
    }
    100% {
      opacity: 0;
    }
  }
  @keyframes fadeOut {
    0% {
      opacity: 1;
    }
    100% {
      opacity: 0;
    }
  }
  @-webkit-keyframes fadeIn {
    0% {
      opacity: 0;
    }
    100% {
      opacity: 1;
    }
  }
  @keyframes fadeIn {
    0% {
      opacity: 0;
    }
    100% {
      opacity: 1;
    }
  }
  #successAnimationCircle {
    stroke-dasharray: 151px 151px;
    stroke: #fff;
  }

  #successAnimationCheck {
    stroke-dasharray: 36px 36px;
    stroke: #fff;
  }

  #successAnimationResult {
    fill: #fff;
    opacity: 0;
  }

  #successAnimation.animated {
    -webkit-animation: 1s ease-out 0s 1 both scaleAnimation;
    animation: 1s ease-out 0s 1 both scaleAnimation;
  }
  #successAnimation.animated #successAnimationCircle {
    -webkit-animation: 1s cubic-bezier(0.77, 0, 0.175, 1) 0s 1 both drawCircle, 0.3s linear 0.9s 1 both fadeOut;
    animation: 1s cubic-bezier(0.77, 0, 0.175, 1) 0s 1 both drawCircle, 0.3s linear 0.9s 1 both fadeOut;
  }
  #successAnimation.animated #successAnimationCheck {
    -webkit-animation: 1s cubic-bezier(0.77, 0, 0.175, 1) 0s 1 both drawCheck, 0.3s linear 0.9s 1 both fadeOut;
    animation: 1s cubic-bezier(0.77, 0, 0.175, 1) 0s 1 both drawCheck, 0.3s linear 0.9s 1 both fadeOut;
  }
  #successAnimation.animated #successAnimationResult, .stripe-payment-overlay-text {
    -webkit-animation: 0.3s linear 0.9s both fadeIn;
    animation: 0.3s linear 0.9s both fadeIn;
  }
</style>
<div id="stripe-payment-overlay" style="display: none">
  <div style="text-align: center">
    <svg id="successAnimation" class="animated" xmlns="http://www.w3.org/2000/svg" width="210" height="210" viewBox="0 0 70 70">
      <path id="successAnimationResult" fill="#D8D8D8"
            d="M35,60 C21.1928813,60 10,48.8071187 10,35 C10,21.1928813 21.1928813,10 35,10 C48.8071187,10 60,21.1928813 60,35 C60,48.8071187 48.8071187,60 35,60 Z M23.6332378,33.2260427 L22.3667622,34.7739573 L34.1433655,44.40936 L47.776114,27.6305926 L46.223886,26.3694074 L33.8566345,41.59064 L23.6332378,33.2260427 Z"/>
      <circle id="successAnimationCircle" cx="35" cy="35" r="24" stroke="#979797" stroke-width="2" stroke-linecap="round" fill="transparent"/>
      <polyline id="successAnimationCheck" stroke="#979797" stroke-width="2" points="23 34 34 43 47 27" fill="transparent"/>
    </svg>
    <p style="font-size: 3em; color: white" class="stripe-payment-overlay-text">{l s='Card verified' mod='stripe'}</p>
    <p style="font-size: 3em; color: white" class="stripe-payment-overlay-text">{l s='Redirecting...' mod='stripe'}</p>
  </div>
</div>
<iframe id="stripe-checkout-iframe"
        src="{$link->getModuleLink('stripe', 'checkoutiframe', [], true)|escape:'htmlall'}"
        width="100%"
        frameborder="0"
></iframe>
<script type="text/javascript">
  (function () {
    window.addEventListener('message', function (event) {
      if (!event.data) {
        return;
      }

      try {
        var data = JSON.parse(event.data);
      } catch (e) {
        return;
      }

      if (data && data.messageOrigin === 'checkoutiframe') {
        if (data.subject === 'height') {
          document.getElementById('stripe-checkout-iframe').height = parseInt(data.height, 10);
        } else if (data.subject === 'payment-success') {
          document.querySelector('body').style.overflow = 'hidden';
          document.getElementById('stripe-payment-overlay').style.display = 'block';
        }
      }
    });
  }());
</script>
