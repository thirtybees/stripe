<?php

namespace ThirtybeesStripe\Util;

use ThirtybeesStripe\StripeObject;

abstract class Util
{
    private static $isMbstringAvailable = null;
    private static $isHashEqualsAvailable = null;

    /**
     * Whether the provided array (or other) is a list rather than a dictionary.
     *
     * @param array|mixed $array
     * @return boolean True if the given object is a list.
     */
    public static function isList($array)
    {
        if (!is_array($array)) {
            return false;
        }

      // TODO: generally incorrect, but it's correct given Stripe's response
        foreach (array_keys($array) as $k) {
            if (!is_numeric($k)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Recursively converts the PHP Stripe object to an array.
     *
     * @param array $values The PHP Stripe object to convert.
     * @return array
     */
    public static function convertStripeObjectToArray($values)
    {
        $results = array();
        foreach ($values as $k => $v) {
            // FIXME: this is an encapsulation violation
            if ($k[0] == '_') {
                continue;
            }
            if ($v instanceof StripeObject) {
                $results[$k] = $v->__toArray(true);
            } elseif (is_array($v)) {
                $results[$k] = self::convertStripeObjectToArray($v);
            } else {
                $results[$k] = $v;
            }
        }
        return $results;
    }

    /**
     * Converts a response from the Stripe API to the corresponding PHP object.
     *
     * @param array $resp The response from the Stripe API.
     * @param array $opts
     * @return StripeObject|array
     */
    public static function convertToStripeObject($resp, $opts)
    {
        $types = array(
            'account'             => 'ThirtybeesStripe\\Account',
            'alipay_account'      => 'ThirtybeesStripe\\AlipayAccount',
            'apple_pay_domain'    => 'ThirtybeesStripe\\ApplePayDomain',
            'bank_account'        => 'ThirtybeesStripe\\BankAccount',
            'balance_transaction' => 'ThirtybeesStripe\\BalanceTransaction',
            'card'                => 'ThirtybeesStripe\\Card',
            'charge'              => 'ThirtybeesStripe\\Charge',
            'country_spec'        => 'ThirtybeesStripe\\CountrySpec',
            'coupon'              => 'ThirtybeesStripe\\Coupon',
            'customer'            => 'ThirtybeesStripe\\Customer',
            'dispute'             => 'ThirtybeesStripe\\Dispute',
            'list'                => 'ThirtybeesStripe\\Collection',
            'invoice'             => 'ThirtybeesStripe\\Invoice',
            'invoiceitem'         => 'ThirtybeesStripe\\InvoiceItem',
            'event'               => 'ThirtybeesStripe\\Event',
            'file'                => 'ThirtybeesStripe\\FileUpload',
            'token'               => 'ThirtybeesStripe\\Token',
            'transfer'            => 'ThirtybeesStripe\\Transfer',
            'transfer_reversal'   => 'ThirtybeesStripe\\TransferReversal',
            'order'               => 'ThirtybeesStripe\\Order',
            'order_return'        => 'ThirtybeesStripe\\OrderReturn',
            'payout'              => 'ThirtybeesStripe\\Payout',
            'plan'                => 'ThirtybeesStripe\\Plan',
            'product'             => 'ThirtybeesStripe\\Product',
            'recipient'           => 'ThirtybeesStripe\\Recipient',
            'recipient_transfer'  => 'ThirtybeesStripe\\RecipientTransfer',
            'refund'              => 'ThirtybeesStripe\\Refund',
            'sku'                 => 'ThirtybeesStripe\\SKU',
            'source'              => 'ThirtybeesStripe\\Source',
            'subscription'        => 'ThirtybeesStripe\\Subscription',
            'subscription_item'   => 'ThirtybeesStripe\\SubscriptionItem',
            'three_d_secure'      => 'ThirtybeesStripe\\ThreeDSecure',
            'fee_refund'          => 'ThirtybeesStripe\\ApplicationFeeRefund',
            'bitcoin_receiver'    => 'ThirtybeesStripe\\BitcoinReceiver',
            'bitcoin_transaction' => 'ThirtybeesStripe\\BitcoinTransaction',
        );
        if (self::isList($resp)) {
            $mapped = array();
            foreach ($resp as $i) {
                array_push($mapped, self::convertToStripeObject($i, $opts));
            }
            return $mapped;
        } elseif (is_array($resp)) {
            if (isset($resp['object']) && is_string($resp['object']) && isset($types[$resp['object']])) {
                $class = $types[$resp['object']];
            } else {
                $class = 'ThirtybeesStripe\\StripeObject';
            }
            return $class::constructFrom($resp, $opts);
        } else {
            return $resp;
        }
    }

    /**
     * @param string|mixed $value A string to UTF8-encode.
     *
     * @return string|mixed The UTF8-encoded string, or the object passed in if
     *    it wasn't a string.
     */
    public static function utf8($value)
    {
        if (self::$isMbstringAvailable === null) {
            self::$isMbstringAvailable = function_exists('mb_detect_encoding');

            if (!self::$isMbstringAvailable) {
                trigger_error("It looks like the mbstring extension is not enabled. " .
                    "UTF-8 strings will not properly be encoded. Ask your system " .
                    "administrator to enable the mbstring extension, or write to " .
                    "support@stripe.com if you have any questions.", E_USER_WARNING);
            }
        }

        if (is_string($value) && self::$isMbstringAvailable && mb_detect_encoding($value, "UTF-8", true) != "UTF-8") {
            return utf8_encode($value);
        } else {
            return $value;
        }
    }

    /**
     * Compares two strings for equality. The time taken is independent of the
     * number of characters that match.
     *
     * @param string $a one of the strings to compare.
     * @param string $b the other string to compare.
     * @return bool true if the strings are equal, false otherwise.
     */
    public static function secureCompare($a, $b)
    {
        if (self::$isHashEqualsAvailable === null) {
            self::$isHashEqualsAvailable = function_exists('hash_equals');
        }

        if (self::$isHashEqualsAvailable) {
            return hash_equals($a, $b);
        } else {
            if (strlen($a) != strlen($b)) {
                return false;
            }

            $result = 0;
            for ($i = 0; $i < strlen($a); $i++) {
                $result |= ord($a[$i]) ^ ord($b[$i]);
            }
            return ($result == 0);
        }
    }
}
