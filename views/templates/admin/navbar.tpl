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
<nav class="navbar navbar-default" role="navigation">
	<ul class="nav navbar-nav">
		{if isset($menutabs)}
			{foreach from=$menutabs item=tab}
				<li class="{if $tab.active}active{/if}">
					<a id="{$tab.short|escape:'htmlall':'UTF-8'}" href="{$tab.href|escape:'htmlall':'UTF-8'}">
						<span class="icon {$tab.icon|escape:'htmlall':'UTF-8'}"></span>
						{$tab.desc|escape:'htmlall':'UTF-8'}
					</a>
				</li>
			{/foreach}
		{/if}
	</ul>
</nav>

