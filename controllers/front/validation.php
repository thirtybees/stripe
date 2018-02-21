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
 *  @author    thirty bees <modules@thirtybees.com>
 *  @copyright 2017-2018 thirty bees
 *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

use StripeModule\StripeReview;
use StripeModule\StripeTransaction;

if (!defined('_TB_VERSION_')) {
    exit;
}

/**
 * Class StripeValidationModuleFrontController
 */
class StripeValidationModuleFrontController extends ModuleFrontController
{
    /** @var Stripe $module */
    public $module;

    /**
     * StripeValidationModuleFrontController constructor.
     *
     * @throws PrestaShopException
     * @throws Adapter_Exception
     */
    public function __construct()
    {
        parent::__construct();

        $this->ssl = Tools::usingSecureMode();
    }

    /**
     * Post process
     *
     * @return bool Whether the info has been successfully processed
     * @throws PrestaShopException
     * @throws Adapter_Exception
     */
    public function postProcess()
    {
        $cart = $this->context->cart;
        if ($cart->id_customer == 0 || $cart->id_address_delivery == 0 || $cart->id_address_invoice == 0 || !$this->module->active) {
            Tools::redirect($this->context->link->getPageLink('order', null, null, ['step' => 3]));
        }

        $customer = new Customer($cart->id_customer);
        if (!Validate::isLoadedObject($customer)) {
            Tools::redirect($this->context->link->getPageLink('order', null, null, ['step' => 3]));
        }

        $orderProcess = Configuration::get('PS_ORDER_PROCESS_TYPE') ? 'order-opc' : 'order';
        $this->context->smarty->assign([
            'orderLink' => $this->context->link->getPageLink($orderProcess, true),
        ]);

        if (!Tools::isSubmit('stripe-id_cart')
            || !Tools::isSubmit('stripe-token')
            || Tools::getValue('stripe-id_cart') != $cart->id
        ) {
            $this->errors[] = $this->module->l('An error occurred. Please contact us for more information.', 'validation');
            $this->setTemplate('error.tpl');

            return false;
        }

        $token = Tools::getValue('stripe-token');
        $idCart = Tools::getValue('stripe-id_cart');

        $cart = new Cart((int) $idCart);
        $customer = new Customer((int) $cart->id_customer);
        $currency = new Currency((int) $cart->id_currency);

        $stripe = [
            'secret_key'      => Configuration::get(Stripe::GO_LIVE)
                ? Configuration::get(Stripe::SECRET_KEY_LIVE)
                : Configuration::get(Stripe::SECRET_KEY_TEST),
            'publishable_key' => Configuration::get(Stripe::GO_LIVE)
                ? Configuration::get(Stripe::PUBLISHABLE_KEY_LIVE)
                : Configuration::get(Stripe::PUBLISHABLE_KEY_TEST),
        ];

        $guzzle = new \StripeModule\GuzzleClient();
        \ThirtyBeesStripe\Stripe\ApiRequestor::setHttpClient($guzzle);
        \ThirtyBeesStripe\Stripe\Stripe::setApiKey($stripe['secret_key']);

        $stripeAmount = $cart->getOrderTotal();
        if (!in_array(mb_strtolower($currency->iso_code), Stripe::$zeroDecimalCurrencies)) {
            $stripeAmount = (int) ($stripeAmount * 100);
        }

        try {
            $stripeCustomer = \ThirtyBeesStripe\Stripe\Customer::create([
                'email'  => $customer->email,
                'source' => $token,
            ]);
        } catch (Exception $e) {
            $error = $e->getMessage();
            $this->errors[] = $error;
            $this->setTemplate('error.tpl');

            return false;
        }

        try {
            $source = \ThirtyBeesStripe\Stripe\Source::retrieve($token);
            $defaultCard = $source->card;
        } catch (Exception $e) {
            $defaultCard = new stdClass();
            $defaultCard->three_d_secure = 'not_supported';
        }

        /** @var \ThirtyBeesStripe\Stripe\Card $defaultCard */
        if (Configuration::get(Stripe::THREEDSECURE) && $defaultCard->three_d_secure !== 'not_supported'
            || $defaultCard->three_d_secure === 'required'
        ) {
            try {
                $source = \ThirtyBeesStripe\Stripe\Source::create(
                    [
                        'amount'         => $stripeAmount,
                        'currency'       => mb_strtolower($currency->iso_code),
                        'type'           => 'three_d_secure',
                        'three_d_secure' => [
                            'card' => $defaultCard->id ?: $token,
                        ],
                        'redirect'       => [
                            'return_url' => $this->context->link->getModuleLink(
                                $this->module->name,
                                'sourcevalidation',
                                ['stripe-id_cart' => (string) $cart->id, 'type' => 'three_d_secure'],
                                true
                            ),
                        ],
                        'metadata'       => [
                            'from_back_office' => true,
                        ],
                    ]
                );
            } catch (Exception $e) {
                $error = $e->getMessage();
                $this->errors[] = $error;
                $this->setTemplate('error.tpl');

                return false;
            }

            Tools::redirectLink($source->redirect->url);
        }

        try {
            $stripeCharge = \ThirtyBeesStripe\Stripe\Charge::create([
                'customer' => $stripeCustomer->id,
                'amount'   => $stripeAmount,
                'currency' => mb_strtolower($currency->iso_code),
                'capture'  => false,
                'metadata' => [
                    'from_back_office' => true,
                ],
            ]);
        } catch (Exception $e) {
            $error = $e->getMessage();
            $this->errors[] = $error;
            $this->setTemplate('error.tpl');

            return false;
        }

        $stripeReview = new StripeReview();
        $stripeReview->id_charge = $stripeCharge->id;
        $stripeReview->test = !Configuration::get(Stripe::GO_LIVE);
        $stripeReview->status = 0;
        if ($stripeCharge->status === 'succeeded') {
            $paymentStatus = (int) Configuration::get(Stripe::STATUS_VALIDATED);
            if ($stripeCharge->review || Configuration::get(Stripe::MANUAL_CAPTURE)) {
                $stripeReview->id_review = $stripeCharge->review;
                if (Configuration::get(Stripe::MANUAL_CAPTURE) && Configuration::get(Stripe::USE_STATUS_AUTHORIZED)) {
                    $paymentStatus = (int) Configuration::get(Stripe::STATUS_AUTHORIZED);
                }
                if ($stripeCharge->review && Configuration::get(Stripe::USE_STATUS_IN_REVIEW)) {
                    $paymentStatus = (int) Configuration::get(Stripe::STATUS_IN_REVIEW);
                }
                $stripeReview->status = $stripeCharge->review ? StripeReview::IN_REVIEW : StripeReview::AUTHORIZED;
            } else {
                $stripeCharge->metadata = [
                    'from_back_office' => true,
                ];
                $stripeCharge->capture();
            }

            $message = null;

            /**
             * Converting cart into a valid order
             */
            $currencyId = (int) Context::getContext()->currency->id;

            try {
                $this->module->validateOrder(
                    $idCart,
                    $paymentStatus,
                    $cart->getOrderTotal(),
                    $this->module->l('Credit Card', 'validation'),
                    $message,
                    [],
                    $currencyId,
                    false,
                    $cart->secure_key
                );
            } catch (Exception $e) {
                $this->errors[] = sprintf($this->module->l('An error occurred: %s', 'validation'), $e->getMessage());
                $this->setTemplate('error.tpl');

                return false;
            }

            /**
             * If the order has been validated we try to retrieve it
             */
            $idOrder = Order::getOrderByCartId((int) $cart->id);

            if ($idOrder) {
                // Log transaction
                $stripeTransaction = new StripeTransaction();
                $stripeTransaction->card_last_digits = (int) $stripeCharge->source['last4'];
                $stripeTransaction->id_charge = $stripeCharge->id;
                $stripeTransaction->amount = $stripeAmount;
                $stripeTransaction->id_order = $idOrder;
                switch ($stripeReview->status) {
                    case StripeReview::AUTHORIZED:
                        $stripeTransaction->type = StripeTransaction::TYPE_AUTHORIZED;

                        break;
                    case StripeReview::IN_REVIEW:
                        $stripeTransaction->type = StripeTransaction::TYPE_IN_REVIEW;

                        break;
                    case StripeReview::CAPTURED:
                        $stripeTransaction->type = StripeTransaction::TYPE_CAPTURED;

                        break;
                    default:
                        $stripeTransaction->type = StripeTransaction::TYPE_CHARGE;

                        break;
                }
                $stripeTransaction->source = StripeTransaction::SOURCE_FRONT_OFFICE;
                $stripeTransaction->source_type = 'cc';
                $stripeTransaction->add();

                $stripeReview->id_order = $idOrder;
                $stripeReview->add();

                /**
                 * The order has been placed so we redirect the customer on the confirmation page.
                 */
                Tools::redirect($this->context->link->getPageLink(
                    'order-confirmation',
                    null,
                    null,
                    [
                        'id_cart'   => (int) $cart->id,
                        'id_module' => (int) $this->module->id,
                        'id_order'  => (int) $idOrder,
                        'key'       => Tools::safeOutput($cart->secure_key),
                    ]
                ));
            } else {
                /**
                 * An error occurred and is shown on a new page.
                 */
                $error = $this->module->l('An error occurred. Please contact us for more information.', 'validation');
                $this->errors[] = $error;
                $this->setTemplate('error.tpl');

                return false;
            }
        } else {
            $stripeTransaction = new StripeTransaction();
            $stripeTransaction->card_last_digits = (int) $stripeCharge->source['last4'];
            $stripeTransaction->id_charge = $stripeCharge->id;
            $stripeTransaction->amount = 0;
            $stripeTransaction->id_order = 0;
            $stripeTransaction->type = StripeTransaction::TYPE_CHARGE_FAIL;
            $stripeTransaction->source = StripeTransaction::SOURCE_FRONT_OFFICE;
            $stripeTransaction->source_type = 'cc';
            $stripeTransaction->add();
        }

        /**
         * An error occurred and is shown on a new page.
         */
        $error = $this->module->l('An error occurred. Please contact us for more information.', 'validation');
        $this->errors[] = $error;
        $this->setTemplate('error.tpl');

        return false;
    }
}
