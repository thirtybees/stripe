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
<div class="panel">
  <div class="panel-heading">
    <i class="icon icon-cc-stripe"></i> <span>{l s='Stripe' mod='stripe'}</span>&nbsp;
    {if in_array($stripe_status->status, [1,2,3,4,5])}
      <span class="badge">
        {if in_array($stripe_status->status, [1,3])}
          <i class="icon icon-unlock"></i>
        {elseif $stripe_status->status == 2}
          <i class="icon icon-search"></i>
        {elseif $stripe_status->status == 4}
          <i class="icon icon-lock"></i>
        {else}
          <i class="icon icon-undo"></i>
        {/if}
        {if $stripe_status->status == 1}
          {l s='Authorized' mod='stripe'}
        {elseif $stripe_status->status == 2}
          {l s='In review' mod='stripe'}
        {elseif $stripe_status->status == 3}
          {l s='Approved' mod='stripe'}
        {elseif $stripe_status->status == 4}
          {l s='Captured' mod='stripe'}
        {elseif $stripe_status->status == 5}
          {l s='Refunded' mod='stripe'}
        {/if}
      </span>
    {/if}
  </div>
  {if $stripe_status->status == 2 || ($stripe_status->id_payment_intent && in_array($stripe_status->status, [1,3]))}
    <h4>{l s='Authorize & Capture' mod='stripe'}</h4>
    <form id="stripe_review" action="{$stripe_module_review_action|escape:'htmlall'}" method="post">
      <div class="well">
        {if $stripe_status->status == 2}
          <button id="stripe_approve_button" type="button" class="btn btn-success">
            <i class="icon icon-check"></i> {l s='Approve' mod='stripe'}
          </button>
        {/if}
        <button id="stripe_capture_button" type="button" class="btn btn-success">
          <i class="icon icon-check"></i> {l s='Capture' mod='stripe'}
        </button>
        <button id="stripe_release_button" type="button" class="btn btn-danger">
          <i class="icon icon-undo"></i> {l s='Release...' mod='stripe'}
        </button>
      </div>
      <input type="hidden" id="stripe_review_order" name="stripe_review_order" value="{$id_order|intval}">
      <input name="stripe_action" type="hidden" value="ignore">
    </form>
  {/if}

  {if !$stripe_status->id_payment_intent && in_array($stripe_status->status, [1,3])}
    <div class="alert alert-warning">
      {l s='This transaction must be captured or released from Stripe web application' mod='stripe'}
    </div>
  {/if}

  {if $stripe_review->id_payment_intent}
    {assign var='transactionId' value=$stripe_review->id_payment_intent}
  {else}
    {assign var='transactionId' value=$stripe_review->id_charge}
  {/if}
  {if $transactionId}
    <h4>{l s='Details' mod='stripe'}</h4>
    <span>
      <span>{l s='Transaction:' mod='stripe'}</span>&nbsp;
      <em>
        <a href="https://dashboard.stripe.com/{if $stripe_review->test}test{else}live{/if}/payments/{$transactionId|escape:'html'}"
           target="_blank"
        >
          {$transactionId|escape:'html'}
        </a>
      </em>
    </span>
  {/if}
  {$stripe_transaction_list}
  <br/>
  {if in_array($stripe_status->status, [0, 4])}
    <h4>{l s='Refund' mod='stripe'}</h4>
    <div class="well well-sm">
      <div class="form-inline">
        <div class="form-group">
          <button id="stripe_full_refund_button" type="button" class="btn btn-default">
            <i class="icon icon-undo"></i> {l s='Full refund' mod='stripe'}
          </button>
        </div>
        <div class="form-group">
          <form id="stripe_refund" action="{$stripe_module_refund_action|escape:'htmlall'}" method="post">
            <div class="input-group" style="min-width: 400px;">
              <div class="input-group-addon">
                {l s='Remaining:' mod='stripe'}
              </div>
              <input type="hidden" id="stripe_refund_order" name="stripe_refund_order" value="{$id_order|intval}">
              <input type="text"
                     id="stripe_refund_amount"
                     name="stripe_refund_amount"
                     class="form-control"
                     placeholder="{displayPrice price=$stripe_total_amount currency=$stripe_currency->id}"
              >
              <div class="input-group-btn">
                <button id="stripe_partial_refund_button" class="btn btn-default" type="button">
                  <i class="icon icon-undo"></i> {l s='Partial Refund' mod='stripe'}
                </button>
              </div>
            </div>
          </form>
        </div>
      </div>
    </div>
  {/if}
</div>

<script type="text/javascript">
  (function () {
    var stripeTotalAmount = '{$stripe_total_amount|escape:'javascript'}';

    $(document).ready(function () {
      $('#stripe_partial_refund_button').click(function (event) {
        event.preventDefault();
        var amount = parseFloat($('#stripe_refund_amount').val().replace(',', '.'));
        if (isNaN(amount)) {
          window.showErrorMessage('{l s='The entered amount invalid' mod='stripe' js=1}');
        } else {
          stripeRefundConfirmation(Math.min(amount, stripeTotalAmount));
        }
      });

      $('#stripe_full_refund_button').click(function (event) {
        event.preventDefault();
        $('#stripe_refund_amount').val(stripeTotalAmount);
        stripeRefundConfirmation(stripeTotalAmount);
      });

      $('#stripe_approve_button').click(function () {
        $('input[name="stripe_action"]').val('markAsSafe');
        $form = $('#stripe_review');
        $form.get(0).submit();
      });

      $('#stripe_capture_button').click(function () {
        $('input[name="stripe_action"]').val('capture');
        $form = $('#stripe_review');
        $form.get(0).submit();
      });

      $('#stripe_release_button').click(function (event) {
        event.preventDefault();
        $('input[name="stripe_action"]').val('release');
        stripeReleaseConfirmation();
      });

      function stripeRefundConfirmation(amount) {
        swal({
          title: '{l s='Are you sure?' mod='stripe' js=1}',
          text: '{l s='Do you want to refund this order?' mod='stripe' js=1}\n\n{l s='Refund amount:' mod='stripe' js=1} ' + formatCurrency(amount, window.currency_format, window.currency_sign, window.currency_blank),
          type: 'warning',
          buttons: {
            cancel: '{l s='Cancel' mod='stripe' js=1}',
            confirm: '{l s='Confirm' mod='stripe' js=1}'
          }
        }).then(function (confirm) {
          if (confirm) {
            $form = $('#stripe_refund');
            $form.get(0).submit();
          }
        });
      }

      function stripeReleaseConfirmation() {
        swal({
          title: '{l s='Are you sure?' mod='stripe' js=1}',
          text: '{l s='Do you want to release this payment?' mod='stripe' js=1}',
          type: 'warning',
          buttons: {
            cancel: '{l s='Cancel' mod='stripe' js=1}',
            confirm: '{l s='Confirm' mod='stripe' js=1}'
          }
        }).then(function (confirm) {
          if (confirm) {
            $form = $('#stripe_review');
            $form.get(0).submit();
          }
        });
      }
    });
  }());
</script>
