<?php
/**
 * Copyright (C) 2017-2024 thirty bees
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.md
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@thirtybees.com so we can send you a copy immediately.
 *
 * @author    thirty bees <modules@thirtybees.com>
 * @copyright 2017-2024 thirty bees
 * @license   https://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

use StripeModule\PaymentMethodsRepository;
use StripeModule\PaymentProcessor;
use StripeModule\StripeReview;
use StripeModule\StripeTransaction;
use StripeModule\StripeApi;
use StripeModule\Utils;
use StripeModule\Logger\FileLogger;

if (!defined('_TB_VERSION_')) {
    return;
}

require_once __DIR__.'/vendor/autoload.php';

/**
 * Class Stripe
 */
class Stripe extends PaymentModule
{
    const MENU_SETTINGS = 'settings';
    const MENU_TRANSACTIONS = 'transactions';

    // global settings
    const ACCOUNT_COUNTRY = 'STRIPE_ACCOUNT_COUNTRY';
    const GO_LIVE = 'STRIPE_GO_LIVE';
    const PUBLISHABLE_KEY_LIVE = 'STRIPE_PUBLISHABLE_KEY_LIVE';
    const PUBLISHABLE_KEY_TEST = 'STRIPE_PUBLISHABLE_KEY_TEST';
    const SECRET_KEY_LIVE = 'STRIPE_SECRET_KEY_LIVE';
    const SECRET_KEY_TEST = 'STRIPE_SECRET_KEY_TEST';
    const ORDER_OF_METHODS = 'STRIPE_ORDER_OF_METHODS';

    // Order process settings
    const MANUAL_CAPTURE = 'STRIPE_MANUAL_CAPTURE';

    // Order statuses
    const USE_STATUS_AUTHORIZED = 'STRIPE_USE_STAT_AUTHORIZED';
    const STATUS_AUTHORIZED = 'STRIPE_STAT_AUTHORIZED';

    const USE_STATUS_IN_REVIEW = 'STRIPE_USE_STAT_IN_REVIEW';
    const STATUS_IN_REVIEW = 'STRIPE_STAT_IN_REVIEW';

    const STATUS_PROCESSING = 'STRIPE_STAT_SOFORT';
    const STATUS_VALIDATED = 'STRIPE_STAT_VALIDATED';

    // Full refunds
    const USE_STATUS_REFUND = 'STRIPE_USE_STAT_REFUND';
    const STATUS_REFUND = 'STRIPE_STAT_REFUND';
    const GENERATE_CREDIT_SLIP = 'STRIPE_CREDIT_SLIP';

    // Partial refurnds
    const USE_STATUS_PARTIAL_REFUND = 'STRIPE_USE_STAT_PART_REFUND';
    const STATUS_PARTIAL_REFUND = 'STRIPE_STAT_PART_REFUND';


    // Payment Methods specific settings
    const COLLECT_BILLING = 'STRIPE_COLLECT_BILLING';
    const SHOW_PAYMENT_LOGOS = 'STRIPE_PAYMENT_LOGOS';

    // Credit Card customization
    const STRIPE_PAYMENT_REQUEST = 'STRIPE_STRIPE_PAYMENT_REQUEST';
    const BUTTON_BACKGROUND_COLOR = 'STRIPE_BUTTON_BACKGROUND_COLOR';
    const BUTTON_FOREGROUND_COLOR = 'STRIPE_BUTTON_FOREGROUND_COLOR';
    const CHECKOUT_FONT_FAMILY = 'STRIPE_CHECKOUT_FONT_FAMILY';
    const CHECKOUT_FONT_SIZE = 'STRIPE_CHECKOUT_FONT_SIZE';
    const ERROR_COLOR = 'STRIPE_ERROR_COLOR';
    const ERROR_GLYPH_COLOR = 'STRIPE_ERROR_GLYPH_COLOR';
    const HIGHLIGHT_COLOR = 'STRIPE_HIGHLIGHT_COLOR';
    const INPUT_FONT_FAMILY = 'STRIPE_INPUT_FONT_FAMILY';
    const INPUT_PLACEHOLDER_COLOR = 'STRIPE_INPUT_PLACEHOLDER_COLOR';
    const INPUT_TEXT_BACKGROUND_COLOR = 'STRIPE_PAYMENT_REQBGC';
    const INPUT_TEXT_FOREGROUND_COLOR = 'STRIPE_PAYMENT_REQFGC';
    const PAYMENT_REQUEST_BUTTON_STYLE = 'STRIPE_PRB_STYLE';

    /**
     * @var int $menu Current menu
     */
    private $menu;

    /**
     * @var PaymentMethodsRepository
     */
    private $methods;

    /**
     * @var StripeApi
     */
    private $api;

    /**
     * ThirtyBeesStripe constructor.
     *
     * @throws PrestaShopException
     */
    public function __construct()
    {
        $this->name = 'stripe';
        $this->tab = 'payments_gateways';
        $this->version = '1.9.4';
        $this->author = 'thirty bees';
        $this->need_instance = 0;

        $this->bootstrap = true;

        $this->controllers = [
            'demoiframe',
            'hook',
            'payment',
            'validation',
        ];

        $this->is_eu_compatible = 1;
        $this->currencies = true;
        $this->currencies_mode = 'checkbox';

        parent::__construct();

        $this->displayName = $this->l('Stripe');
        $this->description = $this->l('Accept payments with Stripe');

        $this->api = new StripeApi($this->version);
        $this->methods = new PaymentMethodsRepository($this->getStripeApi());
    }

    /**
     * @return StripeApi
     */
    public function getStripeApi()
    {
        return $this->api;
    }

    /**
     * Install the module
     *
     * @return bool Whether the module has been successfully installed
     *
     * @throws PrestaShopException
     */
    public function install()
    {
        if (!parent::install()) {
            parent::uninstall();

            return false;
        }

        $this->registerHook('displayPaymentTop');
        $this->registerHook('displayPayment');
        $this->registerHook('displayPaymentEU');
        $this->registerHook('paymentReturn');
        $this->registerHook('displayAdminOrder');
        $this->registerHook('actionAdminOrdersListingFieldsModifier');

        StripeTransaction::createDatabase();
        StripeReview::createDatabase();

        Configuration::updateGlobalValue(static::STATUS_VALIDATED, Configuration::get('PS_OS_PAYMENT'));
        Configuration::updateGlobalValue(static::USE_STATUS_REFUND, true);
        Configuration::updateGlobalValue(static::STATUS_REFUND, Configuration::get('PS_OS_REFUND'));
        Configuration::updateGlobalValue(static::USE_STATUS_PARTIAL_REFUND, false);
        Configuration::updateGlobalValue(static::STATUS_PARTIAL_REFUND, Configuration::get('PS_OS_REFUND'));
        Configuration::updateGlobalValue(static::GENERATE_CREDIT_SLIP, true);

        return true;
    }

    /**
     * Uninstall the module
     *
     * @return bool Whether the module has been successfully installed
     *
     * @throws PrestaShopException
     */
    public function uninstall()
    {
        Configuration::deleteByName(static::SECRET_KEY_TEST);
        Configuration::deleteByName(static::PUBLISHABLE_KEY_TEST);
        Configuration::deleteByName(static::SECRET_KEY_LIVE);
        Configuration::deleteByName(static::PUBLISHABLE_KEY_LIVE);
        Configuration::deleteByName(static::GO_LIVE);
        Configuration::deleteByName(static::USE_STATUS_REFUND);
        Configuration::deleteByName(static::USE_STATUS_PARTIAL_REFUND);
        Configuration::deleteByName(static::USE_STATUS_AUTHORIZED);
        Configuration::deleteByName(static::USE_STATUS_IN_REVIEW);
        Configuration::deleteByName(static::STATUS_PARTIAL_REFUND);
        Configuration::deleteByName(static::STATUS_REFUND);
        Configuration::deleteByName(static::STATUS_AUTHORIZED);
        Configuration::deleteByName(static::STATUS_IN_REVIEW);
        Configuration::deleteByName(static::GENERATE_CREDIT_SLIP);
        Configuration::deleteByName(static::SHOW_PAYMENT_LOGOS);
        Configuration::deleteByName(static::ORDER_OF_METHODS);

        foreach ($this->methods->getAllMethods() as $method) {
            $method->cleanConfiguration();
        }

        return parent::uninstall();
    }

    /**
     * Load the configuration form
     *
     * @return string HTML
     *
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function getContent()
    {
        $output = '';

        $this->initNavigation();


        $this->postProcess();

        $this->context->smarty->assign([
            'menutabs' => $this->initNavigation(),
            'stripe_webhook_url' => $this->context->link->getModuleLink($this->name, 'hook'),
        ]);

        $output .= $this->display(__FILE__, 'views/templates/admin/navbar.tpl');

        switch (Tools::getValue('menu')) {
            case static::MENU_TRANSACTIONS:
                return $output . $this->renderTransactionsPage();
            default:
                $this->context->controller->addJquery();
                $this->context->controller->addCSS($this->_path . 'views/css/fontselect.css', 'all');
                $this->context->controller->addJS($this->_path . 'views/js/fontselect.js');
                $this->context->controller->addJS($this->_path . 'views/js/designer.js');

                Media::addJsDef([
                    'stripe_input_placeholder_color' => Configuration::get(Stripe::INPUT_PLACEHOLDER_COLOR),
                    'stripe_button_background_color' => Configuration::get(Stripe::BUTTON_BACKGROUND_COLOR),
                    'stripe_button_foreground_color' => Configuration::get(Stripe::BUTTON_FOREGROUND_COLOR),
                    'stripe_highlight_color' => Configuration::get(Stripe::HIGHLIGHT_COLOR),
                    'stripe_error_color' => Configuration::get(Stripe::ERROR_COLOR),
                    'stripe_error_glyph_color' => Configuration::get(Stripe::ERROR_GLYPH_COLOR),
                    'stripe_payment_request_foreground_color' => Configuration::get(Stripe::INPUT_TEXT_FOREGROUND_COLOR),
                    'stripe_payment_request_background_color' => Configuration::get(Stripe::INPUT_TEXT_BACKGROUND_COLOR),
                    'stripe_input_font_family' => Configuration::get(Stripe::INPUT_FONT_FAMILY),
                    'stripe_checkout_font_family' => Configuration::get(Stripe::CHECKOUT_FONT_FAMILY),
                    'stripe_checkout_font_size' => Configuration::get(Stripe::CHECKOUT_FONT_SIZE),
                    'stripe_color_url' => $this->context->link->getAdminLink('AdminModules', true) . '&configure=stripe&ajax=1&action=SaveDesign',
                ]);

                $this->menu = static::MENU_SETTINGS;

                return $output . $this->renderSettingsPage();
        }
    }

    /**
     * Initialize navigation
     *
     * @return array Menu items
     * @throws PrestaShopException
     */
    protected function initNavigation()
    {
        $menu = [
            static::MENU_SETTINGS => [
                'short' => $this->l('Settings'),
                'desc' => $this->l('Module settings'),
                'href' => $this->getModuleUrl(static::MENU_SETTINGS),
                'active' => false,
                'icon' => 'icon-gears',
            ],
            static::MENU_TRANSACTIONS => [
                'short' => $this->l('Transactions'),
                'desc' => $this->l('Stripe transactions'),
                'href' => $this->getModuleUrl(static::MENU_TRANSACTIONS),
                'active' => false,
                'icon' => 'icon-credit-card',
            ],
        ];

        switch (Tools::getValue('menu')) {
            case static::MENU_TRANSACTIONS:
                $this->menu = static::MENU_TRANSACTIONS;
                $menu[static::MENU_TRANSACTIONS]['active'] = true;
                break;
            default:
                $this->menu = static::MENU_SETTINGS;
                $menu[static::MENU_SETTINGS]['active'] = true;
                break;
        }

        return $menu;
    }

