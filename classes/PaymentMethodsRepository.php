<?php

namespace StripeModule;


use Cart;
use Configuration;
use PrestaShopException;
use RuntimeException;
use Stripe;
use StripeModule\PaymentMethod\CheckoutMethod;

class PaymentMethodsRepository
{
    /**
     * @var array<string, PaymentMethod>|null
     */
    private $methods = null;

    /**
     * @var StripeApi
     */
    private StripeApi $stripeApi;

    /**
     * @param StripeApi $stripeApi
     */
    public function __construct(StripeApi $stripeApi)
    {
        $this->stripeApi = $stripeApi;
    }

    /**
     * @param string $methodId
     *
     * @return PaymentMethod|null
     * @throws PrestaShopException
     */
    public function getMethod(string $methodId): ?PaymentMethod
    {
        $methods = $this->getAllMethods();
        if (array_key_exists($methodId, $methods)) {
            return $methods[$methodId];
        }
        return null;
    }


    /**
     * @param Cart $cart
     *
     * @return PaymentMethod[]
     * @throws PrestaShopException
     */
    public function getAvailableMethods(Cart $cart): array
    {
        $available = [];
        foreach ($this->getAllMethods() as $method) {
            if ($method->isAvailable($cart)) {
                $available[] = $method;
            }
        }
        return $available;
    }

    /**
     * @return PaymentMethod[]
     * @throws PrestaShopException
     */
    public function getAllMethods()
    {
        if (is_null($this->methods)) {
            $this->methods = $this->resolveAvailableMethods();

        }
        return $this->methods;
    }


    /**
     * @return PaymentMethod[]
     * @throws PrestaShopException
     */
    private function resolveAvailableMethods()
    {
        $files = glob(__DIR__ . "/PaymentMethod/*.php");
        $methods = [];
        foreach ($files as $path) {
            $name = basename($path);
            if ($name === 'index.php') {
                continue;
            }
            $code = ucfirst(Utils::camelize(str_replace('.php', '', $name)));
            $className = '\StripeModule\PaymentMethod\\' . $code;
            if (!class_exists($className)) {
                throw new RuntimeException("$path does not contain $className");
            }
            /** @var PaymentMethod $instance */
            $instance = new $className($this->stripeApi);
            if (!($instance instanceof PaymentMethod)) {
                throw new RuntimeException("Class $className is not instance of StripeModule\PaymentMethod");
            }
            $methods[$instance->getMethodId()] = $instance;
        }
        return $this->sortMethods($methods);
    }

    /**
     * @param array $methods
     *
     * @return array
     * @throws PrestaShopException
     */
    private function sortMethods(array $methods): array
    {
        $result = [];
        foreach ($this->getConfiguredOrder() as $key) {
            if (isset($methods[$key])) {
                $result[$key] = $methods[$key];
                unset($methods[$key]);
            }
        }

        $restKeys = array_keys($methods);
        if ($restKeys) {
            sort($restKeys);
            foreach ($restKeys as $key) {
                $result[$key] = $methods[$key];
            }
        }
        return $result;
    }

    /**
     * @return array
     * @throws PrestaShopException
     */
    protected function getConfiguredOrder(): array
    {
        $value = (string)Configuration::get(Stripe::ORDER_OF_METHODS);
        if (! $value) {
            return [CheckoutMethod::METHOD_ID];
        }
        return explode('|', $value);
    }

    /**
     * @param array $order
     *
     * @return void
     * @throws PrestaShopException
     */
    public function setMethodsOrder(array $order)
    {
        $order = array_filter($order, function($methodId) {
            return (bool)$this->getMethod($methodId);
        });
        Configuration::updateValue(Stripe::ORDER_OF_METHODS, implode('|', $order));
    }


}