<?php

/**
 * @file
 * Lists available colors and color schemes for the OpenY Lily theme.
 */

$info = [
  // Available colors and color labels used in theme.
  'fields' => [
    'headerbg' => t('Header background'),
    'headermenulink' => t('Header menu link'),
    'headermenuhover' => t('Header menu hover'),
    'headermenulinkhover' => t('Header menu link hover'),
    'headermainmenubg' => t('Header main menu background'),
    'headermainmenulink' => t('Header main menu link'),
    'bg' => t('Main background'),
    'footer' => t('Footer background'),
    'footertext' => t('Footer text'),
    'text' => t('Global text color'),
    'link' => t('Global link color'),
    'button' => t('Global button color'),
    'buttonlink' => t('Global Button link color'),
    'primaryhighlight' => t('Primary highlight color'),
    'secondaryhighlight' => t('Secondary highlight color'),
    'branchsubheaderbg' => t('Branch subheader background'),
    'branchbutton' => t('Branch button color'),
    'branchbuttonlink' => t('Branch button link color'),
    'campsubheaderbg' => t('Camp subheader background'),
    'campmenubg' => t('Camp menu background'),
    'campmenulink' => t('Camp menu link color'),
    'campmenuhighlight' => t('Camp menu highlight color'),
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
        'headermenulink' => '#777777',
        'headermenuhover' => '#ffffff',
        'headermenulinkhover' => '#000000',
        'headermainmenubg' => '#0060af',
        'headermainmenulink' => '#fffffe',
        'bg' => '#fffffb',
        'footer' => '#fffff9',
        'footertext' => '#a8a8a8',
        'text' => '#636466',
        'link' => '#377bb5',
        'button' => '#b92f9e',
        'buttonlink' => '#fffffc',
        'primaryhighlight' => '#5c2e91',
        'secondaryhighlight' => '#c6168d',
        'branchsubheaderbg' => '#5a2b8d',
        'branchbutton' => '#b92f9f',
        'branchbuttonlink' => '#fffff8',
        'campsubheaderbg' => '#f47520',
        'campmenubg' => '#383838',
        'campmenulink' => '#fffffd',
        'campmenuhighlight' => '#505050',
        'camplink' => '#019c85',
        'campbutton' => '#019c86',
        'campbuttonlink' => '#fffff7',
        'campprimaryhighlight' => '#019c87',
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
