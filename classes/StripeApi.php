<?php
/**
 * Copyright (C) 2019 thirty bees
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@thirtybees.com so we can send you a copy immediately.
 *
 * @author    thirty bees <modules@thirtybees.com>
 * @copyright 2019 thirty bees
 * @license   Academic Free License (AFL 3.0)
 */

namespace StripeModule;

use \Configuration;
use \Cart;
use \Context;
use \Customer;
use ThirtyBeesStripe\Stripe\Error\ApiConnection;

if (!defined('_TB_VERSION_')) {
    return;
}

/**
 * Class StripeApi
 */
class StripeApi
{
    /** @var GuzzleClient */
    private $guzzle;

    /**
     * StripeApi constructor.
     * @param null $liveMode
     * @throws \PrestaShopException
     */
    public function __construct($liveMode = null)
    {
        if (is_null($liveMode)) {
            $liveMode = Configuration::get(\Stripe::GO_LIVE);
        }

        $this->guzzle = new GuzzleClient();
        \ThirtyBeesStripe\Stripe\ApiRequestor::setHttpClient($this->guzzle);
        \ThirtyBeesStripe\Stripe\Stripe::setApiKey($liveMode
            ? Configuration::get(\Stripe::SECRET_KEY_LIVE)
            : Configuration::get(\Stripe::SECRET_KEY_TEST)
        );
    }

    /**
     * @param Cart $cart
     * @return string
     * @throws ApiConnection
     * @throws \Adapter_Exception
     * @throws \PrestaShopDatabaseException
     * @throws \PrestaShopException
     */
    public function createCheckoutSession(Cart $cart)
    {
        $context = Context::getContext();
        $total = Utils::getCartTotal($cart);
        $link = $context->link;
        $validationLink = $link->getModuleLink('stripe', 'validation', ['type' => 'checkout']);
        $sessionData = [
            'payment_method_types' => ['card'],
            'line_items' => [[
                'amount' => $total,
                'currency' => Utils::getCurrencyCode($cart),
                'quantity' => 1,
                'name' => sprintf('Purchase from %s', Configuration::get('PS_SHOP_NAME')),
            ]],
            'success_url' => $validationLink,
            'cancel_url' => $validationLink,
        ];

        if ((bool)Configuration::get(\Stripe::COLLECT_BILLING)) {
            $sessionData['billing_address_collection'] = 'required';
        }

        // manual capture
        if ((bool)Configuration::get(\Stripe::MANUAL_CAPTURE)) {
            $sessionData['payment_intent_data'] = [
                'capture_method' => 'manual'
            ];
        }

        // pre-fill customer email
        if ($cart->id_customer) {
            $customer = (int)$context->customer->id === (int)$cart->id_customer ? $context->customer : new Customer($cart->id_customer);
            $sessionData['customer_email'] = $customer->email;
        }

        $session = \ThirtyBeesStripe\Stripe\Checkout\Session::create($sessionData);
        return $session->id;
    }

    /**
     * Method tries to retrieve session by its ID
     *
     * @param string $id
     *
     * @throws ApiConnection
     * @return \ThirtyBeesStripe\Stripe\Checkout\Session
     */
    public function getCheckoutSession($id)
    {
        return \ThirtyBeesStripe\Stripe\Checkout\Session::retrieve($id);
    }

    /**
     * @param string $id
     *
     * @throws ApiConnection
     * @return \ThirtyBeesStripe\Stripe\PaymentIntent
     */
    public function getPaymentIntent($id)
    {
        return \ThirtyBeesStripe\Stripe\PaymentIntent::retrieve($id);
    }

    /**
     * Create payment intent
     * @param Cart $cart
     *
     * @return \ThirtyBeesStripe\Stripe\PaymentIntent
     *
     * @throws ApiConnection
     * @throws \Adapter_Exception
     * @throws \PrestaShopDatabaseException
     * @throws \PrestaShopException
     */
    public function createPaymentIntent(Cart $cart)
    {
        $paymentIntentData = [
            'payment_method_types' => ['card'],
            'amount' => Utils::getCartTotal($cart),
            'currency' => Utils::getCurrencyCode($cart),
        ];
        // manual capture
        if ((bool)Configuration::get(\Stripe::MANUAL_CAPTURE)) {
            $paymentIntentData['capture_method'] = 'manual';
        }
        return \ThirtyBeesStripe\Stripe\PaymentIntent::create($paymentIntentData);
    }
}
