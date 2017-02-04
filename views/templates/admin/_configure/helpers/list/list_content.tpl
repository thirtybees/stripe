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
{extends file="helpers/list/list_content.tpl"}


{block name="td_content"}
	{if isset($params.prefix)}{$params.prefix}{/if}
	{if isset($params.badge_success) && $params.badge_success && isset($tr.badge_success) && $tr.badge_success == $params.badge_success}<span class="badge badge-success">{/if}
{if isset($params.badge_warning) && $params.badge_warning && isset($tr.badge_warning) && $tr.badge_warning == $params.badge_warning}<span class="badge badge-warning">{/if}
{if isset($params.badge_danger) && $params.badge_danger && isset($tr.badge_danger) && $tr.badge_danger == $params.badge_danger}<span class="badge badge-danger">{/if}
{if isset($params.color) && isset($tr[$params.color])}
	<span class="label color_field" style="background-color:{$tr[$params.color]};color:{if Tools::getBrightness($tr[$params.color]) < 128}white{else}#383838{/if}">
{/if}
	{if isset($tr.$key)}
		{if isset($params.active)}
			{$tr.$key}
		{elseif isset($params.activeVisu)}
			{if $tr.$key}
				<i class="icon-check-ok"></i>
					 {l s='Enabled'}
							{else}

				<i class="icon-remove"></i>
				{l s='Disabled'}
			{/if}
						{elseif isset($params.position)}
							{if !$filters_has_value && $order_by == 'position' && $order_way != 'DESC'}
			<div class="dragGroup">
									<div class="positions">
										{$tr.$key.position + 1}
									</div>
								</div>
		{else}
			{$tr.$key.position + 1}
		{/if}
						{elseif isset($params.image)}
							{$tr.$key}
						{elseif isset($params.icon)}
							{if is_array($tr[$key])}
			{if isset($tr[$key]['class'])}
				<i class="{$tr[$key]['class']}"></i>

													{else}

				<img src="../img/admin/{$tr[$key]['src']}" alt="{$tr[$key]['alt']}" title="{$tr[$key]['alt']}"/>
			{/if}
		{/if}
						{elseif isset($params.type) && $params.type == 'price'}
							{if isset($params.id_currency)}
			{displayPrice price=$tr.$key currency=$params.id_currency}
		{else}
			{displayPrice price=$tr.$key}
		{/if}

						{elseif isset($params.type) && $params.type == 'type_icon'}

			<i class="icon icon-{$tr['type_icon']|escape:'htmlall':'UTF-8'}"></i>
				 {$tr['type_text']}
						{elseif isset($params.float)}
							{$tr.$key}
						{elseif isset($params.type) && $params.type == 'date'}
							{dateFormat date=$tr.$key full=0}
						{elseif isset($params.type) && $params.type == 'datetime'}
							{dateFormat date=$tr.$key full=1}
						{elseif isset($params.type) && $params.type == 'decimal'}
							{$tr.$key|string_format:"%.2f"}
						{elseif isset($params.type) && $params.type == 'percent'}
							{$tr.$key} {l s='%'}
						{* If type is 'editable', an input is created *}
						{elseif isset($params.type) && $params.type == 'editable' && isset($tr.id)}

			<input type="text" name="{$key}_{$tr.id}" value="{$tr.$key|escape:'html':'UTF-8'}" class="{$key}"/>

										{elseif isset($params.callback)}
							{if isset($params.maxlength) && Tools::strlen($tr.$key) > $params.maxlength}
			<span title="{$tr.$key}">{$tr.$key|truncate:$params.maxlength:'...'}</span>
		{else}
			{$tr.$key}
		{/if}
						{elseif $key == 'color'}
							{if !is_array($tr.$key)}
			<div style="background-color: {$tr.$key};" class="attributes-color-container"></div>

											{else} {*TEXTURE*}

			<img src="{$tr.$key.texture}" alt="{$tr.name}" class="attributes-color-container"/>
		{/if}
						{elseif isset($params.maxlength) && Tools::strlen($tr.$key) > $params.maxlength}

			<span title="{$tr.$key|escape:'html':'UTF-8'}">{$tr.$key|truncate:$params.maxlength:'...'|escape:'html':'UTF-8'}</span>
		{else}
			{$tr.$key|escape:'html':'UTF-8'}
		{/if}
	{else}
		{block name="default_field"}--{/block}
	{/if}
	{if isset($params.suffix)}{$params.suffix}{/if}
{if isset($params.color) && isset($tr.color)}
	</span>
{/if}
{if isset($params.badge_danger) && $params.badge_danger && isset($tr.badge_danger) && $tr.badge_danger == $params.badge_danger}</span>{/if}
{if isset($params.badge_warning) && $params.badge_warning && isset($tr.badge_warning) && $tr.badge_warning == $params.badge_warning}</span>{/if}
	{if isset($params.badge_success) && $params.badge_success && isset($tr.badge_success) && $tr.badge_success == $params.badge_success}</span>{/if}
{/block}

