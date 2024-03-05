<?php

namespace StripeModule;

use Cart;
use PrestaShopException;
use Stripe\PaymentIntent;
use Tools;

class PaymentMetadata
{
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
    private string $paymentIntentId;

    /**
     * @var string
     */
    private string $paymentIntentClientSecret;

    /**
     * @param string $methodId
     * @param int $cartId
     * @param int $timestamp
     * @param float $total
     * @param string $paymentIntentId
     * @param string $paymentIntentClientSecret
     */
    public function __construct(
        string $methodId,
        int $cartId,
        int $timestamp,
        float $total,
        string $paymentIntentId,
        string $paymentIntentClientSecret
    )
    {
        $this->methodId = $methodId;
        $this->cartId = $cartId;
        $this->timestamp = $timestamp;
        $this->total = $total;
        $this->paymentIntentId = $paymentIntentId;
        $this->paymentIntentClientSecret = $paymentIntentClientSecret;
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
    public static function create(string $methodId, Cart $cart, PaymentIntent $paymentIntent)
    {
        return new static(
            $methodId,
            (int)$cart->id,
            time(),
            Utils::getCartTotal($cart),
            $paymentIntent->id,
            $paymentIntent->client_secret
        );
    }

    /**
     * @return string
     */
    public function serialize(): string
    {
        return implode(':', [
            $this->methodId . ':' .
            $this->timestamp . ':' .
            $this->cartId . ':' .
            $this->total . ':' .
            $this->paymentIntentId . ':' .
            $this->paymentIntentClientSecret
        ]);
    }

    /**
     * @param string $input
     *
     * @return static|null
     */
    public static function deserialize(string $input): ?PaymentMetadata
    {
        $parts = explode(':', $input);
        if (count($parts) === 6) {
            $methodId = (string)$parts[0];
            $timestamp = (int)$parts[1];
            $cartId = (int)$parts[2];
            $total = (int)$parts[3];
            $paymentIntentId = (string)$parts[4];
            $paymentIntentClientSecret = (string)$parts[4];
            if ($methodId && $timestamp && $cartId && $total && $paymentIntentId && $paymentIntentClientSecret) {
                return new static(
                    $methodId,
                    $cartId,
                    $timestamp,
                    $total,
                    $paymentIntentId,
                    $paymentIntentClientSecret
                );
            }
        }
        return null;
    }

    /**
     * @return string
     */
    public function getPaymentIntentId(): string
    {
        return $this->paymentIntentId;
    }

    /**
     * @return string
     */
    public function getPaymentIntentClientSecret(): string
    {
        return $this->paymentIntentClientSecret;
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

        return $errors;
    }

}