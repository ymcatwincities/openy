<?php

/**
 * @file
 * Lists available colors and color schemes for the OpenY Rose theme.
 */

$info = [
  // Available colors and color labels used in theme.
  'fields' => [
    'headerbg' => t('Header background'),
    'headerhover' => t('Header menu hover'),
    'headerlink' => t('Header link'),
    'headerlinkhover' => t('Header link hover'),
    'bg' => t('Main background'),
    'footer' => t('Footer background'),
    'footertext' => t('Footer text'),
    'text' => t('Text color'),
    'link' => t('Link color'),
    'button' => t('Button color'),
    'buttonlink' => t('Button link color'),
    'primaryhighlight' => t('Primary highlight color'),
    'secondaryhighlight' => t('Secondary highlight color'),
    'branchsubheaderbg' => t('Branch subheader background'),
    'campheaderbg' => t('Camp header background'),
    'campheaderhover' => t('Camp header menu hover'),
    'campheaderlink' => t('Camp header link'),
    'campheaderlinkhover' => t('Camp header link hover'),
    'campsubheaderbg' => t('Camp subheader background'),
    'campmenubg' => t('Camp menu background'),
    'campmenulink' => t('Camp menu link color'),
    'campmenuhighlight' => t('Camp menu highlight color'),
    'campbg' => t('Camp main background'),
    'campfooter' => t('Camp footer background'),
    'campfootertext' => t('Camp footer text'),
    'camptext' => t('Camp text color'),
    'camplink' => t('Camp link color'),
    'campbutton' => t('Camp button color'),
    'campbuttonlink' => t('Camp button link color'),
  ],
  // Pre-defined color schemes.
  'schemes' => [
    'default' => [
      'title' => t('Default'),
      'colors' => [
        'headerbg' => '#0089d0',
        'headerhover' => '#0060af',
        'headerlink' => '#fffffb',
        'headerlinkhover' => '#d8d8d8',
        'bg' => '#ffffff',
        'footer' => '#4f4f4f',
        'footertext' => '#fffffa',
        'text' => '#636466',
        'link' => '#337ab7',
        'button' => '#00aeef',
        'buttonlink' => '#fffffc',
        'primaryhighlight' => '#5c2e91',
        'secondaryhighlight' => '#c6168d',
        'branchsubheaderbg' => '#c6168b',
        'campheaderbg' => '#f47920',
        'campheaderhover' => '#f86b05',
        'campheaderlink' => '#fffff8',
        'campheaderlinkhover' => '#d8d8d7',
        'campsubheaderbg' => '#c6168c',
        'campmenubg' => '#0060ad',
        'campmenulink' => '#fffffd',
        'campmenuhighlight' => '#0089d1',
        'campbg' => '#fffffe',
        'campfooter' => '#01a490',
        'campfootertext' => '#fffff7',
        'camptext' => '#636465',
        'camplink' => '#01a491',
        'campbutton' => '#2fb998',
        'campbuttonlink' => '#fffff9',
      ],
    ],
  ],

  // CSS files (excluding @import) to rewrite with new color scheme.
  'css' => [
    'css/colors.css',
  ],

  // Copy the logo file from the theme. Color module forces this behavior.
  'copy' => [
    'logo.svg',
  ],

  // Preview files.
  'preview_library' => 'openy_lily/color.preview',
  'preview_html' => 'color/preview.html',

  // Attachments.
  '#attached' => [
    'drupalSettings' => [
      'color' => [
        // Put the logo path into JavaScript for the live preview.
        'logo' => theme_get_setting('logo.url', 'openy_lily'),
      ],
    ],
  ],
];
