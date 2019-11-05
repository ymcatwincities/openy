/**
 * @file
 * Open Y Carnation JS.
 */
(function ($) {
  "use strict";

  /**
   * User Login Form
   */
  Drupal.behaviors.openyUserLogin = {
    attach: function (context, settings) {
      $("input[type='password'][data-eye]").each(function (i) {
        var $this = $(this);

        $this.wrap($("<div class='position-relative'>"));

        $this.after($("<div/>", {
          html: 'Show',
          class: 'btn btn-primary btn-sm passeye-toggle',
          id: 'passeye-toggle-' + i,
        }));

        $this.after($("<input/>", {
          type: 'hidden',
          id: 'passeye-' + i
        }));

        $this.on("keyup paste", function () {
          $("#passeye-" + i).val($(this).val());
        });

        $("#passeye-toggle-" + i).on("click", function () {
          if ($this.hasClass("show")) {
            $this.attr('type', 'password');
            $this.removeClass("show");
            $(this).removeClass("btn-outline-primary");
          }
          else {
            $this.attr('type', 'text');
            $this.val($("#passeye-" + i).val());
            $this.addClass("show");
            $(this).addClass("btn-outline-primary");
          }
        });

      });
    }
  };

  /**
   * Hide/Show membership form.
   */
  Drupal.behaviors.showMember = {
    attach: function (context, settings) {
      $(context).find('#membership-page .webform-submission-form').once('membForm').each(function () {
        $('.try-the-y-toggle').on('click', function (e) {
          e.preventDefault();
          $('.try-the-y-toggle').addClass('active');
          $('.landing-content > .paragraph:nth-child(1), .landing-content > .paragraph:nth-child(3),  article.webform').slideDown('fast');
          $('html, body').animate({
            scrollTop: $("#membership-page .webform form").offset().top - 250
          }, 500);
        });
      });
    }
  };
})(jQuery);
