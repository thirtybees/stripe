<?php
/**
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
 * @author    thirty bees <modules@thirtybees.com>
 * @copyright 2017-2018 thirty bees
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

if (!defined('_TB_VERSION_')) {
    exit;
}

use Stripe\Exception\ApiConnectionException;
use Stripe\Exception\ApiErrorException;
use StripeModule\Utils;

/**
 * Class StripeEupaymentModuleFrontController
 */
class StripeEupaymentModuleFrontController extends ModuleFrontController
{
    /** @var Stripe $module */
    public $module;

    /**
     * StripeEupaymentModuleFrontController constructor.
     *
     * @throws PrestaShopException
     */
    public function __construct()
    {
        parent::__construct();

        $this->ssl = Tools::usingSecureMode();
    }

    /**
     * @throws ApiConnectionException
     * @throws ApiErrorException
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function initContent()
    {
        if (!Module::isEnabled('stripe')) {
            Tools::redirect($this->getCheckoutUrl());
        }
        $cart = $this->context->cart;
        if (!$cart->id_customer || !$cart->id_address_delivery || !$cart->id_address_invoice || !$this->module->active) {
            Tools::redirect($this->getCheckoutUrl());
        }

        $customer = new Customer($cart->id_customer);
        if (!Validate::isLoadedObject($customer)) {
            Tools::redirect($this->getCheckoutUrl());
        }

        parent::initContent();

        switch (Tools::getValue('method')) {
            case 'credit_card':
                $this->initCreditCard();
                break;
            case 'stripe_checkout':
                $this->initStripeCheckout();
                break;
            case 'ideal':
                $this->initIdeal();
                break;
            case 'bancontact':
                $this->initBancontact();
                break;
            case 'giropay':
                $this->initGiropay();
                break;
            case 'sofort':
                $this->initSofort();
                break;
            case 'p24':
                $this->initP24();
                break;
            case 'alipay':
                $this->initAlipay();
                break;

            default:
                if (Configuration::get(Stripe::STRIPE_CC_FORM)) {
                    $this->initCreditCard();
                } else {
                    $this->initStripeCheckout();
                }
                break;
        }
    }

    /**
     * Init stripe checkout flow payment
     *
     * @throws PrestaShopException
     * @throws ApiErrorException
     */
    protected function initStripeCheckout()
    {
        $this->context->controller->addJS('https://js.stripe.com/v3/');
        $this->context->smarty->assign([
            'stripe_publishable_key'   => Configuration::get(Stripe::GO_LIVE) ? Configuration::get(Stripe::PUBLISHABLE_KEY_LIVE) : Configuration::get(Stripe::PUBLISHABLE_KEY_TEST),
            'stripe_session_id'        => $this->module->getCheckoutSession(),
            'autoplay'                 => true,
        ]);
        $this->setTemplate('eupayment.tpl');
    }

