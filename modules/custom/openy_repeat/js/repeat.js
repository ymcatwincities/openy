(function ($) {
  if (!$('.repeat-schedule').length) {
    return;
  }

  var text = '18.05.89';
  changeDateTitle(text);

  function changeDateTitle(text) {
    $('span.date').text(text);
  }



})(jQuery);
