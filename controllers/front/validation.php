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

use Stripe\Exception\ApiConnectionException;
use Stripe\Exception\ApiErrorException;
use StripeModule\PaymentMetadata;
use StripeModule\PaymentMethod;
use StripeModule\StripeApi;
use StripeModule\Utils;
use StripeModule\PaymentProcessor;
use Stripe\PaymentIntent;

if (!defined('_TB_VERSION_')) {
    exit;
}

/**
 * Class StripeValidationModuleFrontController
 */
class StripeValidationModuleFrontController extends ModuleFrontController
{
    /**
     * @var Stripe $module
     */
    public $module;

    /**
     * Main controller method
     *
     * @throws PrestaShopException
     * @throws ApiConnectionException
     * @throws ApiErrorException
     */
    public function postProcess()
    {
        if (!Module::isEnabled('stripe')) {
           $this->redirectToCheckout();
        }

        if (! Utils::hasValidConfiguration()) {
            $this->redirectToCheckout();
        }

        $methodId = Tools::getValue('type');
        if (! $methodId) {
            return $this->displayError(Tools::displayError('Payment method parameter not provided'));
        }

        $repository = $this->module->getPaymentMethodsRepository();
        $method = $repository->getMethod($methodId);
        if ($method) {
            $metadata = Utils::getPaymentMetadata($this->context->cookie);
            if ($metadata) {
                return $this->validatePaymentMethod($method, $metadata);
            } else {
                return $this->displayError(Tools::displayError('Payment metadata not found'));
            }
        }  else {
            return $this->displayError(sprintf(Tools::displayError('Payment method %s is not available'), $methodId));
        }
    }

    /**
     * @param PaymentMethod $method
     * @param PaymentMetadata $metadata
     *
     * @return bool
     * @throws ApiErrorException
     * @throws PrestaShopException
     */
    public function validatePaymentMethod(PaymentMethod $method, PaymentMetadata $metadata)
    {
        $api = $this->module->getStripeApi();
        $cart = $this->context->cart;

        if (! Validate::isLoadedObject($cart)) {
            return $this->displayError(Tools::displayError('Cart not found'));
        }

        $paymentIntentId = $this->getPaymentIntentId($api, $metadata);
        if (! $paymentIntentId) {
            $this->redirectToCheckout();
            return false;
        }

        // Optional check for provided payment intent
        $providedPaymentIntentId = Tools::getValue('payment_intent');
        if ($providedPaymentIntentId) {
            if ($paymentIntentId !== $providedPaymentIntentId) {
                return $this->displayError(Tools::displayError('Invalid parameter payment_intent'));
            }
        }

        $errors = $metadata->validate($method, $cart);
        if ($errors) {
            return $this->displayErrors($errors);
        }

        $methodId = $method->getMethodId();
        $methodName = sprintf(
            Translate::getModuleTranslation('stripe', 'Stripe: %s', 'validation'),
            $method->getShortName()
        );

        $paymentIntent = $api->getPaymentIntent($paymentIntentId);
        switch ($paymentIntent->status) {
            case PaymentIntent::STATUS_SUCCEEDED:
            case PaymentIntent::STATUS_REQUIRES_CAPTURE:
            case PaymentIntent::STATUS_PROCESSING:
                $this->processPayment($cart, $paymentIntent, $methodId, $methodName);
                return true;
            case PaymentIntent::STATUS_CANCELED:
            case PaymentIntent::STATUS_REQUIRES_PAYMENT_METHOD:
                Utils::removeFromCookie($this->context->cookie);
                $this->redirectToCheckout();
                return false;
            default:
                Utils::removeFromCookie($this->context->cookie);
                $this->displayError('Payment intent has invalid status: ' . $paymentIntent->status);
                return false;
        }
    }

    /**
     * Performs redirect to checkout page
     *
     * @throws PrestaShopException
     */
    private function redirectToCheckout()
    {
        $orderProcess = Configuration::get('PS_ORDER_PROCESS_TYPE') ? 'order-opc' : 'order';
        Tools::redirect($this->context->link->getPageLink($orderProcess, true));
    }

    /**
     * Method called when payment has been successfully completed
     *
     * @param Cart $cart
     * @param PaymentIntent $paymentIntent
     * @param string $methodId
     * @param string $paymentMethodName
     *
     * @throws ApiErrorException
     * @throws PrestaShopException
     */
    private function processPayment(Cart $cart, PaymentIntent $paymentIntent, string $methodId, string $paymentMethodName)
    {
        $processor = new PaymentProcessor($this->module);
        if ($processor->processPayment($cart, $paymentIntent, $methodId, $paymentMethodName)) {
            Utils::removeFromCookie($this->context->cookie);
            $processor->redirectToOrderConfirmation();
        } else {
            $this->displayErrors($processor->getErrors());
        }
    }

    /**
     * @param string $error
     *
     * @return bool
     * @throws PrestaShopException
     */
    private function displayError(string $error)
    {
        return $this->displayErrors([ $error ]);
    }

    /**
     * @param string[] $errors
     * @return bool
     * @throws PrestaShopException
     */
    private function displayErrors($errors)
    {
        $orderProcess = Configuration::get('PS_ORDER_PROCESS_TYPE') ? 'order-opc' : 'order';
        $this->context->smarty->assign('orderLink', $this->context->link->getPageLink($orderProcess, true));
        $this->errors = $errors;
        $this->setTemplate('error.tpl');
        return false;
    }

    /**
     * @param StripeApi $api
     * @param PaymentMetadata $metadata
     *
     * @return string|null
     * @throws ApiErrorException
     */
    private function getPaymentIntentId(StripeApi $api, PaymentMetadata $metadata)
    {
        switch ($metadata->getType()) {
            case PaymentMetadata::TYPE_PAYMENT_INTENT:
                return $metadata->getId();
            case PaymentMetadata::TYPE_SESSION:
                $sessionId = $metadata->getId();
                $session = $api->getCheckoutSession($sessionId);
                return $session->payment_intent;
        }
    }
}
