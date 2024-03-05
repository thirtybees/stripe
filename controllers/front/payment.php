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

if (!defined('_TB_VERSION_')) {
    exit;
}

use StripeModule\ExecutionResultProcessor;
use StripeModule\PaymentMetadata;
use StripeModule\PaymentMethod;
use StripeModule\Utils;

/**
 * Class StripePaymentModuleFrontController
 */
class StripePaymentModuleFrontController extends ModuleFrontController implements ExecutionResultProcessor
{
    /** @var Stripe $module */
    public $module;

    /**
     * StripePaymentModuleFrontController constructor.
     *
     * @throws PrestaShopException
     */
    public function __construct()
    {
        parent::__construct();

        $this->ssl = Tools::usingSecureMode();
    }

    /**
     * @throws PrestaShopException
     */
    public function initContent()
    {
        if (!Module::isEnabled('stripe')) {
            Tools::redirect($this->getCheckoutUrl());
        }

        if (! Utils::hasValidConfiguration()) {
            Tools::redirect($this->getCheckoutUrl());
        }

        $cart = $this->context->cart;
        if (!$cart->id_customer || !$cart->id_address_delivery || !$cart->id_address_invoice || !$this->module->active) {
            Tools::redirect($this->getCheckoutUrl());
        }

        $customer = new Customer($cart->id_customer);
        if (!Validate::isLoadedObject($customer)) {
            Tools::redirect($this->getCheckoutUrl());
        }

        parent::initContent();
        $methodId = Tools::getValue('method');
        if (! $methodId) {
            $this->processError(Tools::displayError('Payment method parameter not provided'));
        }

        $repository = $this->module->getPaymentMethodsRepository();
        $paymentMethod = $repository->getMethod($methodId);
        if ($paymentMethod) {
            $this->initPaymentMethod($paymentMethod, $cart, $customer);
        } else {
            $this->processError(sprintf(Tools::displayError('Payment method %s is not available'), $methodId));
        }
    }

    /**
     * @param PaymentMethod $method
     * @param Cart $cart
     * @param Customer $customer
     *
     * @return void
     * @throws PrestaShopException
     */
    protected function initPaymentMethod(PaymentMethod $method, Cart $cart, Customer $customer)
    {
        $errors = $method->validateMethod($cart);
        if ($errors) {
            $this->processErrors($errors);
            return;
        }
        $method
            ->executeMethod($cart)
            ->processResult($this);
    }

    /**
     * @return string
     * @throws PrestaShopException
     */
    protected function getCheckoutUrl()
    {
        return Context::getContext()->link->getPageLink('order', null, null, 'step=3');
    }

    /**
     * @param PaymentMetadata $metadata
     * @param string $redirectUrl
     *
     * @return void
     * @throws PrestaShopException
     */
    public function processRedirect(PaymentMetadata $metadata, string $redirectUrl)
    {
        static::savePaymentMetadata($metadata);
        Tools::redirect($redirectUrl);
    }

    /**
     * @param PaymentMetadata $metadata
     * @param string $template
     * @param array $params
     * @param array $js
     * @param array $css
     *
     * @return void
     * @throws PrestaShopException
     */
    public function processRender(PaymentMetadata $metadata, string $template, array $params, array $js, array $css)
    {
        static::savePaymentMetadata($metadata);

        foreach ($js as $jsFile) {
            $this->addJS($jsFile);
        }
        foreach ($css as $cssFile) {
            $this->addCSS($cssFile);
        }

        $this->context->smarty->assign($params);
        $this->setTemplate($template);
    }


    /**
     * @param array $errors
     *
     * @return void
     * @throws PrestaShopException
     */
    public function processErrors(array $errors)
    {
        $orderProcess = Configuration::get('PS_ORDER_PROCESS_TYPE') ? 'order-opc' : 'order';
        $this->context->smarty->assign('orderLink', $this->context->link->getPageLink($orderProcess, true));
        $this->errors = $errors;
        $this->setTemplate('error.tpl');
    }

    /**
     * @param PaymentMetadata $metadata
     *
     * @return void
     */
    private function savePaymentMetadata(PaymentMetadata $metadata)
    {
        $cookie = Context::getContext()->cookie;
        Utils::savePaymentMetadata($cookie, $metadata);
    }

    /**
     * @param string $error
     *
     * @return void
     * @throws PrestaShopException
     */
    protected function processError(string $error)
    {
        $this->processErrors([ $error ]);
    }
}
