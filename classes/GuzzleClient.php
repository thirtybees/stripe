<?php
/**
 * Copyright (C) 2017-2018 thirty bees
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
 * @copyright 2017-2018 thirty bees
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

namespace StripeModule;

if (!defined('_TB_VERSION_')) {
    return;
}

use GuzzleHttp\Client;
use GuzzleHttp\Exception\BadResponseException;
use ThirtyBeesStripe\Stripe\Error;
use ThirtyBeesStripe\Stripe\HttpClient\ClientInterface;

/**
 * Class GuzzleClient
 *
 * @package ThirtyBeesStripe\HttpClient
 *
 * @since   1.0.0
 */
class GuzzleClient implements ClientInterface
{
    const DEFAULT_TIMEOUT = 80;
    const DEFAULT_CONNECT_TIMEOUT = 30;
    /** @var GuzzleClient $instance */
    private static $instance;
    /** @var array|callable|null $defaultOptions */
    protected $defaultOptions;
    /** @var int $timeout */
    private $timeout = self::DEFAULT_TIMEOUT;
    /** @var int $connectTimeout */
    private $connectTimeout = self::DEFAULT_CONNECT_TIMEOUT;

    /**
     * GuzzleClient constructor.
     *
     * Pass in a callable to $defaultOptions that returns an array of CURLOPT_* values to start
     * off a request with, or an flat array with the same format used by curl_setopt_array() to
     * provide a static set of options. Note that many options are overridden later in the request
     * call, including timeouts, which can be set via setTimeout() and setConnectTimeout().
     *
     * Note that request() will silently ignore a non-callable, non-array $defaultOptions, and will
     * throw an exception if $defaultOptions returns a non-array value.
     *
     * @param array|callable|null $defaultOptions
     */
    public function __construct($defaultOptions = null)
    {
        $this->defaultOptions = $defaultOptions;
    }

    /**
     * @return GuzzleClient
     *
     * @since 1.0.0
     */
    public static function instance()
    {
        if (!self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * @return array|callable|null
     *
     * @since 1.0.0
     */
    public function getDefaultOptions()
    {
        return $this->defaultOptions;
    }

    /**
     * @return int
     *
     * @since 1.0.0
     */
    public function getTimeout()
    {
        return $this->timeout;
    }

    /**
     * @param int $seconds
     *
     * @return $this
     *
     * @since 1.0.0
     */
    public function setTimeout($seconds)
    {
        $this->timeout = (int) max($seconds, 0);

        return $this;
    }

    /**
     * @return int
     *
     * @since 1.0.0
     */
    public function getConnectTimeout()
    {
        return $this->connectTimeout;
    }

    /**
     * @param int $seconds
     *
     * @return $this
     *
     * @since 1.0.0
     */
    public function setConnectTimeout($seconds)
    {
        $this->connectTimeout = (int) max($seconds, 0);

        return $this;
    }

    /**
     * @param string  $method  The HTTP method being used
     * @param string  $absUrl  The URL being requested, including domain and protocol
     * @param array   $headers Headers to be used in the request (full strings, not KV pairs)
     * @param array   $params  KV pairs for parameters. Can be nested for arrays and hashes
     * @param boolean $hasFile Whether or not $params references a file (via an @ prefix or
     *                         CurlFile)
     *
     * @return array & Error\ApiConnection
     * @throws Error\Api & Error\ApiConnection
     * @throws Error\ApiConnection
     */
    public function request($method, $absUrl, $headers, $params, $hasFile)
    {
        $method = strtoupper($method);
        $requestHeaders = [];
        foreach ($headers as $header) {
            $requestHeader = explode(': ', $header, 2);
            if (is_array($requestHeader) && count($requestHeader) === 2) {
                $requestHeaders[$requestHeader[0]] = $requestHeader[1];
            }
        }

        // By default for large request body sizes (> 1024 bytes), cURL will
        // send a request without a body and with a `Expect: 100-continue`
        // header, which gives the server a chance to respond with an error
        // status code in cases where one can be determined right away (say
        // on an authentication problem for example), and saves the "large"
        // request body from being ever sent.
        //
        // Unfortunately, the bindings don't currently correctly handle the
        // success case (in which the server sends back a 100 CONTINUE), so
        // we'll error under that condition. To compensate for that problem
        // for the time being, override cURL's behavior by simply always
        // sending an empty `Expect:` header.
        $requestHeaders['Expect'] = '';

        // Add Stripe API version since it defaults to using very new versions
        // that don't work
        $requestHeaders['Stripe-Version'] = '2020-08-27';

        $options = [
            'headers' => $requestHeaders,
        ];

        if ($method === 'GET') {
            if ($hasFile) {
                throw new Error\Api(
                    "Issuing a GET request with a file parameter"
                );
            }
            if (count($params) > 0) {
                $encoded = self::encode($params);
                $absUrl = "$absUrl?$encoded";
            }
        } elseif ($method === 'DELETE') {
            if (count($params) > 0) {
                $encoded = self::encode($params);
                $absUrl = "$absUrl?$encoded";
            }
        } elseif ($method === 'POST') {
            if (!$hasFile) {
                $options['body'] = self::encode($params);
            }
        } else {
            throw new Error\Api("Unrecognized method $method");
        }


        $guzzle = new Client([
            'verify'      => _PS_TOOL_DIR_.'cacert.pem',
            'timeout'     => 20,
        ]);
        try {
            $response = $guzzle->request($method, $absUrl, $options);
            $rbody = $response->getBody();
            $rcode = $response->getStatusCode();
            $rheaders = [];
            foreach ($response->getHeaders() as $name => $values) {
                if (is_array($values)) {
                    $rheaders[$name] = implode(', ', $values);
                } elseif (is_string($values)) {
                    $rheaders[$name] = $values;
                }
            }
        } catch (BadResponseException $e) {
            $headers = [];
            foreach ($e->getResponse()->getHeaders() as $name => $values) {
                if (is_array($values)) {
                    $headers[$name] = implode(', ', $values);
                } elseif (is_string($values)) {
                    $headers[$name] = $values;
                }
            }
            $message = 'Could not connect with Stripe';
            try {
                $json = json_decode((string)$e->getResponse()->getBody(), true);
                if (isset($json['error']['message'])) {
                    $message = $json['error']['message'];
                }
            } catch (\Exception $ignored) {}
            throw new Error\ApiConnection(
                $message,
                $e->getResponse()->getStatusCode(),
                (string) $e->getResponse()->getBody(),
                json_encode((string) $e->getResponse()->getBody()),
                $headers
            );
        } catch (\Exception $e) {
            throw new Error\ApiConnection('Could not connect with Stripe: ' . $e);
        }

        return array($rbody, $rcode, $rheaders);
    }

    /**
     * @param array       $arr    A map of param keys to values.
     * @param string|null $prefix
     *
     * Only public for testability, should not be called outside of CurlClient
     *
     * @return string A querystring, essentially.
     */
    public static function encode($arr, $prefix = null)
    {
        if (!is_array($arr)) {
            return $arr;
        }

        $r = array();
        foreach ($arr as $k => $v) {
            if (is_null($v)) {
                continue;
            }

            if ($prefix) {
                if ($k !== null && (!is_int($k) || is_array($v))) {
                    $k = $prefix."[".$k."]";
                } else {
                    $k = $prefix."[]";
                }
            }

            if (is_array($v)) {
                $enc = self::encode($v, $k);
                if ($enc) {
                    $r[] = $enc;
                }
            } else {
                $r[] = urlencode($k)."=".urlencode($v);
            }
        }

        return implode("&", $r);
    }
}
