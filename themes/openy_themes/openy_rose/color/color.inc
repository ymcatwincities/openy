<?php

/**
 * @file
 * Lists available colors and color schemes for the OpenY Rose theme.
 */

$info = array(
  // Available colors and color labels used in theme.
  'fields' => array(
    'header_bg' => t('Header background'),
    'header_hover' => t('Header menu hover'),
    'main_bg' => t('Main background'),
    'footer' => t('Footer background'),
    'text' => t('Text color'),
    'link' => t('Link color'),
    'button' => t('Button color'),
  ),
  // Pre-defined color schemes.
  'schemes' => array(
    'default' => array(
      'title' => t('Lochmara (default)'),
      'colors' => array(
        'header_bg' => '#0089d0',
        'header_hover' => '#0060af',
        'main_bg' => '#ffffff',
        'footer' => '#4f4f4f',
        'text' => '#636466',
        'link' => '#337ab7',
        'button' => '#00aeef',
      ),
    ),
  ),

  // CSS files (excluding @import) to rewrite with new color scheme.
  'css' => array(
    'css/colors.css',
  ),

  /**
   * This copies the logo file from the theme. It is needed because the logo
   * gets overridden by the Color module automatically.
   * */
  'copy' => array(
    'logo.svg',
  ),

);
