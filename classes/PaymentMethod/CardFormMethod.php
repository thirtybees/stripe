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
use Tools;

class CardFormMethod extends PaymentMethod
{
    const METHOD_ID = 'stripe_cc_form';

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
     * @return array
     */
    public function getJavascriptUris(): array
    {
        return [
            Utils::stripeJavascriptUrl()
        ];
    }


    /**
     * @param Cart $cart
     *
     * @return ExecutionResult
     * @throws PrestaShopException
     */
    public function executeMethod(Cart $cart): ExecutionResult
    {
        $parameters = $this->getPaymentTemplateParameters($cart);
        $metadata = Utils::getPaymentMetadata(Context::getContext()->cookie);
        $javascripts = [
            Utils::stripeJavascriptUrl()
        ];
        return ExecutionResult::render($metadata, 'card-form.tpl', $parameters, $javascripts);
    }


    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->l('Stripe credit card form');
    }

    /**
     * @return string
     */
    public function getShortName(): string
    {
        return $this->l('Credit Card');
    }

    /**
     * @return string
     */
    public function getCTA(): string
    {
        return $this->l('Pay by Credit Card');
    }


    /**
     * @return string
     */
    public function getPaymentTemplateName(): string
    {
        return 'card-form.tpl';
    }

    /**
     * @param Cart $cart
     *
     * @return array
     * @throws PrestaShopException
     */
    protected function getPaymentTemplateParameters(Cart $cart): array
    {
        $link = Context::getContext()->link;
        $validationUrl = $link ->getModuleLink('stripe', 'validation', [ 'type' => $this->getMethodId() ], true);
        $data = [
            'validationUrl' => $validationUrl,
            'stripe_currency' => Utils::getCurrencyCode($cart),
            'stripe_country' => Utils::getStripeCountry(),
            'stripe_amount' => Utils::getCartTotal($cart),
            'stripe_publishable_key' => Utils::getStripePublishableKey(),
            'stripe_payment_request' => Configuration::get(Stripe::STRIPE_PAYMENT_REQUEST),
            'stripe_input_placeholder_color' => Configuration::get(Stripe::INPUT_PLACEHOLDER_COLOR),
            'stripe_button_background_color' => Configuration::get(Stripe::BUTTON_BACKGROUND_COLOR),
            'stripe_button_foreground_color' => Configuration::get(Stripe::BUTTON_FOREGROUND_COLOR),
            'stripe_highlight_color' => Configuration::get(Stripe::HIGHLIGHT_COLOR),
            'stripe_error_color' => Configuration::get(Stripe::ERROR_COLOR),
            'stripe_error_glyph_color' => Configuration::get(Stripe::ERROR_GLYPH_COLOR),
            'stripe_payment_request_foreground_color' => Configuration::get(Stripe::INPUT_TEXT_FOREGROUND_COLOR),
            'stripe_payment_request_background_color' => Configuration::get(Stripe::INPUT_TEXT_BACKGROUND_COLOR),
            'stripe_input_font_family' => Configuration::get(Stripe::INPUT_FONT_FAMILY),
            'stripe_checkout_font_family' => Configuration::get(Stripe::CHECKOUT_FONT_FAMILY),
            'stripe_checkout_font_size' => Configuration::get(Stripe::CHECKOUT_FONT_SIZE),
            'stripe_payment_request_style' => Configuration::get(Stripe::PAYMENT_REQUEST_BUTTON_STYLE),

        ];
        try {
            $data['stripe_client_secret'] = $this->getPaymentIntentSecret($cart);
        } catch (\Throwable $e) {
            $data['stripe_error'] = Tools::displayError("Failed to initialize stripe credit card form");
        }
        return array_merge($data, parent::getPaymentTemplateParameters($cart));
    }

    /**
     * Returns current payment intent associated with cart object
     *
     * This method returns existing payment intent, or creates a new one
     *
     * @param Cart $cart
     *
     * @return string
     *
     * @throws PrestaShopException
     * @throws ApiErrorException
     */
    public function getPaymentIntentSecret(Cart $cart)
    {
        $cookie = Context::getContext()->cookie;
        $metadata = Utils::getPaymentMetadata($cookie);
        if (!$metadata || $metadata->getType() !== PaymentMetadata::TYPE_PAYMENT_INTENT || count($metadata->validate($this, $cart)) > 0 ) {
            $paymentIntent = $this->getStripeApi()->createPaymentIntent($cart);
            $metadata = PaymentMetadata::createForPaymentIntent($this->getMethodId(), $cart, $paymentIntent);
            Utils::savePaymentMetadata($cookie, $metadata);
        }
        return $metadata->getSecret();
    }

    /**
     * @return string
     */
    public function getDocLink(): string
    {
        return "https://docs.stripe.com/payments/payment-card-element-comparison";
    }
}