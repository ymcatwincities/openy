/**
 * @file openy_gtranslate.js
 */
(function ($, Drupal, drupalSettings) {
  Drupal.googleTranslateElementInit = function() {
    var elem = $('.openy-google-translate:visible').get(0);
    if (elem === undefined) {
      console.log('Placeholder for google translate widget not found.');
    }
    else {
      new google.translate.TranslateElement(
        {
          pageLanguage: drupalSettings.path.currentLanguage,
          layout: google.translate.TranslateElement.InlineLayout.VERTICAL,
        },
        elem
      );
    }
  };
}(jQuery, Drupal, drupalSettings));
