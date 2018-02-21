(function () {
  function initBulkActions() {
    if (typeof $ === 'undefined') {
      setTimeout(initBulkActions, 100);

      return;
    }

    $(document).ready(function () {
      $('.bulk-actions.btn-group > ul.dropdown-menu').append('<li class="divider"></li><li>\n' +
        '    <a href="#" onclick="sendBulkAction($(this).closest(\'form\').get(0), \'submitBulkupdateStripeCapture\');">\n' +
        '      <i class="icon icon-cc-stripe"></i>&nbsp;Capture\n' +
        '    </a>\n' +
        '  </li>');
    });
  }
  initBulkActions();
}());
