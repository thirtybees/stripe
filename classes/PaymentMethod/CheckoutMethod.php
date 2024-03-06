<?php

namespace StripeModule\PaymentMethod;

use Cart;
use Configuration;
use Context;
use PrestaShopException;
use Stripe;
use Stripe\Exception\ApiErrorException;
use StripeModule\ExecutionResult;
use StripeModule\PaymentMetadata;
use StripeModule\PaymentMethod;
use StripeModule\Utils;

class CheckoutMethod extends PaymentMethod
{
    const METHOD_ID = 'stripe_checkout';

    /**
     * @return string
     */
    public function getMethodId(): string
    {
        return static::METHOD_ID;
    }

    /**
     * @return string[]
     */
    protected function getAllowedCurrencies(): array
    {
        return Utils::getAllSupportedCurrencies();
    }


    /**
     * @return string[]
     */
    protected function getAllowedAccountCountries(): array
    {
        return static::ALL;
    }

    /**
     * @return string[]
     */
    protected function getAllowedCustomerCountries(): array
    {
        return static::ALL;
    }

    /**
     * @return string
     */
    public function getImageFile(): string
    {
        return 'stripebtnlogo.png';
    }

    /**
     * @return string
     */
    public function getPaymentTemplateName(): string
    {
        return 'stripe-checkout.tpl';
    }

    /**
     * @param Cart $cart
     *
     * @return array
     * @throws PrestaShopException
     */
    protected function getPaymentTemplateParameters(Cart $cart): array
    {
        $paymentLogos = false;
        if (Configuration::get(Stripe::SHOW_PAYMENT_LOGOS)) {
            $link = Context::getContext()->link;
            $uri = _MODULE_DIR_ . '/stripe/views/img/creditcards.png';
            $paymentLogos = $link->getMediaLink($uri);
        }
        return array_merge(parent::getPaymentTemplateParameters($cart), [
            'paymentLogos' => $paymentLogos,
        ]);
    }


    /**
     * @param Cart $cart
     *
     * @return ExecutionResult
     * @throws PrestaShopException
     */
    public function executeMethod(Cart $cart): ExecutionResult
    {
        try {
            $api = $this->getStripeApi();
            $session = $api->createCheckoutSession($cart, $this->getMethodId());
            $paymentIntent = $api->getPaymentIntent($session->payment_intent);
            $metadata = PaymentMetadata::create($this->getMethodId(), $cart, $paymentIntent);
            $templateParams = [
                'sessionId' => $session->id,
                'stripePublishableKey' => Utils::getStripePublishableKey(),
            ];
            $javascripts = [
                Utils::stripeJavascriptUrl()
            ];

            return ExecutionResult::render($metadata, 'stripe-checkout.tpl', $templateParams, $javascripts);
        } catch (ApiErrorException $e) {
            return $this->handleApiException($e);
        }
    }


    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->l('Checkout', 'stripe');
    }

    /**
     * @return string
     */
    public function getCTA(): string
    {
        return $this->l('Pay by Credit Card', 'stripe');
    }

    /**
     * @return string
     */
    public function getDocLink(): string
    {
        return "https://docs.stripe.com/payments/checkout";
    }

}