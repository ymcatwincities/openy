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
    'branchsubheaderbg' => t('Branch subheader background'),
    'campheaderbg' => t('Camp header background'),
    'campheaderhover' => t('Camp header menu hover'),
    'campsubheaderbg' => t('Camp subheader background'),
    'campmenubg' => t('Camp menu background'),
    'campmenulink' => t('Camp menu link color'),
    'campmenuhighlight' => t('Camp menu highlight color'),
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
        'branchsubheaderbg' => '#c6168d',
        'campheaderbg' => '#f47920',
        'campheaderhover' => '#f86b05',
        'campsubheaderbg' => '#c6168c',
        'campmenubg' => '#0060ad',
        'campmenulink' => '#fffffd',
        'campmenuhighlight' => '#0089d1',
        'campbg' => '#fffffe',
        'campfooter' => '#01a490',
        'camptext' => '#636465',
        'camplink' => '#01a491',
        'campbutton' => '#2fb998',
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
        'branchsubheaderbg' => '#c6168d',
        'campheaderbg' => '#f47920',
        'campheaderhover' => '#f86b05',
        'campsubheaderbg' => '#c6168c',
        'campmenubg' => '#0060ad',
        'campmenulink' => '#fffffd',
        'campmenuhighlight' => '#0089d1',
        'campbg' => '#fffffe',
        'campfooter' => '#01a490',
        'camptext' => '#636465',
        'camplink' => '#01a491',
        'campbutton' => '#2fb998',
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
        'branchsubheaderbg' => '#c6168d',
        'campheaderbg' => '#f47920',
        'campheaderhover' => '#f86b05',
        'campsubheaderbg' => '#c6168c',
        'campmenubg' => '#0060ad',
        'campmenulink' => '#fffffd',
        'campmenuhighlight' => '#0089d1',
        'campbg' => '#fffffe',
        'campfooter' => '#01a490',
        'camptext' => '#636465',
        'camplink' => '#01a491',
        'campbutton' => '#2fb998',
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

  // Preview files.
  'preview_library' => 'openy_rose/color.preview',
  'preview_html' => 'color/preview.html',

  // Attachments.
  '#attached' => [
    'drupalSettings' => [
      'color' => [
        // Put the logo path into JavaScript for the live preview.
        'logo' => theme_get_setting('logo.url', 'openy_rose'),
      ],
    ],
  ],
);
