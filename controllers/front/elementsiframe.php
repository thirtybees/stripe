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

/**
 * Class StripeElementsIframeModuleFrontController
 *
 * @since 1.5.0
 */
class StripeElementsIframeModuleFrontController extends ModuleFrontController
{
    /** @var Stripe $module */
    public $module;

    /**
     * @return bool|void
     *
     * @throws PrestaShopException
     *
     * @since 1.5.0
     */
    public function initContent()
    {
        if (!$this->module->active ||
            (!Configuration::get(Stripe::SECRET_KEY_TEST) && !Configuration::get(Stripe::PUBLISHABLE_KEY_TEST))
            && (!Configuration::get(Stripe::SECRET_KEY_LIVE) && !Configuration::get(Stripe::PUBLISHABLE_KEY_LIVE))
        ) {
            exit;
        }

        $context = Context::getContext();
        $cookie = $context->cookie;

        $stripeEmail = $cookie->email;

        /** @var Cart $cart */
        $cart = $context->cart;
        $currency = new Currency($cart->id_currency);
        $stripeCurrency = strtolower($currency->iso_code);

        $link = $this->context->link;

        $stripeAmount = $cart->getOrderTotal();
        if (!in_array(mb_strtolower($currency->iso_code), Stripe::$zeroDecimalCurrencies)) {
            $stripeAmount = (int) ($stripeAmount * 100);
        }

        $invoiceAddress = new Address((int) $cart->id_address_invoice);
        $country = new Country($invoiceAddress->id_country);
        $customer = new Customer($cart->id_customer);

        $autoplay = true;
        $this->context->smarty->assign(
            [
                'stripe_name'                   => $invoiceAddress->firstname.' '.$invoiceAddress->lastname,
                'stripe_email'                  => $stripeEmail,
                'stripe_currency'               => $stripeCurrency,
                'stripe_country'                => mb_strtoupper($country->iso_code),
                'stripe_amount'                 => $stripeAmount,
                'stripe_amount_string'          => (string) $cart->getOrderTotal(),
                'stripe_amount_formatted'       => Tools::displayPrice($cart->getOrderTotal(), Currency::getCurrencyInstance($cart->id_currency)),
                'id_cart'                       => (int) $cart->id,
                'stripe_secret_key'             => Configuration::get(Stripe::GO_LIVE) ? Configuration::get(Stripe::SECRET_KEY_LIVE) : Configuration::get(Stripe::SECRET_KEY_TEST),
                'stripe_publishable_key'        => Configuration::get(Stripe::GO_LIVE) ? Configuration::get(Stripe::PUBLISHABLE_KEY_LIVE) : Configuration::get(Stripe::PUBLISHABLE_KEY_TEST),
                'stripe_locale'                 => Stripe::getStripeLanguage($this->context->language->language_code),
                'stripe_zipcode'                => (bool) Configuration::get(Stripe::ZIPCODE),
                'stripecc_zipcode'              => (bool) Configuration::get(Stripe::ZIPCODE),
                'stripe_checkout'               => Configuration::get(Stripe::STRIPE_CHECKOUT),
                'stripe_cc_form'                => Configuration::get(Stripe::STRIPE_CC_FORM),
                'stripe_ideal'                  => Configuration::get(Stripe::IDEAL),
                'stripe_apple_pay'              => Configuration::get(Stripe::STRIPE_PAYMENT_REQUEST),
                'stripe_bancontact'             => Configuration::get(Stripe::BANCONTACT),
                'stripe_giropay'                => Configuration::get(Stripe::GIROPAY),
                'stripe_sofort'                 => Configuration::get(Stripe::SOFORT),
                'stripe_alipay_block'           => (bool) Configuration::get(Stripe::ALIPAY_BLOCK),
                'stripe_shopname'               => $this->context->shop->name,
                'stripe_ajax_validation'        => $link->getModuleLink($this->name, 'ajaxvalidation', [], Tools::usingSecureMode()),
                'stripe_confirmation_page'      => $link->getModuleLink($this->name, 'validation', [], Tools::usingSecureMode()),
                'stripe_ajax_confirmation_page' => $link->getPageLink(
                    'order-confirmation',
                    Tools::usingSecureMode(),
                    $this->context->language->id,
                    [
                        'id_cart'   => (int) $cart->id,
                        'id_module' => (int) $this->id,
                        'key'       => $customer->secure_key,
                    ]
                ),
                'showPaymentLogos'              => Configuration::get(Stripe::SHOW_PAYMENT_LOGOS),
                'stripeShopThumb'               => str_replace('http://', 'https://', $this->context->link->getMediaLink(__PS_BASE_URI__.'modules/stripe/views/img/shop'.$this->module->getShopId().'.jpg')),
                'stripe_collect_billing'        => Configuration::get(Stripe::COLLECT_BILLING),
                'stripe_collect_shipping'       => Configuration::get(Stripe::COLLECT_SHIPPING),
                'autoplay'                      => $autoplay,
                'three_d_secure'                => Configuration::get(Stripe::THREEDSECURE),
                'local_module_dir'              => _PS_MODULE_DIR_,
                'module_dir'                    => __PS_BASE_URI__.'modules/stripe/',
            ]
        );

        echo $this->context->smarty->fetch(_PS_MODULE_DIR_.'stripe/views/templates/front/elementsiframe.tpl');
        exit;
    }
}
