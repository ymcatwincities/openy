<?php

/**
 * @file
 * Lists available colors and color schemes for the OpenY Rose theme.
 */

$info = array(
  // Available colors and color labels used in theme.
  'fields' => array(
    'headerbg' => t('Header background'),
    'headerhover' => t('Header menu hover'),
    'bg' => t('Main background'),
    'footer' => t('Footer background'),
    'text' => t('Text color'),
    'link' => t('Link color'),
    'button' => t('Button color'),
    'campheaderbg' => t('Camp header background'),
    'campheaderhover' => t('Camp header menu hover'),
    'campbg' => t('Camp main background'),
    'campfooter' => t('Camp footer background'),
    'camptext' => t('Camp text color'),
    'camplink' => t('Camp link color'),
    'campbutton' => t('Camp button color'),
  ),
  // Pre-defined color schemes.
  'schemes' => array(
    'default' => array(
      'title' => t('Lochmara (default)'),
      'colors' => array(
        'headerbg' => '#0089d0',
        'headerhover' => '#0060af',
        'bg' => '#ffffff',
        'footer' => '#4f4f4f',
        'text' => '#636466',
        'link' => '#337ab7',
        'button' => '#00aeef',
        'campheaderbg' => '#0089d1',
        'campheaderhover' => '#0060ae',
        'campbg' => '#fffffe',
        'campfooter' => '#4f4f4e',
        'camptext' => '#636465',
        'camplink' => '#337ab6',
        'campbutton' => '#00aeee',
      ),
    ),
    'endeavour' => array(
      'title' => t('Endeavour'),
      'colors' => array(
        'headerbg' => '#0060af',
        'headerhover' => '#3983c1',
        'bg' => '#ffffff',
        'footer' => '#434343',
        'text' => '#636466',
        'link' => '#428bca',
        'button' => '#00aeef',
        'campheaderbg' => '#0060af',
        'campheaderhover' => '#3983c1',
        'campbg' => '#ffffff',
        'campfooter' => '#434343',
        'camptext' => '#636466',
        'camplink' => '#428bca',
        'campbutton' => '#00aeef',
      ),
    ),
    'vividviolet' => array(
      'title' => t('Vivid Violet'),
      'colors' => array(
        'headerbg' => '#822a92',
        'headerhover' => '#9950a6',
        'bg' => '#ffffff',
        'footer' => '#434343',
        'text' => '#636466',
        'link' => '#822a92',
        'button' => '#991f76',
        'campheaderbg' => '#822a92',
        'campheaderhover' => '#9950a6',
        'campbg' => '#ffffff',
        'campfooter' => '#434343',
        'camptext' => '#636466',
        'camplink' => '#822a92',
        'campbutton' => '#991f76',
      ),
    ),
  ),

  // CSS files (excluding @import) to rewrite with new color scheme.
  'css' => array(
    'css/colors.css',
  ),

  // Copy the logo file from the theme. Color module forces this behavior.
  'copy' => array(
    'logo.svg',
  ),

);
