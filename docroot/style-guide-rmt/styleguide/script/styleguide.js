(function (window, document, $) {

  //Show/hide code
  $(".show-code").click(function() {
    $(this).next(".component--source").toggleClass("element-invisible");
  });
  $(".hide-code").click(function() {
    $(this).parent(".component--source").addClass("element-invisible");
    $('html, body').animate({
      scrollTop: $(this).parents(".component--item").offset().top
    }, 500);
  });

  //List components
  $(".show-subitem").click(function(){
    $(this).next().toggleClass("element-invisible");
    $(this).toggleClass("active");
  });
  $(".show-list").click(function(){
    $(".component--list").addClass("active");
    $(".show-list").hide();
    $(".hide-list").addClass("active").show();
  });
  $(".hide-list").click(function(){
    $(".component--list").removeClass("active");
    $(".hide-list").removeClass("active").hide();
    $(".show-list").show();
  });

  // Choose device
  $(".js-choose-device").click(function(e){
    e.preventDefault();
    var device = $(this).attr('href').replace("#", ""),
        iframe = $('.device-mode');
    iframe.removeClass("device-mobile device-tablet device-desktop" );
    $('.js-choose-device').removeClass('active');
    $(this).addClass('active');
    if(device != 'full') {
      iframe.addClass("device-"+device);
    }
  });

  // Scroll animate.
  var frameScrollAnimate = function () {
    var idActive = $(this).attr('href'),
      scrollTop,
      iframeF = $('.device-mode').contents();
    var heightHeader = iframeF.find('.header').height();
      scrollTop = $(idActive, iframeF).offset().top - heightHeader;
      iframeF.scrollTop(scrollTop);
    $(".sub").children('a').removeClass('active');
    $(this).addClass('active');
    return false;
  };

  $(".sub").children('a').click(frameScrollAnimate);

  SyntaxHighlighter.all()

}(this, this.document, this.jQuery));
