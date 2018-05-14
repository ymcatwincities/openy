(function(){

  function updateJson() {
    var $active = $(".page.active");
    var $next = $active.next();
    if ($next.length === 0) {
      $next = $(".page").first();
    }
    $active.fadeOut('slow', function() {
      $(this).removeClass('active');
      $next.fadeIn('slow').addClass('active');
    });
    $('.title').text($('html').attr('class')).css({fontSize: 13});
  };

  function timedUpdate () {
    init();
    setTimeout(timedUpdate, 10000);
  };

  function init() {
    var url = './parts/part-2.html?';
    setStatus('initializing');
    var t = (new Date()).getTime();
    $.get(url + (new Date()).getTime(), function(data) {
      var dt = (new Date()).getTime() - t;

      setStatus('content page requested in ' + dt + 'ms');
      setTimeout(function() {
        var $preloaded_data = $(data);
        $('.block', $preloaded_data).css({display: 'inline'});
        var image_urls = [];
        $('img', $preloaded_data).each(function() {
          var $this = $(this);
          var src = $this.attr('src');
          $(this).css({width: '10vw'});
          image_urls.push(src);
        });


        // Let's call it:
        preloadImages(image_urls, function(){
          setStatus('All images preloaded');
          var $new_page = $(".page-content").append('<div class="page"></div>');

          $(".loader, .page.active").fadeOut('slow', function () {
            $(".page.active").remove();
            $new_page.append($preloaded_data).fadeIn('slow', function() {
              $(this).addClass('active');
            });
          });
        });
      }, 500);

    });
  }

  function setStatus(status) {
    $(".status").append('<div>' + status + '</div>');
  }

  function preloadImages(urls, allImagesLoadedCallback){
    var loadedCounter = 0;
    var toBeLoadedNumber = urls.length;
    urls.forEach(function(url){
      preloadImage(url, function(){
        loadedCounter++;
        setStatus('Loaded images: ' + loadedCounter + '/' + toBeLoadedNumber);
        if (loadedCounter == toBeLoadedNumber){
          allImagesLoadedCallback();
        }
      });
    });
    function preloadImage(url, anImageLoadedCallback){
      var img = new Image();
      img.src = url;
      img.onload = anImageLoadedCallback;
      img.onerror = anImageLoadedCallback;
    }
  }


  //init();

  timedUpdate();

})();
