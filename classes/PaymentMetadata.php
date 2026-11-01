<?php

namespace StripeModule;

use Cart;
use PrestaShopException;
use Stripe\Checkout\Session;
use Stripe\PaymentIntent;
use Tools;

class PaymentMetadata
{
    const TYPE_PAYMENT_INTENT = 1;
    const TYPE_SESSION = 2;

    /**
     * @var int
     */
    private int $type;

    /**
     * @var string
     */
    private string $methodId;

    /**
     * @var int
     */
    private int $cartId;

    /**
     * @var int
     */
    private int $timestamp;

    /**
     * Total amount in currency unit
     *
     * @var int
     */
    private int $total;

    /**
     * @var string
     */
    private string $id;

    /**
     * @var string
     */
    private string $secret;

    /**
     * Lowercase ISO code of the cart currency
     *
     * @var string
     */
    private string $currency;

    /**
     * @param int $type
     * @param string $methodId
     * @param int $cartId
     * @param int $timestamp
     * @param float $total
     * @param string $id
     * @param string $secret
     * @param string $currency
     */
    public function __construct(
        int $type,
        string $methodId,
        int $cartId,
        int $timestamp,
        float $total,
        string $id,
        string $secret = '',
        string $currency = ''
    )
    {
        $this->type = $type;
        $this->methodId = $methodId;
        $this->cartId = $cartId;
        $this->timestamp = $timestamp;
        $this->total = $total;
        $this->id = $id;
        $this->secret = $secret;
        $this->currency = mb_strtolower($currency);
    }

    /**
     * @return int
     */
    public function getCartId(): int
    {
        return $this->cartId;
    }

    /**
     * @return int
     */
    public function getTimestamp(): int
    {
        return $this->timestamp;
    }

    /**
     * @return float
     */
    public function getTotal(): float
    {
        return $this->total;
    }

    /**
     * @return string
     */
    public function getMethodId(): string
    {
        return $this->methodId;
    }


    /**
     * @param string $methodId
     * @param Cart $cart
     * @param PaymentIntent $paymentIntent
     *
     * @return PaymentMetadata
     * @throws PrestaShopException
     */
    public static function createForPaymentIntent(string $methodId, Cart $cart, PaymentIntent $paymentIntent)
    {
        return new static(
            static::TYPE_PAYMENT_INTENT,
            $methodId,
            (int)$cart->id,
            time(),
            Utils::getCartTotal($cart),
            $paymentIntent->id,
            $paymentIntent->client_secret,
            Utils::getCurrencyCode($cart)
        );
    }

    /**
     * @param string $methodId
     * @param Cart $cart
     * @param Session $session
     *
     * @return PaymentMetadata
     * @throws PrestaShopException
     */
    public static function createForSession(string $methodId, Cart $cart, Session $session)
    {
        return new static(
            static::TYPE_SESSION,
            $methodId,
            (int)$cart->id,
            time(),
            Utils::getCartTotal($cart),
            $session->id,
            '',
            Utils::getCurrencyCode($cart)
        );
    }

    /**
     * @return string
     */
    public function serialize(): string
    {
        return implode(':', array_values($this->getData()));
    }

    /**
     * @return array
     */
    public function getData(): array
    {
        return [
            'type' => $this->type,
            'methodId' => $this->methodId,
            'ts' => $this->timestamp,
            'cartId' => $this->cartId,
            'cartTotal' => $this->total,
            'id' => $this->id,
            'secret' => $this->secret,
            'currency' => $this->currency,
        ];
    }

    /**
     * @param string $input
     *
     * @return static|null
     */
    public static function deserialize(string $input): ?PaymentMetadata
    {
        $parts = explode(':', $input);
        // 7 parts: metadata stored before the currency was added
        if (count($parts) === 7 || count($parts) === 8) {
            $type = (int)$parts[0];
            $methodId = (string)$parts[1];
            $timestamp = (int)$parts[2];
            $cartId = (int)$parts[3];
            $total = (int)$parts[4];
            $id = (string)$parts[5];
            $secret = (string)$parts[6];
            $currency = isset($parts[7]) ? (string)$parts[7] : '';
            if ($type && $methodId && $timestamp && $cartId && $total && $id) {
                return new static(
                    $type,
                    $methodId,
                    $cartId,
                    $timestamp,
                    $total,
                    $id,
                    $secret,
                    $currency
                );
            }
        }
        return null;
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getSecret(): string
    {
        return $this->secret;
    }

    /**
     * @return string
     */
    public function getCurrency(): string
    {
        return $this->currency;
    }

    /**
     * @param PaymentMethod $method
     * @param Cart $cart
     *
     * @return string[]
     * @throws PrestaShopException
     */
    public function validate(PaymentMethod $method, Cart $cart): array
    {
        $errors = [];

        if ($this->methodId !== $method->getMethodId()) {
            $errors[] = Tools::displayError("Payment method mismatch. Expected " . $this->methodId . ", received " . $method->getMethodId());
        }

        if ($this->timestamp < (time() - 24 * 60 * 60)) {
            $errors[] = Tools::displayError("Expired link");
        }

        if ($this->cartId !== (int)$cart->id) {
            $errors[] = Tools::displayError("Invalid cart");
        }

        $total = Utils::getCartTotal($cart);
        if ($this->total != $total) {
            $errors[] = Tools::displayError("Cart amount has changed");
        }

        if ($this->currency !== '' && $this->currency !== Utils::getCurrencyCode($cart)) {
            $errors[] = Tools::displayError("Cart currency has changed");
        }

        return $errors;
    }

    /**
     * @return int
     */
    public function getType(): int
    {
        return $this->type;
    }

}