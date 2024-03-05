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

use PrestaShopException;
use Stripe\Exception\ApiErrorException;
use Stripe\PaymentIntent;
use Stripe\Charge;
use Configuration;
use Cart;
use Stripe;
use Db;
use Order;
use Context;
use Throwable;
use Tools;

if (!defined('_TB_VERSION_')) {
    return;
}

/**
 * Class PaymentProcessor
 */
class PaymentProcessor
{
    /**
     * @var Stripe
     */
    private $module;

    /**
     * @var string[]
     */
    private $errors = [];

    /**
     * @var string[]
     */
    private $debug = [];

    /**
     * @var int
     */
    private $orderId = 0;

    /**
     * @var string
     */
    private $redirect = null;

    /**
     * @var StripeReview
     */
    private $review = null;

    /**
     * @var StripeTransaction
     */
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
     * Redirects to order confirmation page if payment processing was successful
     *
     * @throws PrestaShopException
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
     * @param string $methodId
     * @param string $paymentMethodName
     *
     * @return bool
     *
     * @throws ApiErrorException
     * @throws PrestaShopException
     */
    public function processPayment(Cart $cart, PaymentIntent $paymentIntent, string $methodId, string $paymentMethodName)
    {
        $this->reset();

        $charge = $this->getCharge($paymentIntent->latest_charge);
        if ($charge) {
            $this->processCharge($cart, $charge, $paymentIntent, $methodId, $paymentMethodName);
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
        } else {
            $this->addError($this->l('No charges associated with payment'), print_r($paymentIntent, true));
        }

        // if there was any error, mark transaction as failed
        if ($this->errors && $this->transaction && $this->transaction->type !== StripeTransaction::TYPE_CHARGE_FAIL) {
            $this->transaction->type = StripeTransaction::TYPE_CHARGE_FAIL;
            $this->transaction->save();
        }

        return $this->isValid();
    }


