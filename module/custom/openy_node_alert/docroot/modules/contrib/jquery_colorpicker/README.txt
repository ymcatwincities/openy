The Drupal 8 branch of Jquery Colorpicker offers a form element than can be
included in any form in this way:

<?php
$form['element'] = [
	'#type' => 'jquery_colorpicker',
	'#title' => t('Color'),
	'#default_value' => 'FFFFFF',
];
?>

This module includes Field API integration. A colorpicker field can be added to
any content type with the JQuery Colorpicker widget

==================
Installation guide
==================

Manual installation:
 1.- Navigate to the /libraries folder in the Drupal webroot. Create this directory if it does not exist.
 2.- Go to www.eyecon.ro/colorpicker/ and download colorpicker.zip.
 3.- Extract the the zip file content to the
      /libraries/jquery_colorpicker folder.
 4.- If you have extracted the contents correctly, the following file should
      exist: /libraries/jquery_colorpicker/js/colorpicker.js
 5.- Install the module same as any other Drupal module
 6.- Enjoy your colors!!
