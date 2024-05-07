{*
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
 *  @author    thirty bees <modules@thirtybees.com>
 *  @copyright 2017-2024 thirty bees
 *  @license   https://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*}
{strip}
    <div class="row">
        <div class="col-xs-12 col-md-12">
            <p class="payment_module" id="stripe_payment_button">
                <a id="stripe_payment_link" href="{$paymentLink|escape:'htmlall'}" title="{$cta|escape:'htmlall'}">
                    <img src="{$img}" alt="{$cta|escape:'htmlall'}" width="auto" height="64" />&nbsp;
                    {$cta|escape:'html'}
                    {if $paymentLogos}
                        <img src="{$paymentLogos|escape:'htmlall'}"
                             alt="{l s='Credit cards' mod='stripe'}"/>
                    {/if}
                </a>
            </p>
        </div>
    </div>
{/strip}