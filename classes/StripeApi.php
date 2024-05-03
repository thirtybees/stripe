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
use Order;
use PrestaShopException;
use Stripe\Charge;
use Stripe\Checkout\Session;
use Stripe\Event;
use Stripe\Exception\ApiErrorException;
use Stripe\PaymentIntent;
use Stripe\Refund;
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
     * StripeApi constructor.
     *
     * @param string $moduleVersion
     *
     * @throws PrestaShopException
     */
    public function __construct($moduleVersion)
    {
        \Stripe\Stripe::setAppInfo('thirty bees', $moduleVersion, 'https://thirtybees.com/');
        \Stripe\ApiRequestor::setHttpClient(new GuzzleClient());
        \Stripe\Stripe::setApiKey(Configuration::get(\Stripe::GO_LIVE)
            ? Configuration::get(\Stripe::SECRET_KEY_LIVE)
            : Configuration::get(\Stripe::SECRET_KEY_TEST)
        );
    }

    /**
     * @param Cart $cart
     * @param string $methodId
     * @param array $methods
     * @param array $paymentMethodOptions
     *
     * @return Session
     *
     * @throws ApiErrorException
     * @throws PrestaShopException
     */
    public function createCheckoutSession(
        Cart $cart,
        string $methodId,
        array $methods = [],
        array $paymentMethodOptions = []
    ) {
        $context = Context::getContext();
        $total = Utils::getCartTotal($cart);
        $validationLink = Utils::getValidationUrl($methodId);
        if (! $methods) {
            $methods = [
                \Stripe\PaymentMethod::TYPE_CARD
            ];
        }

        $sessionData = [
            'payment_method_types' => $methods,
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
            'payment_intent_data' => [
                'metadata' => $this->getCartMetadata($cart),
            ],
            'metadata' => $this->getCartMetadata($cart),
            'mode' => 'payment',
            'success_url' => $validationLink,
            'cancel_url' => $validationLink,
        ];

        if ($paymentMethodOptions) {
            $sessionData['payment_method_options'] = $paymentMethodOptions;
        }

        if (Configuration::get(\Stripe::COLLECT_BILLING)) {
            $sessionData['billing_address_collection'] = 'required';
        }

        // manual capture
        if (Configuration::get(\Stripe::MANUAL_CAPTURE)) {
            $sessionData['payment_intent_data']['capture_method'] = 'manual';
        }

        // pre-fill customer email
        if ($cart->id_customer) {
            $customer = (int)$context->customer->id === (int)$cart->id_customer ? $context->customer : new Customer($cart->id_customer);
            $sessionData['customer_email'] = $customer->email;
        }

        return \Stripe\Checkout\Session::create($sessionData);
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
     *
     * @param Cart $cart
     * @param string $methodType
     * @param array $methodData
     * @param string $returnUrl
     *
     * @return PaymentIntent
     *
     * @throws ApiErrorException
     * @throws PrestaShopException
     */
    public function createPaymentIntent(
        Cart $cart,
        string $methodType = \Stripe\PaymentMethod::TYPE_CARD,
        array $methodData = [],
        string $returnUrl = ""
    ) {
        $paymentIntentData = [
            'payment_method_types' => [ $methodType ],
            'amount' => Utils::getCartTotal($cart),
            'currency' => Utils::getCurrencyCode($cart),
            'metadata' => $this->getCartMetadata($cart),
        ];
        if ($returnUrl) {
            $paymentIntentData['confirm'] = true;
            $paymentIntentData['return_url'] = $returnUrl;
        }
        if ($methodData) {
            $paymentIntentData['payment_method_data'] = $methodData;
        }
        // manual capture
        if ($methodType === \Stripe\PaymentMethod::TYPE_CARD && Configuration::get(\Stripe::MANUAL_CAPTURE)) {
            $paymentIntentData['capture_method'] = 'manual';
        }
        return \Stripe\PaymentIntent::create($paymentIntentData);
    }


    /**
     * @param string $eventId
     *
     * @return Event
     * @throws ApiErrorException
     */
    public function getEvent($eventId)
    {
        return \Stripe\Event::retrieve($eventId);
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

    /**
     * @param string $idCharge
     * @param int $amount
     *
     * @return Refund
     * @throws ApiErrorException
     */
    public function createRefund(string $idCharge, int $amount)
    {
        return \Stripe\Refund::create([
            'charge' => $idCharge,
            'amount' => $amount,
            'metadata' => [
                'from_back_office' => 'true',
            ]
        ]);
    }

    /**
     * @param Charge $charge
     * @param array $data
     *
     * @return Charge
     * @throws ApiErrorException
     */
    public function updateCharge(\Stripe\Charge $charge, array $data = [])
    {
        if (! isset($data['metadata'])) {
            $data['metadata'] = [];
        }
        $data['metadata']['from_back_office'] = 'true';

        return \Stripe\Charge::update($charge->id, $data);
    }

    /**
     * @param Cart $cart
     *
     * @return array
     */
    protected function getCartMetadata(Cart $cart): array
    {
        if (\Validate::isLoadedObject($cart)) {
            return [
                'cart_id' => (string)$cart->id,
            ];
        }  else {
            return [];
        }
    }

    /**
     * @param PaymentIntent $paymentIntent
     * @param Order[] $orders
     *
     * @return void
     * @throws ApiErrorException
     */
    public function addOrderMetadata(PaymentIntent $paymentIntent, array $orders)
    {
        $orderIds = [];
        $reference = '';
        foreach ($orders as $order) {
            $orderIds[] = (int)$order->id;
            $reference = $order->reference;
        }
        if ($orderIds && $reference) {
            $data = [
                'metadata' => [
                    'order_id' => implode(',', $orderIds),
                    'order_reference' => $reference,
                ]
            ];
            \Stripe\PaymentIntent::update($paymentIntent->id, $data);
        }
    }
}
