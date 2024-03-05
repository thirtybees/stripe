<?php

namespace StripeModule\PaymentMethod;

use Address;
use Cart;
use Country;
use PrestaShopException;
use StripeModule\ExecutionResult;
use StripeModule\PaymentMethod;
use StripeModule\Utils;

class SofortMethod extends PaymentMethod
{
    const METHOD_ID = 'sofort';

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
        return ['EUR'];
    }

    /**
     * @return array|string[]
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
        return ['AT', 'BE', 'DE', 'ES', 'IT', 'NL'];
    }

    /**
     * @param Cart $cart
     *
     * @return ExecutionResult
     * @throws PrestaShopException
     */
    public function executeMethod(Cart $cart): ExecutionResult
    {
        $invoiceAddress = new Address((int) $cart->id_address_invoice);
        $country = new Country($invoiceAddress->id_country);

        return $this->startRedirectPaymentFlow($cart, \Stripe\PaymentMethod::TYPE_SOFORT,
            [
                'billing_details' => [
                    'name' => Utils::getCustomerName($cart),
                ],
                'sofort' => [
                    'country' => strtoupper($country->iso_code)
                ]
            ]
        );
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->l('Sofort Banking', 'stripe');
    }

    /**
     * @return bool
     */
    public function requiresWebhook(): bool
    {
        return true;
    }

}