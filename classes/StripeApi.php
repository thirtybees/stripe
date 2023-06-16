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

use Configuration;
use Cart;
use Context;
use Customer;
use PrestaShopDatabaseException;
use PrestaShopException;
use Stripe\Exception\ApiErrorException;
use Translate;

if (!defined('_TB_VERSION_')) {
    return;
}

/**
 * Class StripeApi
 */
class StripeApi
{

    /**
     * @var GuzzleClient
     */
    private $guzzle;

    /**
     * StripeApi constructor.
     *
     * @param bool|null $liveMode
     *
     * @throws PrestaShopException
     */
    public function __construct($liveMode = null)
    {
        if (is_null($liveMode)) {
            $liveMode = Configuration::get(\Stripe::GO_LIVE);
        }
        $apiVersion = Configuration::get(\Stripe::STRIPE_API_VERSION);
        if (! $apiVersion) {
            $apiVersion = null;
        }

        $this->guzzle = new GuzzleClient();
        \Stripe\ApiRequestor::setHttpClient($this->guzzle);
        \Stripe\Stripe::setApiVersion($apiVersion);
        \Stripe\Stripe::setApiKey($liveMode
            ? Configuration::get(\Stripe::SECRET_KEY_LIVE)
            : Configuration::get(\Stripe::SECRET_KEY_TEST)
        );
    }

    /**
     * @param Cart $cart
     *
     * @return string
     *
     * @throws PrestaShopException
     * @throws ApiErrorException
     */
    public function createCheckoutSession(Cart $cart)
    {
        $context = Context::getContext();
        $total = Utils::getCartTotal($cart);
        $link = $context->link;
        $validationLink = $link->getModuleLink('stripe', 'validation', ['type' => 'checkout']);
        $sessionData = [
            'payment_method_types' => ['card'],
            'line_items' => [
                [
                    'quantity' => 1,
                    'price_data' => [
                        'currency' => Utils::getCurrencyCode($cart),
                        'unit_amount' => $total,
                        'product_data' => [
                            'name' => sprintf($this->l('Purchase from %s'), Configuration::get('PS_SHOP_NAME')),
                        ],
                    ]
                ]
            ],
            'mode' => 'payment',
            'success_url' => $validationLink,
            'cancel_url' => $validationLink,
        ];

        if (Configuration::get(\Stripe::COLLECT_BILLING)) {
            $sessionData['billing_address_collection'] = 'required';
        }

        // manual capture
        if (Configuration::get(\Stripe::MANUAL_CAPTURE)) {
            $sessionData['payment_intent_data'] = [
                'capture_method' => 'manual'
            ];
        }

        // pre-fill customer email
        if ($cart->id_customer) {
            $customer = (int)$context->customer->id === (int)$cart->id_customer ? $context->customer : new Customer($cart->id_customer);
            $sessionData['customer_email'] = $customer->email;
        }

        $session = \Stripe\Checkout\Session::create($sessionData);
        return $session->id;
    }

    /**
     * Method tries to retrieve session by its ID
     *
     * @param string $id
     *
     * @throws ApiErrorException
     * @return \Stripe\Checkout\Session
     */
    public function getCheckoutSession($id)
    {
        return \Stripe\Checkout\Session::retrieve($id);
    }

    /**
     * @param string $id
     *
     * @return \Stripe\PaymentIntent
     *
     * @throws ApiErrorException
     */
    public function getPaymentIntent($id)
    {
        return \Stripe\PaymentIntent::retrieve($id);
    }

    /**
     * @param array|string $chargeId
     *
     * @return \Stripe\Charge
     *
     * @throws ApiErrorException
     */
    public function getCharge($chargeId)
    {
        return \Stripe\Charge::retrieve($chargeId);
    }

    /**
     * Create payment intent
     * @param Cart $cart
     *
     * @return \Stripe\PaymentIntent
     *
     * @throws ApiErrorException
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function createPaymentIntent(Cart $cart)
    {
        $paymentIntentData = [
            'payment_method_types' => ['card'],
            'amount' => Utils::getCartTotal($cart),
            'currency' => Utils::getCurrencyCode($cart),
        ];
        // manual capture
        if (Configuration::get(\Stripe::MANUAL_CAPTURE)) {
            $paymentIntentData['capture_method'] = 'manual';
        }
        return \Stripe\PaymentIntent::create($paymentIntentData);
    }

    /**
     * @param string $string
     *
     * @return string
     */
    public function l($string)
    {
        return Translate::getModuleTranslation('stripe', $string, 'StripeApi');
    }
}
