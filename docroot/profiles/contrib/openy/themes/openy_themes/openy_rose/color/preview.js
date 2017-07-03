/**
 * @file
 * Preview for the OpenY Rose theme.
 */
(function ($, Drupal, drupalSettings) {

  'use strict';

  Drupal.color = {
    logoChanged: false,
    callback: function (context, settings, $form) {
      // Change the logo to be the real one.
      if (!this.logoChanged) {
        $('.color-preview-logo').css('background-image', 'url(' + drupalSettings.color.logo + ')');
        this.logoChanged = true;
      }

      var $colorPreview = $form.find('.color-preview');
      var $colorPalette = $form.find('.js-color-palette');

      // Solid background.
      $colorPreview.css('backgroundColor', $colorPalette.find('input[name="palette[bg]"]').val());

      // Header.
      $colorPreview.find('.color-preview-header').css('background-color', $colorPalette.find('input[name="palette[headerbg]"]').val());
      $colorPreview.find('.color-preview-menu .hover').css('background-color', $colorPalette.find('input[name="palette[headerhover]"]').val());
      $colorPreview.find('.color-preview-menu-link').css('color', $colorPalette.find('input[name="palette[headerlink]"]').val());
      $colorPreview.find('.color-preview-menu-link.hover').css('color', $colorPalette.find('input[name="palette[headerlinkhover]"]').val());

      // Branch subhead.
      $colorPreview.find('.color-preview-branch-subheader').css('background-color', $colorPalette.find('input[name="palette[branchsubheaderbg]"]').val());

      // Text preview.
      $colorPreview.find('.color-preview-main').css('color', $colorPalette.find('input[name="palette[text]"]').val());
      $colorPreview.find('.color-preview-main a').css('color', $colorPalette.find('input[name="palette[link]"]').val());
      $colorPreview.find('.color-preview-main h3').css('color', $colorPalette.find('input[name="palette[primaryhighlight]"]').val());
      $colorPreview.find('.color-preview-main h4').css('color', $colorPalette.find('input[name="palette[secondaryhighlight]"]').val());
      $colorPreview.find('.preview-button').css('background-color', $colorPalette.find('input[name="palette[button]"]').val());
      $colorPreview.find('.preview-button').css('color', $colorPalette.find('input[name="palette[buttonlink]"]').val());

      // Footer.
      $colorPreview.find('.color-preview-footer').css('background-color', $colorPalette.find('input[name="palette[footer]"]').val());
      $colorPreview.find('.color-preview-footer').css('color', $colorPalette.find('input[name="palette[footertext]"]').val());

      // Camp customizations.
      var $campPreview = $form.find('.camp-color-preview');

      // Solid background.
      $campPreview.css('backgroundColor', $colorPalette.find('input[name="palette[campbg]"]').val());

      // Header.
      $campPreview.find('.color-preview-header').css('background-color', $colorPalette.find('input[name="palette[campheaderbg]"]').val());
      $campPreview.find('.color-preview-menu .hover').css('background-color', $colorPalette.find('input[name="palette[campheaderhover]"]').val());
      $campPreview.find('.color-preview-menu .color-preview-menu-link').css('color', $colorPalette.find('input[name="palette[campheaderlink]"]').val());
      $campPreview.find('.color-preview-menu .color-preview-menu-link.hover').css('color', $colorPalette.find('input[name="palette[campheaderlinkhover]"]').val());

      // Branch subhead.
      $campPreview.find('.color-preview-branch-subheader').css('background-color', $colorPalette.find('input[name="palette[campsubheaderbg]"]').val());

      // Text preview.
      $campPreview.find('.color-preview-main').css('color', $colorPalette.find('input[name="palette[camptext]"]').val());
      $campPreview.find('.color-preview-main a').css('color', $colorPalette.find('input[name="palette[camplink]"]').val());
      $campPreview.find('.preview-button').css('background-color', $colorPalette.find('input[name="palette[campbutton]"]').val());
      $campPreview.find('.preview-button').css('color', $colorPalette.find('input[name="palette[campbuttonlink]"]').val());

      // Camp menu.
      $campPreview.find('.color-preview-campmenu').css('background-color', $colorPalette.find('input[name="palette[campmenubg]"]').val());
      $campPreview.find('.color-preview-campmenu').css('color', $colorPalette.find('input[name="palette[campmenulink]"]').val());
      $campPreview.find('.color-preview-campmenu .hover').css('background-color', $colorPalette.find('input[name="palette[campmenuhighlight]"]').val());

      // Footer.
      $campPreview.find('.color-preview-footer').css('background-color', $colorPalette.find('input[name="palette[campfooter]"]').val());
      $campPreview.find('.color-preview-footer').css('color', $colorPalette.find('input[name="palette[campfootertext]"]').val());
    }
  };
})(jQuery, Drupal, drupalSettings);
