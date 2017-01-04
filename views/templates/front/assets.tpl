<script type="text/javascript">
	(function() {
		function initStripeAssets() {
			if (typeof $ === 'undefined') {
				setTimeout(initStripeAssets, 100);
				return;
			}

			{if $stripe_checkout}
			if (typeof StripeCheckout === 'undefined') {
				$.getScript('https://checkout.stripe.com/checkout.js');
			}
			{/if}

			{if $stripe_cc_form || $stripe_apple_pay}
			if (typeof Stripe === 'undefined') {
				$.getScript('https://js.stripe.com/v2/');
			}
			{/if}

			{if $stripe_cc_form}
				if (typeof Card === 'undefined') {
					$.getScript('{$baseDir|escape:'javascript':'UTF-8' nofilter}js/jquery.card.js');
				}
				if (!$("link[href='{$baseDir|escape:'javascript':'UTF-8' nofilter}css/stripe-bootstrap.css']").length) {
					$('<link href="{$baseDir|escape:'javascript':'UTF-8' nofilter}css/stripe-bootstrap.css" rel="stylesheet">').appendTo('head');
				}
				if (!$("link[href='{$baseDir|escape:'javascript':'UTF-8' nofilter}css/creditcard-embedded.css']").length) {
					$('<link href="{$baseDir|escape:'javascript':'UTF-8' nofilter}css/creditcard-embedded.css" rel="stylesheet">').appendTo('head');
				}
				if (!$("link[href='{$baseDir|escape:'javascript':'UTF-8' nofilter}css/simplespinner.css']").length) {
					$('<link href="{$baseDir|escape:'javascript':'UTF-8' nofilter}css/simplespinner.css" rel="stylesheet">').appendTo('head');
				}
			{/if}

			{if $stripe_checkout}
				{if $smarty.const._PS_VERSION_|@addcslashes:'\'' >= '1.6'}
				if (!$("link[href='{$baseDir|escape:'javascript':'UTF-8' nofilter}css/front.css']").length) {
					$('<link href="{$baseDir|escape:'javascript':'UTF-8' nofilter}css/front.css" rel="stylesheet">').appendTo('head');
				}
				{else}
				if (!$("link[href='{$baseDir|escape:'javascript':'UTF-8' nofilter}css/front15.css']").length) {
					$('<link href="{$baseDir|escape:'javascript':'UTF-8' nofilter}css/front15.css" rel="stylesheet">').appendTo('head');
				}
				{/if}
			{/if}
		}

		initStripeAssets();
	})();
</script>
