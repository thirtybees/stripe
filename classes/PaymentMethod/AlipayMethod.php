<?php

namespace StripeModule\PaymentMethod;

use Cart;
use PrestaShopException;
use Stripe\Exception\ApiErrorException;
use StripeModule\ExecutionResult;
use StripeModule\PaymentMetadata;
use StripeModule\PaymentMethod;

class AlipayMethod extends PaymentMethod
{
    const METHOD_ID = 'alipay';

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
        return ['AUD', 'CAD', 'EUR', 'GBP', 'HKD', 'JPY', 'NZD', 'SGD', 'USD', 'MYR'];
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
        try {
            $api = $this->getStripeApi();
            $session = $api->createCheckoutSession(
                $cart,
                $this->getMethodId(),
                [\Stripe\PaymentMethod::TYPE_ALIPAY],
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
        return $this->l('Alipay');
    }


}