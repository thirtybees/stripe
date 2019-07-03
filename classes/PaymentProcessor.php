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

use ThirtyBeesStripe\Stripe\PaymentIntent;
use ThirtyBeesStripe\Stripe\Charge;
use Configuration;
use Cart;
use Stripe;
use Exception;
use Db;
use Order;
use Context;
use Tools;

if (!defined('_TB_VERSION_')) {
    return;
}

/**
 * Class PaymentProcessor
 */
class PaymentProcessor
{
    /** @var Stripe */
    private $module;

    /** @var string[] */
    private $errors = [];

    /** @var string[] */
    private $debug = [];

    /** @var int */
    private $orderId = 0;

    /** @var string */
    private $redirect = null;

    /** @var StripeReview */
    private $review = null;

    /** @var StripeTransaction */
    private $transaction = null;

    /**
     * PaymentProcessor constructor.
     *
     * @param Stripe $module
     */
    public function __construct(Stripe $module)
    {
        $this->module = $module;
    }

    /**
     *  Redirects to order confirmation page if payment processing was successful
     */
    public function redirectToOrderConfirmation()
    {
        if ($this->redirect) {
            Tools::redirect($this->redirect);
        }
    }

    /**
     * Returns true if payment was processed
     *
     * @return bool
     */
    public function isValid()
    {
        return (
            !$this->errors &&
            $this->orderId
        );
    }

    /**
     * Process stripe payment
     *
     * @param Cart $cart
     * @param PaymentIntent $paymentIntent
     * @return bool
     */
    public function processPayment(Cart $cart, PaymentIntent $paymentIntent)
    {
        $this->reset();

        $charges = [];
        foreach ($paymentIntent->charges as $charge) {
            $charges[] = $charge;
        }
        if (! $charges) {
            $this->addError($this->l('No charges associated with payment'), print_r($paymentIntent, true));
        } else if (count($charges) === 1) {
            try {
                $this->processCharge($cart, $charges[0], $paymentIntent);
                if ($this->orderId) {
                    $this->redirect = Context::getContext()->link->getPageLink(
                        'order-confirmation',
                        null,
                        null,
                        [
                            'id_cart' => (int)$cart->id,
                            'id_module' => (int)$this->module->id,
                            'id_order' => (int)$this->orderId,
                            'key' => Tools::safeOutput($cart->secure_key),
                        ]
                    );
                }
            } catch (Exception $e) {
                $this->addError($this->l('Unknown error when processing payment'), (string)$e);
            }
        } else {
            $this->addError($this->l('Payment contains multiple charges'), print_r($paymentIntent, true));
        }

        // if there was any error, mark transaction as failed
        if ($this->errors && $this->transaction && $this->transaction->type !== StripeTransaction::TYPE_CHARGE_FAIL) {
            $this->transaction->type = StripeTransaction::TYPE_CHARGE_FAIL;
            try {
                $this->transaction->save();
            } catch (Exception $ignored) {}
        }

        return $this->isValid();
    }


    /**
     * Process charge associated with payment intent
     *
     * @param Cart $cart
     * @param Charge $charge
     * @param PaymentIntent $paymentIntent
     * @return bool
     * @throws Exception
     */
    public function processCharge(Cart $cart, Charge $charge, PaymentIntent $paymentIntent)
    {
        $paymentStatus = (int) Configuration::get(Stripe::STATUS_VALIDATED);

        // log information about stripe review
        $this->review = new StripeReview();
        $this->review->id_charge = $charge->id;
        $this->review->test = !Configuration::get(Stripe::GO_LIVE);
        $this->review->status = StripeReview::APPROVED;
        $this->review->id_order = 0;
        $review = $paymentIntent->review ? $paymentIntent->review : $charge->review;
        if ($review) {
            $this->review->id_review = $review;
            if (Configuration::get(Stripe::USE_STATUS_IN_REVIEW)) {
                $paymentStatus = (int) Configuration::get(Stripe::STATUS_IN_REVIEW);
            }
            $this->review->status = StripeReview::IN_REVIEW;
        }
        if (! $this->review->add()) {
            $this->addError($this->l('Failed to create stripe review object'), Db::getInstance()->getMsgError());
            return false;
        }

        // Log information about stripe transaction to db
        $this->transaction = new StripeTransaction();
        $this->transaction->card_last_digits = Utils::getCardLastDigits($charge->payment_method_details);
        $this->transaction->id_charge = $charge->id;
        $this->transaction->amount = Utils::getCartTotal($cart);
        $this->transaction->id_order = 0;
        $this->transaction->type = Utils::getTransactionType($paymentIntent->status, $this->review);
        $this->transaction->source = StripeTransaction::SOURCE_FRONT_OFFICE;
        $this->transaction->source_type = 'cc';
        if (! $this->transaction->add()) {
            $this->addError($this->l('Failed to create stripe transaction object'), Db::getInstance()->getMsgError());
            return false;
        }

        if ($charge->status === Charge::STATUS_SUCCEEDED) {
            try {
                $this->module->validateOrder(
                    (int)$cart->id,
                    $paymentStatus,
                    $cart->getOrderTotal(),
                    Utils::getPaymentMethodName($charge->payment_method_details),
                    null,
                    ['transaction_id' => $paymentIntent->id],
                    null,
                    false,
                    $cart->secure_key
                );
            } catch (Exception $e) {
                $this->addError($this->l('Failed to validate order'), (string)$e);
                return false;
            }

            $this->orderId = (int)Order::getOrderByCartId((int) $cart->id);
            if ($this->orderId) {
                // update review and transaction
                $this->transaction->id_order = $this->orderId;
                $this->transaction->update();
                $this->review->id_order = $this->orderId;
                $this->review->update();
                return true;
            } else {
                $this->addError($this->l('Order not found'));
                return false;
            }
        } else {
            $this->addError($this->l('Charge has invalid status'), print_r($charge, true));
            return false;
        }
    }

    /**
     * Collect error message
     *
     * @param $displayable
     * @param null $debug
     */
    private function addError($displayable, $debug=null)
    {
        if (_PS_MODE_DEV_) {
            $displayable .= "\n" . $debug;
        }
        $this->errors[] = $displayable;
        $this->debug[] = $debug;
    }

    /**
     * Resets object
     */
    private function reset()
    {
        $this->errors = [];
        $this->debug = [];
        $this->transaction = null;
        $this->review = null;
        $this->orderId = 0;
        $this->transaction = null;
    }

    /**
     * Returns error collected during payment processing
     *
     * @return string[]
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * Translates string
     *
     * @param string $message
     *
     * @return string
     */
    public function l($message)
    {
        return $this->module->l($message);
    }
}
