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
		<legend>{l s='Stripe' mod='stripe'}</legend>
		<strong>{l s='Accept payments with Stripe' mod='stripe'}</strong>
		<p>
			{l s='Thank you for using this module!' mod='stripe'}
		</p>
		<strong>{l s='Quick start' mod='stripe'}</strong>
		<ol>
			<li>{l s='Visit' mod='stripe'} <a href="https://stripe.com/">https://stripe.com/</a> {l s='and find your API keys.' mod='stripe'}</li>
			<li>{l s='Enter your keys on this page' mod='stripe'}</li>
			<li>{l s='Optionally configure the webhooks and repeat this process for every store if you have multistore enabled' mod='stripe'}</li>
			<li>
				{l s='You are good to go! Should you find any problems, please check out the' mod='stripe'}
				<a href="https://github.com/thirtybees/stripe/wiki">wiki</a>
			</li>
			<li>
				{l s='If you have found a bug or the wiki didn\'t solve your problem, please open an issue on GitHub:' mod='stripe'}
				<a href="https://github.com/thirtybees/stripe/issues">https://github.com/thirtybees/stripe/issues</a>
			</li>
		</ol>
	</fieldset>
	<br />

	<fieldset>
		<legend>{l s='Webhooks' mod='stripe'}</legend>
		<p>{l s='This module supports procesing refunds through webhooks' mod='stripe'}</p>
		<p>{l s='You can use the following URL:' mod='stripe'}<br/>
			<a href="{$stripe_webhook_url|escape:'htmlall':'UTF-8'}">{$stripe_webhook_url|escape:'htmlall':'UTF-8'}</a>
		</p>
	</fieldset>
	<br />
{else}
	<div class="panel">
		<h3><i class="icon icon-puzzle-piece"></i> {l s='Stripe' mod='stripe'}</h3>
		<strong>{l s='Accept payments with Stripe' mod='stripe'}</strong>
		<p>
			{l s='Thank you for using this module!' mod='stripe'}
		</p>
		<strong>{l s='Quick start' mod='stripe'}</strong>
		<ol>
			<li>{l s='Visit' mod='stripe'} <a href="https://stripe.com/">https://stripe.com/</a> {l s='and find your API keys.' mod='stripe'}</li>
			<li>{l s='Enter your keys on this page' mod='stripe'}</li>
			<li>{l s='Optionally configure the webhooks and repeat this process for every store if you have multistore enabled' mod='stripe'}</li>
			<li>
				{l s='You are good to go! Should you find any problems, please check out the' mod='stripe'}
				<a href="https://github.com/thirtybees/stripe/wiki">wiki</a>
			</li>
			<li>
				{l s='If you have found a bug or the wiki didn\'t solve your problem, please open an issue on GitHub:' mod='stripe'}
				<a href="https://github.com/thirtybees/stripe/issues">https://github.com/thirtybees/stripe/issues</a>
			</li>
		</ol>
	</div>

	<div class="panel">
		<h3><i class="icon icon-anchor"></i> {l s='Webhooks' mod='stripe'}</h3>
		<p>{l s='This module supports procesing refunds through webhooks' mod='stripe'}</p>
		<p>{l s='You can use the following URL:' mod='stripe'}<br/>
			<a href="{$stripe_webhook_url|escape:'htmlall':'UTF-8'}">{$stripe_webhook_url|escape:'htmlall':'UTF-8'}</a>
		</p>
	</div>
{/if}
