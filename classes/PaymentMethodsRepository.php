<?php

namespace StripeModule;


use Cart;
use PrestaShopException;
use RuntimeException;

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
            $code = lcfirst(Utils::camelize(str_replace('.php', '', $name)));
            $className = '\StripeModule\PaymentMethod\\' . Utils::camelize($code);
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
        return $methods;
    }

}