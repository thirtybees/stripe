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
{if $status == 4}
  <span id="stripe-stat-{$tr['id_order']|intval}"
        data-tooltip="title"
        title="{l s='Captured' mod='stripe'}"
        style="white-space: nowrap;"
  >
    {$paymentText|escape:'html'} <i class="icon icon-lock"></i>
  </span>
{elseif $status == 1}
  <span id="stripe-stat-{$tr['id_order']|intval}"
        data-tooltip="title"
        title="{l s='Authorized' mod='stripe'}"
        style="white-space: nowrap;"
  >
    {$paymentText|escape:'html'} <i class="icon icon-unlock"></i>
  </span>
{elseif $status == 2}
  <span id="stripe-stat-{$tr['id_order']|intval}"
        data-tooltip="title"
        title="{l s='In review' mod='stripe'}"
        style="white-space: nowrap;"
  >
    {$paymentText|escape:'html'} <i class="icon icon-search"></i>
  </span>
{/if}
<script type="text/javascript" data-cookieconsent="necessary">
  (function () {
    function initTooltip() {
      if (typeof $ === 'undefined') {
        setTimeout(initTooltip, 100);
        return;
      }

      $('#stripe-stat-{$tr['id_order']|intval}').tooltip();
    }
    initTooltip();
  }());
</script>
