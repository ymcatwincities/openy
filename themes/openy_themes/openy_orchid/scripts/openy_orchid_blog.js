(function($) {
    "use strict";
    Drupal.behaviors.ymca_blog_autosubmit = {
        attach: function(context, settings) {
            // Autosubmit views exposed form.
            $("div.blog-more-teaser").find("form.views-exposed-form").find("select").bind("change", function () {
                $(this).closest("form").find('input[type="submit"]').click();
            }).end();
        }
    };
})(jQuery);

