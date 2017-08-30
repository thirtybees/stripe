<?php
/**
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
 * @author    thirty bees <modules@thirtybees.com>
 * @copyright 2017 thirty bees
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

if (!defined('_TB_VERSION_')) {
    exit;
}

/**
 * Class StripeEupaymentModuleFrontController
 */
class StripeEupaymentModuleFrontController extends ModuleFrontController
{
    /** @var Stripe $module */
    public $module;

    /**
     * StripeEupaymentModuleFrontController constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->ssl = Tools::usingSecureMode();
    }

    /**
     * @throws PrestaShopException
     */
    public function initContent()
    {
        if (!Module::isEnabled('stripe') || (!Configuration::get(Stripe::STRIPE_CHECKOUT) && !Configuration::get(Stripe::STRIPE_CC_FORM))) {
            Tools::redirect('index.php?controller=order&step=1');
        }
        $cart = $this->context->cart;
        if (!$cart->id_customer || !$cart->id_address_delivery || !$cart->id_address_invoice || !$this->module->active) {
            Tools::redirect('index.php?controller=order&step=1');
        }

        $customer = new Customer($cart->id_customer);
        if (!Validate::isLoadedObject($customer)) {
            Tools::redirect('index.php?controller=order&step=1');
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
     * Init credit card payment
     */
    protected function initStripeCheckout()
    {
        $this->context->controller->addJS('https://checkout.stripe.com/checkout.js');

        /** @var Cookie $email */
        $cookie = $this->context->cookie;
        $stripeEmail = $cookie->email;

        /** @var Cart $cart */
        $cart = $this->context->cart;
        $currency = new Currency($cart->id_currency);

        $link = $this->context->link;

        $stripeAmount = $cart->getOrderTotal();
        if (!in_array(Tools::strtolower($currency->iso_code), Stripe::$zeroDecimalCurrencies)) {
            $stripeAmount = (int) ($stripeAmount * 100);
        }

        $this->module->checkShopThumb();

        $this->context->smarty->assign(
            [
                'stripe_email'             => $stripeEmail,
                'stripe_currency'          => $currency->iso_code,
                'stripe_amount'            => $stripeAmount,
                'stripe_confirmation_page' => $link->getModuleLink('stripe', 'validation'),
                'id_cart'                  => (int) $cart->id,
                'stripe_secret_key'        => Configuration::get(Stripe::GO_LIVE) ? Configuration::get(Stripe::SECRET_KEY_LIVE) : Configuration::get(Stripe::SECRET_KEY_TEST),
                'stripe_publishable_key'   => Configuration::get(Stripe::GO_LIVE) ? Configuration::get(Stripe::PUBLISHABLE_KEY_LIVE) : Configuration::get(Stripe::PUBLISHABLE_KEY_TEST),
                'stripe_locale'            => Stripe::getStripeLanguage($this->context->language->language_code),
                'stripe_zipcode'           => (bool) Configuration::get(Stripe::ZIPCODE),
                'stripe_bitcoin'           => (bool) Configuration::get(Stripe::BITCOIN) && Tools::strtolower($currency->iso_code) === 'usd',
                'stripe_alipay'            => (bool) Configuration::get(Stripe::ALIPAY),
                'stripe_shopname'          => $this->context->shop->name,
                'stripe_collect_billing'   => Configuration::get(Stripe::COLLECT_BILLING),
                'stripe_collect_shipping'  => Configuration::get(Stripe::COLLECT_SHIPPING),
                'autoplay'                 => true,
                'stripeShopThumb'          => $this->context->link->getMediaLink('/modules/stripe/views/img/shop'.$this->context->shop->id.'.jpg'),
                'module_dir'               => __PS_BASE_URI__.'modules/stripe/',
            ]
        );

        $this->setTemplate('eupayment.tpl');
    }

    /**
     * Init credit card payment
     */
    protected function initCreditCard()
    {
        $this->context->controller->addJS('https://checkout.stripe.com/checkout.js');

        /** @var Cookie $email */
        $cookie = $this->context->cookie;
        $stripeEmail = $cookie->email;

        /** @var Cart $cart */
        $cart = $this->context->cart;
        $currency = new Currency($cart->id_currency);

        $link = $this->context->link;

        $stripeAmount = $cart->getOrderTotal();
        if (!in_array(Tools::strtolower($currency->iso_code), Stripe::$zeroDecimalCurrencies)) {
            $stripeAmount = (int) ($stripeAmount * 100);
        }

        $invoiceAddress = new Address((int) $cart->id_address_invoice);
        $country = new Country($invoiceAddress->id_country);
        $customer = new Customer($cart->id_customer);

        $this->module->checkShopThumb();

        $this->context->smarty->assign(
            [
                'stripe_name'                   => $invoiceAddress->firstname.' '.$invoiceAddress->lastname,
                'stripe_email'                  => $stripeEmail,
                'stripe_currency'               => $currency->iso_code,
                'stripe_country'                => Tools::strtoupper($country->iso_code),
                'stripe_amount'                 => $stripeAmount,
                'stripe_amount_string'          => (string) $cart->getOrderTotal(),
                'stripe_amount_formatted'       => Tools::displayPrice($cart->getOrderTotal(), Currency::getCurrencyInstance($cart->id_currency)),
                'id_cart'                       => (int) $cart->id,
                'stripe_secret_key'             => Configuration::get(Stripe::GO_LIVE) ? Configuration::get(Stripe::SECRET_KEY_LIVE) : Configuration::get(Stripe::SECRET_KEY_TEST),
                'stripe_publishable_key'        => Configuration::get(Stripe::GO_LIVE) ? Configuration::get(Stripe::PUBLISHABLE_KEY_LIVE) : Configuration::get(Stripe::PUBLISHABLE_KEY_TEST),
                'stripe_locale'                 => Stripe::getStripeLanguage($this->context->language->language_code),
                'stripe_zipcode'                => (bool) Configuration::get(Stripe::ZIPCODE),
                'stripecc_zipcode'              => (bool) Configuration::get(Stripe::ZIPCODE),
                'stripe_bitcoin'                => (bool) Configuration::get(Stripe::BITCOIN) && Tools::strtolower($currency->iso_code) === 'usd',
                'stripe_alipay'                 => (bool) Configuration::get(Stripe::ALIPAY),
                'ideal'                         => Configuration::get(Stripe::IDEAL),
                'stripe_shopname'               => $this->context->shop->name,
                'stripe_ajax_validation'        => $link->getModuleLink($this->module->name, 'ajaxvalidation', [], Tools::usingSecureMode()),
                'stripe_confirmation_page'      => $link->getModuleLink($this->module->name, 'validation', [], Tools::usingSecureMode()),
                'stripe_ajax_confirmation_page' => $link->getPageLink('order-confirmation', Tools::usingSecureMode(), '&id_cart='.$cart->id.'&id_module='.$this->module->id.'&key='.$customer->secure_key),
                'showPaymentLogos'              => Configuration::get(Stripe::SHOW_PAYMENT_LOGOS),
                'stripeShopThumb'               => str_replace('http://', 'https://', $this->context->link->getMediaLink(__PS_BASE_URI__.'modules/stripe/views/img/shop'.$this->module->getShopId().'.jpg')),
                'stripe_collect_billing'        => Configuration::get(Stripe::COLLECT_BILLING),
                'stripe_collect_shipping'       => Configuration::get(Stripe::COLLECT_SHIPPING),
                'stripe_apple_pay'              => Configuration::get(Stripe::STRIPE_APPLE_PAY),
                'stripe_checkout'               => Configuration::get(Stripe::STRIPE_CHECKOUT),
                'stripe_cc_form'                => Configuration::get(Stripe::STRIPE_CC_FORM),
                'stripe_cc_animation'           => Configuration::get(Stripe::STRIPE_CC_ANIMATION),
                'three_d_secure'                => Configuration::get(Stripe::THREEDSECURE),
                'module_dir'                    => __PS_BASE_URI__.'modules/stripe/',
            ]
        );

        $this->setTemplate('eucc.tpl');
    }

    /**
     * Initialize iDEAL payment
     */
    protected function initIdeal()
    {
        /** @var Cart $cart */
        $cart = $this->context->cart;
        $currency = new Currency($cart->id_currency);

        $stripeAmount = $cart->getOrderTotal();
        if (!in_array(Tools::strtolower($currency->iso_code), Stripe::$zeroDecimalCurrencies)) {
            $stripeAmount = (int) ($stripeAmount * 100);
        }

        $invoiceAddress = new Address((int) $cart->id_address_invoice);

        ThirtyBeesStripe\Stripe::setApiKey(Configuration::get(Stripe::GO_LIVE) ? Configuration::get(Stripe::SECRET_KEY_LIVE) : Configuration::get(Stripe::SECRET_KEY_TEST));

        $source = \ThirtyBeesStripe\Source::create([
            'type' => 'ideal',
            'amount' => (int)$stripeAmount,
            'currency' => $currency->iso_code,
            'owner' => [
                'name' => $invoiceAddress->firstname.' '.$invoiceAddress->lastname,
            ],
            'redirect' => [
                'return_url' => $this->context->link->getModuleLink('stripe', 'sourcevalidation', ['stripe-id_cart' => $cart->id, 'type' => 'ideal'], true),
            ]
        ]);

        Tools::redirect($source->redirect->url);
    }

    /**
     * Initialize Bancontact payment
     */
    protected function initBancontact()
    {
        /** @var Cart $cart */
        $cart = $this->context->cart;
        $currency = new Currency($cart->id_currency);

        $stripeAmount = $cart->getOrderTotal();
        if (!in_array(Tools::strtolower($currency->iso_code), Stripe::$zeroDecimalCurrencies)) {
            $stripeAmount = (int) ($stripeAmount * 100);
        }

        $invoiceAddress = new Address((int) $cart->id_address_invoice);

        ThirtyBeesStripe\Stripe::setApiKey(Configuration::get(Stripe::GO_LIVE) ? Configuration::get(Stripe::SECRET_KEY_LIVE) : Configuration::get(Stripe::SECRET_KEY_TEST));

        $source = \ThirtyBeesStripe\Source::create([
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

        Tools::redirect($source->redirect->url);
    }

    /**
     * Initialize Giropay payment
     */
    protected function initGiropay()
    {
        /** @var Cart $cart */
        $cart = $this->context->cart;
        $currency = new Currency($cart->id_currency);

        $stripeAmount = $cart->getOrderTotal();
        if (!in_array(Tools::strtolower($currency->iso_code), Stripe::$zeroDecimalCurrencies)) {
            $stripeAmount = (int) ($stripeAmount * 100);
        }

        $invoiceAddress = new Address((int) $cart->id_address_invoice);

        ThirtyBeesStripe\Stripe::setApiKey(Configuration::get(Stripe::GO_LIVE) ? Configuration::get(Stripe::SECRET_KEY_LIVE) : Configuration::get(Stripe::SECRET_KEY_TEST));

        $source = \ThirtyBeesStripe\Source::create([
            'type' => 'giropay',
            'amount' => (int)$stripeAmount,
            'currency' => $currency->iso_code,
            'owner' => [
                'name' => $invoiceAddress->firstname.' '.$invoiceAddress->lastname,
            ],
            'redirect' => [
                'return_url' => $this->context->link->getModuleLink('stripe', 'sourcevalidation', ['stripe-id_cart' => $cart->id, 'type' => 'giropay'], true),
            ]
        ]);

        Tools::redirect($source->redirect->url);
    }

    /**
     * Initialize Sofort Banking payment
     */
    protected function initSofort()
    {
        /** @var Cart $cart */
        $cart = $this->context->cart;
        $currency = new Currency($cart->id_currency);

        $stripeAmount = $cart->getOrderTotal();
        if (!in_array(Tools::strtolower($currency->iso_code), Stripe::$zeroDecimalCurrencies)) {
            $stripeAmount = (int) ($stripeAmount * 100);
        }

        $invoiceAddress = new Address((int) $cart->id_address_invoice);
        $country = new Country($invoiceAddress->id_country);

        ThirtyBeesStripe\Stripe::setApiKey(Configuration::get(Stripe::GO_LIVE) ? Configuration::get(Stripe::SECRET_KEY_LIVE) : Configuration::get(Stripe::SECRET_KEY_TEST));

        $source = \ThirtyBeesStripe\Source::create([
            'type' => 'sofort',
            'amount' => (int)$stripeAmount,
            'currency' => $currency->iso_code,
            'owner' => [
                'name' => $invoiceAddress->firstname.' '.$invoiceAddress->lastname,
            ],
            'redirect' => [
                'return_url' => $this->context->link->getModuleLink('stripe', 'sourcevalidation', ['stripe-id_cart' => $cart->id, 'type' => 'sofort'], true),
            ],
            'sofort' => [
                'country' => Tools::strtoupper($country->iso_code),
            ]
        ]);

        Tools::redirect($source->redirect->url);
    }

    /**
     * Initialize Alipay payment
     */
    protected function initAlipay()
    {
        /** @var Cart $cart */
        $cart = $this->context->cart;
        $currency = new Currency($cart->id_currency);

        $stripeAmount = $cart->getOrderTotal();
        if (!in_array(Tools::strtolower($currency->iso_code), Stripe::$zeroDecimalCurrencies)) {
            $stripeAmount = (int) ($stripeAmount * 100);
        }

        ThirtyBeesStripe\Stripe::setApiKey(Configuration::get(Stripe::GO_LIVE) ? Configuration::get(Stripe::SECRET_KEY_LIVE) : Configuration::get(Stripe::SECRET_KEY_TEST));

        $source = \ThirtyBeesStripe\Source::create([
            'type' => 'alipay',
            'amount' => (int)$stripeAmount,
            'currency' => $currency->iso_code,
            'redirect' => [
                'return_url' => $this->context->link->getModuleLink('stripe', 'sourcevalidation', ['stripe-id_cart' => $cart->id, 'type' => 'alipay'], true),
            ],
        ]);

        Tools::redirect($source->redirect->url);
    }
}
