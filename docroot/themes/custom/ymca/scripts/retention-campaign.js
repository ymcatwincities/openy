(function ($) {

  /**
   * Controls registration slide elements.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches the behavior.
   */
  Drupal.behaviors.registrationTips = {
    attach: function (context, settings) {
      jQuery('.where-can-i-find', context).once('registrationTips').bind('click', function () {
        jQuery('.access-help').addClass('open');
        return false;
      });
    }
  };

  /**
   * Controls slides on register/report section of Summer Retention page.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches the behavior.
   */
  Drupal.behaviors.registerAndReportSlides = {
    animationSpeed: 400,
    isAnimating: false,
    slidesContainer: $('#register-and-report'),
    attach: function (context, settings) {
      $('#register-and-report', context).once('register-reports-slides').each(function () {
        $(this).find('.slide').not(':first').slideUp();
      });
      $('.slide-controller', Drupal.behaviors.registerAndReportSlides.slidesContainer)
        .once('register-reports-slide')
        .each(function () {
          $(this).on('click', function () {
            var id = $(this).attr('href');
            Drupal.behaviors.registerAndReportSlides.slideTo(id);
            return false;
          });
        });
      if (context == document && location.hash) {
        switch (location.hash) {
          case '#registration':
          case '#report':
            setTimeout(function () {
              $('#hero-section').scrollTop(0).scrollLeft(0);
              $.scrollTo($('#hero-section'), 800);
              Drupal.behaviors.registerAndReportSlides.slideTo(location.hash);
            }, 1000);
            break;
        }
      }

    },
    slideTo: function (id) {
      if (Drupal.behaviors.registerAndReportSlides.isAnimating === true) {
        return;
      }
      Drupal.behaviors.registerAndReportSlides.isAnimating = true;
      var active_slide = $('.slide-active', Drupal.behaviors.registerAndReportSlides.slidesContainer);
      var hero = $('#hero-section');
      var slides = Drupal.behaviors.registerAndReportSlides.slidesContainer.find('.slide');
      var target_slide = jQuery(id, Drupal.behaviors.registerAndReportSlides.slidesContainer);
      var next_id = id;
      if ((active_slide.attr('id') == 'registration' && id == '#report') ||
          (active_slide.attr('id') == 'report' && id == '#registration')) {
        next_id = '#register-or-report';
      }
      target_slide.removeClass('slide-inactive');
      $('.alert', target_slide).remove();
      var animate_value = '-50%';
      if (next_id == '#register-or-report') {
        animate_value = '0%';
        hero.removeClass('shifted-layers');
      }
      else {
        target_slide.css({height: 'auto', display: 'block'});
        hero.addClass('shifted-layers');
      }
      if (active_slide.attr('id') == 'registration') {
        active_slide
          .find('.access-help')
          .removeClass('open');
      }

      hero.scrollLeft(0);
      slides.animate({left: animate_value}, Drupal.behaviors.registerAndReportSlides.animationSpeed, function () {
        var delay = 0;
        hero.scrollLeft(0);
        if (next_id == '#register-or-report') {
          delay = Drupal.behaviors.registerAndReportSlides.animationSpeed / 2;
          active_slide
            .removeClass('slide-active')
            .slideUp(Drupal.behaviors.registerAndReportSlides.animationSpeed / 2);
        }
        else {
          target_slide.addClass('slide-active');
          switch (next_id) {
            case '#report':
              $('#report .email').focus();
              break;

            case '#registration':
              $('#registration .facility-access-id').focus();
              break;
          }
        }
        Drupal.behaviors.registerAndReportSlides.isAnimating = false;
        if (id != next_id) {
          setTimeout(function () {
            Drupal.behaviors.registerAndReportSlides.slideTo(id);
          }, delay);
        }
      });
    }
  };

  /**
   * Fixes mobile menu.
   *
   * @type {{attach: Drupal.behaviors.mobileMenu.attach}}
   */
  Drupal.behaviors.mobileMenu = {
    attach: function (context, settings) {
      $('.ysr-menu', context).once('mobile-menu').each(function () {
        var $menu = jQuery(this);
        $('a', $menu).bind('click', function () {
          var parser = document.createElement('a');
          parser.href = $(this).prop('href');
          if (parser.pathname != location.pathname) {
            return true;
          }
          var hash = $(this).prop('hash');
          if (hash) {
            switch (hash) {
              case "#registration":
              case "#report":
                Drupal.behaviors.registerAndReportSlides.slideTo(hash);
                $.scrollTo($('#hero-section'), 800);
                break;
              default:
                $.scrollTo($(hash), 800);
                break;
            }
          }

          $menu.removeClass('in').addClass('collapsing');
          // WTF?
          setTimeout(function () {
            $('#hero-section').scrollTop(0).scrollLeft(0);
          }, 0);
          setTimeout(function () {
            $menu.removeClass('collapsing').addClass('collapse');
          }, 500);
          return false;
        });
      });

      if (context == document) {
        setTimeout(function () {
          $('#hero-section').scrollTop(0).scrollLeft(0);
        }, 0);
      }
    }
  };

  /**
   * Slick slider.
   *
   * To be replace by Slick Slider Drupal module's behaviour.
   *
   * @type {{attach: Drupal.behaviors.prototypeSlickSlider.attach}}
   */
  Drupal.behaviors.prototypeSlickSlider = {
    attach: function (context, settings) {
      jQuery('.slider', context).once('slick-slider').each(function () {
        jQuery(this).slick({
          dots: true,
          infinite: true,
          speed: 500,
          slidesToShow: 1,
          slidesToScroll: 1,
          autoplay: true,
          autoplaySpeed: 5000,
        });
      });
    }
  };

})(jQuery);
