/*jslint browser: true*/
/*global $, jQuery, Modernizr, enquire*/
(function (window, document, $) {
  var $html = $('html'),
    mobileOnly = "screen and (max-width:47.9375em)", // 767px.
    mobileLandscape = "(min-width:30em)", // 480px.
    tablet = "(min-width:48em)"; // 768px.

    // Enquire usage:
    // enquire.register(tablet, {
    //   match: function () {
    //     $(window).on('resize', hander).resize();
    //   },
    //   unmatch : function () {
    //     $(window).on('resize', hander);
    //   },
    // });

  // Add your functional here.

  // Toggle hearts.
  $('.activity__heart').on('click', function (e) {
    var _self = $(this);
    e.preventDefault();
    _self.toggleClass('activity__heart-active');
    var activeHearts = $('.activity__heart-active').length;
    $('.amount--circle .amount').text(activeHearts);
  });

  $('.activity--alt__heart').on('click', function (e) {
    var _self = $(this);
    e.preventDefault();
    _self.toggleClass('activity--alt__heart-active');
    var activeHearts = $('.activity--alt__heart-active').length;
    $('.amount--circle .amount').text(activeHearts);
  });


  // Enable chosen.
  $(".chosen-select").chosen({
    disable_search_threshold: 10,
    disable_search: true
  });

  $(".banner--category-page .chosen-select").on('change', function(evt, params) {
     var backgroundUrl = "url("+params.selected+")";
     $(".banner__image").css("background-image", backgroundUrl); 
  });

  // Accordion.
  var accordion = 'accordion',
    item = '__item',
    $item = $('.' + accordion + item),
    $itemLink = $item.find('h2');

  if ($itemLink) {
    $itemLink.on('click', function () {
      var _self = $(this);
      _self.parent().toggleClass(accordion + item + '-active');
    });
  }
}(this, this.document, this.jQuery));
