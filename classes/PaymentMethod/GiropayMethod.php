<?php

namespace StripeModule\PaymentMethod;

use Cart;
use PrestaShopException;
use StripeModule\ExecutionResult;
use StripeModule\PaymentMethod;
use StripeModule\Utils;

class GiropayMethod extends PaymentMethod
{
    const METHOD_ID = 'giropay';

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
     * @param Cart $cart
     *
     * @return ExecutionResult
     * @throws PrestaShopException
     */
    public function executeMethod(Cart $cart): ExecutionResult
    {
        return $this->startRedirectPaymentFlow($cart, \Stripe\PaymentMethod::TYPE_GIROPAY,
            [
                'billing_details' => [
                    'name' => Utils::getCustomerName($cart),
                ],
            ]
        );
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->l('Giropay', 'stripe');
    }

}