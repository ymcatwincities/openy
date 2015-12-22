Drupal.behaviors.ymca_theme = {
  attach: function (context, settings) {

    function getUrlVars() {
      var vars = {};
      var parts = window.location.href.replace(/[?&]+([^=&]+)=([^&]*)/gi, function (m, key, value) {
        vars[key] = value;
      });
      return vars;
    }


    jQuery(function () {
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

      var component_id = '400002';
      var component_el = jQuery(".abe_blog_archives_display");

      var blog_current_year_el = component_el.find("#blog_archive_year_" + blog_archive_year);
      blog_current_year_el.css({"display": "block"});
      blog_current_year_el.prevAll('.blog_year_li').addClass("expanded")
        .find('.blog_year_link > i').removeClass('glyphicon-plus-sign').addClass('glyphicon-minus-sign');
      component_el.find("#blog_archive_month_" + blog_archive_year + "_" + blog_archive_month).addClass("active");

      component_el.find('.blog_year_link').on('click', function (e) {
        e.preventDefault();
        var el = jQuery(this);
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
  }
};
