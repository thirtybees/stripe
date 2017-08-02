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

use StripeModule\StripeTransaction;
use ThirtybeesStripe\ApiRequestor;
use ThirtybeesStripe\Error\InvalidRequest;
use ThirtybeesStripe\HttpClient\GuzzleClient;

if (!defined('_TB_VERSION_')) {
    exit;
}

require_once __DIR__.'/classes/autoload.php';

/**
 * Class Stripe
 */
class Stripe extends PaymentModule
{
    const MENU_SETTINGS = 1;
    const MENU_TRANSACTIONS = 2;

    const ZIPCODE = 'STRIPE_ZIPCODE';
    const COLLECT_BILLING = 'STRIPE_COLLECT_BILLING';
    const COLLECT_SHIPPING = 'STRIPE_COLLECT_SHIPPING';
    const BITCOIN = 'STRIPE_BITCOIN';
    const ALIPAY = 'STRIPE_ALIPAY';

    const GO_LIVE = 'STRIPE_GO_LIVE';
    const SECRET_KEY_TEST = 'STRIPE_SECRET_KEY_TEST';
    const PUBLISHABLE_KEY_TEST = 'STRIPE_PUBLISHABLE_KEY_TEST';
    const SECRET_KEY_LIVE = 'STRIPE_SECRET_KEY_LIVE';
    const PUBLISHABLE_KEY_LIVE = 'STRIPE_PUBLISHABLE_KEY_LIVE';

    const SHOP_THUMB = 'STRIPE_SHOP_THUMB';

    const STATUS_VALIDATED = 'STRIPE_STAT_VALIDATED';
    const STATUS_PARTIAL_REFUND = 'STRIPE_STAT_PART_REFUND';
    const USE_STATUS_PARTIAL_REFUND = 'STRIPE_USE_STAT_PART_REFUND';
    const STATUS_REFUND = 'STRIPE_STAT_REFUND';
    const STATUS_SOFORT = 'STRIPE_STAT_SOFORT';
    const USE_STATUS_REFUND = 'STRIPE_USE_STAT_REFUND';
    const GENERATE_CREDIT_SLIP = 'STRIPE_CREDIT_SLIP';

    const SHOW_PAYMENT_LOGOS = 'STRIPE_PAYMENT_LOGOS';

    const STRIPE_CHECKOUT = 'STRIPE_STRIPE_CHECKOUT';
    const STRIPE_CC_FORM = 'STRIPE_STRIPE_CC_FORM';
    const STRIPE_CC_ANIMATION = 'STRIPE_STRIPE_CC_ANIMATION';
    const STRIPE_APPLE_PAY = 'STRIPE_STRIPE_APPLE';
    const IDEAL = 'STRIPE_IDEAL';
    const BANCONTACT = 'STRIPE_BANCONTACT';
    const GIROPAY = 'STRIPE_GIROPAY';
    const SOFORT = 'STRIPE_SOFORT';
    const THREEDSECURE = 'STRIPE_THREEDSECURE';

    const OPTIONS_MODULE_SETTINGS = 1;

    const TLS_OK = 'STRIPE_TLS_OK';
    const TLS_LAST_CHECK = 'STRIPE_TLS_LAST_CHECK';

    const ENUM_TLS_OK = 1;
    const ENUM_TLS_ERROR = -1;
    /** @var array Supported languages */
    public static $stripeLanguages = ['zh', 'nl', 'en', 'fr', 'de', 'it', 'ja', 'es'];
    /** @var array Supported zero-decimal currencies */
    public static $zeroDecimalCurrencies = ['bif', 'clp', 'djf', 'gnf', 'jpy', 'kmf', 'krw', 'mga', 'pyg', 'rwf', 'vdn', 'vuv', 'xaf', 'xof', 'xpf'];
    /** @var string $baseUrl Module base URL */
    public $baseUrl;
    public $moduleUrl;
    /** @var array Hooks */
    public $hooks = [
        'displayHeader',
        'displayPaymentTop',
        'displayPayment',
        'displayPaymentEU',
        'paymentReturn',
        'displayAdminOrder',
    ];

    /** @var int $menu Current menu */
    public $menu;

    /**
     * ThirtybeesStripe constructor.
     *
     * @since 1.0.0
     */
    public function __construct()
    {
        $this->name = 'stripe';
        $this->tab = 'payments_gateways';
        $this->version = '1.2.2';
        $this->author = 'thirty bees';
        $this->need_instance = 1;

        $this->bootstrap = true;

        $this->controllers = ['hook', 'validation', 'ajaxvalidation', 'sourcevalidation', 'eupayment'];

        $this->is_eu_compatible = 1;
        $this->currencies = true;
        $this->currencies_mode = 'checkbox';

        parent::__construct();

        $this->displayName = $this->l('Stripe');
        $this->description = $this->l('Accept payments with Stripe');

        $this->tb_versions_compliancy = '~1.0.0';
    }

