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
    'campbg' => t('Camp header'),
    'camptext' => t('Camp header text')
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
        'campbg' => '#c6168d',
        'camptext' => '#fff',
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
        'campbg' => '#c6168d',
        'camptext' => '#fff',
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
        'campbg' => '#c6168d',
        'camptext' => '#fff',
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
