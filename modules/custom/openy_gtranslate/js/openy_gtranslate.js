/**
 * @file openy_gtranslate.js
 */
function googleTranslateElementInit() {
  var elem = jQuery('.openy-google-translate:visible').get(0);
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
}
