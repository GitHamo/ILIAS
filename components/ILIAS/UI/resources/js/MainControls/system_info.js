il = il || {};
il.UI = il.UI || {};
il.UI.maincontrols = il.UI.maincontrols || {};

(function ($, maincontrols) {
  maincontrols.system_info = (function ($) {
    let calculating = false;

    const maybeShowMoreButton = function (item, moreButton) {
      calculating = true;
      const content = item.find('.il-system-info-content');
      const itemHeight = item.prop('offsetHeight');
      const contentHeight = content.prop('offsetHeight');

      if (contentHeight > itemHeight) {
        moreButton.show();
      } else {
        moreButton.hide();
      }
      calculating = false;
    };

    /**
     * decide and init condensed/wide version
     */
    const init = function (id) {
      const item = $(`#${id}`);
      const moreButton = item.find('.il-system-info-more');
      moreButton.click(() => {
        item.toggleClass('full');
        moreButton.hide();
      });

      maybeShowMoreButton(item, moreButton);
      $(window).resize(() => {
        if (!calculating) {
          maybeShowMoreButton(item);
        }
      });
    };

    const close = function (id) {
      const element = $(`#${id}`);
      const close_uri = decodeURI(element.data('closeUri'));
      $.ajax({
        async: false,
        type: 'GET',
        url: close_uri,
        success(data) {
          element.slideUp(500, function () {
            $(this).remove();
          });
        },
      });
    };

    return {
      init,
      close,
    };
  }($));
}($, il.UI.maincontrols));
