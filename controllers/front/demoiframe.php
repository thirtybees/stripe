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

/**
 * Class StripeDemoIframeModuleFrontController
 *
 * @since 1.5.0
 */
class StripeDemoIframeModuleFrontController extends ModuleFrontController
{
    /** @var Stripe $module */
    public $module;

    /**
     * StripeDemoIframeModuleFrontController constructor.
     *
     * @throws Adapter_Exception
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function __construct()
    {
        parent::__construct();

        $this->ssl = Tools::usingSecureMode();

        // Check if employee is logged in
        $cookie = new Cookie('psAdmin');
        if (!$cookie->id_employee) {
            Tools::redirectLink($this->context->link->getPageLink('index'));
        }
    }

    /**
     * Prevent displaying the maintenance page
     *
     * @return void
     */
    protected function displayMaintenancePage()
    {
    }

    /**
     * @return bool|void
     *
     * @throws PrestaShopException
     *
     * @since 1.5.0
     */
    public function initContent()
    {
        if (!$this->module->active ||
            (!Configuration::get(Stripe::SECRET_KEY_TEST) && !Configuration::get(Stripe::PUBLISHABLE_KEY_TEST))
            && (!Configuration::get(Stripe::SECRET_KEY_LIVE) && !Configuration::get(Stripe::PUBLISHABLE_KEY_LIVE))
        ) {
            exit;
        }

        $this->context->smarty->assign(
            [
                'stripe_name'                             => 'Demo demo',
                'stripe_email'                            => 'demo@demo.com',
                'stripe_currency'                         => 'USD',
                'stripe_country'                          => 'US',
                'stripe_amount'                           => 1000,
                'stripe_amount_string'                    => '10.00',
                'id_cart'                                 => 1,
                'stripe_publishable_key'                  => 'pk_test_6pRNASCoBOKtIshFeQd4XMUh',
                'stripe_locale'                           => Stripe::getStripeLanguage(Context::getContext()->language->language_code),
                'stripe_checkout'                         => true,
                'stripe_cc_form'                          => true,
                'stripe_payment_request'                  => Configuration::get(Stripe::STRIPE_PAYMENT_REQUEST),
                'stripe_shopname'                         => $this->context->shop->name,
                'stripe_confirmation_page'                => '',
                'local_module_dir'                        => _PS_MODULE_DIR_,
                'module_dir'                              => __PS_BASE_URI__.'modules/stripe/',
                'stripe_input_placeholder_color'          => Configuration::get(Stripe::INPUT_PLACEHOLDER_COLOR.'_TEMP'),
                'stripe_button_background_color'          => Configuration::get(Stripe::BUTTON_BACKGROUND_COLOR.'_TEMP'),
                'stripe_button_foreground_color'          => Configuration::get(Stripe::BUTTON_FOREGROUND_COLOR.'_TEMP'),
                'stripe_highlight_color'                  => Configuration::get(Stripe::HIGHLIGHT_COLOR.'_TEMP'),
                'stripe_error_color'                      => Configuration::get(Stripe::ERROR_COLOR.'_TEMP'),
                'stripe_error_glyph_color'                => Configuration::get(Stripe::ERROR_GLYPH_COLOR.'_TEMP'),
                'stripe_payment_request_foreground_color' => Configuration::get(Stripe::INPUT_TEXT_FOREGROUND_COLOR.'_TEMP'),
                'stripe_payment_request_background_color' => Configuration::get(Stripe::INPUT_TEXT_BACKGROUND_COLOR.'_TEMP'),
                'stripe_input_font_family'                => Configuration::get(Stripe::INPUT_FONT_FAMILY.'_TEMP'),
                'stripe_checkout_font_family'             => Configuration::get(Stripe::CHECKOUT_FONT_FAMILY.'_TEMP'),
                'stripe_checkout_font_size'               => Configuration::get(Stripe::CHECKOUT_FONT_SIZE.'_TEMP'),
                'stripe_payment_request_style'            => Configuration::get(Stripe::PAYMENT_REQUEST_BUTTON_STYLE.'_TEMP'),
            ]
        );

        echo $this->context->smarty->fetch(_PS_MODULE_DIR_.'stripe/views/templates/front/demoiframe.tpl');
        exit;
    }
}
