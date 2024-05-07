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

use StripeModule\Utils;

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
    /**
     * @var Stripe $module
     */
    public $module;

    /**
     * StripeDemoIframeModuleFrontController constructor.
     *
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
     * @return void
     *
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function initContent()
    {
        if (!$this->module->active) {
            die('module not enabled');
        }

        $this->context->smarty->assign(
            [
                'stripeJs'                                => Utils::stripeJavascriptUrl(),
                'stripe_currency'                         => 'USD',
                'stripe_country'                          => 'US',
                'stripe_amount'                           => 1000,
                'stripe_publishable_key'                  => 'pk_test_6pRNASCoBOKtIshFeQd4XMUh',
                'stripe_payment_request'                  => Configuration::get(Stripe::STRIPE_PAYMENT_REQUEST),
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
