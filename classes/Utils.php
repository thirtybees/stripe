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

use Cart;
use Cookie;
use ThirtyBeesStripe\Stripe\Charge;
use ThirtyBeesStripe\Stripe\PaymentIntent;
use Translate;
use Currency;
use Context;
use Db;
use DbQuery;
use Stripe;
use Configuration;

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
    const STRIPE_SESSION = 'stripeSession';
    const STRIPE_PAYMENT_INTENT_ID = 'stripePIID';
    const STRIPE_PAYMENT_INTENT_CLIENT_SECRET = 'stripePICS';

    /**
     * Returns cart total in smallest common currency unit
     *
     * @param Cart $cart
     * @return int
     * @throws \Adapter_Exception
     * @throws \PrestaShopDatabaseException
     * @throws \PrestaShopException
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
        if (in_array(mb_strtolower($currency->iso_code), Stripe::$zeroDecimalCurrencies)) {
            return (int)$amount;
        }
        return (int)round($amount * 100);
    }

    /**
     * Return currency code for given cart
     *
     * @param Cart $cart
     * @return string
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
     * @return mixed
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
     * @return int
     */
    public static function getCardLastDigits(Charge $charge)
    {
        $paymentMethodDetails = $charge->payment_method_details;
        if ($paymentMethodDetails && isset($paymentMethodDetails->type) && $paymentMethodDetails->type === 'card') {
            return (int)$paymentMethodDetails->card->last4;
        }

        if (isset($charge->source['last4'])) {
            return (int)$charge->source['last4'];
        }

        return 0;
    }

    /**
     * Returns stripe object ID from cookie
     *
     * @param Cookie $cookie
     * @param Cart $cart
     * @return mixed|null
     * @throws \Adapter_Exception
     * @throws \PrestaShopDatabaseException
     * @throws \PrestaShopException
     */
    public static function getSessionFromCookie(Cookie $cookie, Cart $cart)
    {
        return static::getFromCookie(static::STRIPE_SESSION, $cookie, $cart);
    }

    /**
     * Saves stripe session id into cookie
     *
     * @param Cookie $cookie
     * @param Cart $cart
     * @param $sessionId
     * @throws \Adapter_Exception
     * @throws \PrestaShopDatabaseException
     * @throws \PrestaShopException
     */
    public static function saveSessionToCookie(Cookie $cookie, Cart $cart, $sessionId)
    {
        static::saveToCookie(static::STRIPE_SESSION, $cookie, $cart, $sessionId);
    }

    /**
     * Returns stripe payment intent ID from cookie
     *
     * @param Cookie $cookie
     * @param Cart $cart
     * @return mixed|null
     * @throws \Adapter_Exception
     * @throws \PrestaShopDatabaseException
     * @throws \PrestaShopException
     */
    public static function getPaymentIntentIdFromCookie(Cookie $cookie, Cart $cart)
    {
        return static::getFromCookie(static::STRIPE_PAYMENT_INTENT_ID, $cookie, $cart);
    }

    /**
     * Returns stripe payment intent client secret from cookie
     *
     * @param Cookie $cookie
     * @param Cart $cart
     * @return mixed|null
     * @throws \Adapter_Exception
     * @throws \PrestaShopDatabaseException
     * @throws \PrestaShopException
     */
    public static function getPaymentIntentClientSecretFromCookie(Cookie $cookie, Cart $cart)
    {
        return static::getFromCookie(static::STRIPE_PAYMENT_INTENT_CLIENT_SECRET, $cookie, $cart);
    }

    /**
     * Saves stripe payment intent information into cookie
     *
     * @param Cookie $cookie
     * @param Cart $cart
     * @param $paymentIntentId
     * @param $clientSecret
     * @throws \Adapter_Exception
     * @throws \PrestaShopDatabaseException
     * @throws \PrestaShopException
     */
    public static function savePaymentIntentToCookie(Cookie $cookie, Cart $cart, $paymentIntentId, $clientSecret)
    {
        static::saveToCookie(static::STRIPE_PAYMENT_INTENT_ID, $cookie, $cart, $paymentIntentId);
        static::saveToCookie(static::STRIPE_PAYMENT_INTENT_CLIENT_SECRET, $cookie, $cart, $clientSecret);
    }

    /**
     * Returns stripe object ID from cookie
     *
     * Session information is stored in variable $key with this format
     *
     * <timestamp>:<cartId>:<cartTotal>:<sessionId>
     *
     * @param Cookie $cookie
     * @param Cart $cart
     * @return mixed|null
     * @throws \Adapter_Exception
     * @throws \PrestaShopDatabaseException
     * @throws \PrestaShopException
     */
    private static function getFromCookie($key, Cookie $cookie, Cart $cart)
    {
        // check if cookie contains stripe session variable
        if (!isset($cookie->{$key})) {
            return null;
        }

        $arr = explode(':', $cookie->{$key});

        if (!$arr) {
            return null;
        }

        // check timestamp expiration
        $ts = (int)array_shift($arr);
        if ($ts < (time() - 24 * 60 * 60)) {
            return null;
        }

        // check cart id
        $cartId = (int)array_shift($arr);
        if ($cartId !== (int)$cart->id) {
            return null;
        }

        // check that cart total hasn't changed
        $sessionTotal = (int)array_shift($arr);
        $total = Utils::getCartTotal($cart);
        if ($sessionTotal != $total) {
            return null;
        }

        return array_shift($arr);
    }

    /**
     * Saves stripe object id into cookie
     *
     * Object information is stored in variable {$key} with this format
     *
     * <timestamp>:<cartId>:<cartTotal>:<sessionId>
     *
     * @param string $key
     * @param Cookie $cookie
     * @param Cart $cart
     * @param $sessionId
     * @throws \Adapter_Exception
     * @throws \PrestaShopDatabaseException
     * @throws \PrestaShopException
     */
    private static function saveToCookie($key, Cookie $cookie, Cart $cart, $sessionId)
    {
        $now = time();
        $cartId = (int)$cart->id;
        $total = static::getCartTotal($cart);
        $cookie->{$key} = $now . ':' . $cartId . ':' . $total . ':' . $sessionId;
    }

    /**
     * Removes stripe objects information from cookie
     *
     * @param Cookie $cookie
     */
    public static function removeFromCookie(Cookie $cookie)
    {
        foreach ([static::STRIPE_SESSION, static::STRIPE_PAYMENT_INTENT_ID, static::STRIPE_PAYMENT_INTENT_CLIENT_SECRET] as $key) {
            if (isset($cookie->{$key})) {
                unset($cookie->{$key});
            }
        }
    }

    /**
     * Return list of stripe countries
     *
     * @return array
     */
    public static function getStripeCountryCodes()
    {
	return ['AU', 'AT', 'BE', 'BG', 'BR', 'CA', 'CY', 'CZ', 'DK', 'EE', 'FI', 'FR', 'DE', 'GR', 'HK', 'IN', 'IE', 'IT', 'JP', 'LV', 'LT', 'LU', 'MY', 'MT', 'MX', 'NL', 'NZ', 'NO', 'PL', 'PT', 'RO', 'SG', 'SK', 'SI', 'ES', 'SE', 'CH', 'GB', 'US'];
    }

    /**
     * Returns list of stripe countries
     *
     * @return array
     * @throws \PrestaShopDatabaseException
     * @throws \PrestaShopException
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
     */
    public static function getStripeCountry()
    {
        $value = Configuration::get(Stripe::ACCOUNT_COUNTRY);
        if (!$value || !in_array($value, static::getStripeCountryCodes())) {
            return 'US';
        }
        return $value;
    }

}
