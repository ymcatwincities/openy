/**
 * @file ymca_alters.promo_blocks.js
 */
(function ($, Drupal, drupalSettings) {

  "use strict";

  /**
   * Registers behaviors related to promo blocks.
   */
  Drupal.behaviors.PromoBlocks = {
    attach: function (context) {

      // Decorate promos which were migrated as is (without entity embed).
      var promos = $('.sidebar-promos > .richtext.original');
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

          $('p', this).each(function (i) {
            // Remove all comment nodes.
            $(this)
              .contents()
              .filter(function(){
                return this.nodeType == 8;
              })
              .remove();
            var text = $(this).html();
            if (text.replace(/\s/g, '') === '') {
              $(this).addClass('hidden');
            }
          });
          $(this).find('p:empty').remove();
        }
      );

    }
  };
}(jQuery, Drupal, drupalSettings));
