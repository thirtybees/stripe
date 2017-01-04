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
		<legend>{l s='Check for updates' mod='stripe'}</legend>
		<p>
			<strong>{l s='Check if this module needs updates' mod='stripe'}</strong><br />
		</p>
		{if isset($needsPatchUpdate) && $needsPatchUpdate || isset($needsMinorUpdate) && $needsMinorUpdate || isset($needsMajorUpdate) && $needsMajorUpdate}
			<div class="warn">
				{l s='This module needs to be updated to version %s' mod='stripe' sprintf=[$latestVersion]}
			</div>
		{else}
			<div class="confirm">
				{l s='This module is up to date.' mod='stripe'}
			</div>
		{/if}
		<br />
		<a class="button" href="{$module_url}&mdstripeCheckUpdate=1"><i class="icon icon-search"></i> {l s='Check for updates' mod='stripe'}</a>
		{if isset($needsPatchUpdate) && $needsPatchUpdate}
			<br />
			<br />
			<a class="button clear" href="{$module_url|escape:'htmlall':'UTF-8'}&mdstripeApplyPatchUpdate=1"><i class="icon icon-refresh"></i> {l s='Update module to the latest PATCH version' mod='stripe'}</a>
		{/if}
		{if isset($needsMinorUpdate) && $needsMinorUpdate}
			<br />
			<br />
			<a class="button clear" href="{$module_url|escape:'htmlall':'UTF-8'}&mdstripeApplyMinorUpdate=1"><i class="icon icon-refresh"></i> {l s='Update module to the latest MINOR version' mod='stripe'}</a>
		{/if}
		{if isset($needsMajorUpdate) && $needsMajorUpdate}
			<br />
			<br />
			<a class="button clear" href="{$module_url|escape:'htmlall':'UTF-8'}&mdstripeApplyMajorUpdate=1"><i class="icon icon-refresh"></i> {l s='Update module to the latest MAJOR version' mod='stripe'}</a>
		{/if}
	</fieldset>
	<br />
{else}
	<div class="panel">
		<h3><i class="icon icon-refresh"></i> {l s='Check for updates' mod='stripe'}</h3>
		<p>
			<strong>{l s='Check if this module needs updates' mod='stripe'}</strong><br />
		</p>
		{if isset($needsPatchUpdate) && $needsPatchUpdate || isset($needsMinorUpdate) && $needsMinorUpdate || isset($needsMajorUpdate) && $needsMajorUpdate}
			<div class="alert alert-warning">
				{l s='This module needs to be updated to version %s' mod='stripe' sprintf=[$latestVersion]}
			</div>
		{else}
			<div class="alert alert-success">
				{l s='This module is up to date.' mod='stripe'}
			</div>
		{/if}
		<a class="btn btn-default" href="{$module_url}&mdstripeCheckUpdate=1"><i class="icon icon-search"></i> {l s='Check for updates' mod='stripe'}</a>
		{if isset($needsPatchUpdate) && $needsPatchUpdate}
			<br />
			<br />
			<a class="btn btn-default clearfix" href="{$module_url|escape:'htmlall':'UTF-8'}&mdstripeApplyPatchUpdate=1"><i class="icon icon-refresh"></i> {l s='Update module to the latest PATCH version' mod='stripe'}</a>
		{/if}
		{if isset($needsMinorUpdate) && $needsMinorUpdate}
			<br />
			<br />
			<a class="btn btn-default clearfix" href="{$module_url|escape:'htmlall':'UTF-8'}&mdstripeApplyMinorUpdate=1"><i class="icon icon-refresh"></i> {l s='Update module to the latest MINOR version' mod='stripe'}</a>
		{/if}
		{if isset($needsMajorUpdate) && $needsMajorUpdate}
			<br />
			<br />
			<a class="btn btn-default clearfix" href="{$module_url|escape:'htmlall':'UTF-8'}&mdstripeApplyMajorUpdate=1"><i class="icon icon-refresh"></i> {l s='Update module to the latest MAJOR version' mod='stripe'}</a>
		{/if}
	</div>
{/if}