    /**
     * Save form data.
     *
     * @throws PrestaShopException
     * @throws SmartyException
     */
    protected function postProcess()
    {
        if (Tools::isSubmit('activepayment_methods')) {
            $this->togglePaymentMethod(Tools::getValue('methodId'));
        }
        if (Tools::isSubmit('updatePositions')) {
            $this->updatePaymentMethodsPositions();
        }
        if (Tools::isSubmit('orderstriperefund')
            && Tools::isSubmit('stripe_refund_order')
            && Tools::isSubmit('stripe_refund_amount')
        ) {
            $this->processRefund();
        } elseif (Tools::isSubmit('orderstripereview')
            && Tools::isSubmit('stripe_review_order')
        ) {
            $this->processReview();
        } elseif ($this->menu == static::MENU_SETTINGS) {
            if (Tools::isSubmit('submitOptionsconfiguration')) {
                $this->postProcessGeneralOptions();
                $this->postProcessOrderOptions();
                $this->postProcessDesignOptions();
            }
        } elseif ($this->menu == static::MENU_TRANSACTIONS) {
            if (Tools::isSubmit('submitBulkdelete' . StripeTransaction::$definition['table'])
                && !empty(Tools::getValue(StripeTransaction::$definition['table'] . 'Box'))
            ) {
                if (StripeTransaction::deleteRange(Tools::getValue(StripeTransaction::$definition['table'] . 'Box'))) {
                    $this->addConfirmation($this->l('Successfully deleted the selected transactions'));
                } else {
                    $this->addError($this->l('Unable to delete the selected transactions'));
                }
            }
        }
    }

    /**
     * @return void
     *
     * @throws PrestaShopException
     * @throws SmartyException
     */
    protected function processRefund()
    {
        $idOrder = (int)Tools::getValue('stripe_refund_order');
        $access = Profile::getProfileAccess($this->context->employee->id_profile, Tab::getIdFromClassName('AdminOrders'));
        if (!$access) {
            $this->setErrorMessage($this->l('Unable to determine employee permissions.'));
            Tools::redirectAdmin($this->context->link->getAdminLink('AdminOrders', true) . '&vieworder&id_order=' . $idOrder);
        }

        if (!$access['edit']) {
            $this->setErrorMessage($this->l('You do not have permission to refund orders.'));
            Tools::redirectAdmin($this->context->link->getAdminLink('AdminOrders', true) . '&vieworder&id_order=' . $idOrder);
        }


        $idCharge = StripeTransaction::getChargeByIdOrder($idOrder);
        $order = new Order($idOrder);
        $currency = new Currency($order->id_currency);

        $orderTotal = Utils::toCurrencyUnit($currency, $order->getTotalPaid());
        $amount = Utils::toCurrencyUnit($currency, (float)static::parseNumber(Tools::getValue('stripe_refund_amount')));
        $amountRefunded = StripeTransaction::getRefundedAmountByOrderId($idOrder);
        $newOrderTotal = $orderTotal - ($amountRefunded + $amount);

        try {
            $this->api->createRefund($idCharge, $amount);
        } catch (Exception $e) {
            $this->setErrorMessage(sprintf('Invalid Stripe request: %s', $e->getMessage()));
            Tools::redirectAdmin($this->context->link->getAdminLink('AdminOrders', true) . '&vieworder&id_order=' . $idOrder);
        }

        if ($newOrderTotal === 0) {
            // Full refund
            if (Configuration::get(static::GENERATE_CREDIT_SLIP)) {
                $fullProductList = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
                    (new DbQuery())
                        ->select('od.`id_order_detail`, od.`product_quantity`')
                        ->from('order_detail', 'od')
                        ->where('od.`id_order` = ' . (int)$order->id)
                );

                if (is_array($fullProductList) && !empty($fullProductList)) {
                    $productList = [];
                    $quantityList = [];
                    foreach ($fullProductList as $dbOrderDetail) {
                        $idOrderDetail = (int)$dbOrderDetail['id_order_detail'];
                        $productList[] = (int)$idOrderDetail;
                        $quantityList[$idOrderDetail] = (int)$dbOrderDetail['product_quantity'];
                    }
                    OrderSlip::createOrderSlip($order, $productList, $quantityList, $order->getShipping());
                }
            }

            $transaction = new StripeTransaction();
            $transaction->card_last_digits = (int)StripeTransaction::getLastFourDigitsByChargeId($idCharge);
            $transaction->id_charge = $idCharge;
            $transaction->amount = $amount;
            $transaction->id_order = $order->id;
            $transaction->type = StripeTransaction::TYPE_FULL_REFUND;
            $transaction->source = StripeTransaction::SOURCE_BACK_OFFICE;
            $transaction->add();

            if (Configuration::get(Stripe::USE_STATUS_REFUND)) {
                $orderHistory = new OrderHistory();
                $orderHistory->id_order = $order->id;
                $orderHistory->changeIdOrderState((int)Configuration::get(Stripe::STATUS_REFUND), $idOrder, !$order->hasInvoice());
                $orderHistory->addWithemail(true);
            }

            $review = StripeReview::getByOrderId($idOrder);
            $review->status = StripeReview::RELEASED;
            $review->save();
        } else {
            $transaction = new StripeTransaction();
            $transaction->card_last_digits = (int)StripeTransaction::getLastFourDigitsByChargeId($idCharge);
            $transaction->id_charge = $idCharge;
            $transaction->amount = $amount;
            $transaction->id_order = $order->id;
            $transaction->type = StripeTransaction::TYPE_PARTIAL_REFUND;
            $transaction->source = StripeTransaction::SOURCE_BACK_OFFICE;
            $transaction->add();

            if (Configuration::get(Stripe::USE_STATUS_PARTIAL_REFUND)) {
                $orderHistory = new OrderHistory();
                $orderHistory->id_order = $order->id;
                $orderHistory->changeIdOrderState((int)Configuration::get(Stripe::STATUS_PARTIAL_REFUND), $idOrder, !$order->hasInvoice());
                $orderHistory->addWithemail(true);
            }
        }

