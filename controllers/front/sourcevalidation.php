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

use StripeModule\StripeReview;
use StripeModule\StripeTransaction;
use StripeModule\Utils;
use Stripe\PaymentIntent;

if (!defined('_TB_VERSION_')) {
    exit;
}

/**
 * Class StripeSourcevalidationModuleFrontController
 */
class StripeSourcevalidationModuleFrontController extends ModuleFrontController
{
    /** @var Stripe $module */
    public $module;

    /**
     * StripeSourcevalidationModuleFrontController constructor.
     *
     * @throws PrestaShopException
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
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws SmartyException
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
        $this->context->smarty->assign(
            [
                'orderLink' => $this->context->link->getPageLink($orderProcess, true),
            ]
        );

        if (!Tools::isSubmit('source') || (int) Tools::getValue('stripe-id_cart') != $cart->id || !Tools::isSubmit('type')) {
            $error = $this->module->l('An error occurred. Please contact us for more information.', 'validation');
            $this->errors[] = $error;
            $this->setTemplate('error.tpl');

            return false;
        }

        $source = Tools::getValue('source');
        $idCart = Tools::getValue('stripe-id_cart');
        $type = Tools::getValue('type');

        $cart = new Cart((int) $idCart);
        $customer = new Customer((int) $cart->id_customer);
        $currency = new Currency((int) $cart->id_currency);

        $secretKey = Configuration::get(Stripe::GO_LIVE)
                ? Configuration::get(Stripe::SECRET_KEY_LIVE)
                : Configuration::get(Stripe::SECRET_KEY_TEST);

        $guzzle = new \StripeModule\GuzzleClient();
        \Stripe\ApiRequestor::setHttpClient($guzzle);
        \Stripe\Stripe::setApiKey($secretKey);


        $stripeAmount = $cart->getOrderTotal();
        if (!in_array(mb_strtolower($currency->iso_code), Stripe::$zeroDecimalCurrencies)) {
            $stripeAmount = (int) ($stripeAmount * 100);
        }

        try {
            $stripeCharge = \Stripe\Charge::create(
                [
                    'amount'   => $stripeAmount,
                    'currency' => mb_strtolower($currency->iso_code),
                    'source'   => $source,
                    'metadata' => [
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

        $paymentStatus = Configuration::get(Stripe::STATUS_VALIDATED);
        $stripeReview = new StripeReview();
        $stripeReview->id_charge = $stripeCharge->id;
        $stripeReview->status = StripeReview::CAPTURED;
        $stripeReview->test = !Configuration::get(Stripe::GO_LIVE);
        $stripeReview->captured = true;
        if ($stripeCharge->status === 'succeeded') {
            $message = null;

            /**
             * Converting cart into a valid order
             */
            $currencyId = (int) Context::getContext()->currency->id;

            $paymentMethod = $type;
            switch ($paymentMethod) {
                case 'ideal':
                    $paymentMethod = $this->module->l('iDEAL');
                    break;
                case 'bancontact':
                    $paymentMethod = $this->module->l('Bancontact');
                    break;
                case 'giropay':
                    $paymentMethod = $this->module->l('Giropay');
                    break;
                case 'sofort':
                    $paymentMethod = $this->module->l('Sofort Banking');
                    break;
                default:
                    $paymentMethod = $this->module->l('Credit Card');
                    break;
            }

            $this->module->validateOrder(
                $idCart,
                $paymentStatus,
                $cart->getOrderTotal(),
                $paymentMethod,
                $message,
                [],
                $currencyId,
                false,
                $cart->secure_key
            );

            /**
             * If the order has been validated we try to retrieve it
             */
            $idOrder = Order::getOrderByCartId((int) $cart->id);

            if ($idOrder) {
                // Log transaction
                $stripeTransaction = new StripeTransaction();
                $stripeTransaction->card_last_digits = Utils::getCardLastDigits($stripeCharge);
                $stripeTransaction->id_charge = $stripeCharge->id;
                $stripeTransaction->amount = $stripeAmount;
                $stripeTransaction->id_order = $idOrder;
                $stripeTransaction->type = Utils::getTransactionType(PaymentIntent::STATUS_SUCCEEDED, $stripeReview);
                $stripeTransaction->source = StripeTransaction::SOURCE_FRONT_OFFICE;
                $stripeTransaction->source_type = $type;
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
                        'key'       => Tools::safeOutput($customer->secure_key),
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
        } elseif ($type === 'sofort' && $stripeCharge->status === 'pending') {
            $paymentStatus = (int) Configuration::get(Stripe::STATUS_SOFORT);
            if (!$paymentStatus) {
                $paymentStatus = (int) Configuration::get('PS_OS_PREPARATION');
            }
            $message = null;

            /**
             * Converting cart into a valid order
             */
            $currencyId = (int) Context::getContext()->currency->id;

            $this->module->validateOrder($idCart, $paymentStatus, $cart->getOrderTotal(), $this->module->l('Sofort Banking'), $message, [], $currencyId, false, $cart->secure_key);

            /**
             * If the order has been validated we try to retrieve it
             */
            $idOrder = Order::getOrderByCartId((int) $cart->id);

            if ($idOrder) {
                // Log transaction
                $stripeTransaction = new StripeTransaction();
                if (isset($stripeCharge->source['last4'])) {
                    $stripeTransaction->card_last_digits = (int) $stripeCharge->source['last4'];
                } else {
                    // Often not available with 3D secure or any other source
                    $stripeTransaction->card_last_digits = 0;
                }
                $stripeTransaction->id_charge = $stripeCharge->id;
                $stripeTransaction->amount = (int) $stripeAmount;
                $stripeTransaction->id_order = (int) $idOrder;
                $stripeTransaction->type = StripeTransaction::TYPE_CHARGE_PENDING;
                $stripeTransaction->source = StripeTransaction::SOURCE_FRONT_OFFICE;
                $stripeTransaction->source_type = $type;
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
                        'id_cart'   => $cart->id,
                        'id_module' => $this->module->id,
                        'id_order'  => $idOrder,
                        'key'       => $customer->secure_key,
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
            if (isset($stripeCharge->source['last4'])) {
                $stripeTransaction->card_last_digits = (int) $stripeCharge->source['last4'];
            } else {
                // Often not available with 3D secure or any other source
                $stripeTransaction->card_last_digits = 0;
            }
            $stripeTransaction->id_charge = $stripeCharge->id;
            $stripeTransaction->amount = 0;
            $stripeTransaction->id_order = 0;
            $stripeTransaction->type = StripeTransaction::TYPE_CHARGE_FAIL;
            $stripeTransaction->source = StripeTransaction::SOURCE_FRONT_OFFICE;
            $stripeTransaction->source_type = $type;
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
