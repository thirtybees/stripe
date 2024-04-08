<script type="text/javascript" data-cookieconsent="necessary">
    (function () {

        const isScriptAlreadyIncluded = (src) => {
            var scripts = document.getElementsByTagName("script");
            for(let i = 0; i < scripts.length; i++) {
                if (scripts[i].getAttribute('src') === src) {
                    return true;
                }
            }
            return false;
        }

        const loadScript = (src) => {
            const script = document.createElement('script');
            script.type = 'text/javascript';
            script.src = src;
            document.querySelector('head').appendChild(script);
        }

        const ensureScript = (src) => {
            if (! isScriptAlreadyIncluded()) {
                loadScript(src);
            }
        }

        {foreach $jsAssets as $script}
        ensureScript('{$script|escape:'javascript'}');
        {/foreach}
    })();
</script>