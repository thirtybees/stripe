{*
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
 *  @author    thirty bees <modules@thirtybees.com>
 *  @copyright 2017 thirty bees
 *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*}
{if (Configuration::get(Stripe::STRIPE_CHECKOUT) && in_array($stripe_currency, Stripe::$methodCurrencies['credit_card']))}
  {if file_exists('./payment.tpl')}{include file="./payment.tpl"}{else}{include file="{$smarty.const.PS_MODULE_DIR}stripe/views/templates/hook/payment.tpl"}{/if}
{/if}
{if (Configuration::get(Stripe::IDEAL) && in_array($stripe_currency, Stripe::$methodCurrencies['ideal']))}
  {if file_exists('./idealpayment.tpl')}{include file="./idealpayment.tpl"}{else}{include file="{$smarty.const.PS_MODULE_DIR}stripe/views/templates/hook/idealpayment.tpl"}{/if}
{/if}
{if (Configuration::get(Stripe::BANCONTACT) && in_array($stripe_currency, Stripe::$methodCurrencies['bancontact']))}
  {if file_exists('./bancontactplayment.tpl')}{include file="./bancontactpayment.tpl"}{else}{include file="{$smarty.const.PS_MODULE_DIR}stripe/views/templates/hook/bancontactpayment.tpl"}{/if}
{/if}
{if (Configuration::get(Stripe::GIROPAY) && in_array($stripe_currency, Stripe::$methodCurrencies['giropay']))}
  {if file_exists('./giropaypayment.tpl')}{include file="./giropaypayment.tpl"}{else}{include file="{$smarty.const.PS_MODULE_DIR}stripe/views/templates/hook/giropaypayment.tpl"}{/if}
{/if}
{if (Configuration::get(Stripe::SOFORT) && in_array(Tools::strtoupper($country->iso_code), ['AT', 'DE', 'NL', 'BE', 'ES'])  && in_array($stripe_currency, Stripe::$methodCurrencies['sofort']))}
  {if file_exists('./sofortpayment.tpl')}{include file="./sofortpayment.tpl"}{else}{include file="{$smarty.const.PS_MODULE_DIR}stripe/views/templates/hook/sofortpayment.tpl"}{/if}
{/if}
{if (Configuration::get(Stripe::ALIPAY) && in_array($stripe_currency, Stripe::$methodCurrencies['alipay']))}
  {if file_exists('./alipaypayment.tpl')}{include file="./alipaypayment.tpl"}{else}{include file="{$smarty.const.PS_MODULE_DIR}stripe/views/templates/hook/alipaypayment.tpl"}{/if}
{/if}
{if (Configuration::get(Stripe::STRIPE_CC_FORM) && in_array($stripe_currency, Stripe::$methodCurrencies['credit_card']))}
  {if file_exists('./ccpayment.tpl')}{include file="./ccpayment.tpl"}{else}{include file="{$smarty.const.PS_MODULE_DIR}stripe/views/templates/hook/ccpayment.tpl"}{/if}
{/if}
{if (Configuration::get(Stripe::STRIPE_APPLE_PAY) && in_array($stripe_currency, Stripe::$methodCurrencies['credit_card']))}
  {if file_exists('./applepayment.tpl')}{include file="./applepayment.tpl"}{else}{include file="{$smarty.const.PS_MODULE_DIR}stripe/views/templates/hook/applepayment.tpl"}{/if}
{/if}
