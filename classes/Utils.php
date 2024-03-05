<?php
/**
 * Copyright (C) 2019 thirty bees
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
 * @copyright 2019 thirty bees
 * @license   Academic Free License (AFL 3.0)
 */

namespace StripeModule;

use Address;
use Cart;
use Cookie;
use PrestaShopException;
use Stripe\Charge;
use Stripe\PaymentIntent;
use Translate;
use Currency;
use Context;
use Db;
use DbQuery;
use Stripe;
use Configuration;
use Validate;

if (!defined('_TB_VERSION_')) {
    return;
}

/**
 * Class Utils
 */
class Utils
{
    /**
     * Cookie keys
     */
    const STRIPE_METHOD_METADATA = 'stripeMM';

    /**
     * Returns cart total in smallest common currency unit
     *
     * @param Cart $cart
     * @return int
     * @throws PrestaShopException
     */
    public static function getCartTotal(Cart $cart)
    {
        return static::toCurrencyUnit(new Currency($cart->id_currency), $cart->getOrderTotal());
    }

    /**
     * Converts amount into smallest common currency unit - that's cents for currencies like
     * dollar or euro
     *
     * @param Currency $currency
     * @param float $amount
     * @return int
     */
    public static function toCurrencyUnit(Currency $currency, $amount)
    {
        return static::toCurrencyUnitWithIso($currency->iso_code, $amount);
    }

    /**
     * Converts amount into smallest common currency unit - that's cents for currencies like
     * dollar or euro
     *
     * @param float $amount
     * @return float
     */
    public static function fromCurrencyUnitWithIso(string $currencyIsoCode, $amount)
    {
        return static::isZeroDecimalCurrency($currencyIsoCode)
            ? $amount
            : $amount / 100;
    }

    /**
     * @param Currency $currency
     * @param float $amount
     *
     * @return float
     */
    public static function fromCurrencyUnit(Currency $currency, $amount)
    {
        return static::fromCurrencyUnitWithIso($currency->iso_code, $amount);
    }

    /**
     * Converts amount into smallest common currency unit - that's cents for currencies like
     * dollar or euro
     *
     * @param float $amount
     * @return int
     */
    public static function toCurrencyUnitWithIso(string $currencyIsoCode, $amount)
    {
        if (static::isZeroDecimalCurrency($currencyIsoCode)) {
            return (int)$amount;
        }
        return (int)round($amount * 100);
    }

    /**
     * @param string $currencyIsoCode
     *
     * @return bool
     */
    public static function isZeroDecimalCurrency(string $currencyIsoCode)
    {
        return in_array(mb_strtoupper($currencyIsoCode), static::getZeroDecimalCurrency());
    }


    /**
     * Return currency code for given cart
     *
     * @param Cart $cart
     *
     * @return string
     * @throws PrestaShopException
     */
    public static function getCurrencyCode(Cart $cart)
    {
        $currency = Currency::getCurrencyInstance((int)$cart->id_currency);
        return mb_strtolower($currency->iso_code);
    }

    /**
     * Derive StripeTransaction type from stripe review status
     *
     * @param string $status payment intent status
     * @param StripeReview $stripeReview
     * @return int
     */
    public static function getTransactionType($status, StripeReview $stripeReview)
    {
        if ($status === PaymentIntent::STATUS_PROCESSING) {
            return StripeTransaction::TYPE_CHARGE_PENDING;
        }
        if ($status === PaymentIntent::STATUS_SUCCEEDED || $status === PaymentIntent::STATUS_REQUIRES_CAPTURE) {
            switch ($stripeReview->status) {
                case StripeReview::AUTHORIZED:
                    return StripeTransaction::TYPE_AUTHORIZED;
                case StripeReview::IN_REVIEW:
                    return StripeTransaction::TYPE_IN_REVIEW;
                case StripeReview::CAPTURED:
                    return StripeTransaction::TYPE_CAPTURED;
                default:
                    return StripeTransaction::TYPE_CHARGE;
            }
        }
        return StripeTransaction::TYPE_CHARGE_FAIL;
    }

    /**
     * Returns payment method name
     *
     * @param mixed $paymentMethodDetails payment method details returned by stripe
     *
     * @return string
     */
    public static function getPaymentMethodName($paymentMethodDetails)
    {
        if ($paymentMethodDetails && isset($paymentMethodDetails->type) && $paymentMethodDetails->type === 'card') {
            return sprintf(
                Translate::getModuleTranslation('stripe', 'Stripe: %s', 'validation'),
                Translate::getModuleTranslation('stripe', 'Credit Card', 'validation')
            );
        }
        return Translate::getModuleTranslation('stripe', 'Stripe', 'validation');
    }

