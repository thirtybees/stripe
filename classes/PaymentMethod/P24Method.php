<?php

namespace StripeModule\PaymentMethod;

use Cart;
use Customer;
use PrestaShopException;
use StripeModule\ExecutionResult;
use StripeModule\PaymentMethod;
use StripeModule\Utils;

class P24Method extends PaymentMethod
{
    const METHOD_ID = 'p24';

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
        return ['EUR', 'PLN'];
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
        return static::ALL;
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
        return $this->startRedirectPaymentFlow($cart, \Stripe\PaymentMethod::TYPE_P24,
            [
                'billing_details' => [
                    'name' => Utils::getCustomerName($cart),
                    'email' => $customer->email
                ],
            ]
        );
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->l('P24');
    }


}