<?php

/**
 * @file
 * Lists available colors and color schemes for the OpenY Rose theme.
 */

$info = [
  // Available colors and color labels used in theme.
  'fields' => [
    'headerbg' => t('Header background'),
    'headerhover' => t('Header hover'),
    'headerlink' => t('Header link'),
    'headerlinkhover' => t('Header link hover'),
    'headermainmenubg' => t('Header main menu background'),
    'headermainmenulink' => t('Header main menu link'),
    'bg' => t('Main background'),
    'footer' => t('Footer background'),
    'footertext' => t('Footer text'),
    'text' => t('Global text color'),
    'link' => t('Global link color'),
    'button' => t('Button color'),
    'buttonlink' => t('Button link color'),
    'primaryhighlight' => t('Primary highlight color'),
    'secondaryhighlight' => t('Secondary highlight color'),
    'branchsubheaderbg' => t('Branch subheader background'),
    'branchmenubg' => t('Branch menu background'),
    'branchmenulink' => t('Branch menu link color'),
    'branchmenuhighlight' => t('Branch menu highlight color'),
    'branchbutton' => t('Branch button color'),
    'branchbuttonlink' => t('Branch button link color'),
    'campheaderbg' => t('Camp header background'),
    'campheaderhover' => t('Camp header hover'),
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
    'campprimaryhighlight' => t('Camp primary highlight color'),
    'campsecondaryhighlight' => t('Camp secondary highlight color'),
  ],
  // Pre-defined color schemes.
  'schemes' => [
    'default' => [
      'title' => t('Default'),
      'colors' => [
        'headerbg' => '#f8f8f8',
        'headerhover' => '#ffffff',
        'headerlink' => '#777777',
        'headerlinkhover' => '#000000',
        'headermainmenubg' => '#0060af',
        'headermainmenulink' => '#ffffff',
        'bg' => '#fffffb',
        'footer' => '#fffff9',
        'footertext' => '#a8a8a8',
        'text' => '#636466',
        'link' => '#377bb5',
        'button' => '#b92f9e',
        'buttonlink' => '#fffffc',
        'primaryhighlight' => '#5c2e91',
        'secondaryhighlight' => '#c6168d',
        'branchsubheaderbg' => '#c6168b',
        'branchmenubg' => '#383839',
        'branchmenulink' => '#fffff6',
        'branchmenuhighlight' => '#505051',
        'branchbutton' => '#7a1f63',
        'branchbuttonlink' => '#fffff5',
        'campheaderbg' => '#f8f8f9',
        'campheaderhover' => '#fffffa',
        'campheaderlink' => '#777778',
        'campheaderlinkhover' => '#000001',
        'campsubheaderbg' => '#c6168c',
        'campmenubg' => '#383838',
        'campmenulink' => '#fffffd',
        'campmenuhighlight' => '#505050',
        'campbg' => '#fffffe',
        'campfooter' => '#fffff8',
        'campfootertext' => '#a8a8a9',
        'camptext' => '#636465',
        'camplink' => '#377bb6',
        'campbutton' => '#1dafed',
        'campbuttonlink' => '#fffff7',
        'campprimaryhighlight' => '#5c2e92',
        'campsecondaryhighlight' => '#c6168e',
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
