(function ($, Drupal) {

  "use strict";

  // Explain link in query log.
  Drupal.behaviors.devel_explain = {
    attach: function (context, settings) {
      $('a.dev-explain').click(function () {
        var qid = $(this).attr("qid");
        var cell = $('#devel-query-' + qid);
        $('.dev-explain', cell).load(settings.path.baseUrl + 'devel/explain/' + settings.devel.request_id + '/' + qid).show();
        $('.dev-placeholders', cell).hide();
        $('.dev-arguments', cell).hide();
        return false;
      });
    }
  };

  // Arguments link in query log.
  Drupal.behaviors.devel_arguments = {
    attach: function (context, settings) {
      $('a.dev-arguments').click(function () {
        var qid = $(this).attr("qid");
        var cell = $('#devel-query-' + qid);
        $('.dev-arguments', cell).load(settings.path.baseUrl + 'devel/arguments/' + settings.devel.request_id + '/' + qid).show();
        $('.dev-placeholders', cell).hide();
        $('.dev-explain', cell).hide();
        return false;
      });
    }
  };

  // Placeholders link in query log.
  Drupal.behaviors.devel_placeholders = {
    attach: function (context, settings) {
      $('a.dev-placeholders').click(function () {
        var qid = $(this).attr("qid");
        var cell = $('#devel-query-' + qid);
        $('.dev-explain', cell).hide();
        $('.dev-arguments', cell).hide();
        $('.dev-placeholders', cell).show();
        return false;
      });
    }
  };

})(jQuery, Drupal);