        Tools::redirectAdmin($this->context->link->getAdminLink('AdminOrders', true) . '&vieworder&stripeRefund=refunded&id_order=' . $idOrder);
    }

    /**
     * @param string | array $error
     *
     * @throws PrestaShopException
     */
    private function setErrorMessage($error)
    {
        if (is_array($error)) {
            $error = implode(', ', $error);
        }
        $this->saveToCookie('error', $error);
    }

    /**
     * @param string $type
     * @param string $message
     *
     * @return void
     * @throws PrestaShopException
     */
    private function saveToCookie($type, $message)
    {
        $cookie = new Cookie('stripe');
        $cookie->__set($type, $message);
        $cookie->write();
    }

    /**
     * @param string $value
     *
     * @return float
     */
    private static function parseNumber($value)
    {
        if (method_exists('Tools', 'parseNumber')) {
            return Tools::parseNumber($value);
        } else {
            return (float)str_replace(',', '.', (string)$value);
        }
    }

    /**
     * @throws PrestaShopException
     */
    protected function processReview()
    {
        $idOrder = (int)Tools::getValue('stripe_review_order');
        $order = new Order($idOrder);
        $access = Profile::getProfileAccess($this->context->employee->id_profile, Tab::getIdFromClassName('AdminOrders'));
        if (!$access) {
            $this->setErrorMessage($this->l('Unable to determine employee permissions.'));
            Tools::redirectAdmin($this->context->link->getAdminLink('AdminOrders', true) . '&vieworder&id_order=' . $idOrder);
        }

        if (!$access['edit']) {
            $this->setErrorMessage($this->l('You do not have permission to review payments.'));
            Tools::redirectAdmin($this->context->link->getAdminLink('AdminOrders', true) . '&vieworder&id_order=' . $idOrder);
        }

        $review = StripeReview::getByOrderId($idOrder);
        if (!Validate::isLoadedObject($review)) {
            $this->setErrorMessage($this->l('An error occurred while processing the request.'));
            Tools::redirectAdmin($this->context->link->getAdminLink('AdminOrders', true) . '&vieworder&id_order=' . $idOrder);
        }

        if (Tools::getValue('stripe_action') === 'markAsSafe') {
            try {
                $charge = $this->api->getCharge($review->id_charge);
                $this->api->updateCharge($charge,
                    [
                        'fraud_details' => [
                            'user_repor' => 'safe'
                        ]
                    ]
                );

                $review->status = $review->captured ? StripeReview::CAPTURED : StripeReview::APPROVED;
                $review->save();

                $transaction = new StripeTransaction();
                $transaction->id_order = $idOrder;
                $transaction->id_charge = $charge->id;
                $transaction->source = StripeTransaction::SOURCE_FRONT_OFFICE;
                $transaction->type = StripeTransaction::TYPE_AUTHORIZED;
                $transaction->card_last_digits = (int)StripeTransaction::getLastFourDigitsByChargeId($charge->id);
                $transaction->amount = (int)$charge->amount;
                $transaction->save();

                $this->setConfirmationMessage($this->l('The payment has been approved'));

                if (Configuration::get(Stripe::USE_STATUS_AUTHORIZED)) {
                    $orderHistory = new OrderHistory();
                    $orderHistory->id_order = $idOrder;
                    $orderHistory->changeIdOrderState((int)Configuration::get(Stripe::STATUS_AUTHORIZED), $idOrder, !$order->hasInvoice());
                    $orderHistory->addWithemail(true);
                }
            } catch (Exception $e) {
                $this->setErrorMessage(sprintf('Invalid Stripe request: %s', $e->getMessage()));
            }
        } elseif (Tools::getValue('stripe_action') === 'capture') {
            $processor = new PaymentProcessor($this, new FileLogger());
            if ($processor->capturePayment($review->id_payment_intent, $review, $idOrder)) {
                $this->setConfirmationMessage($this->l('The payment has been captured'));
            } else {
                $this->setErrorMessage($processor->getErrors());
            }
        } elseif (Tools::getValue('stripe_action') === 'release') {
            $processor = new PaymentProcessor($this, new FileLogger());
            if ($processor->releasePayment($review->id_payment_intent, $review, $idOrder)) {
                $this->setConfirmationMessage($this->l('The payment has been released'));
            } else {
                $this->setErrorMessage($processor->getErrors());
            }
        }

        Tools::redirectAdmin($this->context->link->getAdminLink('AdminOrders', true) . '&vieworder&stripeReview=reviewed&id_order=' . $idOrder);
    }

    /**
     * @param string $confirmation
     *
     * @throws PrestaShopException
     */
    private function setConfirmationMessage($confirmation)
    {
        $this->saveToCookie('confirmation', $confirmation);
    }

    /**
     * Process General Options
     *
     * @return void
     *
     * @throws PrestaShopException
     */
    protected function postProcessGeneralOptions()
    {
        $publishableKeyLive = Tools::getValue(static::PUBLISHABLE_KEY_LIVE);
        $secretKeyLive = Tools::getValue(static::SECRET_KEY_LIVE);
        $goLive = (bool)Tools::getValue(static::GO_LIVE);

        $options = [
            static::SECRET_KEY_TEST => Tools::getValue(static::SECRET_KEY_TEST),
            static::PUBLISHABLE_KEY_TEST => Tools::getValue(static::PUBLISHABLE_KEY_TEST),
            static::SECRET_KEY_LIVE => $secretKeyLive,
            static::PUBLISHABLE_KEY_LIVE => $publishableKeyLive,
            static::GO_LIVE => $goLive,
            static::SHOW_PAYMENT_LOGOS => (bool)Tools::getValue(static::SHOW_PAYMENT_LOGOS),
            static::COLLECT_BILLING => (bool)Tools::getValue(static::COLLECT_BILLING),
            static::STRIPE_PAYMENT_REQUEST => (bool)Tools::getValue(static::STRIPE_PAYMENT_REQUEST),
        ];

        if ($goLive
            && (substr($publishableKeyLive, 0, 7) !== 'pk_live' || substr($secretKeyLive, 0, 7) !== 'sk_live')
        ) {
            /** @var AdminController $controller */
            $controller = $this->context->controller;
            $controller->confirmations = [];
            $controller->errors[] = ($this->l('Live mode has been chosen but one or more of the live keys are invalid'));

            return;
        }

        $this->postProcessOptions($options);
    }

    /**
     * Process options
     *
     * @param array $options
     *
     * @throws PrestaShopException
     */
    protected function postProcessOptions($options)
    {
        if (Shop::isFeatureActive()) {
            if (Shop::getContext() == Shop::CONTEXT_ALL) {
                foreach ($options as $key => $value) {
                    $this->updateAllValue($key, $value);
                }
            } elseif (is_array(Tools::getValue('multishopOverrideOption'))) {
                $idShopGroup = (int)Shop::getGroupFromShop($this->getShopId(), true);
                $multishopOverride = Tools::getValue('multishopOverrideOption');
                if (Shop::getContext() == Shop::CONTEXT_GROUP) {
                    $shops = Shop::getShops(false, null, true);
                } else {
                    $shops = [$this->getShopId()];
                }
                foreach ($shops as $idShop) {
                    foreach ($options as $key => $value) {
                        if (isset($multishopOverride[$key]) && $multishopOverride[$key]) {
                            Configuration::updateValue($key, $value, false, $idShopGroup, $idShop);
                        }
                    }
                }
            }
        }

        foreach ($options as $key => $value) {
            Configuration::updateValue($key, $value);
        }
    }

    /**
     * Update configuration value in ALL contexts
     *
     * @param string $key Configuration key
     * @param mixed $values Configuration values, can be string or array with id_lang as key
     * @param bool $html Contains HTML
     *
     * @throws PrestaShopException
     */
    public function updateAllValue($key, $values, $html = false)
    {
        foreach (Shop::getShops() as $shop) {
            Configuration::updateValue($key, $values, $html, $shop['id_shop_group'], $shop['id_shop']);
        }
        Configuration::updateGlobalValue($key, $values, $html);
    }

    /**
     * Get the Shop ID of the current context
     * Retrieves the Shop ID from the cookie
     *
     * @return int Shop ID
     */
    public function getShopId()
    {
        return (int)Context::getContext()->shop->id;
    }

    /**
     * Process Order Options
     *
     * @return void
     *
     * @throws PrestaShopException
     */
    protected function postProcessOrderOptions()
    {
        $options = [
            static::STATUS_VALIDATED => Tools::getValue(static::STATUS_VALIDATED),
            static::USE_STATUS_REFUND => Tools::getValue(static::USE_STATUS_REFUND),
            static::STATUS_REFUND => Tools::getValue(static::STATUS_REFUND),
            static::STATUS_PROCESSING => Tools::getValue(static::STATUS_PROCESSING),
            static::USE_STATUS_PARTIAL_REFUND => Tools::getValue(static::USE_STATUS_PARTIAL_REFUND),
            static::STATUS_PARTIAL_REFUND => Tools::getValue(static::STATUS_PARTIAL_REFUND),
            static::USE_STATUS_AUTHORIZED => Tools::getValue(static::USE_STATUS_AUTHORIZED),
            static::STATUS_AUTHORIZED => Tools::getValue(static::STATUS_AUTHORIZED),
            static::USE_STATUS_IN_REVIEW => Tools::getValue(static::USE_STATUS_IN_REVIEW),
            static::STATUS_IN_REVIEW => Tools::getValue(static::STATUS_IN_REVIEW),
            static::MANUAL_CAPTURE => Tools::getValue(static::MANUAL_CAPTURE),
            static::ACCOUNT_COUNTRY => Tools::getValue(static::ACCOUNT_COUNTRY),
            static::GENERATE_CREDIT_SLIP => (bool)Tools::getValue(static::GENERATE_CREDIT_SLIP),
        ];

        $this->postProcessOptions($options);
    }

    /**
     * Process Advanced Options
     *
     * @return void
     *
     * @throws PrestaShopException
     */
    protected function postProcessDesignOptions()
    {
        $options = [
            static::INPUT_PLACEHOLDER_COLOR => Tools::getValue(static::INPUT_PLACEHOLDER_COLOR),
            static::BUTTON_BACKGROUND_COLOR => Tools::getValue(static::BUTTON_BACKGROUND_COLOR),
            static::BUTTON_FOREGROUND_COLOR => Tools::getValue(static::BUTTON_FOREGROUND_COLOR),
            static::HIGHLIGHT_COLOR => Tools::getValue(static::HIGHLIGHT_COLOR),
            static::ERROR_COLOR => Tools::getValue(static::ERROR_COLOR),
            static::ERROR_GLYPH_COLOR => Tools::getValue(static::ERROR_GLYPH_COLOR),
            static::INPUT_TEXT_FOREGROUND_COLOR => Tools::getValue(static::INPUT_TEXT_FOREGROUND_COLOR),
            static::INPUT_TEXT_BACKGROUND_COLOR => Tools::getValue(static::INPUT_TEXT_BACKGROUND_COLOR),
            static::INPUT_FONT_FAMILY => Tools::getValue(static::INPUT_FONT_FAMILY),
            static::CHECKOUT_FONT_FAMILY => Tools::getValue(static::CHECKOUT_FONT_FAMILY),
            static::CHECKOUT_FONT_SIZE => Tools::getValue(static::CHECKOUT_FONT_SIZE),
            static::PAYMENT_REQUEST_BUTTON_STYLE => Tools::getValue(static::PAYMENT_REQUEST_BUTTON_STYLE),
        ];

        $this->postProcessOptions($options);
    }

    /**
     * Add confirmation message
     *
     * @param string $message Message
     * @param bool $private
     */
    protected function addConfirmation($message, $private = false)
    {
        /** @var AdminController $controller */
        $controller = $this->context->controller;
        $controller->confirmations[] = $message;
    }

    /**
     * Add error message
     *
     * @param string $message Message
     */
    protected function addError($message, $private = false)
    {
        /** @var AdminController $controller */
        $controller = $this->context->controller;
        $controller->warnings[] = $message;
    }

    /**
     * Render the transactions page
     *
     * @return string HTML
     *
     * @throws PrestaShopException
     * @throws SmartyException
     */
    protected function renderTransactionsPage()
    {
        return $this->renderTransactionsList();
    }

    /**
     * Render the transactions list
     *
     * @return string HTML
     *
     * @throws PrestaShopException
     * @throws SmartyException
     */
    protected function renderTransactionsList()
    {
        $fieldsList = [
            'id_stripe_transaction' => [
                'title' => $this->l('ID'),
                'width' => 'auto',
            ],
            'type_icon' => [
                'type' => 'text',
                'title' => $this->l('Type'),
                'width' => 'auto',
                'color' => 'color',
                'text' => 'type_text',
                'callback' => 'displayEventLabel',
                'callback_object' => StripeTransaction::class,
            ],
            'amount' => [
                'type' => 'price',
                'title' => $this->l('Amount'),
                'width' => 'auto',
            ],
            'card_last_digits' => [
                'type' => 'text',
                'title' => $this->l('Credit card (last 4 digits)'),
                'width' => 'auto',
                'callback' => 'displayCardDigits',
                'callback_object' => StripeTransaction::class,
            ],
            'source_text' => [
                'type' => 'text',
                'title' => $this->l('Source'),
                'width' => 'auto',
            ],
            'source_type' => [
                'type' => 'text',
                'title' => $this->l('Payment type'),
                'width' => 'auto',
            ],
            'date_upd' => [
                'type' => 'datetime',
                'title' => $this->l('Date & time'), 'width' => 'auto',
            ],
        ];

        if (Tools::isSubmit('submitResetstripe_transaction')) {
            $cookie = $this->context->cookie;
            foreach ($fieldsList as $fieldName => $field) {
                unset($cookie->{StripeTransaction::$definition['table'] . 'Filter_' . $fieldName});
                unset($_POST[StripeTransaction::$definition['table'] . 'Filter_' . $fieldName]);
                unset($_GET[StripeTransaction::$definition['table'] . 'Filter_' . $fieldName]);
            }
            unset($this->context->cookie->{StripeTransaction::$definition['table'] . 'Orderby'});
            unset($this->context->cookie->{StripeTransaction::$definition['table'] . 'OrderWay'});

            $cookie->write();
        }

        $sql = new DbQuery();
        $sql->select('COUNT(*)');
        $sql->from(bqSQL(StripeTransaction::$definition['table']));

        $listTotal = (int)Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue($sql);

        $pagination = (int)$this->getSelectedPagination(StripeTransaction::$definition['table']);
        $currentPage = (int)$this->getSelectedPage(StripeTransaction::$definition['table'], $listTotal);

        $helperList = new HelperList();
        $helperList->shopLinkType = false;
        $helperList->list_id = StripeTransaction::$definition['table'];
        $helperList->module = $this;
        $helperList->bulk_actions = [
            'delete' => [
                'text' => $this->l('Delete selected'),
                'confirm' => $this->l('Delete selected items?'),
                'icon' => 'icon-trash',
            ],
        ];
        $helperList->actions = ['view', 'delete'];
        $helperList->page = $currentPage;
        $helperList->_defaultOrderBy = StripeTransaction::$definition['primary'];
        if (Tools::isSubmit(StripeTransaction::$definition['table'] . 'Orderby')) {
            $helperList->orderBy = Tools::getValue(StripeTransaction::$definition['table'] . 'Orderby');
            $this->context->cookie->{StripeTransaction::$definition['table'] . 'Orderby'} = $helperList->orderBy;
        } elseif (!empty($this->context->cookie->{StripeTransaction::$definition['table'] . 'Orderby'})) {
            $helperList->orderBy = $this->context->cookie->{StripeTransaction::$definition['table'] . 'Orderby'};
        } else {
            $helperList->orderBy = StripeTransaction::$definition['primary'];
        }

        if (Tools::isSubmit(StripeTransaction::$definition['table'] . 'Orderway')) {
            $helperList->orderWay = mb_strtoupper(Tools::getValue(StripeTransaction::$definition['table'] . 'Orderway'));
            $this->context->cookie->{StripeTransaction::$definition['table'] . 'Orderway'} = Tools::getValue(StripeTransaction::$definition['table'] . 'Orderway');
        } elseif (!empty($this->context->cookie->{StripeTransaction::$definition['table'] . 'Orderway'})) {
            $helperList->orderWay = mb_strtoupper($this->context->cookie->{StripeTransaction::$definition['table'] . 'Orderway'});
        } else {
            $helperList->orderWay = 'DESC';
        }

        $filterSql = $this->getSQLFilter($helperList, $fieldsList);

        $results = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            (new DbQuery())
                ->select('*')
                ->from(bqSQL(StripeTransaction::$definition['table']), 'st')
                ->orderBy('`' . bqSQL($helperList->orderBy) . '` ' . pSQL($helperList->orderWay))
                ->where('1 ' . $filterSql)
                ->limit($pagination, ($currentPage - 1) * $pagination)
        );

        $sourceTypes = [];
        foreach ($this->methods->getAllMethods() as $method) {
            $sourceTypes[$method->getMethodId()] = $method->getShortName();
        }

        foreach ($results as &$result) {
            // Process results
            $currency = $this->getCurrencyIdByOrderId($result['id_order']);
            $result['amount'] = Utils::fromCurrencyUnit($currency, $result['amount']);
            $result['card_last_digits'] = str_pad($result['card_last_digits'], 4, '0', STR_PAD_LEFT);
            $result['amount'] = Tools::displayPrice($result['amount'], $currency);
            switch ($result['type']) {
                case StripeTransaction::TYPE_CHARGE:
                    $result['color'] = '#32CD32';
                    $result['type_icon'] = 'credit-card';
                    $result['type_text'] = $this->l('Charged');
                    break;
                case StripeTransaction::TYPE_PARTIAL_REFUND:
                    $result['color'] = '#FF8C00';
                    $result['type_icon'] = 'undo';
                    $result['type_text'] = $this->l('Partial refund');
                    break;
                case StripeTransaction::TYPE_FULL_REFUND:
                    $result['color'] = '#ec2e15';
                    $result['type_icon'] = 'undo';
                    $result['type_text'] = $this->l('Full refund');
                    break;
                case StripeTransaction::TYPE_AUTHORIZED:
                    $result['color'] = '#FF8C00';
                    $result['type_icon'] = 'unlock';
                    $result['type_text'] = $this->l('Authorized');
                    break;
                case StripeTransaction::TYPE_IN_REVIEW:
                    $result['color'] = '#FF8C00';
                    $result['type_icon'] = 'search';
                    $result['type_text'] = $this->l('In review');
                    break;
                case StripeTransaction::TYPE_CAPTURED:
                    $result['color'] = '#32CD32';
                    $result['type_icon'] = 'lock';
                    $result['type_text'] = $this->l('Captured');
                    break;
                case StripeTransaction::TYPE_CHARGE_FAIL:
                    $result['color'] = '#ec2e15';
                    $result['type_icon'] = 'close';
                    $result['type_text'] = $this->l('Charge failed');
                    break;
                default:
                    $result['color'] = '';
                    break;
            }

            switch ($result['source']) {
                case StripeTransaction::SOURCE_FRONT_OFFICE:
                    $result['source_text'] = $this->l('Front Office');
                    break;
                case StripeTransaction::SOURCE_BACK_OFFICE:
                    $result['source_text'] = $this->l('Back Office');
                    break;
                case StripeTransaction::SOURCE_WEBHOOK:
                    $result['source_text'] = $this->l('Webhook');
                    break;
                default:
                    $result['source_text'] = $this->l('Unknown');
                    break;
            }

            $result['source_type'] = $sourceTypes[$result['source_type']] ?? $this->l('Unknown');
        }

        $helperList->listTotal = count($results);

        $helperList->identifier = StripeTransaction::$definition['primary'];
        $helperList->title = $this->l('Transactions & Events');
        $helperList->token = Tools::getAdminTokenLite('AdminModules');
        $helperList->currentIndex = AdminController::$currentIndex . '&' . http_build_query([
                'configure' => $this->name,
                'menu' => static::MENU_TRANSACTIONS,
            ]);

        $helperList->table = StripeTransaction::$definition['table'];
        $helperList->tpl_vars['icon'] = 'icon icon-cc-stripe';

        return $helperList->generateList($results, $fieldsList);
    }

    /**
     * Get selected pagination
     *
     * @param int $idList
     * @param int $defaultPagination
     *
     * @return mixed
     */
    protected function getSelectedPagination($idList, $defaultPagination = 50)
    {
        $selectedPagination = Tools::getValue(
            $idList . '_pagination',
            isset($this->context->cookie->{$idList . '_pagination'}) ? $this->context->cookie->{$idList . '_pagination'} : $defaultPagination
        );

        return $selectedPagination;
    }

    /**
     * Get selected page
     *
     * @param int $idList List ID
     * @param int $listTotal Total list items
     *
     * @return int|mixed
     */
    protected function getSelectedPage($idList, $listTotal)
    {
        /* Determine current page number */
        $page = (int)Tools::getValue('submitFilter' . $idList);

        if (!$page) {
            $page = 1;
        }

        $totalPages = max(1, ceil($listTotal / $this->getSelectedPagination($idList)));

        if ($page > $totalPages) {
            $page = $totalPages;
        }
        return $page;
    }

    /**
     * @param HelperList $helperList
     * @param array $fieldsList
     *
     * @return string
     *
     * @throws PrestaShopException
     */
    protected function getSQLFilter($helperList, $fieldsList)
    {
        if (!isset($helperList->list_id)) {
            $helperList->list_id = $helperList->table;
        }

        $prefix = '';
        $sqlFilter = '';

        if (isset($helperList->list_id)) {
            foreach ($_POST as $key => $value) {
                if ($value === '') {
                    unset($helperList->context->cookie->{$prefix . $key});
                } elseif (stripos($key, $helperList->list_id . 'Filter_') === 0) {
                    $helperList->context->cookie->{$prefix . $key} = !is_array($value) ? $value : serialize($value);
                } elseif (stripos($key, 'submitFilter') === 0) {
                    $helperList->context->cookie->$key = !is_array($value) ? $value : serialize($value);
                }
            }

            foreach ($_GET as $key => $value) {
                if (stripos($key, $helperList->list_id . 'Filter_') === 0) {
                    $helperList->context->cookie->{$prefix . $key} = !is_array($value) ? $value : serialize($value);
                } elseif (stripos($key, 'submitFilter') === 0) {
                    $helperList->context->cookie->$key = !is_array($value) ? $value : serialize($value);
                }
                if (stripos($key, $helperList->list_id . 'Orderby') === 0 && is_string($value) && Validate::isOrderBy($value)) {
                    if ($value === '' || $value == $helperList->_defaultOrderBy) {
                        unset($helperList->context->cookie->{$prefix . $key});
                    } else {
                        $helperList->context->cookie->{$prefix . $key} = $value;
                    }
                } elseif (stripos($key, $helperList->list_id . 'Orderway') === 0 && is_string($value) && Validate::isOrderWay($value)) {
                    if ($value === '') {
                        unset($helperList->context->cookie->{$prefix . $key});
                    } else {
                        $helperList->context->cookie->{$prefix . $key} = $value;
                    }
                }
            }
        }

        $filters = $helperList->context->cookie->getFamily($prefix . $helperList->list_id . 'Filter_');
        $definition = false;
        if (isset($helperList->className) && $helperList->className) {
            $definition = ObjectModel::getDefinition($helperList->className);
        }

        foreach ($filters as $key => $value) {
            /* Extracting filters from $_POST on key filter_ */
            if ($value != null && !strncmp($key, $prefix . $helperList->list_id . 'Filter_', 7 + mb_strlen($prefix . $helperList->list_id))) {
                $key = mb_substr($key, 7 + mb_strlen($prefix . $helperList->list_id));
                /* Table alias could be specified using a ! eg. alias!field */
                $tmpTab = explode('!', $key);
                $filter = count($tmpTab) > 1 ? $tmpTab[1] : $tmpTab[0];

                if ($field = $this->filterToField($fieldsList, $key, $filter)) {
                    $type = (array_key_exists('filter_type', $field) ? $field['filter_type'] : (array_key_exists('type', $field) ? $field['type'] : false));
                    if (($type == 'date' || $type == 'datetime') && is_string($value)) {
                        $value = json_decode($value, true);
                    }
                    $key = isset($tmpTab[1]) ? $tmpTab[0] . '.`' . $tmpTab[1] . '`' : '`' . $tmpTab[0] . '`';

                    /* Only for date filtering (from, to) */
                    if (is_array($value)) {
                        if (!empty($value[0])) {
                            if (!Validate::isDate($value[0])) {
                                return $this->displayError('The \'From\' date format is invalid (YYYY-MM-DD)');
                            } else {
                                $sqlFilter .= ' AND ' . pSQL($key) . ' >= \'' . pSQL(Tools::dateFrom($value[0])) . '\'';
                            }
                        }

                        if (!empty($value[1])) {
                            if (!Validate::isDate($value[1])) {
                                return $this->displayError('The \'To\' date format is invalid (YYYY-MM-DD)');
                            } else {
                                $sqlFilter .= ' AND ' . pSQL($key) . ' <= \'' . pSQL(Tools::dateTo($value[1])) . '\'';
                            }
                        }
                    } elseif ($value) {
                        $sqlFilter .= ' AND ';
                        $checkKey = ($key == $helperList->identifier || $key == '`' . $helperList->identifier . '`');
                        $alias = ($definition && !empty($definition['fields'][$filter]['shop'])) ? 'sa' : 'a';

                        if ($type == 'int' || $type == 'bool') {
                            $sqlFilter .= (($checkKey || $key == '`active`') ? $alias . '.' : '') . pSQL($key) . ' = ' . (int)$value . ' ';
                        } elseif ($type == 'decimal') {
                            $sqlFilter .= ($checkKey ? $alias . '.' : '') . pSQL($key) . ' = ' . (float)$value . ' ';
                        } elseif ($type == 'select') {
                            $sqlFilter .= ($checkKey ? $alias . '.' : '') . pSQL($key) . ' = \'' . pSQL($value) . '\' ';
                        } elseif ($type == 'price') {
                            $value = (float)str_replace(',', '.', $value);
                            $sqlFilter .= ($checkKey ? $alias . '.' : '') . pSQL($key) . ' = ' . pSQL(trim($value)) . ' ';
                        } else {
                            $sqlFilter .= ($checkKey ? $alias . '.' : '') . pSQL($key) . ' LIKE \'%' . pSQL(trim($value)) . '%\' ';
                        }
                    }
                }
            }
        }

        return $sqlFilter;
    }

    /**
     * @param array $fieldsList
     * @param string $key
     * @param string $filter
     *
     * @return array|false
     */
    protected function filterToField($fieldsList, $key, $filter)
    {
        foreach ($fieldsList as $field) {
            if (array_key_exists('filter_key', $field) && $field['filter_key'] == $key) {
                return $field;
            }
        }
        if (array_key_exists($filter, $fieldsList)) {
            return $fieldsList[$filter];
        }

        return false;
    }

    /**
     * @param int $idOrder
     *
     * @return Currency
     * @throws PrestaShopException
     */
    protected function getCurrencyIdByOrderId($idOrder)
    {
        $order = new Order($idOrder);
        if (Validate::isLoadedObject($order)) {
            $currency = Currency::getCurrencyInstance($order->id_currency);
        } else {
            $currency = Currency::getCurrencyInstance((int)Configuration::get('PS_CURRENCY_DEFAULT'));
        }

        return $currency;
    }

    /**
     * Render the general settings page
     *
     * @return string HTML
     *
     * @throws PrestaShopException
     * @throws SmartyException
     */
    protected function renderSettingsPage()
    {
        $output = $this->display(__FILE__, 'views/templates/admin/configure.tpl');
        $output .= $this->renderPaymentMethodsList();
        $output .= $this->renderGeneralOptions();
        return $output;
    }


    /**
     * Render the General options form
     *
     * @return string HTML
     *
     * @throws PrestaShopException
     * @throws SmartyException
     */
    protected function renderGeneralOptions()
    {
        $helper = new HelperOptions();
        $helper->module = $this;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex . '&configure=' . $this->name;
        $helper->title = $this->displayName;
        $helper->table = 'configuration';
        $helper->show_toolbar = false;

        $options = array_merge(
            $this->getGeneralOptions(),
            $this->getOrderOptions(),
            $this->getStripeCheckoutOptions(),
            $this->getStripeCreditCardOptions(),
            $this->getDesignOptions(),
        );

        return $helper->generateOptions($options);
    }

    /**
     * Get available general options
     *
     * @return array General options
     *
     * @throws PrestaShopException
     */
    protected function getGeneralOptions()
    {
        return [
            'api' => [
                'title' => $this->l('API Settings'),
                'icon' => 'icon-server',
                'fields' => [
                    static::PUBLISHABLE_KEY_TEST => [
                        'title' => $this->l('Publishable key (test)'),
                        'type' => 'text',
                        'name' => static::PUBLISHABLE_KEY_TEST,
                        'value' => Configuration::get(static::PUBLISHABLE_KEY_TEST),
                        'auto_value' => false,
                        'validation' => 'isString',
                        'cast' => 'strval',
                        'placeholder' => 'pk_test...',
                        'size' => 64,
                    ],
                    static::SECRET_KEY_TEST => [
                        'title' => $this->l('Secret key (test)'),
                        'type' => 'text',
                        'name' => static::SECRET_KEY_TEST,
                        'value' => Configuration::get(static::SECRET_KEY_TEST),
                        'auto_value' => false,
                        'validation' => 'isString',
                        'cast' => 'strval',
                        'placeholder' => 'sk_test...',
                        'size' => 64,
                    ],
                    static::PUBLISHABLE_KEY_LIVE => [
                        'title' => $this->l('Publishable key (live)'),
                        'type' => 'text',
                        'name' => static::PUBLISHABLE_KEY_LIVE,
                        'value' => Configuration::get(static::PUBLISHABLE_KEY_LIVE),
                        'auto_value' => false,
                        'validation' => 'isString',
                        'cast' => 'strval',
                        'placeholder' => 'pk_live...',
                        'size' => 64,
                    ],
                    static::SECRET_KEY_LIVE => [
                        'title' => $this->l('Secret key (live)'),
                        'type' => 'text',
                        'name' => static::SECRET_KEY_LIVE,
                        'value' => Configuration::get(static::SECRET_KEY_LIVE),
                        'auto_value' => false,
                        'validation' => 'isString',
                        'cast' => 'strval',
                        'placeholder' => 'sk_live...',
                        'size' => 64,
                    ],
                    static::ACCOUNT_COUNTRY => [
                        'title' => $this->l('Account country'),
                        'type' => 'select',
                        'label' => $this->l('Country of your stripe account'),
                        'name' => static::ACCOUNT_COUNTRY,
                        'required' => true,
                        'identifier' => 'code',
                        'value' => Utils::getStripeCountry(),
                        'auto_value' => false,
                        'list' => Utils::getStripeCountries(),
                    ],
                    static::GO_LIVE => [
                        'title' => $this->l('Go live'),
                        'type' => 'bool',
                        'desc' => $this->l('Enable this option to accept live payments, otherwise the test keys are used, which you can use to test your store.'),
                        'name' => static::GO_LIVE,
                        'value' => Configuration::get(static::GO_LIVE),
                        'auto_value' => false,
                        'validation' => 'isBool',
                        'cast' => 'intval',
                    ],
                ],
                'submit' => [
                    'title' => $this->l('Save'),
                    'class' => 'button',
                ],
            ],
        ];
    }

    /**
     * Get other payment options
     *
     * @return string
     *
     * @throws PrestaShopException
     * @throws SmartyException
     */
    protected function renderPaymentMethodsList()
    {
        $paymentMethods = [];
        $position = 0;
        foreach ($this->methods->getAllMethods() as $method) {
            $notes = '';
            if ($method->requiresWebhook()) {
                $notes=  $this->l('Requires webhook');
            }
            $item = [
                'position' => $position,
                'methodId' => $method->getMethodId(),
                'name' => $method->getName(),
                'notes' => $notes,
                'active' => $method->isEnabled()
            ];
            $paymentMethods[] = $item;
            $position++;
        }

        $fields = [
            'position' => [
                'title' => $this->l('Position'),
                'align' => 'center',
                'class' => 'fixed-width-sm',
                'position' => 'position'
            ],
            'name' => [
                'title' => $this->l('Payment Method'),
                'type' => 'text',
                'callback_object' => $this,
                'callback' => 'renderListPaymentMethodLink',
            ],
            'notes' => [
                'title' => $this->l('Notes'),
                'type' => 'text',
            ],
            'active' => array(
                'title' => $this->l('Enabled'),
                'align' => 'text-center',
                'active' => 'active',
                'type' => 'bool',
                'orderby' => false,
                'ajax' => true,
            )
        ];

        $helper = new HelperList();
        $helper->list_id = 'payment_methods';
        $helper->table_id = 'module-stripe';
        $helper->table = 'payment_methods';
        $helper->simple_header = true;
        $helper->identifier = 'methodId';
        $helper->show_toolbar = false;
        $helper->position_identifier = 'position';
        $helper->orderBy = 'position';
        $helper->orderWay = 'ASC';
        $helper->no_link = true;

        $helper->title = $this->l('Payment Methods');
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex.'&configure='.$this->name;
        return $helper->generateList($paymentMethods, $fields);
    }

    /**
     * @param string $value
     * @param array $row
     *
     * @return string
     * @throws PrestaShopException
     */
    public function renderListPaymentMethodLink(string $value, array $row)
    {
        $method = $this->methods->getMethod($row['methodId']);
        if ($method) {
            $url = $method->getDocLink();
            return '<a href="'.$url.'" target="_blank">'.Tools::safeOutput($value).'</a>';
        }
        return $value;
    }

    /**
     * Get available options for orders
     *
     * @return array Order options
     *
     * @throws PrestaShopException
     */
    protected function getOrderOptions()
    {
        $orderStatuses = OrderState::getOrderStates($this->context->language->id);
        $statusValidated = (int)Configuration::get(static::STATUS_VALIDATED);
        if ($statusValidated < 1) {
            $statusValidated = (int)Configuration::get('PS_OS_PAYMENT');
        }
        $statusPartialRefund = (int)Configuration::get(static::STATUS_PARTIAL_REFUND);
        if ($statusPartialRefund < 1) {
            $statusPartialRefund = (int)Configuration::get('PS_OS_REFUND');
        }
        $statusRefund = (int)Configuration::get(static::STATUS_REFUND);
        if ($statusRefund < 1) {
            $statusRefund = (int)Configuration::get('PS_OS_REFUND');
        }
        $statusProcessing = (int)Configuration::get(static::STATUS_PROCESSING);
        if ($statusProcessing < 1) {
            $statusProcessing = (int)Configuration::get('PS_OS_PAYMENT');
        }
        $statusAuthorized = (int)Configuration::get(static::STATUS_AUTHORIZED);
        if ($statusAuthorized < 1) {
            $statusAuthorized = (int)Configuration::get('PS_OS_PAYMENT');
        }
        $statusInReview = (int)Configuration::get(static::STATUS_IN_REVIEW);
        if ($statusInReview < 1) {
            $statusInReview = (int)Configuration::get('PS_OS_PAYMENT');
        }

        return [
            'orders' => [
                'title' => $this->l('Order Settings'),
                'icon' => 'icon-credit-card',
                'fields' => [
                    static::STATUS_VALIDATED => [
                        'title' => $this->l('Payment accepted status'),
                        'des' => $this->l('Order status to use when the payment is accepted'),
                        'type' => 'select',
                        'list' => $orderStatuses,
                        'identifier' => 'id_order_state',
                        'name' => static::STATUS_VALIDATED,
                        'value' => $statusValidated,
                        'auto_value' => false,
                        'validation' => 'isString',
                        'cast' => 'strval',
                    ],
                    static::USE_STATUS_PARTIAL_REFUND => [
                        'title' => $this->l('Use partial refund status'),
                        'type' => 'bool',
                        'name' => static::USE_STATUS_PARTIAL_REFUND,
                        'value' => Configuration::get(static::USE_STATUS_PARTIAL_REFUND),
                        'auto_value' => false,
                        'validation' => 'isBool',
                        'cast' => 'intval',
                    ],
                    static::STATUS_PARTIAL_REFUND => [
                        'title' => $this->l('Partial refund status'),
                        'desc' => $this->l('Order status to use when the order is partially refunded'),
                        'type' => 'select',
                        'list' => $orderStatuses,
                        'identifier' => 'id_order_state',
                        'name' => static::STATUS_PARTIAL_REFUND,
                        'value' => $statusPartialRefund,
                        'auto_value' => false,
                        'validation' => 'isString',
                        'cast' => 'strval',
                    ],
                    static::USE_STATUS_REFUND => [
                        'title' => $this->l('Use refund status'),
                        'type' => 'bool',
                        'name' => static::USE_STATUS_REFUND,
                        'value' => Configuration::get(static::USE_STATUS_REFUND),
                        'auto_value' => false,
                        'validation' => 'isBool',
                        'cast' => 'intval',
                    ],
                    static::STATUS_REFUND => [
                        'title' => $this->l('Refund status'),
                        'desc' => $this->l('Order status to use when the order is refunded'),
                        'type' => 'select',
                        'list' => $orderStatuses,
                        'identifier' => 'id_order_state',
                        'name' => static::PUBLISHABLE_KEY_TEST,
                        'value' => $statusRefund,
                        'auto_value' => false,
                        'validation' => 'isString',
                        'cast' => 'strval',
                    ],
                    static::GENERATE_CREDIT_SLIP => [
                        'title' => $this->l('Generate credit slip'),
                        'desc' => $this->l('Automatically generate a credit slip when the order is fully refunded'),
                        'type' => 'bool',
                        'name' => static::GENERATE_CREDIT_SLIP,
                        'value' => Configuration::get(static::GENERATE_CREDIT_SLIP),
                        'auto_value' => false,
                        'validation' => 'isBool',
                        'cast' => 'intval',
                    ],
                    static::USE_STATUS_AUTHORIZED => [
                        'title' => $this->l('Use a status for authorized payments'),
                        'type' => 'bool',
                        'name' => static::USE_STATUS_AUTHORIZED,
                        'value' => Configuration::get(static::USE_STATUS_AUTHORIZED),
                        'auto_value' => false,
                        'validation' => 'isBool',
                        'cast' => 'intval',
                    ],
                    static::STATUS_AUTHORIZED => [
                        'title' => $this->l('Status for authorized payments'),
                        'desc' => $this->l('Order status to use when the payment has only been authorized'),
                        'type' => 'select',
                        'list' => $orderStatuses,
                        'identifier' => 'id_order_state',
                        'name' => static::PUBLISHABLE_KEY_TEST,
                        'value' => $statusAuthorized,
                        'auto_value' => false,
                        'validation' => 'isString',
                        'cast' => 'strval',
                    ],
                    static::USE_STATUS_IN_REVIEW => [
                        'title' => $this->l('Use "in review" status'),
                        'type' => 'bool',
                        'name' => static::USE_STATUS_IN_REVIEW,
                        'value' => Configuration::get(static::USE_STATUS_IN_REVIEW),
                        'auto_value' => false,
                        'validation' => 'isBool',
                        'cast' => 'intval',
                    ],
                    static::STATUS_IN_REVIEW => [
                        'title' => $this->l('"In review" status'),
                        'desc' => $this->l('Order status to use when the payment is in review'),
                        'type' => 'select',
                        'list' => $orderStatuses,
                        'identifier' => 'id_order_state',
                        'name' => static::STATUS_IN_REVIEW,
                        'value' => $statusInReview,
                        'auto_value' => false,
                        'validation' => 'isString',
                        'cast' => 'strval',
                    ],
                    static::MANUAL_CAPTURE => [
                        'title' => $this->l('Manual capture'),
                        'desc' => $this->l('Manually capture payments (use authorize only whenever possible)'),
                        'type' => 'bool',
                        'name' => static::MANUAL_CAPTURE,
                        'value' => Configuration::get(static::MANUAL_CAPTURE),
                        'auto_value' => false,
                        'validation' => 'isBool',
                        'cast' => 'intval',
                    ],
                    static::STATUS_PROCESSING => [
                        'title' => $this->l('Processing status'),
                        'desc' => $this->l('Order status to use when a deleayed banking payment is pending'),
                        'type' => 'select',
                        'list' => $orderStatuses,
                        'identifier' => 'id_order_state',
                        'name' => static::STATUS_PROCESSING,
                        'value' => $statusProcessing,
                        'auto_value' => false,
                        'validation' => 'isString',
                        'cast' => 'strval',
                    ],
                ],
                'submit' => [
                    'title' => $this->l('Save'),
                    'class' => 'button',
                ],
            ],
        ];
    }

    /**
     * Get available general options
     *
     * @return array General options
     *
     * @throws PrestaShopException
     */
    protected function getStripeCheckoutOptions()
    {
        return [
            'checkout' => [
                'title' => $this->l('Stripe Checkout'),
                'icon' => 'icon-credit-card',
                'fields' => [
                    static::COLLECT_BILLING => [
                        'title' => $this->l('Collect billing address'),
                        'type' => 'bool',
                        'name' => static::COLLECT_BILLING,
                        'value' => Configuration::get(static::COLLECT_BILLING),
                        'auto_value' => false,
                        'validation' => 'isBool',
                        'cast' => 'intval',
                    ],
                    static::SHOW_PAYMENT_LOGOS => [
                        'title' => $this->l('Show payment logos'),
                        'type' => 'bool',
                        'name' => static::SHOW_PAYMENT_LOGOS,
                        'value' => Configuration::get(static::SHOW_PAYMENT_LOGOS),
                        'auto_value' => false,
                        'validation' => 'isBool',
                        'cast' => 'intval',
                    ],
                ],
                'submit' => [
                    'title' => $this->l('Save'),
                    'class' => 'button',
                ],
            ],
        ];
    }

    /**
     * Get available general options
     *
     * @return array General options
     *
     * @throws PrestaShopException
     */
    protected function getStripeCreditCardOptions()
    {
        return [
            'creditcard' => [
                'title' => $this->l('Stripe credit card form'),
                'icon' => 'icon-credit-card',
                'fields' => [
                    static::STRIPE_PAYMENT_REQUEST => [
                        'title' => $this->l('Enable the payment request button'),
                        'desc' => $this->l('This option adds an Apple Pay or Google Pay button to the form'),
                        'type' => 'bool',
                        'name' => static::STRIPE_PAYMENT_REQUEST,
                        'value' => Configuration::get(static::STRIPE_PAYMENT_REQUEST),
                        'auto_value' => false,
                        'validation' => 'isBool',
                        'cast' => 'intval',
                        'size' => 64,
                    ],
                ],
                'submit' => [
                    'title' => $this->l('Save'),
                    'class' => 'button',
                ],
            ],
        ];
    }

    /**
     * Get advanced options
     *
     * @return array Advanced options
     *
     * @throws PrestaShopException
     */
    protected function getDesignOptions()
    {
        return [
            'advanced' => [
                'title' => $this->l('Credit card form design'),
                'icon' => 'icon-paint-brush',
                'fields' => [
                    static::INPUT_PLACEHOLDER_COLOR => [
                        'title' => $this->l('Input placeholder color'),
                        'type' => 'color',
                        'name' => static::INPUT_PLACEHOLDER_COLOR,
                        'validation' => 'isString',
                        'cast' => 'strval',
                        'size' => '1',
                        'value' => Configuration::get(static::INPUT_PLACEHOLDER_COLOR) ?: '#000000',
                        'auto_value' => false,
                    ],
                    static::BUTTON_FOREGROUND_COLOR => [
                        'title' => $this->l('Button text color'),
                        'type' => 'color',
                        'name' => static::BUTTON_FOREGROUND_COLOR,
                        'validation' => 'isString',
                        'cast' => 'strval',
                        'size' => '1',
                        'value' => Configuration::get(static::BUTTON_FOREGROUND_COLOR) ?: '#FFFFFF',
                        'auto_value' => false,
                    ],
                    static::BUTTON_BACKGROUND_COLOR => [
                        'title' => $this->l('Button background color'),
                        'type' => 'color',
                        'name' => static::BUTTON_BACKGROUND_COLOR,
                        'validation' => 'isString',
                        'cast' => 'strval',
                        'size' => '1',
                        'value' => Configuration::get(static::BUTTON_BACKGROUND_COLOR) ?: '#000000',
                        'auto_value' => false,
                    ],
                    static::HIGHLIGHT_COLOR => [
                        'title' => $this->l('Highlight color'),
                        'type' => 'color',
                        'name' => static::HIGHLIGHT_COLOR,
                        'validation' => 'isString',
                        'cast' => 'strval',
                        'size' => '1',
                        'value' => Configuration::get(static::HIGHLIGHT_COLOR) ?: '#000000',
                        'auto_value' => false,
                    ],
                    static::ERROR_COLOR => [
                        'title' => $this->l('Error color'),
                        'type' => 'color',
                        'name' => static::ERROR_COLOR,
                        'validation' => 'isString',
                        'cast' => 'strval',
                        'size' => '1',
                        'value' => Configuration::get(static::ERROR_COLOR) ?: '#000000',
                        'auto_value' => false,
                    ],
                    static::ERROR_GLYPH_COLOR => [
                        'title' => $this->l('Error glyph color'),
                        'type' => 'color',
                        'name' => static::ERROR_GLYPH_COLOR,
                        'validation' => 'isString',
                        'cast' => 'strval',
                        'size' => '1',
                        'value' => Configuration::get(static::ERROR_GLYPH_COLOR) ?: '#000000',
                        'auto_value' => false,
                    ],
                    static::INPUT_TEXT_FOREGROUND_COLOR => [
                        'title' => $this->l('Input text foreground color'),
                        'type' => 'color',
                        'name' => static::INPUT_TEXT_FOREGROUND_COLOR,
                        'validation' => 'isString',
                        'cast' => 'strval',
                        'size' => '1',
                        'value' => Configuration::get(static::INPUT_TEXT_FOREGROUND_COLOR) ?: '#000000',
                        'auto_value' => false,
                    ],
                    static::INPUT_TEXT_BACKGROUND_COLOR => [
                        'title' => $this->l('Input text background color'),
                        'type' => 'color',
                        'name' => static::INPUT_TEXT_BACKGROUND_COLOR,
                        'validation' => 'isString',
                        'cast' => 'strval',
                        'size' => '1',
                        'value' => Configuration::get(static::INPUT_TEXT_BACKGROUND_COLOR) ?: '#000000',
                        'auto_value' => false,
                    ],
                    static::INPUT_FONT_FAMILY => [
                        'title' => $this->l('Input font family'),
                        'type' => 'fontselect',
                        'name' => static::INPUT_FONT_FAMILY,
                        'validation' => 'isString',
                        'cast' => 'strval',
                        'size' => '1',
                        'value' => Configuration::get(static::INPUT_FONT_FAMILY) ?: 'Open Sans',
                        'auto_value' => false,
                    ],
                    static::CHECKOUT_FONT_FAMILY => [
                        'title' => $this->l('Checkout font family'),
                        'type' => 'fontselect',
                        'name' => static::CHECKOUT_FONT_FAMILY,
                        'validation' => 'isString',
                        'cast' => 'strval',
                        'size' => '1',
                        'value' => Configuration::get(static::CHECKOUT_FONT_FAMILY) ?: 'Open Sans',
                        'auto_value' => false,
                        'class' => 'fixed-width-sm',
                    ],
                    static::CHECKOUT_FONT_SIZE => [
                        'title' => $this->l('Checkout font size'),
                        'type' => 'text',
                        'name' => static::CHECKOUT_FONT_SIZE,
                        'validation' => 'isString',
                        'cast' => 'strval',
                        'size' => '1',
                        'value' => Configuration::get(static::CHECKOUT_FONT_SIZE) ?: '15px',
                        'auto_value' => false,
                        'class' => 'fixed-width-sm',
                    ],
                    static::PAYMENT_REQUEST_BUTTON_STYLE => [
                        'title' => $this->l('Payment Request Button style'),
                        'type' => 'select',
                        'name' => static::PAYMENT_REQUEST_BUTTON_STYLE,
                        'validation' => 'isString',
                        'cast' => 'strval',
                        'list' => [
                            ['id' => 'dark', 'name' => 'Dark'],
                            ['id' => 'light', 'name' => 'Light'],
                            ['id' => 'light-outline', 'name' => 'Light outline'],
                        ],
                        'identifier' => 'id',
                        'value' => Configuration::get(static::PAYMENT_REQUEST_BUTTON_STYLE) ?: '15px',
                        'auto_value' => false,
                        'class' => 'fixed-width-sm',
                    ],
                    'STRIPE_PREVIEW' => [
                        'title' => $this->l('Preview'),
                        'type' => 'democheckout',
                        'name' => 'STRIPE_PREVIEW',
                        'validation' => 'isBool',
                        'cast' => 'intval',
                        'auto_value' => false,
                        'value' => false,
                    ],
                ],
                'submit' => [
                    'title' => $this->l('Save'),
                    'class' => 'button',
                ],
            ],
        ];
    }

    /**
     * Process temporary colors
     *
     * @throws PrestaShopException
     */
    public function ajaxProcessSaveDesign()
    {
        $colors = json_decode(file_get_contents('php://input'), true);

        Configuration::updateValue(static::INPUT_PLACEHOLDER_COLOR . '_TEMP', $colors['stripe_input_placeholder_color']);
        Configuration::updateValue(static::BUTTON_BACKGROUND_COLOR . '_TEMP', $colors['stripe_button_background_color']);
        Configuration::updateValue(static::BUTTON_FOREGROUND_COLOR . '_TEMP', $colors['stripe_button_foreground_color']);
        Configuration::updateValue(static::HIGHLIGHT_COLOR . '_TEMP', $colors['stripe_highlight_color']);
        Configuration::updateValue(static::ERROR_COLOR . '_TEMP', $colors['stripe_error_color']);
        Configuration::updateValue(static::ERROR_GLYPH_COLOR . '_TEMP', $colors['stripe_error_glyph_color']);
        Configuration::updateValue(static::INPUT_TEXT_FOREGROUND_COLOR . '_TEMP', $colors['stripe_payment_request_foreground_color']);
        Configuration::updateValue(static::INPUT_TEXT_BACKGROUND_COLOR . '_TEMP', $colors['stripe_payment_request_background_color']);
        Configuration::updateValue(static::INPUT_FONT_FAMILY . '_TEMP', $colors['stripe_input_font_family']);
        Configuration::updateValue(static::CHECKOUT_FONT_FAMILY . '_TEMP', $colors['stripe_checkout_font_family']);
        Configuration::updateValue(static::CHECKOUT_FONT_SIZE . '_TEMP', $colors['stripe_checkout_font_size']);
        Configuration::updateValue(static::PAYMENT_REQUEST_BUTTON_STYLE . '_TEMP', $colors['stripe_payment_request_style']);

        die(json_encode([
            'success' => true,
        ]));
    }

    /**
     * This method is used to render the payment button,
     * Take care if the button should be displayed or not.
     *
     * @param array $params Hook parameters
     *
     * @return string|false
     *
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function hookPayment($params)
    {
        if ($this->active && Utils::hasValidConfiguration()) {
            $cart = Context::getContext()->cart;

            $result = "";
            foreach ($this->methods->getAvailableMethods($cart) as $method) {
                $result .= $method->renderPaymentMethod($cart);
            }
            return $result;
        }
        return false;
    }

    /**
     * Hook to Advanced EU checkout
     *
     * @param array $params Hook parameters
     *
     * @return array|false Smarty variables, nothing if should not be shown
     * @throws PrestaShopException
     */
    public function hookDisplayPaymentEU($params)
    {
        if ($this->active && Utils::hasValidConfiguration()) {
            $cart = Context::getContext()->cart;


            $paymentOptions = [];
            foreach ($this->methods->getAvailableMethods($cart) as $method) {
                $paymentOptions[] = [
                    'cta_text' => $method->getCTA(),
                    'logo' => $method->getImageLink(),
                    'action' => $method->getLink()
                ];
            }
            return $paymentOptions;
        }

        return false;
    }

    /**
     * This hook is used to display the order confirmation page.
     *
     * @param array $params Hook parameters
     *
     * @return string Hook HTML
     *
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function hookPaymentReturn($params)
    {
        if (!$this->active) {
            return '';
        }

        /** @var Order $order */
        $order = $params['objOrder'];
        $currency = new Currency($order->id_currency);

        if (isset($order->reference) && $order->reference) {
            $totalToPay = (float)$order->getTotalPaid($currency);
            $reference = $order->reference;
        } else {
            $totalToPay = $order->total_paid_tax_incl;
            $reference = $this->l('Unknown');
        }

        if ($order->getCurrentOrderState()->id != Configuration::get('PS_OS_ERROR')) {
            $this->context->smarty->assign('status', 'ok');
        }

        $this->context->smarty->assign(
            [
                'id_order' => $order->id,
                'reference' => $reference,
                'params' => $params,
                'total' => Tools::displayPrice($totalToPay, $currency, false),
            ]
        );

        return $this->display(__FILE__, 'views/templates/front/confirmation.tpl');
    }

    /**
     * Hook to the top a payment page
     *
     * @return string Hook HTML
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function hookDisplayPaymentTop()
    {
        $cart = $this->context->cart;
        $controller = Context::getContext()->controller;

        // include front css file
        $controller->addCSS($this->_path . '/views/css/front.css');

        // include payment method specific javascripts and css files
        $scripts = [];
        foreach ($this->methods->getAvailableMethods($cart) as $method) {
            foreach ($method->getJavascriptUris() as $script) {
                $scripts[$script] = $script;
                $controller->addJS($script);
            }
            foreach ($method->getCssUris() as $css) {
                $controller->addCss($css);
            }
        }

        if ($scripts) {
            $this->context->smarty->assign([
                'jsAssets' => $scripts
            ]);
            return $this->display(__FILE__, 'views/templates/front/load-assets.tpl');
        }
        return '';
    }

    /**
     * Display on Back Office order page
     *
     * @param array $params Hok parameters
     *
     * @return string Hook HTML
     *
     * @throws SmartyException
     * @throws PrestaShopException
     */
    public function hookDisplayAdminOrder($params)
    {
        $cookie = new Cookie('stripe');

        /** @var AdminController $controller */
        $controller = $this->context->controller;

        if (isset($cookie->error)) {
            $controller->errors[] = $cookie->error;
        }
        if (isset($cookie->confirmation)) {
            $controller->confirmations[] = $cookie->confirmation;
        }
        unset($cookie->error);
        unset($cookie->confirmation);
        $cookie->write();

        if (Tools::getValue('stripeRefund') === 'refunded' && empty($controller->errors)) {
            $controller->confirmations[] = $this->l('The refund via Stripe has been successfully processed');
        }

        if (StripeTransaction::getTransactionsByOrderId($params['id_order'], true)) {
            $controller->addJS($this->_path . 'views/js/sweetalert-2.1.0.min.js');

            $order = new Order($params['id_order']);
            $orderCurrency = new Currency($order->id_currency);

            $totalRefundLeft = Utils::toCurrencyUnit($orderCurrency, $order->getTotalPaid());
            $totalRefundLeft -= (int)StripeTransaction::getRefundedAmountByOrderId($order->id);
            $totalRefundLeft = Utils::fromCurrencyUnit($orderCurrency, $totalRefundLeft);

            $this->context->smarty->assign(
                [
                    'stripe_review' => StripeReview::getByOrderId($order->id),
                    'stripe_transaction_list' => $this->renderAdminOrderTransactionList($params['id_order']),
                    'stripe_currency' => $orderCurrency,
                    'stripe_status' => StripeReview::getByOrderId($params['id_order']),
                    'stripe_total_amount' => $totalRefundLeft,
                    'stripe_module_refund_action' => $this->context->link->getAdminLink('AdminModules', true) .
                        "&configure={$this->name}&tab_module={$this->tab}&module_name={$this->name}&orderstriperefund",
                    'stripe_module_review_action' => $this->context->link->getAdminLink('AdminModules', true) .
                        "&configure={$this->name}&tab_module={$this->tab}&module_name={$this->name}&orderstripereview",
                    'id_order' => (int)$order->id,
                    'canViewStripeRefunds' => true,
                    'canEditStripeRefunds' => true,
                ]
            );

            return $this->display(__FILE__, 'views/templates/admin/adminorder.tpl');
        }

        return '';
    }

    /**
     * Render the admin order transaction list
     *
     * @param int $idOrder Order ID
     *
     * @return string Transaction list HTML
     *
     * @throws PrestaShopException
     * @throws SmartyException
     */
    protected function renderAdminOrderTransactionList($idOrder)
    {
        $results = StripeTransaction::getTransactionsByOrderId($idOrder);

        $order = new Order($idOrder);
        $currency = Currency::getCurrencyInstance($order->id_currency);

        $sourceTypes = [];
        foreach ($this->methods->getAllMethods() as $method) {
            $sourceTypes[$method->getMethodId()] = $method->getShortName();
        }

        foreach ($results as &$result) {
            // Process results
            $result['amount'] = Utils::fromCurrencyUnit($currency, $result['amount']);
            $result['amount'] = Tools::displayPrice($result['amount'], $currency);

            switch ($result['type']) {
                case StripeTransaction::TYPE_CHARGE:
                    $result['color'] = '#32CD32';
                    $result['type_icon'] = 'credit-card';
                    $result['type_text'] = $this->l('Charged');
                    break;
                case StripeTransaction::TYPE_PARTIAL_REFUND:
                    $result['color'] = '#FF8C00';
                    $result['type_icon'] = 'undo';
                    $result['type_text'] = $this->l('Partial refund');
                    break;
                case StripeTransaction::TYPE_FULL_REFUND:
                    $result['color'] = '#ec2e15';
                    $result['type_icon'] = 'undo';
                    $result['type_text'] = $this->l('Full refund');
                    break;
                case StripeTransaction::TYPE_AUTHORIZED:
                    $result['color'] = '#FF8C00';
                    $result['type_icon'] = 'unlock';
                    $result['type_text'] = $this->l('Authorized');
                    break;
                case StripeTransaction::TYPE_IN_REVIEW:
                    $result['color'] = '#FF8C00';
                    $result['type_icon'] = 'search';
                    $result['type_text'] = $this->l('In review');
                    break;
                case StripeTransaction::TYPE_CAPTURED:
                    $result['color'] = '#32CD32';
                    $result['type_icon'] = 'lock';
                    $result['type_text'] = $this->l('Captured');
                    break;
                case StripeTransaction::TYPE_CHARGE_FAIL:
                    $result['color'] = '#ec2e15';
                    $result['type_icon'] = 'close';
                    $result['type_text'] = $this->l('Charge failed');
                    break;
                default:
                    $result['color'] = '';
                    break;
            }

            $result['source_type'] = $sourceTypes[$result['source_type']] ?? $this->l('Unknown');

            switch ($result['source']) {
                case StripeTransaction::SOURCE_FRONT_OFFICE:
                    $result['source_text'] = $this->l('Front Office');
                    break;
                case StripeTransaction::SOURCE_BACK_OFFICE:
                    $result['source_text'] = $this->l('Back Office');
                    break;
                case StripeTransaction::SOURCE_WEBHOOK:
                    $result['source_text'] = $this->l('Webhook');
                    break;
                default:
                    $result['source_text'] = $this->l('Unknown');
                    break;
            }
        }

        $helperList = new HelperList();
        $helperList->list_id = 'stripe_transaction';
        $helperList->shopLinkType = false;
        $helperList->no_link = true;
        $helperList->_defaultOrderBy = 'date_add';
        $helperList->simple_header = true;
        $helperList->module = $this;
        $fieldsList = [
            'id_stripe_transaction' => [
                'type' => 'text',
                'title' => $this->l('ID'),
                'width' => 'auto',
            ],
            'type_icon' => [
                'type' => 'text',
                'title' => $this->l('Type'),
                'width' => 'auto',
                'color' => 'color',
                'text' => 'type_text',
                'callback' => 'displayEventLabel',
                'callback_object' => '\\StripeModule\\StripeTransaction',
            ],
            'amount' => [
                'type' => 'price',
                'title' => $this->l('Amount'),
                'width' => 'auto',
            ],
            'card_last_digits' => [
                'type' => 'text',
                'title' => $this->l('Credit card (last 4 digits)'),
                'width' => 'auto',
                'callback' => 'displayCardDigits',
                'callback_object' => '\\StripeModule\\StripeTransaction',
            ],
            'source_type' => [
                'type' => 'text',
                'title' => $this->l('Method'),
                'width' => 'auto',
            ],
            'source_text' => [
                'type' => 'text',
                'title' => $this->l('Source'),
                'width' => 'auto',
            ],
            'date_upd' => [
                'type' => 'datetime',
                'title' => $this->l('Date & time'),
                'width' => 'auto',
            ],
        ];

        $helperList->identifier = 'id_stripe_transaction';
        $helperList->token = Tools::getAdminTokenLite('AdminOrders');
        $helperList->currentIndex = AdminController::$currentIndex . '&' . http_build_query([
                'id_order' => $idOrder,
            ]);

        // Hide actions
        $helperList->tpl_vars['show_filters'] = false;
        $helperList->actions = [];
        $helperList->bulk_actions = [];

        $helperList->table = 'stripe_transaction';

        $listHtml = $helperList->generateList($results, $fieldsList);

        $doc = new DOMDocument('1.0', 'UTF-8');
        $doc->loadHTML('<meta charset="UTF-8">' . $listHtml);
        $node = $doc->getElementsByTagName('table')->item(0);

        return '<h4>' . $this->l('Transactions & Events') . '</h4>' . $doc->saveXML($node->parentNode);
    }

    /**
     * @param array $params
     *
     * @return void
     *
     * @noinspection PhpArrayWriteIsNotUsedInspection
     * @noinspection PhpArrayUsedOnlyForWriteInspection
     */
    public function hookActionAdminOrdersListingFieldsModifier($params)
    {
        static $called = false;
        if (!$called) {
            $params['fields']['payment']['callback'] = 'displayPaymentText';
            $params['fields']['payment']['callback_object'] = StripeReview::class;
            $params['fields']['payment']['callback_export'] = false;
            if (Tools::isSubmit('submitBulkupdateStripeCapture')) {
                $this->processBulkCapture();
            }

            $this->context->controller->addJquery();
            $this->context->controller->addJS($this->_path . 'views/js/list.js');
            $called = true;
        }
    }

    /**
     * @return void
     */
    protected function processBulkCapture()
    {
        $idOrders = Tools::getValue('orderBox');
        if (!is_array($idOrders)) {
            $this->context->controller->errors[] = $this->l('No orders found');

            return;
        } elseif (count($idOrders) > 10) {
            $this->context->controller->errors[] = $this->l('Currently only a maximum of 10 payments can be captured at a time');

            return;
        }

        try {
            foreach ($idOrders as $idOrder) {
                $charge = $this->api->getCharge(StripeTransaction::getChargeByIdOrder($idOrder));
                $charge->metadata = [
                    'from_back_office' => true,
                ];
                $charge->capture();
                $order = new Order($idOrder);

                $review = StripeReview::getByOrderId($idOrder);
                $review->status = StripeReview::CAPTURED;
                $review->save();

                $transaction = new StripeTransaction();
                $transaction->id_order = $idOrder;
                $transaction->id_charge = $charge->id;
                $transaction->source = StripeTransaction::SOURCE_FRONT_OFFICE;
                $transaction->type = StripeTransaction::TYPE_CAPTURED;
                $transaction->card_last_digits = (int)StripeTransaction::getLastFourDigitsByChargeId($charge->id);
                $transaction->amount = (int)$charge->amount;
                $transaction->save();

                $orderHistory = new OrderHistory();
                $orderHistory->id_order = $idOrder;
                $orderHistory->changeIdOrderState((int)Configuration::get('PS_OS_PAYMENT'), $idOrder, !$order->hasInvoice());
                $orderHistory->addWithemail(true);
            }
        } catch (Exception $e) {
            $this->addError(sprintf($this->l('An error occurred while capturing: %s'), $e->getMessage()));

            return;
        }
        /** @var AdminController $controller */
        $controller = $this->context->controller;
        $controller->confirmations[] = $this->l('The payments have been successfully captured');
    }

    /**
     * @return PaymentMethodsRepository
     */
    public function getPaymentMethodsRepository(): PaymentMethodsRepository
    {
        return $this->methods;
    }

    /**
     * Get Tab name from database
     *
     * @param string $className Class name of tab
     * @param int $idLang Language id
     *
     * @return string Returns the localized tab name
     */
    protected function getTabName($className, $idLang)
    {
        if ($className == null || $idLang == null) {
            return '';
        }

        try {
            return (string)Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
                (new DbQuery())
                    ->select('tl.`name`')
                    ->from('tab_lang', 'tl')
                    ->innerJoin('tab', 't', 't.`id_tab` = tl.`id_tab`')
                    ->where('t.`class_name` = \''.pSQL($className).'\'')
                    ->where('tl.`id_lang` = '.(int) $idLang)
            );
        } catch (Exception $e) {
            return $this->l('Unknown');
        }
    }

    /**
     * @param string $methodId
     *
     * @return void
     *
     * @throws PrestaShopException
     */
    protected function togglePaymentMethod(string $methodId)
    {
        $method = $this->methods->getMethod($methodId);
        if ($method) {
            $enabled = !$method->isEnabled();
            $method->setEnabled($enabled);

            die(json_encode([
                'success' => true,
                'text' => $enabled ? $this->l('Payment Method enabled') : $this->l('Payment method disabled'),
            ]));
        }
        die(json_encode([
            'success' => false,
            'text' => $this->l('Payment method not found'),
        ]));
    }

    /**
     * @return void
     * @throws PrestaShopException
     */
    protected function updatePaymentMethodsPositions()
    {
        $data = Tools::getValue('module-stripe');
        if (is_array($data)) {
            $methods = [];
            foreach ($data as $id) {
                if (preg_match("/^tr_[0-9]+_(.+)_[0-9]+$/", (string)$id, $matches)) {
                    if (isset($matches[1]) && $matches[1]) {
                        $methods[] = $matches[1];
                    }
                }
            }
            $this->methods->setMethodsOrder($methods);
            die(json_encode(['success' => true]));
        }
        die(json_encode(['success' => false]));
    }

    /**
     * @param string $menu
     *
     * @return string
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    protected function getModuleUrl(string $menu)
    {
        return $this->context->link->getAdminLink('AdminModules', true, [
            'configure' => $this->name,
            'tab_module' => $this->tab,
            'module_name' => $this->name,
            'menu' => $menu
        ]);
    }
}
