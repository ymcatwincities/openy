
ABOUT
Provides integration with bLazy to lazy load and multi-serve images to save
bandwidth and server requests. The user will have faster load times and save
data usage if they don't browse the whole page.


FEATURES
o Supports core Image.
o Supports core Responsive image.
o Supports Colorbox/Photobox, also multimedia lightboxes.
o Supports Retina display.
o Multi-serving images for configurable XS, SM and MD breakpoints, almost
  similar to core Responsive image, only less complex.
o CSS background lazyloading, see Mason, GridStack, and Slick carousel.
o IFRAME urls via via custom coded, Blazy Image with Media entity via Video
  Embed Media, or see Slick Video, Slick Media.
o Delay loading for below-fold images until 100px (configurable) before they are
  visible at viewport.
o A simple effortless CSS loading indicator.
o It doesn't take over all images, so it can be enabled as needed via Blazy
  formatter, or its supporting modules.


OPTIONAL FEATURES
o Views fields for File ER and Media Entity integration.
o Views style plugin Blazy Grid.
o Field formatters: Blazy, and Blazy Image with Media integration.


REQUIREMENTS
- bLazy library:
  o Download bLazy from https://github.com/dinbror/blazy
  o Extract it as is, rename "blazy-master" to "blazy", so the assets are at:

    /libraries/blazy/blazy.min.js


INSTALLATION
Install the module as usual, more info can be found on:
http://drupal.org/documentation/install/modules-themes/modules-7


USAGES
Be sure to enable Blazy UI which can be uninstalled at production later.
o Go to Manage display page, e.g.:
  admin/structure/types/manage/page/display

o Find "Blazy" formatter under "Manage display".

o Go to "admin/config/media/blazy" to manage few global options, including
  enabling support for lazyloading core Responsive image.

For custom usages, add a class "b-lazy" along with a "data-src" attribute
referring to an expected image or iframe URL, or to any supported element:
IMG, IFRAME or DIV/BODY, etc.
Non-media element, DIV/BODY/etc., will have background image lazyloaded instead.

Wrap the parent container with [data-blazy attribute containing the expected
options to limit the scope, or for simple need without aspect ratio. Add extra
class .blazy to support aspect ratio with multi-serving images.
And load the blazy library accordingly.


MODULES THAT INTEGRATE WITH OR REQUIRE BLAZY
o Blazy PhotoSwipe
o GridStack
o Intense
o Mason
o Slick (D8 only by now)
o Slick Views (D8 only by now)
o Slick Media
o Slick Video
o Slick Browser

Most duplication efforts from the above modules will be merged into
\Drupal\blazy\Dejavu namespace.


SIMILAR MODULES
https://www.drupal.org/project/lazyload
https://www.drupal.org/project/lazyloader


TROUBLESHOOTING
Resizing is not supported. Just reload the page.

VIEWS INTEGRATION
Blazy provides two simple Views fields for File ER, and Media Entity.

When using Blazy formatter within Views, check "Use field template" under
  "Style settings", if trouble with Blazy Formatter as stand alone Views output.
  On the contrary, uncheck "Use field template", when Blazy formatter
  is embedded inside another module such as GridStack so to pass the renderable
  array accordingly.
  This is a Views common gotcha with field formatter, so be aware of it.
  This confusion should be solved later when Blazy formatter is aware of Views.

MIN-WIDTH
If the images appear to be shrinked within a floating container, add
  some expected width or min-width to the parent container via CSS accordingly.
  Non-floating image parent containers aren't affected.

MIN-HEIGHT
Add a min-height CSS to individual element to avoid layout reflow if not using
  Aspect ratio or when Aspect ratio is not supported such as with Responsive
  image. Otherwise some collapsed images containers will defeat the purpose of
  lazyloading. When using CSS background, the container may also be collapsed.
  Both layout reflow and lazyloading delay issues are actually taken care of
  if Aspect ratio is enabled in the first place.

Adjust, and override blazy CSS files accordingly.


ROADMAP/TODO
[x] Adds a basic configuration to load the library, probably an image formatter.
    2/24/2016
[x] Media entity image/video, and Video embed field lazyloading, if any.
    10/25/2016
    Added both simple Blazy Media formatter and Views field Media Entity.
o Makes a solid lazyloading solution for IMG, DIV, IFRAME tags.


CURRENT DEVELOPMENT STATUS
A full release should be reasonable after proper feedbacks from the community,
some code cleanup, and optimization where needed. Patches are very much welcome.

Alpha and Beta releases are for developers only. Be aware of possible breakage.


UPDATE SOP:
Visit any of the following URLs when updating Blazy, or its related modules.

1. /admin/config/development/performance
  Unless an update is required, clearing cache should fix most issues.

  o Hit "Clear all caches" button once the new Blazy in place.
  o Regenerate CSS and JS as the latest fixes may contain changes to the assets.

2. /admin/reports/status
  Check for any pending update, and run /update.php from the brower address bar.

3. If Twig templates are customized, compare against the latest.


PERFORMANCE TIPS:
o If breakpoints provided with tons of images, using image styles with ANY crop
  is recommended to avoid image dimension calculation with individual images.
  The image dimensions will be set once, and inherited by all images as long as
  they contain word crop. If using scaled image styles, regular calculation
  applies.


AUTHOR/MAINTAINER/CREDITS
gausarts

Contributors:
https://www.drupal.org/node/2663268/committers


READ MORE
See the project page on drupal.org: http://drupal.org/project/blazy.

See the bLazy docs at:
o https://github.com/dinbror/blazy
o http://dinbror.dk/blazy/
