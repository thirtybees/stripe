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
 *  @author    thirty bees <modules@thirtybees.com>
 *  @copyright 2017 thirty bees
 *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

if (!defined('_TB_VERSION_')) {
    exit;
}

require_once dirname(__FILE__).'/../../vendor/autoload.php';

/**
 * Class StripeValidationModuleFrontController
 */
class StripeValidationModuleFrontController extends ModuleFrontController
{
    /** @var Stripe $module */
    public $module;

    /**
     * StripeValidationModuleFrontController constructor.
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
     */
    public function postProcess()
    {
        $cart = $this->context->cart;
        if ($cart->id_customer == 0 || $cart->id_address_delivery == 0 || $cart->id_address_invoice == 0 || !$this->module->active) {
            Tools::redirect('index.php?controller=order&step=1');
        }

        $customer = new Customer($cart->id_customer);
        if (!Validate::isLoadedObject($customer)) {
            Tools::redirect('index.php?controller=order&step=1');
        }

        $orderProcess = Configuration::get('PS_ORDER_PROCESS_TYPE') ? 'order-opc' : 'order';
        $this->context->smarty->assign(
            [
            'orderLink' => $this->context->link->getPageLink($orderProcess, true),
            ]
        );

        if ((Tools::isSubmit('stripe-id_cart') == false) || (Tools::isSubmit('stripe-token') == false) || (int) Tools::getValue('stripe-id_cart') != $cart->id) {
            $error = $this->module->l('An error occurred. Please contact us for more information.', 'validation');
            $this->errors[] = $error;
            $this->setTemplate('error.tpl');


            return false;
        }

        $token = Tools::getValue('stripe-token');
        $idCart = Tools::getValue('stripe-id_cart');

        $cart = new Cart((int) $idCart);
        $customer = new Customer((int) $cart->id_customer);
        $currency = new Currency((int) $cart->id_currency);

        $stripe = [
            'secret_key' => Configuration::get(Stripe::SECRET_KEY),
            'publishable_key' => Configuration::get(Stripe::PUBLISHABLE_KEY),
        ];

        $guzzle = new \Stripe\HttpClient\GuzzleClient();
        \Stripe\ApiRequestor::setHttpClient($guzzle);
        \Stripe\Stripe::setApiKey($stripe['secret_key']);

        try {
            $stripeCustomer = \Stripe\Customer::create(
                [
                'email' => $customer->email,
                'source' => $token,
                ]
            );
        } catch (Exception $e) {
            $error = $e->getMessage();
            $this->errors[] = $error;
            $this->setTemplate('error.tpl');

            return false;
        }

        $stripeAmount = $cart->getOrderTotal();
        if (!in_array(Tools::strtolower($currency->iso_code), Stripe::$zeroDecimalCurrencies)) {
            $stripeAmount = (int) ($stripeAmount * 100);
        }

        try {
            $stripeCharge = \Stripe\Charge::create(
                [
                    'customer' => $stripeCustomer->id,
                    'amount' => $stripeAmount,
                    'currency' => Tools::strtolower($currency->iso_code),
                ]
            );
        } catch (Exception $e) {
            $error = $e->getMessage();
            $this->errors[] = $error;
            $this->setTemplate('error.tpl');

            return false;
        }

        if ($stripeCharge->paid === true) {
            $paymentStatus = Configuration::get(Stripe::STATUS_VALIDATED);
            $message = null;

            /**
             * Converting cart into a valid order
             */
            $currencyId = (int) Context::getContext()->currency->id;

            $this->module->validateOrder($idCart, $paymentStatus, $cart->getOrderTotal(), 'Stripe', $message, [], $currencyId, false, $cart->secure_key);

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
                $stripeTransaction->type = StripeTransaction::TYPE_CHARGE;
                $stripeTransaction->source = StripeTransaction::SOURCE_FRONT_OFFICE;
                $stripeTransaction->add();

                /**
                 * The order has been placed so we redirect the customer on the confirmation page.
                 */
                Tools::redirect('index.php?controller=order-confirmation&id_cart='.$cart->id.'&id_module='.$this->module->id.'&id_order='.$idOrder.'&key='.$customer->secure_key);
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
