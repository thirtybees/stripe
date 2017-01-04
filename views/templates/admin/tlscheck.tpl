{*
 * 2016 Michael Dekker
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@michaeldekker.com so we can send you a copy immediately.
 *
 *  @author    Michael Dekker <prestashop@michaeldekker.com>
 *  @copyright 2016 Michael Dekker
 *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*}
{if $smarty.const._PS_VERSION_|@addcslashes:'\'' < '1.6'}
	<fieldset>
		<legend>{l s='TLS v1.2 support' mod='stripe'}</legend>
		<p>
			<strong>{l s='Check if your server supports TLS v1.2' mod='stripe'}</strong><br />
			{l s='This module cannot process payments if TLS v1.2 is not supported by your server.' mod='stripe'}<br />
			{l s='With this tool you can check if you need to configure your server in order to use the module.' mod='stripe'}<br />
			{l s='If the module was unable to verify that TLS v1.2 is supported, Stripe will automatically be disabled.' mod='stripe'}
			{l s='Make sure you see a (green) confirmation message underneath and you will be good to go.' mod='stripe'}
		</p>
		{if $tls_ok === MDStripe::ENUM_TLS_OK}
			<div class="confirm">
				{l s='TLS v1.2 is supported' mod='stripe'}
			</div>
		{elseif $tls_ok === MDStripe::ENUM_TLS_ERROR}
			<div class="error">
				{l s='TLS v1.2 is not supported. Please upgrade your server.' mod='stripe'}
			</div>
		{else}
			<div class="warn">
				{l s='Status is unknown. Please check if TLS v1.2 is supported.' mod='stripe'}
			</div>
		{/if}
		<br />
		<a class="button" href="{$module_url}&checktls=1">{l s='Check for TLS v1.2 support' mod='stripe'}</a>
	</fieldset>
	<br />
{else}
	<div class="panel">
		<h3><i class="icon icon-lock"></i> {l s='TLS v1.2 support' mod='stripe'}</h3>
		<p>
			<strong>{l s='Check if your server supports TLS v1.2' mod='stripe'}</strong><br />
			{l s='This module cannot process payments if TLS v1.2 is not supported by your server.' mod='stripe'}<br />
			{l s='With this tool you can check if you need to configure your server in order to use the module.' mod='stripe'}<br />
			{l s='If the module was unable to verify that TLS v1.2 is supported, Stripe will automatically be disabled.' mod='stripe'}
			{l s='Make sure you see a (green) confirmation message underneath and you will be good to go.' mod='stripe'}
		</p>
		{if $tls_ok === MDStripe::ENUM_TLS_OK}
			<div class="alert alert-success">
				{l s='TLS v1.2 is supported' mod='stripe'}
			</div>
		{elseif $tls_ok === MDStripe::ENUM_TLS_ERROR}
			<div class="alert alert-danger">
				{l s='TLS v1.2 is not supported. Please upgrade your server.' mod='stripe'}
			</div>
		{else}
			<div class="alert alert-warning">
				{l s='Status is unknown. Please check if TLS v1.2 is supported.' mod='stripe'}
			</div>
		{/if}
		<a class="btn btn-default" href="{$module_url}&checktls=1">{l s='Check for TLS v1.2 support' mod='stripe'}</a>
	</div>
{/if}
