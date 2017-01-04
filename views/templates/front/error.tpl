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
<!-- stripe views/templates/front/error.tpl -->
{capture name=path}
	<a href="{$orderLink|escape:'html':'UTF-8'}">
		{l s='Order'}
	</a>
	<span class="navigation-pipe">
        {$navigationPipe}
    </span>
	<span class="navigation_page">
        {l s='Payment error'}
    </span>
{/capture}
<div>
	<h3>{l s='An error occurred' mod='stripe'}:</h3>
	<ul class="alert alert-danger">
		{foreach from=$errors item='error'}
			<li>{$error|escape:'htmlall':'UTF-8'}</li>
		{/foreach}
	</ul>
</div>
<ul class="footer_links clearfix">
	<li><a class="btn btn-default button button-small" href="{$orderLink|escape:'html':'UTF-8'}" title="{l s='Back to your shopping cart' mod='stripe'}"><span><i class="icon-chevron-left"></i> {l s='Back to your shopping cart' mod='stripe'}</span></a></li>
</ul>
<!-- /stripe views/templates/front/error.tpl -->
