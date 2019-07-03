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
use ThirtyBeesStripe\Stripe\PaymentIntent;
use Translate;
use Currency;
use Stripe;

if (!defined('_TB_VERSION_')) {
    return;
}

/**
 * Class Utils
 */
class Utils
{
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
        return (int)($amount * 100);
    }

    /**
     * Return currency code for given cart
     *
     * @param Cart $cart
     * @return string
     * @throws \PrestaShopException
     */
    public static function getCurrencyCode(Cart $cart)
    {
        $currency = new Currency((int)$cart->id_currency);
        return mb_strtolower($currency->iso_code);
    }

    /**
     * Returns stripe session ID from cookie
     *
     * Session information is stored in variable stripeSession with this format
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
    public static function getSessionFromCookie(Cookie $cookie, Cart $cart)
    {
        // check if cookie contains stripe session variable
        if (!isset($cookie->stripeSession)) {
            return null;
        }

        $arr = explode(':', $cookie->stripeSession);

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
     * Saves stripe session into cookie
     *
     * Session information is stored in variable stripeSession with this format
     *
     * <timestamp>:<cartId>:<cartTotal>:<sessionId>
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
        $now = time();
        $cartId = (int)$cart->id;
        $total = static::getCartTotal($cart);
        $cookie->stripeSession = $now . ':' . $cartId . ':' . $total . ':' .$sessionId;
    }

    /**
     * Removes stripe session information from cookie
     *
     * @param Cookie $cookie
     */
    public static function removeSessionFromCookie(Cookie $cookie)
    {
        if (isset($cookie->stripeSession)) {
           unset($cookie->stripeSession);
        }
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
        if ($status === PaymentIntent::STATUS_SUCCEEDED) {
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
     * @param mixed $paymentMethodDetails payment method details returned by stripe
     * @return int
     */
    public static function getCardLastDigits($paymentMethodDetails)
    {
        if ($paymentMethodDetails && isset($paymentMethodDetails->type) && $paymentMethodDetails->type === 'card') {
            return (int)$paymentMethodDetails->card->last4;
        }
        return 0;
    }

}
