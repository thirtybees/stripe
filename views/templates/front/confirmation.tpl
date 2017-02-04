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
{if (isset($status) == true) && ($status == 'ok')}
	<h3>{l s='Your order on %s is complete.' sprintf=[$shop_name] mod='stripe'}</h3>
	<p>
		<br />- {l s='Amount' mod='stripe'} : <span class="price"><strong>{$total|escape:'htmlall':'UTF-8'}</strong></span>
		<br />- {l s='Reference' mod='stripe'} : <span class="reference"><strong>{$reference|escape:'html':'UTF-8'}</strong></span>
		<br /><br />{l s='An email has been sent with this information.' mod='stripe'}
		<br /><br />{l s='If you have questions, comments or concerns, please contact our' mod='stripe'} <a href="{$link->getPageLink('contact', true)|escape:'html':'UTF-8'}">{l s='expert customer support team.' mod='stripe'}</a>
	</p>
{else}
	<h3>{l s='Your order on %s has not been accepted.' sprintf=[$shop_name] mod='stripe'}</h3>
	<p>
	<p>
		<br />- {l s='Reference' mod='stripe'} <span class="reference"> <strong>{$reference|escape:'html':'UTF-8'}</strong></span>
		<br /><br />{l s='Please, try to order again.' mod='stripe'}
		<br /><br />{l s='If you have questions, comments or concerns, please contact our' mod='stripe'} <a href="{$link->getPageLink('contact', true)|escape:'html':'UTF-8'}">{l s='expert customer support team.' mod='stripe'}</a>
	</p>
{/if}
<hr />
