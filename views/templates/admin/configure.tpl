{*
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
 *  @author    thirty bees <modules@thirtybees.com>
 *  @copyright 2017-2018 thirty bees
 *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*}
<div class="panel">
  <h3><i class="icon icon-cc-stripe"></i> {l s='Stripe' mod='stripe'}</h3>
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
  <p>{l s='This module supports stripe webhooks to process defered payments and refunds' mod='stripe'}</p>
  <p>{l s='You go to you stripe dashboard and configure webhook using following URL:' mod='stripe'} <code>{$stripe_webhook_url|escape:'htmlall'}</code>
  </p>
</div>