    /**
     * Process charge associated with payment intent
     *
     * @param Cart $cart
     * @param Charge $charge
     * @param PaymentIntent $paymentIntent
     * @param string $methodId
     * @param string $paymentMethodName
     *
     * @return bool
     * @throws PrestaShopException
     */
    public function processCharge(Cart $cart, Charge $charge, PaymentIntent $paymentIntent, string $methodId, string $paymentMethodName)
    {
        if ($paymentIntent->status === PaymentIntent::STATUS_PROCESSING) {
            $paymentStatus = (int) Configuration::get(Stripe::STATUS_PROCESSING);
            if (!$paymentStatus) {
                $paymentStatus = (int) Configuration::get('PS_OS_PREPARATION');
            }
        } else {
            $paymentStatus = (int) Configuration::get(Stripe::STATUS_VALIDATED);
        }

        // log information about stripe review
        $this->review = new StripeReview();
        $this->review->id_charge = $charge->id;
        $this->review->id_payment_intent = $paymentIntent->id;
        $this->review->test = !Configuration::get(Stripe::GO_LIVE);
        $this->review->status = $charge->captured ? StripeReview::CAPTURED : StripeReview::AUTHORIZED;
        $this->review->captured = !!$charge->captured;
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
        $this->transaction->card_last_digits = Utils::getCardLastDigits($charge);
        $this->transaction->id_charge = $charge->id;
        $this->transaction->amount = Utils::getCartTotal($cart);
        $this->transaction->id_order = 0;
        $this->transaction->type = Utils::getTransactionType($paymentIntent->status, $this->review);
        $this->transaction->source = StripeTransaction::SOURCE_FRONT_OFFICE;
        $this->transaction->source_type = $methodId;
        if (! $this->transaction->add()) {
            $this->addError($this->l('Failed to create stripe transaction object'), Db::getInstance()->getMsgError());
            return false;
        }

        if ($charge->status === Charge::STATUS_SUCCEEDED || $charge->status === Charge::STATUS_PENDING) {
            try {
                $this->module->validateOrder(
                    (int)$cart->id,
                    $paymentStatus,
                    $cart->getOrderTotal(),
                    $paymentMethodName,
                    null,
                    ['transaction_id' => $paymentIntent->id],
                    null,
                    false,
                    $cart->secure_key
                );
            } catch (Throwable $e) {
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
     * Captures uncaptured payment
     *
     * @param string $paymentIntentId
     * @param StripeReview $review
     * @param int $orderId
     * @return bool
     */
    public function capturePayment($paymentIntentId, StripeReview $review, $orderId)
    {
        $this->reset();
        $this->review = $review;
        try {
            $paymentIntent = $this->module->getStripeApi()->getPaymentIntent($paymentIntentId);

            if ($paymentIntent->status === PaymentIntent::STATUS_SUCCEEDED) {
                // already captured
                $this->review->captured = true;
                $this->review->status = StripeReview::CAPTURED;
                if (! $this->review->update()) {
                    $this->addError($this->l('Failed to update review object'), Db::getInstance()->getMsgError());
                }
                return true;
            }

            if ($paymentIntent->status === PaymentIntent::STATUS_REQUIRES_CAPTURE) {
                // capture amount
                $updatedIntent = $paymentIntent->capture();
                // update review
                $this->review->captured = true;
                $this->review->status = StripeReview::CAPTURED;
                if (!$this->review->update()) {
                    $this->addError($this->l('Failed to update review object'), Db::getInstance()->getMsgError());
                }

                // Log information about stripe transaction to db
                $charge = $this->getCharge($updatedIntent->latest_charge);
                if ($charge) {
                    $this->transaction = new StripeTransaction();
                    $this->transaction->card_last_digits = Utils::getCardLastDigits($charge);
                    $this->transaction->id_charge = $charge->id;
                    $this->transaction->amount = $charge->amount;
                    $this->transaction->id_order = $orderId;
                    $this->transaction->type = StripeTransaction::TYPE_CAPTURED;
                    $this->transaction->source = StripeTransaction::SOURCE_BACK_OFFICE;
                    $this->transaction->source_type = 'cc';
                    if (!$this->transaction->add()) {
                        $this->addError($this->l('Failed to create stripe transaction object'), Db::getInstance()->getMsgError());
                        return false;
                    }
                } else {
                    $this->addError($this->l('Failed to create stripe transaction object, charge not found'));
                    return false;
                }
                return true;
            }
            $this->addError('Invalid payment intent status: '.$paymentIntent->status, print_r($paymentIntent, true));
            return false;
        } catch (ApiErrorException $e) {
            $this->addError($e->getMessage(), (string)$e);
        } catch (Throwable $e) {
            $this->addError(sprintf($this->l("Couldn't find payment intent %s"), $paymentIntentId), (string)$e);
        }
        return false;
    }


    /**
     * Releases uncaptured payment
     *
     * @param string $paymentIntentId
     * @param StripeReview $review
     * @param int $orderId
     * @return bool
     */
    public function releasePayment($paymentIntentId, StripeReview $review, $orderId)
    {
        $this->reset();
        $this->review = $review;
        try {
            $paymentIntent = $this->module->getStripeApi()->getPaymentIntent($paymentIntentId);

            if ($paymentIntent->status === PaymentIntent::STATUS_CANCELED) {
                // already captured
                $this->review->captured = false;
                $this->review->status = StripeReview::RELEASED;
                if (! $this->review->update()) {
                    $this->addError($this->l('Failed to update review object'), Db::getInstance()->getMsgError());
                }
                return true;
            }

            if ($paymentIntent->status === PaymentIntent::STATUS_REQUIRES_CAPTURE) {
                // capture amount
                $updatedIntent = $paymentIntent->cancel();
                // update review
                $this->review->captured = false;
                $this->review->status = StripeReview::RELEASED;
                if (!$this->review->update()) {
                    $this->addError($this->l('Failed to update review object'), Db::getInstance()->getMsgError());
                }

                // Log information about stripe transaction to db
                $charge = $this->getCharge($updatedIntent->latest_charge);
                if ($charge) {
                    $this->transaction = new StripeTransaction();
                    $this->transaction->card_last_digits = Utils::getCardLastDigits($charge);
                    $this->transaction->id_charge = $charge->id;
                    $this->transaction->amount = $charge->amount;
                    $this->transaction->id_order = $orderId;
                    $this->transaction->type = StripeTransaction::TYPE_FULL_REFUND;
                    $this->transaction->source = StripeTransaction::SOURCE_BACK_OFFICE;
                    $this->transaction->source_type = 'cc';
                    if (!$this->transaction->add()) {
                        $this->addError($this->l('Failed to create stripe transaction object'), Db::getInstance()->getMsgError());
                        return false;
                    }
                    return true;
                } else {
                    $this->addError($this->l('Failed to create stripe transaction object, charge not found'));
                    return false;
                }
            }
            $this->addError('Invalid payment intent status: '.$paymentIntent->status, print_r($paymentIntent, true));
            return false;
        } catch (ApiErrorException $e) {
            $this->addError($e->getMessage(), (string)$e);
        } catch (Throwable $e) {
            $this->addError(sprintf($this->l("Couldn't find payment intent %s"), $paymentIntentId), (string)$e);
        }
        return false;
    }

    /**
     * Collect error message
     *
     * @param string $displayable
     * @param array|string|null $debug
     */
    private function addError($displayable, $debug=null)
    {
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
     * @return string[]
     */
    public function getDebug()
    {
        return $this->debug;
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

    /**
     * @param string|null $chargeId
     *
     * @return Charge|null
     *
     * @throws ApiErrorException
     */
    protected function getCharge($chargeId)
    {
        if ($chargeId) {
            return $this->module->getStripeApi()->getCharge($chargeId);
        } else {
            return null;
        }
    }

}
