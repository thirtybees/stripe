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
<iframe id="elementsiframe"
        src="{$link->getModuleLink('stripe', 'elementsiframe', [], true)|escape:'htmlall':'UTF-8'}"
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

      if (data
        && data.messageOrigin === 'elementsiframe'
        && data.subject === 'height'
      ) {
        document.getElementById('elementsiframe').height = parseInt(data.height, 10);
      }
    });
  }());
</script>