    /**
     * Init credit card payment
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws ApiConnectionException
     * @throws ApiErrorException
     */
    protected function initCreditCard()
    {
        $this->context->controller->addJS('https://js.stripe.com/v3/');

        $cart = $this->context->cart;
        $stripeAmount = Utils::getCartTotal($cart);
        $invoiceAddress = new Address((int) $cart->id_address_invoice);
        $this->context->smarty->assign([
            'stripe_client_secret'                    => $this->module->getPaymentIntentSecret(),
            'stripe_name'                             => $invoiceAddress->firstname.' '.$invoiceAddress->lastname,
            'stripe_currency'                         => Utils::getCurrencyCode($cart),
            'stripe_country'                          => Utils::getStripeCountry(),
            'stripe_amount'                           => $stripeAmount,
            'id_cart'                                 => (int) $cart->id,
            'stripe_publishable_key'                  => Configuration::get(Stripe::GO_LIVE) ? Configuration::get(Stripe::PUBLISHABLE_KEY_LIVE) : Configuration::get(Stripe::PUBLISHABLE_KEY_TEST),
            'stripe_locale'                           => Stripe::getStripeLanguage($this->context->language->language_code),
            'stripe_shopname'                         => $this->context->shop->name,
            'showPaymentLogos'                        => Configuration::get(Stripe::SHOW_PAYMENT_LOGOS),
            'stripeShopThumb'                         => str_replace('http://', 'https://', $this->context->link->getMediaLink(__PS_BASE_URI__.'modules/stripe/views/img/shop'.$this->module->getShopId().'.jpg')),
            'stripe_apple_pay'                        => Configuration::get(Stripe::STRIPE_PAYMENT_REQUEST),
            'module_dir'                              => __PS_BASE_URI__.'modules/stripe/',
            'stripe_payment_request'                  => Configuration::get(Stripe::STRIPE_PAYMENT_REQUEST),
            'stripe_input_placeholder_color'          => Configuration::get(Stripe::INPUT_PLACEHOLDER_COLOR),
            'stripe_button_background_color'          => Configuration::get(Stripe::BUTTON_BACKGROUND_COLOR),
            'stripe_button_foreground_color'          => Configuration::get(Stripe::BUTTON_FOREGROUND_COLOR),
            'stripe_highlight_color'                  => Configuration::get(Stripe::HIGHLIGHT_COLOR),
            'stripe_error_color'                      => Configuration::get(Stripe::ERROR_COLOR),
            'stripe_error_glyph_color'                => Configuration::get(Stripe::ERROR_GLYPH_COLOR),
            'stripe_payment_request_foreground_color' => Configuration::get(Stripe::INPUT_TEXT_FOREGROUND_COLOR),
            'stripe_payment_request_background_color' => Configuration::get(Stripe::INPUT_TEXT_BACKGROUND_COLOR),
            'stripe_input_font_family'                => Configuration::get(Stripe::INPUT_FONT_FAMILY),
            'stripe_checkout_font_family'             => Configuration::get(Stripe::CHECKOUT_FONT_FAMILY),
            'stripe_checkout_font_size'               => Configuration::get(Stripe::CHECKOUT_FONT_SIZE),
            'stripe_payment_request_style'            => Configuration::get(Stripe::PAYMENT_REQUEST_BUTTON_STYLE),
        ]);
        $this->setTemplate('eucc.tpl');
    }

    /**
     * Initialize iDEAL payment
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws ApiErrorException
     */
    protected function initIdeal()
    {
        $cart = $this->context->cart;
        $currency = new Currency($cart->id_currency);

        $stripeAmount = $cart->getOrderTotal();
        if (!in_array(mb_strtolower($currency->iso_code), Stripe::$zeroDecimalCurrencies)) {
            $stripeAmount = (int) ($stripeAmount * 100);
        }

        $invoiceAddress = new Address((int) $cart->id_address_invoice);

        $guzzle = new \StripeModule\GuzzleClient();
        \Stripe\ApiRequestor::setHttpClient($guzzle);
        Stripe\Stripe::setApiKey(Configuration::get(Stripe::GO_LIVE)
            ? Configuration::get(Stripe::SECRET_KEY_LIVE)
            : Configuration::get(Stripe::SECRET_KEY_TEST)
        );

        $source = \Stripe\Source::create([
            'type' => 'ideal',
            'amount' => (int)$stripeAmount,
            'currency' => $currency->iso_code,
            'owner' => [
                'name' => $invoiceAddress->firstname.' '.$invoiceAddress->lastname,
            ],
            'redirect' => [
                'return_url' => $this->context->link->getModuleLink(
                    'stripe',
                    'sourcevalidation',
                    ['stripe-id_cart' => $cart->id, 'type' => 'ideal'],
                    true
                ),
            ]
        ]);

        $this->performRedirect($source);
    }

