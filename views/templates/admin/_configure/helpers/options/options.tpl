{extends file="helpers/options/options.tpl"}

{block name="input"}
  {if $field['type'] == 'fontselect'}
    <div class="col-lg-9">
      <div class="form-group">
        <label id="label-{$field['name']|escape:'html'}"
               for="{$field['name']|escape:'html'}"
               class="control-label fixed-width-xxl"
               style="margin-left: 5px"
        >
          <select id="{$field['name']|escape:'html'}"
                  name="{$field['name']|escape:'html'}"
          >
            <option value="{$field['value']|escape:'javascript'}"
                    label="{$field['value']|escape:'javascript'}"
                    selected="selected"
            >
              {$field['value']|escape:'javascript'}
            </option>
          </select>
      </div>
    </div>
    <script type="text/javascript">
      (function () {
        function ready(fn) {
          if (document.readyState !== 'loading') {
            fn();
          } else if (document.addEventListener) {
            document.addEventListener('DOMContentLoaded', fn);
          } else {
            document.attachEvent('onreadystatechange', function () {
              if (document.readyState !== 'loading')
                fn();
            });
          }
        }

        function initFontselect() {
          if (typeof window.Fontselect === 'undefined') {
            setTimeout(initFontselect, 100);

            return;
          }

          new window.Fontselect('{$field['name']|escape:'html'}', {
            {if isset($field['value'])}placeholder: '{$field['value']|escape:'javascript'}',{/if}
          });
        }

        ready(initFontselect);
      }());
    </script>
  {elseif $field['type'] == 'democheckout'}
    <div class="col-lg-9">
      <iframe id="stripe-demo-iframe"
              src="../index.php?fc=module&module=stripe&controller=demoiframe"
              width="100%"
              frameborder="0"
      ></iframe>
      <script type="text/javascript">
        (function () {
          window.addEventListener('message', function (event) {
            if (!event.data) {
              return;
            }

            try {
              var data = JSON.parse(event.data);
            } catch (e) {
              return;
            }

            if (data && data.messageOrigin === 'demoiframe') {
              if (data.subject === 'height') {
                document.getElementById('stripe-demo-iframe').height = parseInt(data.height, 10);
              } else if (data.subject === 'payment-success') {
                document.querySelector('body').style.overflow = 'hidden';
                document.getElementById('stripe-payment-overlay').style.display = 'block';
              }
            }
          });
        }());
      </script>
    </div>
  {else}
    {$smarty.block.parent}
  {/if}
{/block}
