<?php

namespace StripeModule\PaymentMethod;

use Cart;
use PrestaShopException;
use Stripe\Exception\ApiErrorException;
use StripeModule\ExecutionResult;
use StripeModule\PaymentMetadata;
use StripeModule\PaymentMethod;

class BlikMethod extends PaymentMethod
{
    const METHOD_ID = 'blik';

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
        return [ 'PLN' ];
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
        try {
            $api = $this->getStripeApi();
            $session = $api->createCheckoutSession(
                $cart,
                $this->getMethodId(),
                [\Stripe\PaymentMethod::TYPE_BLIK],
                []
            );
            $metadata = PaymentMetadata::createForSession($this->getMethodId(), $cart, $session);
            return ExecutionResult::redirect($metadata, $session->url);
        } catch (ApiErrorException $e) {
            return $this->handleApiException($e);
        }
    }


    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->l('Blik', 'stripe');
    }
}