    /**
     * Install the module
     *
     * @return bool Whether the module has been successfully installed
     *
     * @since 1.0.0
     */
    public function install()
    {
        if (!parent::install()) {
            parent::uninstall();

            return false;
        }

        foreach ($this->hooks as $hook) {
            $this->registerHook($hook);
        }

        StripeTransaction::createDatabase();

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
     * @since 1.0.0
     */
    public function uninstall()
    {
        foreach ($this->hooks as $hook) {
            $this->unregisterHook($hook);
        }

        Configuration::deleteByName(static::SECRET_KEY_TEST);
        Configuration::deleteByName(static::PUBLISHABLE_KEY_TEST);
        Configuration::deleteByName(static::SECRET_KEY_LIVE);
        Configuration::deleteByName(static::PUBLISHABLE_KEY_LIVE);
        Configuration::deleteByName(static::GO_LIVE);
        Configuration::deleteByName(static::USE_STATUS_REFUND);
        Configuration::deleteByName(static::USE_STATUS_PARTIAL_REFUND);
        Configuration::deleteByName(static::STATUS_PARTIAL_REFUND);
        Configuration::deleteByName(static::STATUS_REFUND);
        Configuration::deleteByName(static::GENERATE_CREDIT_SLIP);
        Configuration::deleteByName(static::ZIPCODE);
        Configuration::deleteByName(static::ALIPAY);
        Configuration::deleteByName(static::BITCOIN);
        Configuration::deleteByName(static::SHOW_PAYMENT_LOGOS);

        return parent::uninstall();
    }

    /**
     * Load the configuration form
     *
     * @return string HTML
     *
     * @since 1.0.0
     */
    public function getContent()
    {
        $output = '';

        $this->initNavigation();

        $this->moduleUrl = Context::getContext()->link->getAdminLink('AdminModules', false).'&token='.Tools::getAdminTokenLite('AdminModules').'&'.http_build_query([
            'configure' => $this->name,
        ]);

        $this->baseUrl = $this->context->link->getAdminLink('AdminModules', true).'&'.http_build_query([
            'configure'   => $this->name,
            'tab_module'  => $this->tab,
            'module_name' => $this->name,
        ]);

        $this->postProcess();

        $this->context->smarty->assign([
            'menutabs'           => $this->initNavigation(),
            'stripe_webhook_url' => $this->context->link->getModuleLink($this->name, 'hook', [], Tools::usingSecureMode()),
        ]);

        $output .= $this->display(__FILE__, 'views/templates/admin/navbar.tpl');

        switch (Tools::getValue('menu')) {
            case static::MENU_TRANSACTIONS:
                return $output.$this->renderTransactionsPage();
            default:
                $this->menu = static::MENU_SETTINGS;

                return $output.$this->renderSettingsPage();
        }
    }

    /**
     * Initialize navigation
     *
     * @return array Menu items
     *
     * @since 1.0.0
     */
    protected function initNavigation()
    {
        $menu = [
            static::MENU_SETTINGS     => [
                'short'  => $this->l('Settings'),
                'desc'   => $this->l('Module settings'),
                'href'   => $this->moduleUrl.'&menu='.static::MENU_SETTINGS,
                'active' => false,
                'icon'   => 'icon-gears',
            ],
            static::MENU_TRANSACTIONS => [
                'short'  => $this->l('Transactions'),
                'desc'   => $this->l('Stripe transactions'),
                'href'   => $this->moduleUrl.'&menu='.static::MENU_TRANSACTIONS,
                'active' => false,
                'icon'   => 'icon-credit-card',
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
     */
    protected function postProcess()
    {
        if (Tools::isSubmit('orderstriperefund') && Tools::isSubmit('stripe_refund_order') && Tools::isSubmit('stripe_refund_amount')) {
            $this->processRefund();
        } elseif ($this->menu == static::MENU_SETTINGS) {
            if (Tools::isSubmit('submitOptionsconfiguration') || Tools::isSubmit('submitOptionsconfiguration')) {
                $this->postProcessGeneralOptions();
                $this->postProcessOrderOptions();
            }

            if (Tools::isSubmit('checktls') && (bool) Tools::getValue('checktls')) {
                $this->tlsCheck();
            }
        }
    }

    /**
     *
     */
    protected function processRefund()
    {
        $idOrder = (int) Tools::getValue('stripe_refund_order');
        $amount = (float) Tools::getValue('stripe_refund_amount');

        $idCharge = StripeTransaction::getChargeByIdOrder($idOrder);
        $order = new Order($idOrder);
        $currency = new Currency($order->id_currency);
        $orderTotal = $order->getTotalPaid();

        if (!in_array(Tools::strtolower($currency->iso_code), static::$zeroDecimalCurrencies)) {
            $amount = (int) ($amount * 100);
            $orderTotal = (int) ($orderTotal * 100);
        }

        $amountRefunded = StripeTransaction::getRefundedAmountByOrderId($idOrder);

        $guzzle = new GuzzleClient();
        ApiRequestor::setHttpClient($guzzle);
        try {
            \ThirtybeesStripe\Stripe::setApiKey(Configuration::get(Stripe::SECRET_KEY_TEST));
            \ThirtybeesStripe\Refund::create(
                [
                    'charge'   => $idCharge,
                    'amount'   => $amount,
                    'metadata' => [
                        'from_back_office' => 'true',
                    ],
                ]
            );
        } catch (InvalidRequest $e) {
            $this->context->controller->errors[] = sprintf('Invalid Stripe request: %s', $e->getMessage());

            return;
        }

        if (Configuration::get(Stripe::USE_STATUS_REFUND) && 0 === (int) ($orderTotal - ($amountRefunded + $amount))) {
            // Full refund
            if (Configuration::get(Stripe::GENERATE_CREDIT_SLIP)) {
                $sql = new DbQuery();
                $sql->select('od.`id_order_detail`, od.`product_quantity`');
                $sql->from('order_detail', 'od');
                $sql->where('od.`id_order` = '.(int) $order->id);

                $fullProductList = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql);

                if (is_array($fullProductList) && !empty($fullProductList)) {
                    $productList = [];
                    $quantityList = [];
                    foreach ($fullProductList as $dbOrderDetail) {
                        $idOrderDetail = (int) $dbOrderDetail['id_order_detail'];
                        $productList[] = (int) $idOrderDetail;
                        $quantityList[$idOrderDetail] = (int) $dbOrderDetail['product_quantity'];
                    }
                    OrderSlip::createOrderSlip($order, $productList, $quantityList, $order->getShipping());
                }
            }

            $transaction = new StripeTransaction();
            $transaction->card_last_digits = (int) StripeTransaction::getLastFourDigitsByChargeId($idCharge);
            $transaction->id_charge = $idCharge;
            $transaction->amount = $amount;
            $transaction->id_order = $order->id;
            $transaction->type = StripeTransaction::TYPE_FULL_REFUND;
            $transaction->source = StripeTransaction::SOURCE_BACK_OFFICE;
            $transaction->add();

            $orderHistory = new OrderHistory();
            $orderHistory->id_order = $order->id;
            $orderHistory->changeIdOrderState((int) Configuration::get(Stripe::STATUS_REFUND), $idOrder);
            $orderHistory->addWithemail(true);
        } else {
            $transaction = new StripeTransaction();
            $transaction->card_last_digits = (int) StripeTransaction::getLastFourDigitsByChargeId($idCharge);
            $transaction->id_charge = $idCharge;
            $transaction->amount = $amount;
            $transaction->id_order = $order->id;
            $transaction->type = StripeTransaction::TYPE_PARTIAL_REFUND;
            $transaction->source = StripeTransaction::SOURCE_BACK_OFFICE;
            $transaction->add();

            if (Configuration::get(Stripe::USE_STATUS_PARTIAL_REFUND)) {
                $orderHistory = new OrderHistory();
                $orderHistory->id_order = $order->id;
                $orderHistory->changeIdOrderState((int) Configuration::get(Stripe::STATUS_PARTIAL_REFUND), $idOrder);
                $orderHistory->addWithemail(true);
            }
        }

        Tools::redirectAdmin($this->context->link->getAdminLink('AdminOrders', true).'&vieworder&id_order='.$idOrder);
    }

    /**
     * Process General Options
     *
     * @return void
     */
    protected function postProcessGeneralOptions()
    {
        $secretKeyTest = Tools::getValue(static::SECRET_KEY_TEST);
        $publishableKeyTest = Tools::getValue(static::PUBLISHABLE_KEY_TEST);
        $secretKeyLive = Tools::getValue(static::SECRET_KEY_LIVE);
        $publishableKeyLive = Tools::getValue(static::PUBLISHABLE_KEY_LIVE);
        $goLive = (bool) Tools::getValue(static::GO_LIVE);
        $zipcode = (bool) Tools::getValue(static::ZIPCODE);
        $bitcoin = (bool) Tools::getValue(static::BITCOIN);
        $alipay = (bool) Tools::getValue(static::ALIPAY);
        $ideal = (bool) Tools::getValue(static::IDEAL);
        $bancontact = (bool) Tools::getValue(static::BANCONTACT);
        $giropay = (bool) Tools::getValue(static::GIROPAY);
        $sofort = (bool) Tools::getValue(static::SOFORT);
        $threedsecure = (bool) Tools::getValue(static::THREEDSECURE);
        $showPaymentLogos = (bool) Tools::getValue(static::SHOW_PAYMENT_LOGOS);
        $collectBilling = (bool) Tools::getValue(static::COLLECT_BILLING);
        $collectShipping = (bool) Tools::getValue(static::COLLECT_SHIPPING);
        $checkout = (bool) Tools::getValue(static::STRIPE_CHECKOUT);
        $ccform = (bool) Tools::getValue(static::STRIPE_CC_FORM);
        $ccanim = (bool) Tools::getValue(static::STRIPE_CC_ANIMATION);
        $apple = (bool) Tools::getValue(static::STRIPE_APPLE_PAY);

        if ($goLive
            && (substr($publishableKeyLive, 0, 7) !== 'pk_live' || substr($secretKeyLive, 0, 7) !== 'sk_live')
        ) {
            $this->context->controller->confirmations = [];
            $this->context->controller->errors[] = ($this->l('Live mode has been chosen but one or more of the live keys are invalid'));

            return;
        }

        if (Configuration::get('PS_MULTISHOP_FEATURE_ACTIVE')) {
            if (Shop::getContext() == Shop::CONTEXT_ALL) {
                $this->updateAllValue(static::SECRET_KEY_TEST, $secretKeyTest);
                $this->updateAllValue(static::PUBLISHABLE_KEY_TEST, $publishableKeyTest);
                $this->updateAllValue(static::SECRET_KEY_LIVE, $secretKeyLive);
                $this->updateAllValue(static::PUBLISHABLE_KEY_LIVE, $publishableKeyLive);
                $this->updateAllValue(static::GO_LIVE, $goLive);
                $this->updateAllValue(static::ZIPCODE, $zipcode);
                $this->updateAllValue(static::BITCOIN, $bitcoin);
                $this->updateAllValue(static::ALIPAY, $alipay);
                $this->updateAllValue(static::IDEAL, $ideal);
                $this->updateAllValue(static::BANCONTACT, $bancontact);
                $this->updateAllValue(static::GIROPAY, $giropay);
                $this->updateAllValue(static::SOFORT, $sofort);
                $this->updateAllValue(static::THREEDSECURE, $threedsecure);
                $this->updateAllValue(static::SHOW_PAYMENT_LOGOS, $showPaymentLogos);
                $this->updateAllValue(static::COLLECT_BILLING, $collectBilling);
                $this->updateAllValue(static::COLLECT_SHIPPING, $collectShipping);
                $this->updateAllValue(static::STRIPE_CHECKOUT, $checkout);
                $this->updateAllValue(static::STRIPE_CC_FORM, $ccform);
                $this->updateAllValue(static::STRIPE_CC_ANIMATION, $ccanim);
                $this->updateAllValue(static::STRIPE_APPLE_PAY, $apple);
            } elseif (is_array(Tools::getValue('multishopOverrideOption'))) {
                $idShopGroup = (int) Shop::getGroupFromShop($this->getShopId(), true);
                $multishopOverride = Tools::getValue('multishopOverrideOption');
                if (Shop::getContext() == Shop::CONTEXT_GROUP) {
                    foreach (Shop::getShops(false, $this->getShopId()) as $idShop) {
                        if (isset($multishopOverride[static::SECRET_KEY_TEST]) && $multishopOverride[static::SECRET_KEY_TEST]) {
                            Configuration::updateValue(static::SECRET_KEY_TEST, $secretKeyTest, false, $idShopGroup, $idShop);
                        }
                        if (isset($multishopOverride[static::PUBLISHABLE_KEY_TEST]) && $multishopOverride[static::PUBLISHABLE_KEY_TEST]) {
                            Configuration::updateValue(static::PUBLISHABLE_KEY_TEST, $publishableKeyTest, false, $idShopGroup, $idShop);
                        }
                        if (isset($multishopOverride[static::SECRET_KEY_LIVE]) && $multishopOverride[static::SECRET_KEY_LIVE]) {
                            Configuration::updateValue(static::SECRET_KEY_LIVE, $secretKeyLive, false, $idShopGroup, $idShop);
                        }
                        if (isset($multishopOverride[static::PUBLISHABLE_KEY_LIVE]) && $multishopOverride[static::PUBLISHABLE_KEY_LIVE]) {
                            Configuration::updateValue(static::PUBLISHABLE_KEY_LIVE, $publishableKeyLive, false, $idShopGroup, $idShop);
                        }
                        if (isset($multishopOverride[static::GO_LIVE]) && $multishopOverride[static::GO_LIVE]) {
                            Configuration::updateValue(static::GO_LIVE, $goLive, false, $idShopGroup, $idShop);
                        }
                        if (isset($multishopOverride[static::ZIPCODE]) && $multishopOverride[static::ZIPCODE]) {
                            Configuration::updateValue(static::ZIPCODE, $zipcode, false, $idShopGroup, $idShop);
                        }
                        if (isset($multishopOverride[static::BITCOIN]) && $multishopOverride[static::BITCOIN]) {
                            Configuration::updateValue(static::BITCOIN, $bitcoin, false, $idShopGroup, $idShop);
                        }
                        if (isset($multishopOverride[static::ALIPAY]) && $multishopOverride[static::ALIPAY]) {
                            Configuration::updateValue(static::ALIPAY, $alipay, false, $idShopGroup, $idShop);
                        }
                        if (isset($multishopOverride[static::IDEAL]) && $multishopOverride[static::IDEAL]) {
                            Configuration::updateValue(static::IDEAL, $ideal, false, $idShopGroup, $idShop);
                        }
                        if (isset($multishopOverride[static::BANCONTACT]) && $multishopOverride[static::BANCONTACT]) {
                            Configuration::updateValue(static::BANCONTACT, $bancontact, false, $idShopGroup, $idShop);
                        }
                        if (isset($multishopOverride[static::GIROPAY]) && $multishopOverride[static::GIROPAY]) {
                            Configuration::updateValue(static::GIROPAY, $giropay, false, $idShopGroup, $idShop);
                        }
                        if (isset($multishopOverride[static::SOFORT]) && $multishopOverride[static::SOFORT]) {
                            Configuration::updateValue(static::SOFORT, $sofort, false, $idShopGroup, $idShop);
                        }
                        if (isset($multishopOverride[static::THREEDSECURE]) && $multishopOverride[static::THREEDSECURE]) {
                            Configuration::updateValue(static::THREEDSECURE, $threedsecure, false, $idShopGroup, $idShop);
                        }
                        if (isset($multishopOverride[static::SHOW_PAYMENT_LOGOS]) && $multishopOverride[static::SHOW_PAYMENT_LOGOS]) {
                            Configuration::updateValue(static::SHOW_PAYMENT_LOGOS, $showPaymentLogos, false, $idShopGroup, $idShop);
                        }
                        if (isset($multishopOverride[static::COLLECT_BILLING]) && $multishopOverride[static::COLLECT_BILLING]) {
                            Configuration::updateValue(static::COLLECT_BILLING, $collectBilling, false, $idShopGroup, $idShop);
                        }
                        if (isset($multishopOverride[static::COLLECT_SHIPPING]) && $multishopOverride[static::COLLECT_SHIPPING]) {
                            Configuration::updateValue(static::COLLECT_SHIPPING, $collectShipping, false, $idShopGroup, $idShop);
                        }
                        if (isset($multishopOverride[static::STRIPE_CHECKOUT]) && $multishopOverride[static::STRIPE_CHECKOUT]) {
                            Configuration::updateValue(static::STRIPE_CHECKOUT, $checkout, false, $idShopGroup, $idShop);
                        }
                        if (isset($multishopOverride[static::STRIPE_CC_FORM]) && $multishopOverride[static::STRIPE_CC_FORM]) {
                            Configuration::updateValue(static::STRIPE_CC_FORM, $ccform, false, $idShopGroup, $idShop);
                        }
                        if (isset($multishopOverride[static::STRIPE_CC_ANIMATION]) && $multishopOverride[static::STRIPE_CC_ANIMATION]) {
                            Configuration::updateValue(static::STRIPE_CC_ANIMATION, $ccanim, false, $idShopGroup, $idShop);
                        }
                        if (isset($multishopOverride[static::STRIPE_APPLE_PAY]) && $multishopOverride[static::STRIPE_APPLE_PAY]) {
                            Configuration::updateValue(static::STRIPE_APPLE_PAY, $apple, false, $idShopGroup, $idShop);
                        }
                    }
                } else {
                    $idShop = (int) $this->getShopId();
                    if (isset($multishopOverride[static::SECRET_KEY_TEST]) && $multishopOverride[static::SECRET_KEY_TEST]) {
                        Configuration::updateValue(static::SECRET_KEY_TEST, $secretKeyTest, false, $idShopGroup, $idShop);
                    }
                    if (isset($multishopOverride[static::PUBLISHABLE_KEY_TEST]) && $multishopOverride[static::PUBLISHABLE_KEY_TEST]) {
                        Configuration::updateValue(static::PUBLISHABLE_KEY_TEST, $publishableKeyTest, false, $idShopGroup, $idShop);
                    }
                    if (isset($multishopOverride[static::SECRET_KEY_LIVE]) && $multishopOverride[static::SECRET_KEY_LIVE]) {
                        Configuration::updateValue(static::SECRET_KEY_LIVE, $secretKeyLive, false, $idShopGroup, $idShop);
                    }
                    if (isset($multishopOverride[static::PUBLISHABLE_KEY_LIVE]) && $multishopOverride[static::PUBLISHABLE_KEY_LIVE]) {
                        Configuration::updateValue(static::PUBLISHABLE_KEY_LIVE, $publishableKeyLive, false, $idShopGroup, $idShop);
                    }
                    if (isset($multishopOverride[static::GO_LIVE]) && $multishopOverride[static::GO_LIVE]) {
                        Configuration::updateValue(static::GO_LIVE, $goLive, false, $idShopGroup, $idShop);
                    }
                    if (isset($multishopOverride[static::ZIPCODE]) && $multishopOverride[static::ZIPCODE]) {
                        Configuration::updateValue(static::ZIPCODE, $zipcode, false, $idShopGroup, $idShop);
                    }
                    if (isset($multishopOverride[static::BITCOIN]) && $multishopOverride[static::BITCOIN]) {
                        Configuration::updateValue(static::BITCOIN, $bitcoin, false, $idShopGroup, $idShop);
                    }
                    if (isset($multishopOverride[static::ALIPAY]) && $multishopOverride[static::ALIPAY]) {
                        Configuration::updateValue(static::ALIPAY, $alipay, false, $idShopGroup, $idShop);
                    }
                    if (isset($multishopOverride[static::IDEAL]) && $multishopOverride[static::IDEAL]) {
                        Configuration::updateValue(static::IDEAL, $ideal, false, $idShopGroup, $idShop);
                    }
                    if (isset($multishopOverride[static::BANCONTACT]) && $multishopOverride[static::BANCONTACT]) {
                        Configuration::updateValue(static::BANCONTACT, $bancontact, false, $idShopGroup, $idShop);
                    }
                    if (isset($multishopOverride[static::GIROPAY]) && $multishopOverride[static::GIROPAY]) {
                        Configuration::updateValue(static::GIROPAY, $giropay, false, $idShopGroup, $idShop);
                    }
                    if (isset($multishopOverride[static::SOFORT]) && $multishopOverride[static::SOFORT]) {
                        Configuration::updateValue(static::SOFORT, $sofort, false, $idShopGroup, $idShop);
                    }
                    if (isset($multishopOverride[static::THREEDSECURE]) && $multishopOverride[static::THREEDSECURE]) {
                        Configuration::updateValue(static::THREEDSECURE, $threedsecure, false, $idShopGroup, $idShop);
                    }
                    if (isset($multishopOverride[static::SHOW_PAYMENT_LOGOS]) && $multishopOverride[static::SHOW_PAYMENT_LOGOS]) {
                        Configuration::updateValue(static::SHOW_PAYMENT_LOGOS, $showPaymentLogos, false, $idShopGroup, $idShop);
                    }
                    if (isset($multishopOverride[static::COLLECT_BILLING]) && $multishopOverride[static::COLLECT_BILLING]) {
                        Configuration::updateValue(static::COLLECT_BILLING, $collectBilling, false, $idShopGroup, $idShop);
                    }
                    if (isset($multishopOverride[static::COLLECT_SHIPPING]) && $multishopOverride[static::COLLECT_SHIPPING]) {
                        Configuration::updateValue(static::COLLECT_SHIPPING, $collectShipping, false, $idShopGroup, $idShop);
                    }
                    if (isset($multishopOverride[static::STRIPE_CHECKOUT]) && $multishopOverride[static::STRIPE_CHECKOUT]) {
                        Configuration::updateValue(static::STRIPE_CHECKOUT, $checkout, false, $idShopGroup, $idShop);
                    }
                    if (isset($multishopOverride[static::STRIPE_CC_FORM]) && $multishopOverride[static::STRIPE_CC_FORM]) {
                        Configuration::updateValue(static::STRIPE_CC_FORM, $ccform, false, $idShopGroup, $idShop);
                    }
                    if (isset($multishopOverride[static::STRIPE_CC_ANIMATION]) && $multishopOverride[static::STRIPE_CC_ANIMATION]) {
                        Configuration::updateValue(static::STRIPE_CC_ANIMATION, $ccanim, false, $idShopGroup, $idShop);
                    }
                    if (isset($multishopOverride[static::STRIPE_APPLE_PAY]) && $multishopOverride[static::STRIPE_APPLE_PAY]) {
                        Configuration::updateValue(static::STRIPE_APPLE_PAY, $apple, false, $idShopGroup, $idShop);
                    }
                }
            }
        }

        Configuration::updateValue(static::SECRET_KEY_TEST, $secretKeyTest);
        Configuration::updateValue(static::PUBLISHABLE_KEY_TEST, $publishableKeyTest);
        Configuration::updateValue(static::SECRET_KEY_LIVE, $secretKeyLive);
        Configuration::updateValue(static::PUBLISHABLE_KEY_LIVE, $publishableKeyLive);
        Configuration::updateValue(static::GO_LIVE, $goLive);
        Configuration::updateValue(static::ZIPCODE, $zipcode);
        Configuration::updateValue(static::BITCOIN, $bitcoin);
        Configuration::updateValue(static::ALIPAY, $alipay);
        Configuration::updateValue(static::IDEAL, $ideal);
        Configuration::updateValue(static::BANCONTACT, $bancontact);
        Configuration::updateValue(static::GIROPAY, $giropay);
        Configuration::updateValue(static::SOFORT, $sofort);
        Configuration::updateValue(static::THREEDSECURE, $threedsecure);
        Configuration::updateValue(static::SHOW_PAYMENT_LOGOS, $showPaymentLogos);
        Configuration::updateValue(static::COLLECT_BILLING, $collectBilling);
        Configuration::updateValue(static::COLLECT_SHIPPING, $collectShipping);
        Configuration::updateValue(static::STRIPE_CHECKOUT, $checkout);
        Configuration::updateValue(static::STRIPE_CC_FORM, $ccform);
        Configuration::updateValue(static::STRIPE_CC_ANIMATION, $ccanim);
        Configuration::updateValue(static::STRIPE_APPLE_PAY, $apple);
    }

    /**
     * Update configuration value in ALL contexts
     *
     * @param string $key    Configuration key
     * @param mixed  $values Configuration values, can be string or array with id_lang as key
     * @param bool   $html   Contains HTML
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
        if (isset(Context::getContext()->employee->id) && Context::getContext()->employee->id && Shop::getContext() == Shop::CONTEXT_SHOP) {
            $cookie = Context::getContext()->cookie->getFamily('shopContext');

            return (int) Tools::substr($cookie['shopContext'], 2, count($cookie['shopContext']));
        }

        return (int) Context::getContext()->shop->id;
    }

    /**
     * Process Order Options
     *
     * @return void
     */
    protected function postProcessOrderOptions()
    {
        $statusValidated = Tools::getValue(static::STATUS_VALIDATED);
        $useStatusRefund = Tools::getValue(static::USE_STATUS_REFUND);
        $statusRefund = Tools::getValue(static::STATUS_REFUND);
        $statusSofort = Tools::getValue(static::STATUS_SOFORT);
        $useStatusPartialRefund = Tools::getValue(static::USE_STATUS_PARTIAL_REFUND);
        $statusPartialRefund = Tools::getValue(static::STATUS_PARTIAL_REFUND);
        $generateCreditSlip = (bool) Tools::getValue(static::GENERATE_CREDIT_SLIP);

        if (Configuration::get('PS_MULTISHOP_FEATURE_ACTIVE')) {
            if (Shop::getContext() == Shop::CONTEXT_ALL) {
                $this->updateAllValue(static::STATUS_VALIDATED, $statusValidated);
                $this->updateAllValue(static::USE_STATUS_REFUND, $useStatusRefund);
                $this->updateAllValue(static::STATUS_REFUND, $statusRefund);
                $this->updateAllValue(static::STATUS_SOFORT, $statusSofort);
                $this->updateAllValue(static::STATUS_PARTIAL_REFUND, $statusPartialRefund);
                $this->updateAllValue(static::USE_STATUS_PARTIAL_REFUND, $useStatusPartialRefund);
                $this->updateAllValue(static::GENERATE_CREDIT_SLIP, $generateCreditSlip);
            } elseif (is_array(Tools::getValue('multishopOverrideOption'))) {
                $idShopGroup = (int) Shop::getGroupFromShop($this->getShopId(), true);
                $multishopOverride = Tools::getValue('multishopOverrideOption');
                if (Shop::getContext() == Shop::CONTEXT_GROUP) {
                    foreach (Shop::getShops(false, $this->getShopId()) as $idShop) {
                        if (isset($multishopOverride[static::STATUS_VALIDATED]) && $multishopOverride[static::STATUS_VALIDATED]) {
                            Configuration::updateValue(static::STATUS_VALIDATED, $statusValidated, false, $idShopGroup, $idShop);
                        }
                        if (isset($multishopOverride[static::USE_STATUS_REFUND]) && $multishopOverride[static::USE_STATUS_REFUND]) {
                            Configuration::updateValue(static::USE_STATUS_REFUND, $useStatusRefund, false, $idShopGroup, $idShop);
                        }
                        if (isset($multishopOverride[static::STATUS_REFUND]) && $multishopOverride[static::STATUS_REFUND]) {
                            Configuration::updateValue(static::STATUS_REFUND, $statusRefund, false, $idShopGroup, $idShop);
                        }
                        if (isset($multishopOverride[static::STATUS_SOFORT]) && $multishopOverride[static::STATUS_SOFORT]) {
                            Configuration::updateValue(static::STATUS_SOFORT, $statusSofort, false, $idShopGroup, $idShop);
                        }
                        if (isset($multishopOverride[static::USE_STATUS_PARTIAL_REFUND]) && $multishopOverride[static::USE_STATUS_PARTIAL_REFUND]) {
                            Configuration::updateValue(static::STATUS_PARTIAL_REFUND, $useStatusPartialRefund, false, $idShopGroup, $idShop);
                        }
                        if (isset($multishopOverride[static::STATUS_PARTIAL_REFUND]) && $multishopOverride[static::STATUS_PARTIAL_REFUND]) {
                            Configuration::updateValue(static::STATUS_PARTIAL_REFUND, $statusPartialRefund, false, $idShopGroup, $idShop);
                        }
                        if (isset($multishopOverride[static::GENERATE_CREDIT_SLIP]) && $multishopOverride[static::GENERATE_CREDIT_SLIP]) {
                            Configuration::updateValue(static::GENERATE_CREDIT_SLIP, $generateCreditSlip, false, $idShopGroup, $idShop);
                        }
                    }
                } else {
                    $idShop = (int) $this->getShopId();
                    if (isset($multishopOverride[static::STATUS_VALIDATED]) && $multishopOverride[static::STATUS_VALIDATED]) {
                        Configuration::updateValue(static::STATUS_VALIDATED, $statusValidated, false, $idShopGroup, $idShop);
                    }
                    if (isset($multishopOverride[static::USE_STATUS_REFUND]) && $multishopOverride[static::USE_STATUS_REFUND]) {
                        Configuration::updateValue(static::USE_STATUS_REFUND, $useStatusRefund, false, $idShopGroup, $idShop);
                    }
                    if (isset($multishopOverride[static::STATUS_REFUND]) && $multishopOverride[static::STATUS_REFUND]) {
                        Configuration::updateValue(static::STATUS_REFUND, $statusRefund, false, $idShopGroup, $idShop);
                    }
                    if (isset($multishopOverride[static::STATUS_SOFORT]) && $multishopOverride[static::STATUS_SOFORT]) {
                        Configuration::updateValue(static::STATUS_SOFORT, $statusSofort, false, $idShopGroup, $idShop);
                    }
                    if (isset($multishopOverride[static::USE_STATUS_PARTIAL_REFUND]) && $multishopOverride[static::USE_STATUS_PARTIAL_REFUND]) {
                        Configuration::updateValue(static::STATUS_PARTIAL_REFUND, $useStatusPartialRefund, false, $idShopGroup, $idShop);
                    }
                    if (isset($multishopOverride[static::STATUS_PARTIAL_REFUND]) && $multishopOverride[static::STATUS_PARTIAL_REFUND]) {
                        Configuration::updateValue(static::STATUS_PARTIAL_REFUND, $statusPartialRefund, false, $idShopGroup, $idShop);
                    }
                    if (isset($multishopOverride[static::GENERATE_CREDIT_SLIP]) && $multishopOverride[static::GENERATE_CREDIT_SLIP]) {
                        Configuration::updateValue(static::GENERATE_CREDIT_SLIP, $generateCreditSlip, false, $idShopGroup, $idShop);
                    }
                }
            }
        }

        Configuration::updateValue(static::STATUS_VALIDATED, $statusValidated);
        Configuration::updateValue(static::USE_STATUS_REFUND, $useStatusRefund);
        Configuration::updateValue(static::STATUS_REFUND, $statusRefund);
        Configuration::updateValue(static::STATUS_SOFORT, $statusSofort);
        Configuration::updateValue(static::USE_STATUS_PARTIAL_REFUND, $useStatusPartialRefund);
        Configuration::updateValue(static::STATUS_PARTIAL_REFUND, $statusPartialRefund);
        Configuration::updateValue(static::GENERATE_CREDIT_SLIP, $generateCreditSlip);
    }

    /**
     * Check if TLS 1.2 is supported
     */
    protected function tlsCheck()
    {
        $guzzle = new \ThirtybeesStripe\HttpClient\GuzzleClient();
        \ThirtybeesStripe\ApiRequestor::setHttpClient($guzzle);
        \ThirtybeesStripe\Stripe::setApiKey('sk_test_BQokikJOvBiI2HlWgH4olfQ2');
        \ThirtybeesStripe\Stripe::$apiBase = 'https://api-tls12.stripe.com';
        try {
            \ThirtybeesStripe\Charge::all();
            $this->updateAllValue(static::TLS_OK, static::ENUM_TLS_OK);
        } catch (\ThirtybeesStripe\Error\ApiConnection $e) {
            $this->updateAllValue(static::TLS_OK, static::ENUM_TLS_ERROR);
        }
    }

    /**
     * Render the transactions page
     *
     * @return string HTML
     * @throws Exception
     * @throws SmartyException
     *
     * @since 1.0.0
     */
    protected function renderTransactionsPage()
    {
        $output = '';

        $this->context->smarty->assign(
            [
                'module_url' => $this->moduleUrl.'&menu='.static::MENU_TRANSACTIONS,
            ]
        );

        $output .= $this->renderTransactionsList();

        return $output;
    }

    /**
     * Render the transactions list
     *
     * @return string HTML
     * @throws PrestaShopDatabaseException
     *
     * @since 1.0.0
     */
    protected function renderTransactionsList()
    {
        $fieldsList = [
            'id_stripe_transaction' => ['title' => $this->l('ID'), 'width' => 'auto'],
            'type_icon'             => ['type' => 'type_icon', 'title' => $this->l('Type'), 'width' => 'auto', 'color' => 'color', 'text' => 'type_text'],
            'amount'                => ['type' => 'price', 'title' => $this->l('Amount'), 'width' => 'auto'],
            'card_last_digits'      => ['type' => 'text', 'title' => $this->l('Credit card (last 4 digits)'), 'width' => 'auto'],
            'source_text'           => ['type' => 'stripe_source', 'title' => $this->l('Source'), 'width' => 'auto'],
            'source_type'           => ['type' => 'text', 'title' => $this->l('Payment type'), 'width' => 'auto'],
            'date_upd'              => ['type' => 'datetime', 'title' => $this->l('Date & time'), 'width' => 'auto'],
        ];

        if (Tools::isSubmit('submitResetstripe_transaction')) {
            $cookie = $this->context->cookie;
            foreach ($fieldsList as $fieldName => $field) {
                unset($cookie->{StripeTransaction::$definition['table'].'Filter_'.$fieldName});
                unset($_POST[StripeTransaction::$definition['table'].'Filter_'.$fieldName]);
                unset($_GET[StripeTransaction::$definition['table'].'Filter_'.$fieldName]);
            }
            unset($this->context->cookie->{StripeTransaction::$definition['table'].'Orderby'});
            unset($this->context->cookie->{StripeTransaction::$definition['table'].'OrderWay'});

            $cookie->write();
        }

        $sql = new DbQuery();
        $sql->select('COUNT(*)');
        $sql->from(bqSQL(StripeTransaction::$definition['table']));

        $listTotal = (int) Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue($sql);

        $pagination = (int) $this->getSelectedPagination(StripeTransaction::$definition['table']);
        $currentPage = (int) $this->getSelectedPage(StripeTransaction::$definition['table'], $listTotal);

        $helperList = new HelperList();
        $helperList->id = 1;
        $helperList->shopLinkType = false;

        $helperList->list_id = StripeTransaction::$definition['table'];

        $helperList->module = $this;

        $helperList->bulk_actions = [
            'delete' => [
                'text'    => $this->l('Delete selected'),
                'confirm' => $this->l('Delete selected items?'),
            ],
        ];

        $helperList->actions = ['View'];

        $helperList->page = $currentPage;

        $helperList->_defaultOrderBy = StripeTransaction::$definition['primary'];

        if (Tools::isSubmit(StripeTransaction::$definition['table'].'Orderby')) {
            $helperList->orderBy = Tools::getValue(StripeTransaction::$definition['table'].'Orderby');
            $this->context->cookie->{StripeTransaction::$definition['table'].'Orderby'} = $helperList->orderBy;
        } elseif (!empty($this->context->cookie->{StripeTransaction::$definition['table'].'Orderby'})) {
            $helperList->orderBy = $this->context->cookie->{StripeTransaction::$definition['table'].'Orderby'};
        } else {
            $helperList->orderBy = StripeTransaction::$definition['primary'];
        }

        if (Tools::isSubmit(StripeTransaction::$definition['table'].'Orderway')) {
            $helperList->orderWay = Tools::strtoupper(Tools::getValue(StripeTransaction::$definition['table'].'Orderway'));
            $this->context->cookie->{StripeTransaction::$definition['table'].'Orderway'} = Tools::getValue(StripeTransaction::$definition['table'].'Orderway');
        } elseif (!empty($this->context->cookie->{StripeTransaction::$definition['table'].'Orderway'})) {
            $helperList->orderWay = Tools::strtoupper($this->context->cookie->{StripeTransaction::$definition['table'].'Orderway'});
        } else {
            $helperList->orderWay = 'DESC';
        }

        $filterSql = $this->getSQLFilter($helperList, $fieldsList);

        $sql = new DbQuery();
        $sql->select('*');
        $sql->from(bqSQL(StripeTransaction::$definition['table']), 'st');
        $sql->orderBy('`'.bqSQL($helperList->orderBy).'` '.pSQL($helperList->orderWay));
        $sql->where('1 '.$filterSql);
        $sql->limit($pagination, ($currentPage - 1) * $pagination);

        $results = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql);

        foreach ($results as &$result) {
            // Process results
            $currency = $this->getCurrencyIdByOrderId($result['id_order']);
            if (!in_array(Tools::strtolower($currency->iso_code), Stripe::$zeroDecimalCurrencies)) {
                $result['amount'] = (float) ($result['amount'] / 100);
            }
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

            switch ($result['source_type']) {
                case 'ideal':
                    $result['source_type'] = $this->l('iDEAL');
                    break;
                case 'sofort':
                    $result['source_type'] = $this->l('Sofort Banking');
                    break;
                case 'bancontact':
                    $result['source_type'] = $this->l('Bancontact');
                    break;
                case 'giropay':
                    $result['source_type'] = $this->l('Giropay');
                    break;
                case 'three_d_secure':
                    $result['source_type'] = $this->l('3D Secure');
                    break;
                default:
                    $result['source_type'] = $this->l('Credit Card');
                    break;
            }
        }

        $helperList->listTotal = count($results);

        $helperList->identifier = StripeTransaction::$definition['primary'];
        $helperList->title = $this->l('Transactions');
        $helperList->token = Tools::getAdminTokenLite('AdminModules');
        $helperList->currentIndex = AdminController::$currentIndex.'&'.http_build_query(
                [
                    'configure' => $this->name,
                    'menu'      => static::MENU_TRANSACTIONS,
                ]
            );

        $helperList->table = StripeTransaction::$definition['table'];

        $helperList->bulk_actions = false;

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
            $idList.'_pagination',
            isset($this->context->cookie->{$idList.'_pagination'}) ? $this->context->cookie->{$idList.'_pagination'} : $defaultPagination
        );

        return $selectedPagination;
    }

    /**
     * Get selected page
     *
     * @param int $idList    List ID
     * @param int $listTotal Total list items
     *
     * @return int|mixed
     */
    protected function getSelectedPage($idList, $listTotal)
    {
        /* Determine current page number */
        $page = (int) Tools::getValue('submitFilter'.$idList);

        if (!$page) {
            $page = 1;
        }

        $totalPages = max(1, ceil($listTotal / $this->getSelectedPagination($idList)));

        if ($page > $totalPages) {
            $page = $totalPages;
        }

        $this->page = (int) $page;

        return $page;
    }

    /**
     * @param HelperList $helperList
     * @param array      $fieldsList
     *
     * @return array|string
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
                    unset($helperList->context->cookie->{$prefix.$key});
                } elseif (stripos($key, $helperList->list_id.'Filter_') === 0) {
                    $helperList->context->cookie->{$prefix.$key} = !is_array($value) ? $value : serialize($value);
                } elseif (stripos($key, 'submitFilter') === 0) {
                    $helperList->context->cookie->$key = !is_array($value) ? $value : serialize($value);
                }
            }

            foreach ($_GET as $key => $value) {
                if (stripos($key, $helperList->list_id.'Filter_') === 0) {
                    $helperList->context->cookie->{$prefix.$key} = !is_array($value) ? $value : serialize($value);
                } elseif (stripos($key, 'submitFilter') === 0) {
                    $helperList->context->cookie->$key = !is_array($value) ? $value : serialize($value);
                }
                if (stripos($key, $helperList->list_id.'Orderby') === 0 && is_string($value) && Validate::isOrderBy($value)) {
                    if ($value === '' || $value == $helperList->_defaultOrderBy) {
                        unset($helperList->context->cookie->{$prefix.$key});
                    } else {
                        $helperList->context->cookie->{$prefix.$key} = $value;
                    }
                } elseif (stripos($key, $helperList->list_id.'Orderway') === 0 && is_string($value) && Validate::isOrderWay($value)) {
                    if ($value === '') {
                        unset($helperList->context->cookie->{$prefix.$key});
                    } else {
                        $helperList->context->cookie->{$prefix.$key} = $value;
                    }
                }
            }
        }

        $filters = $helperList->context->cookie->getFamily($prefix.$helperList->list_id.'Filter_');
        $definition = false;
        if (isset($helperList->className) && $helperList->className) {
            $definition = ObjectModel::getDefinition($helperList->className);
        }

        foreach ($filters as $key => $value) {
            /* Extracting filters from $_POST on key filter_ */
            if ($value != null && !strncmp($key, $prefix.$helperList->list_id.'Filter_', 7 + Tools::strlen($prefix.$helperList->list_id))) {
                $key = Tools::substr($key, 7 + Tools::strlen($prefix.$helperList->list_id));
                /* Table alias could be specified using a ! eg. alias!field */
                $tmpTab = explode('!', $key);
                $filter = count($tmpTab) > 1 ? $tmpTab[1] : $tmpTab[0];

                if ($field = $this->filterToField($fieldsList, $key, $filter)) {
                    $type = (array_key_exists('filter_type', $field) ? $field['filter_type'] : (array_key_exists('type', $field) ? $field['type'] : false));
                    if (($type == 'date' || $type == 'datetime') && is_string($value)) {
                        $value = Tools::unSerialize($value);
                    }
                    $key = isset($tmpTab[1]) ? $tmpTab[0].'.`'.$tmpTab[1].'`' : '`'.$tmpTab[0].'`';

                    /* Only for date filtering (from, to) */
                    if (is_array($value)) {
                        if (isset($value[0]) && !empty($value[0])) {
                            if (!Validate::isDate($value[0])) {
                                return $this->displayError('The \'From\' date format is invalid (YYYY-MM-DD)');
                            } else {
                                $sqlFilter .= ' AND '.pSQL($key).' >= \''.pSQL(Tools::dateFrom($value[0])).'\'';
                            }
                        }

                        if (isset($value[1]) && !empty($value[1])) {
                            if (!Validate::isDate($value[1])) {
                                return $this->displayError('The \'To\' date format is invalid (YYYY-MM-DD)');
                            } else {
                                $sqlFilter .= ' AND '.pSQL($key).' <= \''.pSQL(Tools::dateTo($value[1])).'\'';
                            }
                        }
                    } else {
                        $sqlFilter .= ' AND ';
                        $checkKey = ($key == $helperList->identifier || $key == '`'.$helperList->identifier.'`');
                        $alias = ($definition && !empty($definition['fields'][$filter]['shop'])) ? 'sa' : 'a';

                        if ($type == 'int' || $type == 'bool') {
                            $sqlFilter .= (($checkKey || $key == '`active`') ? $alias.'.' : '').pSQL($key).' = '.(int) $value.' ';
                        } elseif ($type == 'decimal') {
                            $sqlFilter .= ($checkKey ? $alias.'.' : '').pSQL($key).' = '.(float) $value.' ';
                        } elseif ($type == 'select') {
                            $sqlFilter .= ($checkKey ? $alias.'.' : '').pSQL($key).' = \''.pSQL($value).'\' ';
                        } elseif ($type == 'price') {
                            $value = (float) str_replace(',', '.', $value);
                            $sqlFilter .= ($checkKey ? $alias.'.' : '').pSQL($key).' = '.pSQL(trim($value)).' ';
                        } else {
                            $sqlFilter .= ($checkKey ? $alias.'.' : '').pSQL($key).' LIKE \'%'.pSQL(trim($value)).'%\' ';
                        }
                    }
                }
            }
        }

