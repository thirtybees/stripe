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
{capture name=path}
  <a href="{$orderLink|escape:'html'}">
    {l s='Order'}
  </a>
  <span class="navigation-pipe">
        {$navigationPipe}
    </span>
  <span class="navigation_page">
        {l s='Payment error'}
    </span>
{/capture}
<div class="alert alert-danger">
  <strong>{l s='An error occurred' mod='stripe'}:</strong>
  <ul>
    {foreach from=$errors item='error'}
      <li>{$error|escape:'htmlall'}</li>
    {/foreach}
  </ul>
</div>
<ul class="footer_links clearfix">
  <li><a class="btn btn-default button button-small" href="{$orderLink|escape:'html'}" title="{l s='Back to your shopping cart' mod='stripe'}"><span><i
                class="icon-chevron-left"></i> {l s='Back to your shopping cart' mod='stripe'}</span></a></li>
</ul>
