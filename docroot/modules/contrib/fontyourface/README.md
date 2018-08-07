# @FONT-YOUR-FACE

[![Build Status](https://travis-ci.org/fontyourface/fontyourface.svg?branch=8.x-3.x)](https://travis-ci.org/fontyourface/fontyourface)

1. Installing @font-your-face:
==============================

- Place the extracted module in sites/all/modules/fontyourface
- Go to Administration > Modules and enable @font-your-face and one or more of the submodules.
- Go to Administration > Appearance > @font-your-face > settings and import the fonts.

2a. Use @font-your-face via the interface:
==========================================
- Go to Administration > Appearance > @font-your-face (admin/appearance/font)
  to enable some fonts.
- Click the 'enable font' for each font you want to use.
- You can add CSS selectors for each enabled font via  Administration > Appearance > @font-your-face > Font Display (admin/appearance/font/font_display)

Known issues:
=============
- Note that Internet Explorer has a limit of 32 CSS files, so using @font-your-face on CSS-heavy sites may require
  turning on CSS aggregation under Administer > Configuration > Development > Performance (admin/config/development/performance).
- Note that not all modules from Drupal 7 have been ported (font reference, fontyourface wysiwyg). Help is much appreciated.
- Fonts.com api has some quirks. You may have to use the fonts.com website for enabling all your fonts instead.
- See https://drupal.org/project/fontyourface#support for support options on any issues not mentioned here.