    /**
     * Initialize Bancontact payment
     *
     * @throws ApiErrorException
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    protected function initBancontact()
    {
        $cart = $this->context->cart;
        $currency = new Currency($cart->id_currency);

        $stripeAmount = $cart->getOrderTotal();
        if (!in_array(mb_strtolower($currency->iso_code), Stripe::$zeroDecimalCurrencies)) {
            $stripeAmount = (int) ($stripeAmount * 100);
        }

        $invoiceAddress = new Address((int) $cart->id_address_invoice);

        $guzzle = new \StripeModule\GuzzleClient();
        \Stripe\ApiRequestor::setHttpClient($guzzle);
        Stripe\Stripe::setApiKey(Configuration::get(Stripe::GO_LIVE)
            ? Configuration::get(Stripe::SECRET_KEY_LIVE)
            : Configuration::get(Stripe::SECRET_KEY_TEST)
        );

        $source = \Stripe\Source::create([
            'type' => 'bancontact',
            'amount' => (int)$stripeAmount,
            'currency' => $currency->iso_code,
            'owner' => [
                'name' => $invoiceAddress->firstname.' '.$invoiceAddress->lastname,
            ],
            'redirect' => [
                'return_url' => $this->context->link->getModuleLink('stripe', 'sourcevalidation', ['stripe-id_cart' => $cart->id, 'type' => 'bancontact'], true),
            ]
        ]);

        $this->performRedirect($source);
    }

    /**
     * Initialize Giropay payment
     *
     * @throws ApiErrorException
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    protected function initGiropay()
    {
        $cart = $this->context->cart;
        $currency = new Currency($cart->id_currency);

        $stripeAmount = $cart->getOrderTotal();
        if (!in_array(mb_strtolower($currency->iso_code), Stripe::$zeroDecimalCurrencies)) {
            $stripeAmount = (int) ($stripeAmount * 100);
        }

        $invoiceAddress = new Address((int) $cart->id_address_invoice);

        $guzzle = new \StripeModule\GuzzleClient();
        \Stripe\ApiRequestor::setHttpClient($guzzle);
        Stripe\Stripe::setApiKey(Configuration::get(Stripe::GO_LIVE)
            ? Configuration::get(Stripe::SECRET_KEY_LIVE)
            : Configuration::get(Stripe::SECRET_KEY_TEST)
        );

        $source = \Stripe\Source::create([
            'type' => 'giropay',
            'amount' => (int)$stripeAmount,
            'currency' => $currency->iso_code,
            'owner' => [
                'name' => $invoiceAddress->firstname.' '.$invoiceAddress->lastname,
            ],
            'redirect' => [
                'return_url' => $this->context->link->getModuleLink(
                    'stripe',
                    'sourcevalidation',
                    ['stripe-id_cart' => $cart->id, 'type' => 'giropay'],
                    true
                ),
            ]
        ]);

        $this->performRedirect($source);
    }

    /**
     * Initialize Sofort Banking payment
     *
     * @throws ApiErrorException
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    protected function initSofort()
    {
        $cart = $this->context->cart;
        $currency = new Currency($cart->id_currency);

        $stripeAmount = $cart->getOrderTotal();
        if (!in_array(mb_strtolower($currency->iso_code), Stripe::$zeroDecimalCurrencies)) {
            $stripeAmount = (int) ($stripeAmount * 100);
        }

        $invoiceAddress = new Address((int) $cart->id_address_invoice);
        $country = new Country($invoiceAddress->id_country);

        $guzzle = new \StripeModule\GuzzleClient();
        \Stripe\ApiRequestor::setHttpClient($guzzle);
        Stripe\Stripe::setApiKey(Configuration::get(Stripe::GO_LIVE)
            ? Configuration::get(Stripe::SECRET_KEY_LIVE)
            : Configuration::get(Stripe::SECRET_KEY_TEST)
        );

        $source = \Stripe\Source::create([
            'type'     => 'sofort',
            'amount'   => (int) $stripeAmount,
            'currency' => $currency->iso_code,
            'owner'    => [
                'name' => $invoiceAddress->firstname.' '.$invoiceAddress->lastname,
            ],
            'redirect' => [
                'return_url' => $this->context->link->getModuleLink(
                    'stripe',
                    'sourcevalidation',
                    ['stripe-id_cart' => $cart->id, 'type' => 'sofort'],
                    true
                ),
            ],
            'sofort'   => [
                'country' => mb_strtoupper($country->iso_code),
            ],
        ]);

        $this->performRedirect($source);
    }

    /**
     * Initialize P24 payment
     *
     * @throws ApiErrorException
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    protected function initP24()
    {
        $customer = $this->context->customer;
        $cart = $this->context->cart;
        $currency = new Currency($cart->id_currency);

        $stripeAmount = $cart->getOrderTotal();
        if (!in_array(mb_strtolower($currency->iso_code), Stripe::$zeroDecimalCurrencies)) {
            $stripeAmount = (int) ($stripeAmount * 100);
        }

        $invoiceAddress = new Address((int) $cart->id_address_invoice);

        $guzzle = new \StripeModule\GuzzleClient();
        \Stripe\ApiRequestor::setHttpClient($guzzle);
        Stripe\Stripe::setApiKey(Configuration::get(Stripe::GO_LIVE)
            ? Configuration::get(Stripe::SECRET_KEY_LIVE)
            : Configuration::get(Stripe::SECRET_KEY_TEST)
        );

        $source = \Stripe\Source::create([
            'type' => 'p24',
            'amount' => (int)$stripeAmount,
            'currency' => $currency->iso_code,
            'owner' => [
                'name'  => $invoiceAddress->firstname.' '.$invoiceAddress->lastname,
                'email' => $customer->email,
            ],
            'redirect' => [
                'return_url' => $this->context->link->getModuleLink(
                    'stripe',
                    'sourcevalidation',
                    ['stripe-id_cart' => $cart->id, 'type' => 'p24'],
                    true
                ),
            ]
        ]);

        $this->performRedirect($source);
    }

    /**
     * Initialize Alipay payment
     *
     * @throws ApiErrorException
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    protected function initAlipay()
    {
        $cart = $this->context->cart;
        $currency = new Currency($cart->id_currency);

        $stripeAmount = $cart->getOrderTotal();
        if (!in_array(mb_strtolower($currency->iso_code), Stripe::$zeroDecimalCurrencies)) {
            $stripeAmount = (int) ($stripeAmount * 100);
        }

        $guzzle = new \StripeModule\GuzzleClient();
        \Stripe\ApiRequestor::setHttpClient($guzzle);
        Stripe\Stripe::setApiKey(Configuration::get(Stripe::GO_LIVE)
            ? Configuration::get(Stripe::SECRET_KEY_LIVE)
            : Configuration::get(Stripe::SECRET_KEY_TEST)
        );

        $source = \Stripe\Source::create([
            'type'     => 'alipay',
            'amount'   => (int) $stripeAmount,
            'currency' => $currency->iso_code,
            'redirect' => [
                'return_url' => $this->context->link->getModuleLink(
                    'stripe',
                    'sourcevalidation',
                    ['stripe-id_cart' => $cart->id, 'type' => 'alipay'],
                    true
                ),
            ],
        ]);

        $this->performRedirect($source);
    }

    /**
     * @param \Stripe\Source $source
     *
     * @return void
     * @throws PrestaShopException
     */
    protected function performRedirect(\Stripe\Source $source)
    {
        $url = isset($source->redirect->url)
            ? $source->redirect->url
            : $this->getCheckoutUrl();
        Tools::redirect($url);
    }

    /**
     * @return string
     * @throws PrestaShopException
     */
    protected function getCheckoutUrl()
    {
        return Context::getContext()->link->getPageLink('order', null, null, 'step=3');
    }
}
