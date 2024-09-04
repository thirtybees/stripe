<?php

namespace StripeModule\PaymentMethod;

use Address;
use Cart;
use Country;
use Customer;
use PrestaShopException;
use StripeModule\ExecutionResult;
use StripeModule\PaymentMethod;

class KlarnaMethod extends PaymentMethod
{
    const METHOD_ID = 'klarna';

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
        return [
            'EUR', 'DKK', 'GBP', 'NOK', 'SEK', 'USD', 'CZK', 'RON', 'AUD', 'NZD',
            'CAD', 'PLN', 'CHF'
        ];
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
        return [
            'AU', 'AT', 'BE', 'CA', 'CZ', 'CH', 'DE', 'DK', 'ES', 'FI',
            'FR', 'GB', 'GR', 'IE', 'IT', 'NL', 'NO', 'NZ', 'PL', 'PT',
            'RO', 'SE', 'US',
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
        $customer = new Customer($cart->id_customer);
        $invoiceAddress = new Address((int) $cart->id_address_invoice);
        $country = new Country($invoiceAddress->id_country);
        return $this->startRedirectPaymentFlow($cart, \Stripe\PaymentMethod::TYPE_KLARNA,
            [
                'billing_details' => [
                    'email' => $customer->email,
                    'address' => [
                        'country' => strtoupper($country->iso_code)
                    ]
                ],
            ]
        );
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->l('Klarna');
    }

}