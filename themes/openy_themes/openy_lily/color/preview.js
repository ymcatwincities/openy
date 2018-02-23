/**
 * @file
 * Preview for the OpenY Lily theme.
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
      $colorPreview.find('.color-preview-menu .hover').css('background-color', $colorPalette.find('input[name="palette[headermenuhover]"]').val());
      $colorPreview.find('.color-preview-menu-link').css('color', $colorPalette.find('input[name="palette[headermenulink]"]').val());
      $colorPreview.find('.color-preview-menu-link.hover').css('color', $colorPalette.find('input[name="palette[headermenulinkhover]"]').val());
      $colorPreview.find('.color-preview-main-menu').css('background-color', $colorPalette.find('input[name="palette[headermainmenubg]"]').val());
      $colorPreview.find('.color-preview-main-menu-link').css('color', $colorPalette.find('input[name="palette[headermainmenulink]"]').val());
      $colorPreview.find('.color-preview-main-menu-link.hover').css('color', $colorPalette.find('input[name="palette[headermenulinkhover]"]').val());

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

      // Branch customizations.
      var $branchPreview = $form.find('.branch-color-preview');

      // Solid background.
      $branchPreview.css('backgroundColor', $colorPalette.find('input[name="palette[bg]"]').val());

      // Header.
      $branchPreview.find('.color-preview-header').css('background-color', $colorPalette.find('input[name="palette[headerbg]"]').val());
      $branchPreview.find('.color-preview-menu .hover').css('background-color', $colorPalette.find('input[name="palette[headermenuhover]"]').val());
      $branchPreview.find('.color-preview-menu .color-preview-menu-link').css('color', $colorPalette.find('input[name="palette[headermenulink]"]').val());
      $branchPreview.find('.color-preview-menu .color-preview-menu-link.hover').css('color', $colorPalette.find('input[name="palette[headermenulinkhover]"]').val());

      // Branch subhead.
      $branchPreview.find('.color-preview-branch-subheader').css('background-color', $colorPalette.find('input[name="palette[branchsubheaderbg]"]').val());

      // Text preview.
      $branchPreview.find('.color-preview-main').css('color', $colorPalette.find('input[name="palette[text]"]').val());
      $branchPreview.find('.color-preview-main a').css('color', $colorPalette.find('input[name="palette[link]"]').val());
      $branchPreview.find('.preview-button').css('background-color', $colorPalette.find('input[name="palette[branchbutton]"]').val());
      $branchPreview.find('.preview-button').css('color', $colorPalette.find('input[name="palette[branchbuttonlink]"]').val());

      // Footer.
      $branchPreview.find('.color-preview-footer').css('background-color', $colorPalette.find('input[name="palette[footer]"]').val());
      $branchPreview.find('.color-preview-footer').css('color', $colorPalette.find('input[name="palette[footertext]"]').val());

      // Camp customizations.
      var $campPreview = $form.find('.camp-color-preview');

      // Solid background.
      $campPreview.css('backgroundColor', $colorPalette.find('input[name="palette[bg]"]').val());

      // Header.
      $campPreview.find('.color-preview-header').css('background-color', $colorPalette.find('input[name="palette[headerbg]"]').val());
      $campPreview.find('.color-preview-menu .hover').css('background-color', $colorPalette.find('input[name="palette[headermenuhover]"]').val());
      $campPreview.find('.color-preview-menu .color-preview-menu-link').css('color', $colorPalette.find('input[name="palette[headermenulink]"]').val());
      $campPreview.find('.color-preview-menu .color-preview-menu-link.hover').css('color', $colorPalette.find('input[name="palette[headermenulinkhover]"]').val());

      // Camp subhead.
      $campPreview.find('.color-preview-branch-subheader').css('background-color', $colorPalette.find('input[name="palette[campsubheaderbg]"]').val());

      // Text preview.
      $branchPreview.find('.color-preview-main').css('color', $colorPalette.find('input[name="palette[text]"]').val());
      $campPreview.find('.color-preview-main a').css('color', $colorPalette.find('input[name="palette[camplink]"]').val());
      $campPreview.find('.preview-button').css('background-color', $colorPalette.find('input[name="palette[campbutton]"]').val());
      $campPreview.find('.preview-button').css('color', $colorPalette.find('input[name="palette[campbuttonlink]"]').val());

      // Camp menu.
      $campPreview.find('.color-preview-campmenu').css('background-color', $colorPalette.find('input[name="palette[campmenubg]"]').val());
      $campPreview.find('.color-preview-campmenu').css('color', $colorPalette.find('input[name="palette[campmenulink]"]').val());
      $campPreview.find('.color-preview-campmenu .hover').css('background-color', $colorPalette.find('input[name="palette[campmenuhighlight]"]').val());

      // Footer.
      $campPreview.find('.color-preview-footer').css('background-color', $colorPalette.find('input[name="palette[footer]"]').val());
      $campPreview.find('.color-preview-footer').css('color', $colorPalette.find('input[name="palette[footertext]"]').val());
    }
  };
})(jQuery, Drupal, drupalSettings);
