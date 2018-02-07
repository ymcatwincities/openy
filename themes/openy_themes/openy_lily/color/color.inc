<?php

/**
 * @file
 * Lists available colors and color schemes for the OpenY Lily theme.
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
        'bg' => '#ffffff',
        'footer' => '#ffffff',
        'footertext' => '#a8a8a8',
        'text' => '#636466',
        'link' => '#377bb5',
        'button' => '#b92f9e',
        'buttonlink' => '#fffffc',
        'primaryhighlight' => '#5c2e91',
        'secondaryhighlight' => '#c6168d',
        'branchsubheaderbg' => '#5a2b8d',
        'branchmenubg' => '#383838',
        'branchmenulink' => '#ffffff',
        'branchmenuhighlight' => '#505050',
        'branchbutton' => '#7a1f63',
        'branchbuttonlink' => '#ffffff',
        'campheaderbg' => '#f8f8f8',
        'campheaderhover' => '#ffffff',
        'campheaderlink' => '#777777',
        'campheaderlinkhover' => '#000000',
        'campsubheaderbg' => '#f47920',
        'campmenubg' => '#383838',
        'campmenulink' => '#ffffff',
        'campmenuhighlight' => '#505050',
        'campbg' => '#ffffff',
        'campfooter' => '#ffffff',
        'campfootertext' => '#a8a8a8',
        'camptext' => '#636466',
        'camplink' => '#377bb5',
        'campbutton' => '#019c86',
        'campbuttonlink' => '#ffffff',
        'campprimaryhighlight' => '#019c86',
        'campsecondaryhighlight' => '#c6168d',
      ],
    ],
  ],

  // CSS files (excluding @import) to rewrite with new color scheme.
  'css' => [
    'css/colors.css',
  ],

  // Copy the logo file from the theme. Color module forces this behavior.
  'copy' => [
    'logo.png',
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
