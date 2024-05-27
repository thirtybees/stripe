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

class WeChatPayMethod extends PaymentMethod
{
    const METHOD_ID = 'wechat_pay';

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
            'AUD', 'CAD', 'CNY', 'EUR', 'GBP', 'HKD', 'JPY', 'SGD', 'USD', 'DKK',
            'NOK', 'SEK', 'CHF'
        ];
    }


    /**
     * @return string[]
     */
    protected function getAllowedAccountCountries(): array
    {
        return Utils::getCountriesFromRestriction($this->getCurrencyCountryRestrictions());
    }

    /**
     * @return array[]
     */
    protected function getCurrencyCountryRestrictions(): array
    {
        return [
            'AUD' => ['AU'],
            'CAD' => ['CA'],
            'EUR' => [
                'AT', 'BE', 'CH', 'DE', 'DK', 'ES', 'FI', 'FR', 'IE', 'IT',
                'LU', 'NL', 'NO', 'PT', 'SE',
            ],
            'GBP' => ['GB'],
            'HKD' => ['HK'],
            'JPY' => ['JP'],
            'SGD' => ['SG'],
            'USD' => ['US'],
            'DKK' => ['DK'],
            'NOK' => ['NO'],
            'SEK' => ['SE'],
            'CHF' => ['CH'],
        ];
    }

    /**
     * @return string[]
     */
    protected function getAllowedCustomerCountries(): array
    {
        return [
            'AT', 'AU', 'BE', 'BG', 'CA', 'CH', 'CY', 'CZ', 'DE', 'DK',
            'EE', 'ES', 'FI', 'FR', 'GB', 'GR', 'HK', 'HU', 'IE', 'IT',
            'JP', 'LT', 'LU', 'LV', 'MT', 'NL', 'NO', 'PL', 'PT', 'RO',
            'SE', 'SG', 'SI', 'SK', 'US',
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
        try {
            $api = $this->getStripeApi();
            $session = $api->createCheckoutSession(
                $cart,
                $this->getMethodId(),
                [\Stripe\PaymentMethod::TYPE_WECHAT_PAY],
                ['wechat_pay' => [ 'client' => 'web' ]]
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
        return $this->l('WeChat Pay');
    }
}