        return $sqlFilter;
    }

    /**
     * @param $fieldsList
     * @param $key
     * @param $filter
     *
     * @return bool
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
     * @param $idOrder
     *
     * @return Currency
     */
    protected function getCurrencyIdByOrderId($idOrder)
    {
        $order = new Order($idOrder);
        if (Validate::isLoadedObject($order)) {
            $currency = Currency::getCurrencyInstance($order->id_currency);
        } else {
            $currency = Currency::getCurrencyInstance((int) Configuration::get('PS_CURRENCY_DEFAULT'));
        }

        return $currency;
    }

    /**
     * Render the general settings page
     *
     * @return string HTML
     * @throws Exception
     * @throws SmartyException
     *
     * @since 1.0.0
     */
    protected function renderSettingsPage()
    {
        $output = '';

        $this->context->smarty->assign(
            [
                'module_url' => $this->moduleUrl.'&menu='.static::MENU_SETTINGS,
                'tls_ok'     => (int) Configuration::get(static::TLS_OK),
                'baseUrl'    => $this->baseUrl,
            ]
        );

        $output .= $this->display(__FILE__, 'views/templates/admin/configure.tpl');
        $output .= $this->display(__FILE__, 'views/templates/admin/tlscheck.tpl');

        $output .= $this->renderGeneralOptions();

        return $output;
    }

    /**
     * Render the General options form
     *
     * @return string HTML
     *
     * @since 1.0.0
     */
    protected function renderGeneralOptions()
    {
        $helper = new HelperOptions();
        $helper->id = static::OPTIONS_MODULE_SETTINGS;
        $helper->module = $this;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex.'&configure='.$this->name;
        $helper->title = $this->displayName;
        $helper->table = 'configuration';
        $helper->show_toolbar = false;

        return $helper->generateOptions(
            array_merge(
                $this->getGeneralOptions(),
                $this->getStripeCheckoutOptions(),
                $this->getStripeCreditCardOptions(),
                $this->getEuropeanPaymentMethodsOptions(),
                $this->getApplePayOptions(),
                $this->getOrderOptions()
            )
        );
    }

    /**
     * Get available general options
     *
     * @return array General options
     *
     * @since 1.0.0
     */
    protected function getGeneralOptions()
    {
        return [
            'api' => [
                'title'  => $this->l('API Settings'),
                'icon'   => 'icon-server',
                'fields' => [
                    static::PUBLISHABLE_KEY_TEST => [
                        'title'       => $this->l('Publishable key (test)'),
                        'type'        => 'text',
                        'name'        => static::PUBLISHABLE_KEY_TEST,
                        'value'       => Configuration::get(static::PUBLISHABLE_KEY_TEST),
                        'validation'  => 'isString',
                        'cast'        => 'strval',
                        'placeholder' => 'pk_test...',
                        'size'        => 64,
                    ],
                    static::SECRET_KEY_TEST      => [
                        'title'       => $this->l('Secret key (test)'),
                        'type'        => 'text',
                        'name'        => static::SECRET_KEY_TEST,
                        'value'       => Configuration::get(static::SECRET_KEY_TEST),
                        'validation'  => 'isString',
                        'cast'        => 'strval',
                        'placeholder' => 'sk_test...',
                        'size'        => 64,
                    ],
                    static::PUBLISHABLE_KEY_LIVE => [
                        'title'       => $this->l('Publishable key (live)'),
                        'type'        => 'text',
                        'name'        => static::PUBLISHABLE_KEY_TEST,
                        'value'       => Configuration::get(static::PUBLISHABLE_KEY_TEST),
                        'validation'  => 'isString',
                        'cast'        => 'strval',
                        'placeholder' => 'pk_live...',
                        'size'        => 64,
                    ],
                    static::SECRET_KEY_LIVE      => [
                        'title'       => $this->l('Secret key (live)'),
                        'type'        => 'text',
                        'name'        => static::SECRET_KEY_TEST,
                        'value'       => Configuration::get(static::SECRET_KEY_TEST),
                        'validation'  => 'isString',
                        'cast'        => 'strval',
                        'placeholder' => 'sk_live...',
                        'size'        => 64,
                    ],
                    static::GO_LIVE    => [
                        'title'      => $this->l('Go live'),
                        'type'       => 'bool',
                        'desc'       => $this->l('Enable this options to accept live payments, otherwise the test keys are used, which you can use to test your store.'),
                        'name'       => static::GO_LIVE,
                        'value'      => Configuration::get(static::GO_LIVE),
                        'validation' => 'isBool',
                        'cast'       => 'intval',
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
     * @since 1.0.0
     */
    protected function getStripeCheckoutOptions()
    {
        return [
            'checkout' => [
                'title'  => $this->l('Stripe Checkout'),
                'icon'   => 'icon-credit-card',
                'fields' => [
                    static::STRIPE_CHECKOUT    => [
                        'title'      => $this->l('Enable Stripe Checkout'),
                        'type'       => 'bool',
                        'name'       => static::STRIPE_CHECKOUT,
                        'value'      => Configuration::get(static::STRIPE_CHECKOUT),
                        'validation' => 'isBool',
                        'cast'       => 'intval',
                    ],
                    static::COLLECT_BILLING    => [
                        'title'      => $this->l('Collect billing address'),
                        'type'       => 'bool',
                        'name'       => static::COLLECT_BILLING,
                        'value'      => Configuration::get(static::COLLECT_BILLING),
                        'validation' => 'isBool',
                        'cast'       => 'intval',
                    ],
                    static::COLLECT_SHIPPING   => [
                        'title'      => $this->l('Collect shipping address'),
                        'type'       => 'bool',
                        'name'       => static::COLLECT_SHIPPING,
                        'value'      => Configuration::get(static::COLLECT_SHIPPING),
                        'validation' => 'isBool',
                        'cast'       => 'intval',
                    ],
                    static::ZIPCODE            => [
                        'title'      => $this->l('Zipcode / postcode verification'),
                        'type'       => 'bool',
                        'name'       => static::ZIPCODE,
                        'value'      => Configuration::get(static::ZIPCODE),
                        'validation' => 'isBool',
                        'cast'       => 'intval',
                    ],
                    static::BITCOIN            => [
                        'title'      => $this->l('Accept Bitcoins'),
                        'type'       => 'bool',
                        'name'       => static::BITCOIN,
                        'value'      => Configuration::get(static::BITCOIN),
                        'validation' => 'isBool',
                        'cast'       => 'intval',
                    ],
                    static::ALIPAY             => [
                        'title'      => $this->l('Accept Alipay'),
                        'type'       => 'bool',
                        'name'       => static::ALIPAY,
                        'value'      => Configuration::get(static::ALIPAY),
                        'validation' => 'isBool',
                        'cast'       => 'intval',
                    ],
                    static::SHOW_PAYMENT_LOGOS => [
                        'title'      => $this->l('Show payment logos'),
                        'type'       => 'bool',
                        'name'       => static::SHOW_PAYMENT_LOGOS,
                        'value'      => Configuration::get(static::SHOW_PAYMENT_LOGOS),
                        'validation' => 'isBool',
                        'cast'       => 'intval',
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
     * @since 1.0.0
     */
    protected function getStripeCreditCardOptions()
    {
        return [
            'creditcard' => [
                'title'  => $this->l('Stripe credit card form'),
                'icon'   => 'icon-credit-card',
                'fields' => [
                    static::STRIPE_CC_FORM      => [
                        'title'      => $this->l('Enable Stripe credit card form'),
                        'type'       => 'bool',
                        'name'       => static::STRIPE_CC_FORM,
                        'value'      => Configuration::get(static::STRIPE_CC_FORM),
                        'validation' => 'isBool',
                        'cast'       => 'intval',
                    ],
                    static::STRIPE_CC_ANIMATION => [
                        'title'      => $this->l('Enable credit card animation'),
                        'type'       => 'bool',
                        'name'       => static::STRIPE_CC_ANIMATION,
                        'value'      => Configuration::get(static::STRIPE_CC_ANIMATION),
                        'validation' => 'isBool',
                        'cast'       => 'intval',
                    ],
                    static::THREEDSECURE       => [
                        'title'      => $this->l('Enforce 3D Secure'),
                        'type'       => 'bool',
                        'name'       => static::THREEDSECURE,
                        'value'      => Configuration::get(static::THREEDSECURE),
                        'validation' => 'isBool',
                        'cast'       => 'intval',
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
     * @return array
     *
     * @since 1.2.0
     */
    protected function getEuropeanPaymentMethodsOptions()
    {
        return [
            'euro' => [
                'title'  => $this->l('European payment methods'),
                'icon'   => 'icon-euro',
                'fields' => [
                    static::IDEAL      => [
                        'title'      => $this->l('Accept iDEAL'),
                        'type'       => 'bool',
                        'name'       => static::IDEAL,
                        'value'      => Configuration::get(static::IDEAL),
                        'validation' => 'isBool',
                        'cast'       => 'intval',
                    ],
                    static::BANCONTACT => [
                        'title'      => $this->l('Accept Bancontact'),
                        'type'       => 'bool',
                        'name'       => static::BANCONTACT,
                        'value'      => Configuration::get(static::BANCONTACT),
                        'validation' => 'isBool',
                        'cast'       => 'intval',
                    ],
                    static::GIROPAY    => [
                        'title'      => $this->l('Accept Giropay'),
                        'type'       => 'bool',
                        'name'       => static::GIROPAY,
                        'value'      => Configuration::get(static::GIROPAY),
                        'validation' => 'isBool',
                        'cast'       => 'intval',
                    ],
                    static::SOFORT     => [
                        'title'      => $this->l('Accept Sofort'),
                        'type'       => 'bool',
                        'name'       => static::SOFORT,
                        'value'      => Configuration::get(static::SOFORT),
                        'validation' => 'isBool',
                        'cast'       => 'intval',
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
     * Get available Apple Pay options
     *
     * @return array General options
     *
     * @since 1.0.0
     */
    protected function getApplePayOptions()
    {
        return [
            'apple' => [
                'title'  => $this->l('Apple Pay'),
                'icon'   => 'icon-mobile-phone',
                'fields' => [
                    static::STRIPE_APPLE_PAY => [
                        'title'      => $this->l('Enable Apple Pay'),
                        'type'       => 'bool',
                        'name'       => static::STRIPE_APPLE_PAY,
                        'value'      => Configuration::get(static::STRIPE_APPLE_PAY),
                        'validation' => 'isBool',
                        'cast'       => 'intval',
                        'size'       => 64,
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
     * Get available options for orders
     *
     * @return array Order options
     *
     * @since 1.0.0
     */
    protected function getOrderOptions()
    {
        $orderStatuses = OrderState::getOrderStates($this->context->language->id);

        $statusValidated = (int) Configuration::get(static::STATUS_VALIDATED);
        if ($statusValidated < 1) {
            $statusValidated = (int) Configuration::get('PS_OS_PAYMENT');
        }

        $statusPartialRefund = (int) Configuration::get(static::STATUS_PARTIAL_REFUND);
        if ($statusPartialRefund < 1) {
            $statusPartialRefund = (int) Configuration::get('PS_OS_REFUND');
        }

        $statusRefund = (int) Configuration::get(static::STATUS_REFUND);
        if ($statusRefund < 1) {
            $statusRefund = (int) Configuration::get('PS_OS_REFUND');
        }

        return [
            'orders' => [
                'title'  => $this->l('Order Settings'),
                'icon'   => 'icon-credit-card',
                'fields' => [
                    static::STATUS_VALIDATED          => [
                        'title'      => $this->l('Payment accepted status'),
                        'des'        => $this->l('Order status to use when the payment is accepted'),
                        'type'       => 'select',
                        'list'       => $orderStatuses,
                        'identifier' => 'id_order_state',
                        'name'       => static::STATUS_VALIDATED,
                        'value'      => $statusValidated,
                        'validation' => 'isString',
                        'cast'       => 'strval',
                    ],
                    static::USE_STATUS_PARTIAL_REFUND => [
                        'title'      => $this->l('Use partial refund status'),
                        'type'       => 'bool',
                        'name'       => static::USE_STATUS_PARTIAL_REFUND,
                        'value'      => Configuration::get(static::USE_STATUS_PARTIAL_REFUND),
                        'validation' => 'isBool',
                        'cast'       => 'intval',
                    ],
                    static::STATUS_PARTIAL_REFUND     => [
                        'title'      => $this->l('Partial refund status'),
                        'desc'       => $this->l('Order status to use when the order is partially refunded'),
                        'type'       => 'select',
                        'list'       => $orderStatuses,
                        'identifier' => 'id_order_state',
                        'name'       => static::STATUS_PARTIAL_REFUND,
                        'value'      => $statusPartialRefund,
                        'validation' => 'isString',
                        'cast'       => 'strval',
                    ],
                    static::USE_STATUS_REFUND         => [
                        'title'      => $this->l('Use refund status'),
                        'type'       => 'bool',
                        'name'       => static::USE_STATUS_REFUND,
                        'value'      => Configuration::get(static::USE_STATUS_REFUND),
                        'validation' => 'isBool',
                        'cast'       => 'intval',
                    ],
                    static::STATUS_REFUND             => [
                        'title'      => $this->l('Refund status'),
                        'desc'       => $this->l('Order status to use when the order is refunded'),
                        'type'       => 'select',
                        'list'       => $orderStatuses,
                        'identifier' => 'id_order_state',
                        'name'       => static::PUBLISHABLE_KEY_TEST,
                        'value'      => $statusRefund,
                        'validation' => 'isString',
                        'cast'       => 'strval',
                    ],
                    static::STATUS_SOFORT             => [
                        'title'      => $this->l('Sofort status'),
                        'desc'       => $this->l('Order status to use when a Sofort Banking payment is pending'),
                        'type'       => 'select',
                        'list'       => $orderStatuses,
                        'identifier' => 'id_order_state',
                        'name'       => static::STATUS_SOFORT,
                        'value'      => $statusValidated,
                        'validation' => 'isString',
                        'cast'       => 'strval',
                    ],
                    static::GENERATE_CREDIT_SLIP      => [
                        'title'      => $this->l('Generate credit slip'),
                        'desc'       => $this->l('Automatically generate a credit slip when the order is fully refunded'),
                        'type'       => 'bool',
                        'name'       => static::GENERATE_CREDIT_SLIP,
                        'value'      => Configuration::get(static::GENERATE_CREDIT_SLIP),
                        'validation' => 'isBool',
                        'cast'       => 'intval',
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
     * This method is used to render the payment button,
     * Take care if the button should be displayed or not.
     *
     * @param array $params Hook parameters
     *
     * @return string|bool
     */
    public function hookPayment($params)
    {
        if (!$this->active ||
            (!Configuration::get(static::SECRET_KEY_TEST) && !Configuration::get(static::PUBLISHABLE_KEY_TEST))
            && (!Configuration::get(static::SECRET_KEY_LIVE) && !Configuration::get(static::PUBLISHABLE_KEY_LIVE))
        ) {
            return false;
        }

        /** @var Cookie $cookie */
        $cookie = $params['cookie'];

        $this->checkShopThumb();

        $stripeEmail = $cookie->email;

        /** @var Cart $cart */
        $cart = $params['cart'];
        $currency = new Currency($cart->id_currency);

        $link = $this->context->link;

        $stripeAmount = $cart->getOrderTotal();
        if (!in_array(Tools::strtolower($currency->iso_code), static::$zeroDecimalCurrencies)) {
            $stripeAmount = (int) ($stripeAmount * 100);
        }

        $invoiceAddress = new Address((int) $cart->id_address_invoice);
        $country = new Country($invoiceAddress->id_country);
        $customer = new Customer($cart->id_customer);

        $autoplay = true;
        $this->context->smarty->assign(
            [
                'stripe_name'                   => $invoiceAddress->firstname.' '.$invoiceAddress->lastname,
                'stripe_email'                  => $stripeEmail,
                'stripe_currency'               => $currency->iso_code,
                'stripe_country'                => Tools::strtoupper($country->iso_code),
                'stripe_amount'                 => $stripeAmount,
                'stripe_amount_string'          => (string) $cart->getOrderTotal(),
                'stripe_amount_formatted'       => Tools::displayPrice($cart->getOrderTotal(), Currency::getCurrencyInstance($cart->id_currency)),
                'id_cart'                       => (int) $cart->id,
                'stripe_secret_key'             => Configuration::get(static::GO_LIVE) ? Configuration::get(static::SECRET_KEY_LIVE) : Configuration::get(static::SECRET_KEY_TEST),
                'stripe_publishable_key'        => Configuration::get(static::GO_LIVE) ? Configuration::get(static::PUBLISHABLE_KEY_LIVE) : Configuration::get(static::PUBLISHABLE_KEY_TEST),
                'stripe_locale'                 => static::getStripeLanguage($this->context->language->language_code),
                'stripe_zipcode'                => (bool) Configuration::get(static::ZIPCODE),
                'stripecc_zipcode'              => (bool) Configuration::get(static::ZIPCODE),
                'stripe_bitcoin'                => (bool) Configuration::get(static::BITCOIN) && Tools::strtolower($currency->iso_code) === 'usd',
                'stripe_alipay'                 => (bool) Configuration::get(static::ALIPAY),
                'ideal'                         => Configuration::get(static::IDEAL),
                'stripe_shopname'               => $this->context->shop->name,
                'stripe_ajax_validation'        => $link->getModuleLink($this->name, 'ajaxvalidation', [], Tools::usingSecureMode()),
                'stripe_confirmation_page'      => $link->getModuleLink($this->name, 'validation', [], Tools::usingSecureMode()),
                'stripe_ajax_confirmation_page' => $link->getPageLink('order-confirmation', Tools::usingSecureMode(), '&id_cart='.$cart->id.'&id_module='.$this->id.'&key='.$customer->secure_key),
                'showPaymentLogos'              => Configuration::get(static::SHOW_PAYMENT_LOGOS),
                'stripeShopThumb'               => str_replace('http://', 'https://', $this->context->link->getMediaLink(__PS_BASE_URI__.'modules/stripe/views/img/shop'.$this->getShopId().'.jpg')),
                'stripe_collect_billing'        => Configuration::get(static::COLLECT_BILLING),
                'stripe_collect_shipping'       => Configuration::get(static::COLLECT_SHIPPING),
                'stripe_apple_pay'              => Configuration::get(static::STRIPE_APPLE_PAY),
                'stripe_checkout'               => Configuration::get(static::STRIPE_CHECKOUT),
                'stripe_cc_form'                => Configuration::get(static::STRIPE_CC_FORM),
                'stripe_cc_animation'           => Configuration::get(static::STRIPE_CC_ANIMATION),
                'autoplay'                      => $autoplay,
                'three_d_secure'                => Configuration::get(static::THREEDSECURE),
            ]
        );

        $output = '';

        if (Configuration::get(static::STRIPE_CHECKOUT)) {
            $output .= $this->display(__FILE__, 'views/templates/hook/payment.tpl');
        }
        if (Configuration::get(static::IDEAL)) {
            $output .= $this->display(__FILE__, 'views/templates/hook/idealpayment.tpl');
        }
        if (Configuration::get(static::BANCONTACT)) {
            $output .= $this->display(__FILE__, 'views/templates/hook/bancontactpayment.tpl');
        }
        if (Configuration::get(static::GIROPAY)) {
            $output .= $this->display(__FILE__, 'views/templates/hook/giropaypayment.tpl');
        }
        if (Configuration::get(static::SOFORT) && in_array(Tools::strtoupper($country->iso_code), ['AT', 'DE', 'NL', 'BE', 'ES'])) {
            $output .= $this->display(__FILE__, 'views/templates/hook/sofortpayment.tpl');
        }
        if (Configuration::get(static::STRIPE_CC_FORM)) {
            $output .= $this->display(__FILE__, 'views/templates/hook/ccpayment.tpl');
        }
        if (Configuration::get(static::STRIPE_APPLE_PAY)) {
            $output .= $this->display(__FILE__, 'views/templates/hook/applepayment.tpl');
        }

        return $output;
    }

    /**
     * Check if shop thumbnail exists
     */
    public function checkShopThumb()
    {
        $dbShopThumb = Configuration::get(static::SHOP_THUMB);
        if (empty($dbShopThumb) || !file_exists(_PS_IMG_.$dbShopThumb)) {
            ImageManager::resize(
                _PS_IMG_DIR_.Configuration::get('PS_LOGO'),
                _PS_MODULE_DIR_.'stripe/views/img/shop'.$this->getShopId().'.jpg',
                128,
                128,
                'jpg',
                true
            );
        }
    }

    /**
     * Get the Stripe language
     *
     * @param string $locale IETF locale
     *
     * @return string Stripe language
     */
    public static function getStripeLanguage($locale)
    {
        $languageIso = Tools::strtolower(Tools::substr($locale, 0, 2));

        if (in_array($languageIso, static::$stripeLanguages)) {
            return $languageIso;
        }

        return 'en';
    }

    /**
     * Hook to Advanced EU checkout
     *
     * @param array $params Hook parameters
     *
     * @return array|bool Smarty variables, nothing if should not be shown
     */
    public function hookDisplayPaymentEU($params)
    {
        /** @var Cart $cart */
        if (!$this->active ||
            (!Configuration::get(static::SECRET_KEY_TEST) && !Configuration::get(static::PUBLISHABLE_KEY_TEST))
            && (!Configuration::get(static::SECRET_KEY_LIVE) && !Configuration::get(static::PUBLISHABLE_KEY_LIVE))
        ) {
            return false;
        }

        $this->checkShopThumb();

        $paymentOptions = [
            'cta_text'        => $this->l('Pay by Credit Card'),
            'logo'            => Media::getMediaPath($this->local_path.'views/img/stripebtnlogo.png'),
            'action'          => $this->context->link->getModuleLink($this->name, 'eupayment', [], Tools::usingSecureMode()),
            'stripeShopThumb' => $this->context->link->getMediaLink('/modules/stripe/views/img/shop'.$this->getShopId().'.jpg'),
        ];

        return $paymentOptions;
    }

    /**
     * This hook is used to display the order confirmation page.
     *
     * @param array $params Hook parameters
     *
     * @return string Hook HTML
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
            $totalToPay = (float) $order->getTotalPaid($currency);
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
                'id_order'  => $order->id,
                'reference' => $reference,
                'params'    => $params,
                'total'     => Tools::displayPrice($totalToPay, $currency, false),
            ]
        );

        return $this->display(__FILE__, 'views/templates/front/confirmation.tpl');
    }

    /**
     * Hook to the top a payment page
     *
     * @param array $params Hook parameters
     *
     * @return string Hook HTML
     */
    public function hookDisplayPaymentTop($params)
    {
        $this->context->controller->addJQuery();
        $this->context->smarty->assign(
            [
                'baseDir'          => Tools::getHttpHost(true).__PS_BASE_URI__.'modules/stripe/views/',
                'stripe_checkout'  => (bool) Configuration::get(static::STRIPE_CHECKOUT),
                'stripe_cc_form'   => (bool) Configuration::get(static::STRIPE_CC_FORM),
                'stripe_apple_pay' => (bool) Configuration::get(static::STRIPE_APPLE_PAY),
                'stripe_ideal'     => (bool) Configuration::get(static::IDEAL),
            ]
        );

        return $this->display(__FILE__, 'views/templates/front/assets.tpl');
    }

    /**
     * Display on Back Office order page
     *
     * @param array $params Hok parameters
     *
     * @return string Hook HTML
     * @throws Exception
     * @throws SmartyException
     */
    public function hookDisplayAdminOrder($params)
    {
        if (StripeTransaction::getTransactionsByOrderId($params['id_order'], true)) {
            $this->context->controller->addJS($this->_path.'views/js/sweetalert.min.js');
            $this->context->controller->addCSS($this->_path.'views/css/sweetalert.min.css', 'all');

            $order = new Order($params['id_order']);
            $orderCurrency = new Currency($order->id_currency);

            $totalRefundLeft = $order->getTotalPaid();
            if (!in_array(Tools::strtolower($orderCurrency->iso_code), Stripe::$zeroDecimalCurrencies)) {
                $totalRefundLeft = (int) (Tools::ps_round($totalRefundLeft * 100, 0));
            }

            $amount = (int) StripeTransaction::getRefundedAmountByOrderId($order->id);

            $totalRefundLeft -= $amount;

            if (!in_array(Tools::strtolower($orderCurrency->iso_code), Stripe::$zeroDecimalCurrencies)) {
                $totalRefundLeft = (float) ($totalRefundLeft / 100);
            }

            $this->context->smarty->assign(
                [
                    'stripe_transaction_list'     => $this->renderAdminOrderTransactionList($params['id_order']),
                    'stripe_currency_symbol'      => $orderCurrency->sign,
                    'stripe_total_amount'         => $totalRefundLeft,
                    'stripe_module_refund_action' => $this->context->link->getAdminLink('AdminModules', true).
                        '&configure=stripe&tab_module=payments_gateways&module_name=stripe&orderstriperefund',
                    'id_order'                    => (int) $order->id,
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
     */
    protected function renderAdminOrderTransactionList($idOrder)
    {
        $results = StripeTransaction::getTransactionsByOrderId($idOrder);

        $order = new Order($idOrder);
        $currency = Currency::getCurrencyInstance($order->id_currency);

        if (!in_array(Tools::strtolower($currency->iso_code), Stripe::$zeroDecimalCurrencies)) {
            foreach ($results as &$result) {
                // Process results
                $result['amount'] = (float) ($result['amount'] / 100);
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
            }
        }

        $helperList = new HelperList();
        $helperList->id = 1;

        $helperList->list_id = 'stripe_transaction';
        $helperList->shopLinkType = false;

        $helperList->no_link = true;

        $helperList->_defaultOrderBy = 'date_add';

        $helperList->simple_header = true;

        $helperList->module = $this;

        $fieldsList = [
            'id_stripe_transaction' => ['title' => $this->l('ID'), 'width' => 'auto'],
            'type_icon'             => ['type' => 'type_icon', 'title' => $this->l('Type'), 'width' => 'auto', 'color' => 'color', 'text' => 'type_text'],
            'amount'                => ['type' => 'price', 'title' => $this->l('Amount'), 'width' => 'auto'],
            'card_last_digits'      => ['type' => 'text', 'title' => $this->l('Credit card (last 4 digits)'), 'width' => 'auto'],
            'source_text'           => ['type' => 'stripe_source', 'title' => $this->l('Source'), 'width' => 'auto'],
            'date_upd'              => ['type' => 'datetime', 'title' => $this->l('Date & time'), 'width' => 'auto'],
        ];

        $helperList->identifier = 'id_stripe_transaction';
        $helperList->title = $this->l('Transactions');
        $helperList->token = Tools::getAdminTokenLite('AdminOrders');
        $helperList->currentIndex = AdminController::$currentIndex.'&'.http_build_query([
            'id_order' => $idOrder,
        ]);

        // Hide actions
        $helperList->tpl_vars['show_filters'] = false;
        $helperList->actions = [];
        $helperList->bulk_actions = [];

        $helperList->table = 'stripe_transaction';

        return $helperList->generateList($results, $fieldsList);
    }

    /**
     * Hook after module install
     *
     * @param Module $module
     *
     * @return void
     */
    public function hookActionModuleInstallAfter($module)
    {
        if (!isset($module->name) || empty($module->name)) {
            return;
        }

        $hookHeaderId = (int) Hook::getIdByName('displayHeader');
        $modulesWithControllers = Dispatcher::getModuleControllers('front');

        if (isset($modulesWithControllers[$module->name])) {
            foreach (Shop::getShops() as $shop) {
                foreach ($modulesWithControllers[$module->name] as $cont) {
                    Db::getInstance()->insert(
                        'hook_module_exceptions',
                        [
                            'id_module' => (int) $this->id,
                            'id_hook'   => (int) $hookHeaderId,
                            'id_shop'   => (int) $shop['id_shop'],
                            'file_name' => pSQL($cont),
                        ],
                        false,
                        true,
                        Db::INSERT_IGNORE
                    );
                }
            }
        }
    }

    /**
     * Detect Back Office settings
     *
     * @return array Array with error message strings
     */
    protected function detectBOSettingsErrors()
    {
        $langId = Context::getContext()->language->id;
        $output = [];
        if (Configuration::get('PS_DISABLE_NON_NATIVE_MODULE')) {
            $output[] = $this->l('Non native modules such as this one are disabled. Go to').' "'.
                $this->getTabName('AdminParentPreferences', $langId).
                ' > '.
                $this->getTabName('AdminPerformance', $langId).
                '" '.$this->l('and make sure that the option').' "'.
                Translate::getAdminTranslation('Disable non PrestaShop modules', 'AdminPerformance').
                '" '.$this->l('is set to').' "'.
                Translate::getAdminTranslation('No', 'AdminPerformance').
                '"'.$this->l('.').'<br />';
        }

        return $output;
    }

    /**
     * Get Tab name from database
     *
     * @param $className string Class name of tab
     * @param $idLang    int Language id
     *
     * @return string Returns the localized tab name
     */
    protected function getTabName($className, $idLang)
    {
        if ($className == null || $idLang == null) {
            return '';
        }

        try {
            return (string) Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
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
     * Check currency
     *
     * @param Cart $cart Cart object
     *
     * @return bool Whether the module should be shown
     */
    protected function checkCurrency(Cart $cart)
    {
        $currencyOrder = new Currency($cart->id_currency);
        $currenciesModule = $this->getCurrency($cart->id_currency);

        if (is_array($currenciesModule)) {
            foreach ($currenciesModule as $currencyModule) {
                if ($currencyOrder->id == $currencyModule['id_currency']) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Add information message
     *
     * @param string $message Message
     * @param bool   $private
     */
    protected function addInformation($message, $private = false)
    {
        if (!Tools::isSubmit('configure')) {
            if (!$private) {
                $this->context->controller->informations[] = '<a href="'.$this->baseUrl.'">'.$this->displayName.': '.$message.'</a>';
            }
        } else {
            $this->context->controller->informations[] = $message;
        }
    }

    /**
     * Add confirmation message
     *
     * @param string $message Message
     * @param bool   $private
     */
    protected function addConfirmation($message, $private = false)
    {
        if (!Tools::isSubmit('configure')) {
            if (!$private) {
                $this->context->controller->confirmations[] = '<a href="'.$this->baseUrl.'">'.$this->displayName.': '.$message.'</a>';
            }
        } else {
            $this->context->controller->confirmations[] = $message;
        }
    }

    /**
     * Add warning message
     *
     * @param string $message Message
     * @param bool   $private
     */
    protected function addWarning($message, $private = false)
    {
        if (!Tools::isSubmit('configure')) {
            if (!$private) {
                $this->context->controller->warnings[] = '<a href="'.$this->baseUrl.'">'.$this->displayName.': '.$message.'</a>';
            }
        } else {
            $this->context->controller->warnings[] = $message;
        }
    }

    /**
     * Add error message
     *
     * @param string $message Message
     */
    protected function addError($message, $private = false)
    {
        if (!Tools::isSubmit('configure')) {
            if (!$private) {
                $this->context->controller->errors[] = '<a href="'.$this->baseUrl.'">'.$this->displayName.': '.$message.'</a>';
            }
        } else {
            // Do not add error in this case
            // It will break execution of AdminController
            $this->context->controller->warnings[] = $message;
        }
    }
}
