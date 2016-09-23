(function ($) {
  var ymca_theme_semaphore = false;

  /**
   * Scroll to the hash anchor.
   */
  Drupal.behaviors.ymca_page_with_hash = {
    attached: false,
    attach: function (context, settings) {
      var hash = $(window).attr('location').hash;
      if (hash && !this.attached) {
        window.setTimeout(function() {
          var menuHeight = $('.top-navs').height();
          var top = $(hash).offset().top;
          if ($('.top-navs').height() == 0) {
            // For mobile state.
            $(document).scrollTop(top - 52);
          }
          else {
            // Destop mode.
            $(document).scrollTop(top - 113);
          }
        }, 1000);
        this.attached = true;
      }
    }
  };

  Drupal.behaviors.ymca_theme = {
    attach: function (context, settings) {
      
      function getUrlVars() {
        var vars = {};
        var parts = window.location.href.replace(/[?&]+([^=&]+)=([^&]*)/gi, function (m, key, value) {
          vars[key] = value;
        });
        return vars;
      }

      if (!ymca_theme_semaphore) {
        ymca_theme_semaphore = true;
        var url_vars = getUrlVars(),
          blog_archive_active = false,
          blog_archive_month,
          blog_archive_year;

        if (typeof url_vars.month != "undefined") {
          blog_archive_month = url_vars.month;
          blog_archive_year = url_vars.year;
        }
        else {
          var path_split = window.location.pathname.split("/");
          blog_archive_month = path_split[path_split.length - 2];
          blog_archive_year = path_split[path_split.length - 3];
        }

        var component_el = $(".abe_blog_archives_display");

        var blog_current_year_el = component_el.find("#blog_archive_year_" + blog_archive_year);
        blog_current_year_el.css({"display": "block"});
        blog_current_year_el.prevAll('.blog_year_li').addClass("expanded")
          .find('.blog_year_link > i').removeClass('glyphicon-plus-sign').addClass('glyphicon-minus-sign');
        component_el.find("#blog_archive_month_" + blog_archive_year + "_" + blog_archive_month).addClass("active");

        component_el.find('.blog_year_link').on('click', function (e) {
          e.preventDefault();
          var el = $(this);
          var year_li = el.parents('.blog_year_li');
          year_li.nextAll('.blog_months_container_li').first().slideToggle('fast');
          if (year_li.hasClass("expanded")) {
            year_li.find('.blog_year_link > i').removeClass('glyphicon-minus-sign').addClass('glyphicon-plus-sign');
            year_li.removeClass("expanded");
          }
          else {
            year_li.find('.blog_year_link > i').removeClass('glyphicon-plus-sign').addClass('glyphicon-minus-sign');
            year_li.addClass("expanded");
          }
        });

        // Youth Sports page
        $('.path-youth-sports .join-the-y').on('click touchend', function (e) {
          e.preventDefault();
          var top = $('.content-cards').offset().top;
          $('html, body').animate({scrollTop: top}, 1000);
          return false;
        });

        $('.path-youth-sports .scroll-to-the-video').on('click touchend', function (e) {
          e.preventDefault();
          var top = $('.video-container').offset().top;
          $('html, body').animate({scrollTop: top}, 1000);
          return false;
        });

        // 2014 Annual Report pages
        $(".page_2014_annual_report a[data-toggle='collapse']").click(function () {
          if ($(this).text() == 'Read more') {
            $(this).addClass('opened');
          } else {
            $(this).text('Read more');
          }
        });
      }
    }
  };

  /**
   * March winners.
   */
  Drupal.behaviors.ymca_march = {
    attach: function (context, settings) {
      if ($('#quiz').length > 0 || $('#march-rules').length > 0) {
        $('#sidebar:eq(0)').remove();
        $('.navbar-toggle').click(function () {
          var menu = '<li><li><a href="'+ drupalSettings.path.baseUrl + 'march#prizes">Prizes</a></li><li><a href="'+ drupalSettings.path.baseUrl + 'march#quiz">YMCA Quiz</a></li><li><a href="'+ drupalSettings.path.baseUrl + 'march/rules">Detailed Rules</a></li>';
          $('#sidebar-nav .nav.dropdown-menu').html(menu);
        });

        // QUIZ show.
        $('#quiz .button').click(function (e) {
          e.preventDefault();
          $('#quiz').hide();
          $('#quiz-questions').show();
        });
        $('#quiz-questions').hide();

        $('#quiz-frame').load(function () {
          $('#quiz-frame').iFrameResize({checkOrigin: false, heightCalculationMethod: 'lowestElement'});
        });
      }
    }
  };

  /**
   * March winners.
   */
  Drupal.behaviors.ymca_march_winners = {
    attach: function (context, settings) {
      var $wrap = $('#more-prizes', context);
      if ($wrap.length === 0) {
        return false;
      }
      $('select', $wrap).on('change', function () {
        var val = $(this).val();
        var $parent = $(this).parents('.container');
        $('.table-location', $parent).addClass('hide');
        $('.table-location-' + val, $parent).removeClass('hide');
      });
      $('select', $wrap).trigger('change');
    }
  };

  /**
   * Cliendside Email validation.
   */
  Drupal.behaviors.ymca_email_pattern = {
    attach: function (context, settings) {
      $("input[type=email]", context).each(function () {
        if (!$(this).attr('pattern')) {
          $(this).attr('pattern', '[a-zA-Z0-9.!#$%&’*+/=?^_`{|}~-]+@[a-zA-Z0-9-]+\\.(?:[a-zA-Z0-9-\\.]+)*');
        }
      });
    }
  };

  /**
   * MindBody theme behaviors.
   */
  Drupal.behaviors.ymca_mindbody = {
    attach: function (context, settings) {
      $('#mindbody-pt-form-wrapper .change')
        .once('mindbody-change-toggler')
        .each(function () {
          $(this).bind('click', function (e) {
            var id = $(this).attr('href');
            $(id).slideToggle();
            return false;
          });
        });
    }
  };

  /**
   * GroupEx locations responsive columns.
   */
  Drupal.behaviors.ymca_GroupEx_locations = {
    attach: function (context, settings) {

      var win = $(window);

      var el_parent = $("#group-ex-locations"),
          el_items = el_parent.find(".form-group"),
          wrap_classes = "col-xs-12 col-md-6",
          el_count = el_items.length,
          col_count = 2,
          break_count = Math.ceil(el_count / col_count),
          prev_key = 0,
          cur_key = 0,
          prev_count = 0,
          break_cols = {
            0: 1,
            992: 2,
          };

      //add bootstrap row
      el_parent.addClass("row");

      //wrap location elements in bootstrap columns based on break_cols
      function wrap(item_count) {
        var el_wrap = $(".el-wrap > div");
        if (el_wrap.parent().is(".el-wrap")) {
          el_wrap.unwrap();
        }

        for (var i = 0; i < el_items.length; i += item_count) {
          el_items.slice(i, i + item_count).wrapAll("<div class='el-wrap " + wrap_classes + "'></div>");
        }
      }

      var set_wrap = function () {
        //determine column count per breakpoint
        $.each(break_cols, function (key, value) {
          var w = win.width();

          if (key < w && key !== prev_key) {
            cur_key = key;
          }

          key = prev_key;
        });

        //check if col count has change
        col_count = break_cols[cur_key];
        if (col_count != prev_count) {
          break_count = Math.ceil(el_count / col_count);
          wrap(break_count);
        }

        prev_count = col_count;

      };

      win.smartresize(function () {
        set_wrap();
      });

      set_wrap();


    }
  };

  /**
   * Youth sports behaviors.
   */
  Drupal.behaviors.ymca_youth_sports = {
    attach: function (context, settings) {
      $('.template_youth_sports_overview section.node .content_group section ul li a').each(function() {
        // set css classes based on link title.
        var title = $(this).text().toLowerCase().replace(/ /g, '-').replace(/\//g, '-'),
            css_class = title + ' sports-icon';
        $(this).attr('class', css_class);
      });
      $('.template_youth_sports_overview a[href*=".pdf"], .template_youth_sports_inner a[href*=".pdf"]').each(function() {
        $(this).attr('class', 'pdf-link');
      });
      $('.template_youth_sports_overview a[href^="tel:"], .template_youth_sports_inner a[href^="tel:"]').each(function() {
        $(this).attr('class', 'tel-link');
        if ($(this).find('.icon-phone').length === 0) {
          $(this).prepend('<span class="icon icon-phone"></span>');
        }
      });
      var index = 1;
      $('.template_youth_sports_inner section.node .content-expander').each(function() {
        $(this).addClass('content-expander-' + index);
        index++;
      });

      // Sports Top Sub header.
      if ($('.sports_top_subheader .selectbox ul').length > 0) {
        var selectbox = '<select>';
        $('.sports_top_subheader .selectbox a').each(function () {
          // set css classes based on link title.
          var title = $(this).text().toLowerCase().replace(/ /g, '-').replace(/\//g, '-'),
              css_class = title + ' sports-icon';
          $(this).attr('class', css_class);
          selectbox += '<option class="' + css_class + '" value="' + $(this).attr('href') + '">' + $(this).text() + '</option>';
        });
        selectbox += '</select><a class="find-a-class">' + Drupal.t('FIND A CLASS') + '</a>';
        $('.sports_top_subheader .selectbox').html(selectbox);
        $('.sports_top_subheader .find-a-class').on('click', function(e) {
          e.preventDefault();
          var url = $(this).prev().find(':selected').val();
          window.open(url, '_blank');
        });
        $('.sports_top_subheader select').attr('class', $('.sports_top_subheader select option:eq(0)').attr('class'));
        $('.sports_top_subheader select').on('change', function() {
          var css_class = $(this).find(':selected').attr('class');
          $(this).attr('class', css_class);
        });
      }
    }
  };

  /**
   * Masthead Menu Activation behavior.
   */
  Drupal.behaviors.ymcaMastheadMenuActive = {
    attach: function (context, settings) {
      if (!$('#masthead-menu').length) {
        return;
      }
      $('#masthead-menu')
        .once('ymca-masthead-menu-active')
        .on('shown.bs.dropdown', function(){
          $('body').addClass('masthead-menu-active');
          Drupal.behaviors.ymcaMastheadMenuActive.setDropdownHeight();
        })
        .on('hide.bs.dropdown', function(){
          jQuery('body').removeClass('masthead-menu-active');
        });
      $(window).on('resize', Drupal.behaviors.ymcaMastheadMenuActive.setDropdownHeight);

    },
    setDropdownHeight: function () {
      var windowHeight = $(window).height(),
        menu = $('#masthead-menu'),
        isFixed = menu.hasClass('affix'),
        offset = menu.length ?
          isFixed ?
          menu.position().top + menu.height() :
          menu.position().top - jQuery(window).scrollTop() + menu.height() :
          null;

      if (!offset) {
        return;
      }
      $('#masthead-menu .open .dropdown-menu')
        .css('max-height', (windowHeight - offset) - 30);
    }
  };

})(jQuery);
