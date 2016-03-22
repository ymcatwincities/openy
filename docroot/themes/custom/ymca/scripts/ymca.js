(function($) {
  Drupal.behaviors.ymca_theme = {
    attach: function (context, settings) {

      function getUrlVars() {
        var vars = {};
        var parts = window.location.href.replace(/[?&]+([^=&]+)=([^&]*)/gi, function (m, key, value) {
          vars[key] = value;
        });
        return vars;
      }


      $(function () {
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
      });

      // decorate promos
      $('.home-template .main-promos > .richtext').addClass('col-md-4 col-sm-6');
      var promos = $('.sidebar-promos > .richtext, .main-promos > .main-promo .richtext, .home-template .main-promos > .richtext');

      promos.each(
        function () {
          var el = $(this),
            links = $(this).find('a'),
            link = links.eq(0),
            title = link.text() !== '' ? link.text() : $(this).find('h2').text(),
            href = link.attr('href'),
            clickable = links.length == 1 || el.is('.video'),
            wrapper = clickable ? $('<a class="wrapper"/>')
              .attr('href', href)
              .attr('title', title) : '<div class="text-promo"/>',
            thumb = el
              .find('img')
              .addClass('img-responsive')
              .removeAttr('height')
              .removeAttr('width')
              .wrap('<div class="img-crop img-crop-horizontal"/>')
              .parent();
          if (el.find('.promo-text p').length === 0) {
            el.find('.promo-text').html('<p>' + el.find('.promo-text').text() + '</p>');
          }
          if (clickable) link.remove();
          el
            .wrapInner(wrapper)
            .children()
            .eq(0)
            .prepend(thumb);

          $(this).find('p:empty').remove();
        }
      );
      $('.main-promos').removeClass('hidden');

      // Youth Sports page
      (function($) {
        $(document).ready(function() {
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
        });
      })(jQuery);

      // 2014 Annual Report pages
      $(".page_2014_annual_report a[data-toggle='collapse']").click(function() {
        if ($(this).text() == 'Read more') {
          $(this).addClass('opened');
        } else {
          $(this).text('Read more');
        }
      });
    }
  };
})(jQuery);
