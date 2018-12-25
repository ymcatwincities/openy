#Fonts

The majority of the fonts used by the Open Y distribution are freely available, except for Cachet. 

Helvetica is used for paragraph text, with Verdana as a fallback. Page titles, headers, highlighted text, etc, use Cachet, with Ubuntu Condensed as a fallback.

The font definitions in the distribution's main theme, Open Y Rose, are set to use Cachet, but fallback to Ubuntu Condensed if the font is not present.

    font-family: "Cachet W01 Bold", 'Ubuntu Condensed', sans-serif;

## Ubuntu Condensed
Ubuntu Condensed is a free font available from Google Fonts, https://fonts.google.com/specimen/Ubuntu+Condensed .

If you look at the openy_rose.libraries.yml file you can see Ubunutu Condensed is already linked as part of the theme.

    global-styling:
      css:
        base:
          //fonts.googleapis.com/css?family=Ubuntu+Condensed:400,700: {}

## Cachet
Cachet, specifically "Cachet W01 Medium", is part of the YMCA styleguide. It is a commercial font, and must be purchased for individual sites. It cannot be distributed as part of the Open Y distribution or hosted on Drupal.org.

The font can be purchased from fonts.com, https://www.fonts.com/font/monotype/cachet/book .

## Installing Cachet
There are a number of ways to get Cachet setup on your site. As long as the font gets linked in the page it should get used, since it is already part of the font declarations.

If you purchase Cachet from fonts.com, add the URL you are provided to the global-styling library in openy_rose.libraries.yml, right where Ubuntu Condensed is linked.


      css:
        base:
          //fast.fonts.net/cssapi/abcdef-12345-tuvwxyx-67890.css: {}
          //fonts.googleapis.com/css?family=Ubuntu+Condensed:400,700: {}

You may also need to add a JavaScript file provided by fonts.com. You can add that in the same global-styling library.

    js:
        //fast.fonts.net/jsapi/12345-abcdef-67890.js: {}

Another option is to use the @font-your-face Drupal module, https://www.drupal.org/project/fontyourface .

Once installed you can connect your Drupal site to your font.com account, or one of the other font providers, and have the font automatically retrieved and added to your site. Follow the directions provided by the module to connect to your font provider account.
