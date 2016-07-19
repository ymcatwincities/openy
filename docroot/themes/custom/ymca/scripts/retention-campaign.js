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
    attach: function (context, settings) {
      jQuery('#register-and-report', context).once('register-reports-slides').each(function () {
        var self = jQuery(this);
        var hero = self.parent();
        var slides_container = jQuery(this);
        var slides = jQuery(this).find('.slide');

        slides.not(':first').slideUp();

        jQuery('.slide-controller', slides_container).on('click', function () {
          var id = jQuery(this, slides_container).attr('href');
          var target_slide = jQuery(id, slides_container);

          target_slide.removeClass('slide-inactive');
          $('.alert', target_slide).remove();
          var animate_value = '-50%';
          if (id == '#register-or-report') {
            animate_value = '0%';
            hero.removeClass('shifted-layers');
          }
          else {
            target_slide.css({height: 'auto', display: 'block'});
            hero.addClass('shifted-layers');
          }
          var active_slide = jQuery('.slide-active', slides_container);
          if (active_slide.attr('id') == 'registration') {
            active_slide
              .find('.access-help')
              .removeClass('open');
          }

          hero.scrollLeft(0);
          slides.animate({left: animate_value}, function () {
            if (id == '#register-or-report') {
              active_slide
                .removeClass('slide-active')
                .slideUp();
            }
            else {
              target_slide.addClass('slide-active');
              switch (id) {
                case '#report':
                  jQuery('#report .email', self).focus();
                  break;

                case '#registration':
                  jQuery('#registration .facility-access-id', self).focus();
                  break;
              }
            }

          });

          return false;
        });
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
      jQuery('.ysr-menu', context).once('mobile-menu').each(function () {
        var $menu = jQuery(this);
        jQuery('a', $menu).bind('click', function () {
          var parser = document.createElement('a');
          parser.href = jQuery(this).prop('href');
          if (parser.pathname != location.pathname) {
            return true;
          }
          var hash = jQuery(this).prop('hash');
          if (hash) {
            jQuery.scrollTo($(hash), 800);
          }

          $menu.removeClass('in').addClass('collapsing');
          // WTF?
          setTimeout(function () {
            jQuery('#hero-section').scrollTop(0);
          }, 0);
          setTimeout(function () {
            $menu.removeClass('collapsing').addClass('collapse');
          }, 500);
          return false;
        });
      });
    }
  };

  /**
   * Changeable banner.
   *
   * @type {{attach: Drupal.behaviors.mobileMenu.attach}}
   */
  Drupal.behaviors.prototypeChangeBanner = {
    attach: function (context, settings) {
      jQuery('#hero-banner-selector', context).once('change-banner').each(function () {
        var $self = jQuery(this);
        $self.bind('change', function () {
          jQuery('#hero-section')
            .removeClass('hero-1 hero-2 hero-3')
            .addClass($self.val());
        });
      });
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
