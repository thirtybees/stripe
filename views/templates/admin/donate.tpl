{if $smarty.const._PS_VERSION_|@addcslashes:'\'' < '1.6'}
	<fieldset>
		<legend>{l s='Participate' mod='stripe'}</legend>
		<p>
			<strong>{l s='This module needs your support!' mod='stripe'}</strong><br />
			{l s='This module has already helped hundreds of merchants get their store up and running in no time and at no cost.' mod='stripe'}<br />
			{l s='No need for expensive and hard to configure payment modules, but just getting started right away, in a few simple steps.' mod='stripe'}<br />
			{l s='We would all like to see this happen with more and more modules.' mod='stripe'} {l s='In order to get the revolution started, I need your help!' mod='stripe'}
			<br />
			<br />
			{l s='Therefore I am inviting your to go over to the GitHub page and open bug reports, if you find any.' mod='stripe'} {l s='If you\'re a developer, you can also contribute by making a pull request.' mod='stripe'}
			<br />
			<br />
			{l s='If you don\'t have the time for all that, please consider making a donation to support the development of this module.' mod='stripe'} {l s='You can use this page to donate:' mod='stripe'} <a href="https://paypal.me/Mdekker">https://paypal.me/MDekker</a>
			<br />
			<br />
			{l s='Yours sincerely,' mod='stripe'}<br />
			<a href="https://www.prestashop.com/forums/user/784244-mdekker/" target="_blank">Michael Dekker</a>
		</p>
	</fieldset>
	<br />
{else}
	<div class="panel">
		<h3><i class="icon icon-rocket"></i> {l s='Participate' mod='stripe'}</h3>
		<p>
			<strong>{l s='This module needs your support!' mod='stripe'}</strong><br />
			{l s='This module has already helped hundreds of merchants get their store up and running in no time and at no cost.' mod='stripe'}<br />
			{l s='No need for expensive and hard to configure payment modules, but just getting started right away, in a few simple steps.' mod='stripe'}<br />
			{l s='We would all like to see this happen with more and more modules.' mod='stripe'} {l s='In order to get the revolution started, I need your help!' mod='stripe'}
			<br />
			<br />
			{l s='Therefore I am inviting your to go over to the GitHub page and open bug reports, if you find any.' mod='stripe'} {l s='If you\'re a developer, you can also contribute by making a pull request.' mod='stripe'}
			<br />
			<br />
			{l s='If you don\'t have the time for all that, please consider making a donation to support the development of this module.' mod='stripe'} {l s='You can use this page to donate:' mod='stripe'} <a href="https://paypal.me/Mdekker">https://paypal.me/MDekker</a>
			<br />
			<br />
			{l s='Yours sincerely,' mod='stripe'}<br />
			<a href="https://www.prestashop.com/forums/user/784244-mdekker/" target="_blank">Michael Dekker</a>
		</p>
	</div>
{/if}