    /**
     * Returns last 4 digits of payment
     *
     * @param Charge $charge
     *
     * @return int
     */
    public static function getCardLastDigits(Charge $charge)
    {
        $paymentMethodDetails = $charge->payment_method_details;
        if ($paymentMethodDetails && isset($paymentMethodDetails->type) && $paymentMethodDetails->type === 'card' && isset($paymentMethodDetails->card)) {
            return (int)$paymentMethodDetails->card->last4;
        }

        if (isset($charge->source->last4)) {
            return (int)$charge->source->last4;
        }

        return 0;
    }

    /**
     * @param Cookie $cookie
     *
     * @return PaymentMetadata|null
     */
    public static function getPaymentMetadata(Cookie $cookie)
    {
        $key = static::STRIPE_METHOD_METADATA;
        // check if cookie contains stripe session variable
        if (!isset($cookie->{$key})) {
            return null;
        }
        return PaymentMetadata::deserialize((string)$cookie->{$key});
    }

    /**
     * @param Cookie $cookie
     * @param PaymentMetadata $metadata
     *
     * @return void
     */
    public static function savePaymentMetadata(Cookie $cookie, PaymentMetadata $metadata)
    {
        $key = static::STRIPE_METHOD_METADATA;
        $cookie->{$key} = $metadata->serialize();
    }

    /**
     * Removes stripe objects information from cookie
     *
     * @param Cookie $cookie
     */
    public static function removeFromCookie(Cookie $cookie)
    {
        if (isset($cookie->{static::STRIPE_METHOD_METADATA})) {
            unset($cookie->{static::STRIPE_METHOD_METADATA});
        }
    }

    /**
     * Return list of stripe countries
     *
     * @return array
     */
    public static function getStripeCountryCodes()
    {
	    return [
            'AE', 'AT', 'AU', 'BE', 'BG', 'BR', 'CA', 'CH', 'CY', 'CZ',
            'DE', 'DK', 'EE', 'ES', 'FI', 'FR', 'GB', 'GH', 'GI', 'GR',
            'HK', 'HR', 'HU', 'ID', 'IE', 'IN', 'IT', 'JP', 'KE', 'LI',
            'LT', 'LU', 'LV', 'MT', 'MX', 'MY', 'NG', 'NL', 'NO', 'NZ',
            'PL', 'PT', 'RO', 'SE', 'SG', 'SI', 'SK', 'TH', 'US', 'ZA',
        ];
    }

    /**
     * Returns list of stripe countries
     *
     * @return array
     *
     * @throws PrestaShopException
     */
    public static function getStripeCountries()
    {
        $query = (new DbQuery())
            ->select('c.iso_code AS `code`, COALESCE(cl.name, c.iso_code) AS `name`')
            ->from('country', 'c')
            ->leftJoin('country_lang', 'cl', 'c.id_country = cl.id_country AND cl.id_lang = ' . (int)Context::getContext()->language->id)
            ->where('c.iso_code IN (\'' . implode("','", static::getStripeCountryCodes()) . '\')');
        return Db::getInstance()->executeS($query);
    }

    /**
     * Returns stripe country associated with account
     *
     * @throws PrestaShopException
     */
    public static function getStripeCountry()
    {
        $value = Configuration::get(Stripe::ACCOUNT_COUNTRY);
        if (!$value || !in_array($value, static::getStripeCountryCodes())) {
            return 'US';
        }
        return $value;
    }

    /**
     * @param string $input
     * @param string $delim
     * @param bool $capitalizeFirstLetter
     * @return string
     */
    public static function camelize($input, $delim = '-', $capitalizeFirstLetter = false)
    {
        $exploded_str = explode($delim, $input);
        $exploded_str_camel = array_map('ucwords', $exploded_str);
        $ret = implode('', $exploded_str_camel);
        return $capitalizeFirstLetter ? $ret : lcfirst($ret);
    }

    /**
     * @param Cart $cart
     * @param string $isoCode
     *
     * @return bool
     * @throws PrestaShopException
     */
    public static function checkCurrency(Cart $cart, string $isoCode): bool
    {
        $currencyIsoCode = static::getCurrencyCode($cart);
        $isoCode = strtolower($isoCode);
        return $isoCode === $currencyIsoCode;
    }

