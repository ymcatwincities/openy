(function(){

  var start = (new Date()).getTime();

  function showProgress(callback) {
    $(".page-content").fadeOut('slow', function() {
      $(".loader").fadeIn('slow', callback);
    });
  }

  function hideProgress(callback) {
    $(".loader").fadeOut('slow', function() {
      $(".page-content").fadeIn('slow', callback);
    });
  }

  function loop() {
    var classes = getCurrentAndNext();
    if (needsActiveClassActualization(classes)) {
      showProgress(function() {
        actualizeActiveClasses(classes);
        updateProgressBars();
        setTimeout(function() {
          hideProgress(function() {});
        }, 1000)
      });
    }
  }

  function updateProgressBars() {
    var $class = $(".active-classes .class-active .class");
    var offset = getTimeOffset();
    var from = $class.data('from');
    var to = $class.data('to');
    var progress = 100 * (offset - from) / (to - from);
    $class.find('.class-time-frame-progress-bar').css({width: progress + '%'});
    $class.find('.class-time-frame-progress-bar').css({width: 0});
    $class.find('.class-time-frame-progress-bar').animate({width:'100%'}, (to - offset) * 1000, 'linear');
  }

  function needsActiveClassActualization(classes) {
    var $activeClassContainer = $(".active-classes .class-active");
    var $activeClass = $(".class", $activeClassContainer);
    if (!$activeClass.size() || $activeClass.data('from') != classes.last.data('from')) {
      return true;
    }

    var $upcomingClassContainer = $(".active-classes .class-next");
    var $upcomingClass = $(".class", $upcomingClassContainer);
    if (!$upcomingClass.size() || $upcomingClass.data('from') != classes.next.data('from')) {
      return true;
    }

    return false;
  }

  function actualizeActiveClasses(classes) {
    var $activeClassContainer = $(".active-classes .class-active");
    var $activeClass = $(".class", $activeClassContainer);
    if (!$activeClass.size() || $activeClass.data('from') != classes.last.data('from')) {
      $activeClassContainer.empty().append(classes.last.clone(true));
    }

    var $upcomingClassContainer = $(".active-classes .class-next");
    var $upcomingClass = $(".class", $upcomingClassContainer);
    if (!$upcomingClass.size() || $upcomingClass.data('from') != classes.next.data('from')) {
      $upcomingClassContainer.empty().append(classes.next.first().clone(true));
    }
  }

  function getTimeOffset()  {
    return 1 * ((new Date()).getTime() - start) / 1000;
  }

  function getCurrentAndNext() {
    var $last = null, $next = null;
    var offset = getTimeOffset();
    $(".all-classes .class").each(function(){
      if ($(this).data('from') <= offset) {
        $last = $(this);
      }
      if (!$next && $(this).data('from') > offset) {
        $next = $(this);
      }
    });

    return {
      last: $last,
      next: $next
    }
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

  setInterval(loop, 1000);

  setTimeout(function() {
    location.reload();
  }, 120000);

})();
