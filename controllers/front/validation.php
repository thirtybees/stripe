<?php
/**
 * Copyright (C) 2017-2024 thirty bees
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
 * @copyright 2017-2024 thirty bees
 * @license   Academic Free License (AFL 3.0)
 */

use Stripe\Exception\ApiErrorException;
use StripeModule\PaymentMetadata;
use StripeModule\PaymentMethod;
use StripeModule\StripeApi;
use StripeModule\Utils;
use StripeModule\Logger\Logger;
use StripeModule\Logger\FileLogger;
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
     * @var Logger
     */
    protected $logger;


    /**
     * @throws PrestaShopException
     */
    public function __construct()
    {
        parent::__construct();
        $this->logger = new FileLogger();
    }

    /**
     * Main controller method
     *
     * @throws Throwable
     */
    public function postProcess()
    {
        try {
            $this->logger->log("Payment validation start");
            if (!Module::isEnabled('stripe')) {
                $this->logger->error('Stripe module is not enabled');
                $this->redirectToCheckout();
            }

            if (!Utils::hasValidConfiguration()) {
                $this->logger->error('Stripe module is not configured properly');
                $this->redirectToCheckout();
            }

            $methodId = Tools::getValue('type');
            if (!$methodId) {
                return $this->displayError(
                    Tools::displayError('Payment method parameter not provided'),
                    'Payment method parameter not provided'
                );
            }
            $this->logger->log('Payment method id: ' . $methodId);

            $repository = $this->module->getPaymentMethodsRepository();
            $method = $repository->getMethod($methodId);
            if ($method) {
                $this->logger->log("Payment method: " . $method->getShortName());
                $metadata = Utils::getPaymentMetadata($this->context->cookie);
                if ($metadata) {
                    $this->logger->log("Stored payment metadata: " . json_encode($metadata->getData()));
                    return $this->validatePaymentMethod($method, $metadata);
                } else {
                    return $this->displayError(
                        Tools::displayError('Payment metadata not found'),
                        'Payment metadata not found'
                    );
                }
            } else {
                return $this->displayError(
                    sprintf(Tools::displayError('Payment method %s is not available'), $methodId),
                    sprintf('Payment method %s is not available', $methodId)
                );
            }
        } catch (PrestaShopException $e) {
            $this->logger->exception($e);
            throw $e;
        } finally {
            $this->logger->log("Validation end");
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
            return $this->displayError(
                Tools::displayError('Cart not found'),
                'Cart not found in current session'
            );
        }

        $paymentIntentId = $this->getPaymentIntentId($api, $metadata);
        if (! $paymentIntentId) {
            $this->logger->error("Failed to resolve payment intent");
            $this->redirectToCheckout();
            return false;
        } else {
            $this->logger->log("Payment intent id: " . $paymentIntentId);
        }

        // Optional check for provided payment intent
        $providedPaymentIntentId = Tools::getValue('payment_intent');
        if ($providedPaymentIntentId) {
            if ($paymentIntentId !== $providedPaymentIntentId) {
                return $this->displayError(
                    Tools::displayError('Invalid parameter payment_intent'),
                    'Invalid parameter payment_intent'
                );
            }
        }

        $errors = $metadata->validate($method, $cart);
        if ($errors) {
            $this->logger->error("Failed to validate metadata: " . implode(", ", $errors));
            return $this->displayErrors($errors);
        }

        $methodId = $method->getMethodId();
        $methodName = sprintf(
            Translate::getModuleTranslation('stripe', 'Stripe: %s', 'validation'),
            $method->getShortName()
        );

        $paymentIntent = $api->getPaymentIntent($paymentIntentId);
        if (! $paymentIntent) {
            return $this->displayError(
                Tools::displayError("Failed to retrieve payment intent from stripe"),
                "Failed to retrieve payment intent ".$paymentIntentId
            );
        }
        $this->logger->log("Successfully fetched payment intent data, status = '" . $paymentIntent->status . "'");
        switch ($paymentIntent->status) {
            case PaymentIntent::STATUS_SUCCEEDED:
            case PaymentIntent::STATUS_REQUIRES_CAPTURE:
            case PaymentIntent::STATUS_PROCESSING:
                $this->logger->log('Processing payment');
                $this->processPayment($cart, $paymentIntent, $methodId, $methodName);
                return true;
            case PaymentIntent::STATUS_CANCELED:
            case PaymentIntent::STATUS_REQUIRES_PAYMENT_METHOD:
                $this->logger->log("Payment canceled, cleaning data from cookie");
                Utils::removeFromCookie($this->context->cookie);
                $this->redirectToCheckout();
                return false;
            default:
                Utils::removeFromCookie($this->context->cookie);
                return $this->displayError(
                    sprintf(Tools::displayError('Payment intent has invalid status: %s'), $paymentIntent->status),
                    'Payment intent has invalid status: ' . $paymentIntent
                );
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
        $processor = new PaymentProcessor($this->module, $this->logger);
        if ($processor->processPayment($cart, $paymentIntent, $methodId, $paymentMethodName)) {
            $this->logger->log("Payment sucessfully processed");
            Utils::removeFromCookie($this->context->cookie);
            $processor->redirectToOrderConfirmation();
        } else {
            $this->logger->log("Payment failed");
            $this->displayErrors($processor->getErrors());
        }
    }

    /**
     * @param string $display
     * @param string $log
     *
     * @return bool
     * @throws PrestaShopException
     */
    private function displayError(string $display, string $log)
    {
        $this->logger->error($log);
        return $this->displayErrors([ $display ]);
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
        return null;
    }
}
