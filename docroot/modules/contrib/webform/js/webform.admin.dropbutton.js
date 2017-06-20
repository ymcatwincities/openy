/**
 * @file
 * Dropbutton feature.
 */

(function ($, Drupal) {

  'use strict';

  /**
   * Wrap Drupal's dropbutton behavior so that the dropbutton widget is only visible after it is initialized.
   */
  var dropButton = Drupal.behaviors.dropButton;
  Drupal.behaviors.dropButton = {
    attach: function (context, settings) {
      dropButton.attach(context, settings);
      $(context).find('.dropbutton-wrapper').once('webform-dropbutton').css('visibility', 'visible');
    }
  };

})(jQuery, Drupal);
