<?php
/**
 * Copyright (C) 2017 thirty bees
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.md
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@thirtybees.com so we can send you a copy immediately.
 *
 * @author    thirty bees <modules@thirtybees.com>
 * @copyright 2017 thirty bees
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

spl_autoload_register(
    function ($class) {
        if (in_array($class, [
            'StripeModule\\StripeTransaction',
        ])) {
            // project-specific namespace prefix
            $prefix = 'StripeModule\\';

            // base directory for the namespace prefix
            $baseDir = __DIR__.'/';

            // does the class use the namespace prefix?
            $len = strlen($prefix);
            if (strncmp($prefix, $class, $len) !== 0) {
                // no, move to the next registered autoloader
                return;
            }

            // get the relative class name
            $relativeClass = substr($class, $len);

            // replace the namespace prefix with the base directory, replace namespace
            // separators with directory separators in the relative class name, append
            // with .php
            $file = $baseDir.str_replace('\\', '/', $relativeClass).'.php';

            // if the file exists, require it
            require $file;
        }

        if (in_array($class, [
            'ThirtybeesStripe\\Error\\Api',
            'ThirtybeesStripe\\Error\\ApiConnection',
            'ThirtybeesStripe\\Error\\Authentication',
            'ThirtybeesStripe\\Error\\Base',
            'ThirtybeesStripe\\Error\\Card',
            'ThirtybeesStripe\\Error\\InvalidRequest',
            'ThirtybeesStripe\\Error\\Permission',
            'ThirtybeesStripe\\Error\\RateLimit',
            'ThirtybeesStripe\\HttpClient\\ClientInterface',
            'ThirtybeesStripe\\HttpClient\\CurlClient',
            'ThirtybeesStripe\\HttpClient\\GuzzleClient',
            'ThirtybeesStripe\\Util\\AutoPagingIterator',
            'ThirtybeesStripe\\Util\\RequestOptions',
            'ThirtybeesStripe\\Util\\Set',
            'ThirtybeesStripe\\Util\\Util',
            'ThirtybeesStripe\\Account',
            'ThirtybeesStripe\\AlipayAccount',
            'ThirtybeesStripe\\ApiRequestor',
            'ThirtybeesStripe\\ApiResource',
            'ThirtybeesStripe\\ApiResponse',
            'ThirtybeesStripe\\ApplePayDomain',
            'ThirtybeesStripe\\ApplicationFee',
            'ThirtybeesStripe\\ApplicationFeeRefund',
            'ThirtybeesStripe\\AttachedObject',
            'ThirtybeesStripe\\Balance',
            'ThirtybeesStripe\\BalanceTransaction',
            'ThirtybeesStripe\\BankAccount',
            'ThirtybeesStripe\\BitcoinReceiver',
            'ThirtybeesStripe\\BitcoinTransaction',
            'ThirtybeesStripe\\Card',
            'ThirtybeesStripe\\Charge',
            'ThirtybeesStripe\\Collection',
            'ThirtybeesStripe\\CountrySpec',
            'ThirtybeesStripe\\Coupon',
            'ThirtybeesStripe\\Customer',
            'ThirtybeesStripe\\Dispute',
            'ThirtybeesStripe\\Event',
            'ThirtybeesStripe\\ExternalAccount',
            'ThirtybeesStripe\\FileUpload',
            'ThirtybeesStripe\\Invoice',
            'ThirtybeesStripe\\InvoiceItem',
            'ThirtybeesStripe\\JsonSerializable',
            'ThirtybeesStripe\\Order',
            'ThirtybeesStripe\\OrderReturn',
            'ThirtybeesStripe\\Plan',
            'ThirtybeesStripe\\Product',
            'ThirtybeesStripe\\Recipient',
            'ThirtybeesStripe\\Refund',
            'ThirtybeesStripe\\SingletonApiResource',
            'ThirtybeesStripe\\SKU',
            'ThirtybeesStripe\\Source',
            'ThirtybeesStripe\\Stripe',
            'ThirtybeesStripe\\StripeObject',
            'ThirtybeesStripe\\Subscription',
            'ThirtybeesStripe\\SubscriptionItem',
            'ThirtybeesStripe\\ThreeDSecure',
            'ThirtybeesStripe\\Token',
            'ThirtybeesStripe\\Transfer',
            'ThirtybeesStripe\\TransferReversal',
            'ThirtybeesStripe\\TransferReversal',
        ])) {
            // project-specific namespace prefix
            $prefix = 'ThirtybeesStripe\\';

            // base directory for the namespace prefix
            $baseDir = __DIR__.'/';

            // does the class use the namespace prefix?
            $len = strlen($prefix);
            if (strncmp($prefix, $class, $len) !== 0) {
                // no, move to the next registered autoloader
                return;
            }

            // get the relative class name
            $relativeClass = substr($class, $len);

            // replace the namespace prefix with the base directory, replace namespace
            // separators with directory separators in the relative class name, append
            // with .php
            $file = $baseDir.str_replace('\\', '/', $relativeClass).'.php';

            // if the file exists, require it
            require $file;
        }
    }
);