    /**
     * @param Cart $cart
     * @param array $allowedCurrencies
     *
     * @return bool
     * @throws PrestaShopException
     */
    public static function checkAllowedCurrency(Cart $cart, array $allowedCurrencies): bool
    {
        foreach ($allowedCurrencies as $isoCode) {
            if (static::checkCurrency($cart, $isoCode)) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param Cart $cart
     *
     * @return void
     * @throws PrestaShopException
     */
    public static function getCustomerName(Cart $cart): string
    {
        $invoiceAddress = new Address((int) $cart->id_address_invoice);
        if (Validate::isLoadedObject($invoiceAddress)) {
            return $invoiceAddress->firstname . ' ' . $invoiceAddress->lastname;
        }
        throw new PrestaShopException("Invoice address not found");
    }

    /**
     * @param string $method
     *
     * @return string
     * @throws PrestaShopException
     */
    public static function getValidationUrl(string $method): string
    {
        $link = Context::getContext()->link;
        return $link->getModuleLink('stripe', 'validation', ['type' => $method]);
    }

    /**
     * @param PaymentIntent $paymentIntent
     *
     * @return string|null
     */
    public static function extractRedirectUrl($paymentIntent)
    {
        if ($paymentIntent && $paymentIntent->status === \Stripe\PaymentIntent::STATUS_REQUIRES_ACTION) {
            $nextAction = $paymentIntent->next_action;
            if (isset($nextAction->redirect_to_url->url)) {
                return $nextAction->redirect_to_url->url;
            }
        }
        return null;
    }

    /**
     * @return string
     */
    public static function stripeJavascriptUrl()
    {
        return 'https://js.stripe.com/v3/';
    }


    /**
     * @return bool
     *
     * @throws PrestaShopException
     */
    public static function hasValidConfiguration()
    {
        if (Configuration::get(Stripe::GO_LIVE)) {
            return (
                Configuration::get(Stripe::SECRET_KEY_LIVE) &&
                Configuration::get(Stripe::PUBLISHABLE_KEY_LIVE)
            );
        } else {
            return (
                Configuration::get(Stripe::SECRET_KEY_TEST) &&
                Configuration::get(Stripe::PUBLISHABLE_KEY_TEST)
            );
        }
    }

    /**
     * @return string
     * @throws PrestaShopException
     */
    public static function getStripePublishableKey()
    {
       return  Configuration::get(Stripe::GO_LIVE)
            ? (string)Configuration::get(Stripe::PUBLISHABLE_KEY_LIVE)
            : (string)Configuration::get(Stripe::PUBLISHABLE_KEY_TEST);
    }

    /**
     * @return string[]
     */
    public static function getAllSupportedCurrencies()
    {
        return [
            'AED', 'AFN', 'ALL', 'AMD', 'ANG', 'AOA', 'ARS', 'AUD', 'AWG', 'AZN',
            'BAM', 'BBD', 'BDT', 'BGN', 'BIF', 'BMD', 'BND', 'BOB', 'BRL', 'BSD',
            'BWP', 'BYN', 'BZD', 'CAD', 'CDF', 'CHF', 'CLP', 'CNY', 'COP', 'CRC',
            'CVE', 'CZK', 'DJF', 'DKK', 'DOP', 'DZD', 'EGP', 'ETB', 'EUR', 'FJD',
            'FKP', 'GBP', 'GEL', 'GIP', 'GMD', 'GNF', 'GTQ', 'GYD', 'HKD', 'HNL',
            'HTG', 'HUF', 'IDR', 'ILS', 'INR', 'ISK', 'JMD', 'JPY', 'KES', 'KGS',
            'KHR', 'KMF', 'KRW', 'KYD', 'KZT', 'LAK', 'LBP', 'LKR', 'LRD', 'LSL',
            'MAD', 'MDL', 'MGA', 'MKD', 'MMK', 'MNT', 'MOP', 'MUR', 'MVR', 'MWK',
            'MXN', 'MYR', 'MZN', 'NAD', 'NGN', 'NIO', 'NOK', 'NPR', 'NZD', 'PAB',
            'PEN', 'PGK', 'PHP', 'PKR', 'PLN', 'PYG', 'QAR', 'RON', 'RSD', 'RUB',
            'RWF', 'SAR', 'SBD', 'SCR', 'SEK', 'SGD', 'SHP', 'SLE', 'SOS', 'SRD',
            'STD', 'SZL', 'THB', 'TJS', 'TOP', 'TRY', 'TTD', 'TWD', 'TZS', 'UAH',
            'UGX', 'USD', 'UYU', 'UZS', 'VND', 'VUV', 'WST', 'XAF', 'XCD', 'XOF',
            'XPF', 'YER', 'ZAR', 'ZMW'
            ];
    }

    /**
     * @return string[]
     */
    public static function getZeroDecimalCurrency()
    {
        return [
            'BIF', 'CLP', 'DJF', 'GNF', 'JPY', 'KMF', 'KRW', 'MGA', 'PYG', 'RWF',
            'UGX', 'VND', 'VUV', 'XAF', 'XOF', 'XPF',
        ];
    }

}
