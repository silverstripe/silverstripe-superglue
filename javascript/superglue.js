(function ($) {
  $.entwine('ss', function ($) {
    // custom reload super-glue-normal gridfield when pinned an item.
    $('.super-glue-pinned.ss-gridfield').entwine({
      reload: function (ajaxOpts, successCallback) {
        this._super(ajaxOpts, function () {
          if (ajaxOpts !== undefined) {
            jQuery('.super-glue-normal').entwine('.').entwine('ss').reload();
          }
        });
      },
    });

    // custom reload super-glue-pinned gridfield when unpinned an item.
    $('.super-glue-normal.ss-gridfield').entwine({
      reload: function (ajaxOpts, successCallback) {
        this._super(ajaxOpts, function () {
          if (ajaxOpts !== undefined) {
            jQuery('.super-glue-pinned').entwine('.').entwine('ss').reload();
          }
        });
      },
    });
  });
})(jQuery);
