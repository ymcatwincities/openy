
Slick Carousel
================================================================================

Slick is a powerful and performant slideshow/carousel solution leveraging Ken
Wheeler's Slick carousel.
See http://kenwheeler.github.io/slick

Powerful: Slick is one of the sliders [1], as of 9/15, the only one [2], which
supports nested sliders and a mix of lazy-loaded image/video with
image-to-iframe or multimedia lightbox switchers.
See below for the supported media.

Performant: Slick is stored as plain HTML the first time it is requested, and
then reused on subsequent requests. Carousels with cacheability and lazyload
are lighter and faster than those without.

Slick has gazillion options, please start with the very basic working
samples from slick_example [3] only if trouble to build slicks. Be sure to read
its README.txt. Spending 5 minutes or so will save you hours in building more
complex slideshows.

The module supports Slick 1.6 above.
Slick 2.x is just out 9/21/15, and hasn't been officially supported now, 9/27.

[1] https://groups.drupal.org/node/20384
[2] https://www.drupal.org/node/418616
[3] http://dgo.to/slick_extras


REQUIREMENTS
--------------------------------------------------------------------------------
- Slick library:
  o Download Slick archive >= 1.6 from https://github.com/kenwheeler/slick/
  o Extract it as is, rename "slick-master" to "slick", so the assets are at:

    /libraries/slick/slick/slick.css
    /libraries/slick/slick/slick-theme.css (optional if a skin chosen)
    /libraries/slick/slick/slick.min.js

- Download jqeasing from https://github.com/gdsmith/jquery.easing, so available
  at:
  /libraries/easing/jquery.easing.min.js
  This is CSS easing fallback for non-supporting browsers.

- Blazy.module, to reduce DRY stuffs, and as a bonus, advanced lazyloading
  such as delay lazyloading for below-fold sliders, iframe, (fullscreen) CSS
  background lazyloading, breakpoint dependent multi-serving images, lazyload
  ahead for smoother UX.

  Important! Be sure to enable Blazy first before updating Slick Alphas,
  otherwise a requirement error.

FEATURES
--------------------------------------------------------------------------------
o Fully responsive. Scales with its container.
o Uses CSS3 when available. Fully functional when not.
o Swipe enabled. Or disabled, if you prefer.
o Desktop mouse dragging.
o Fully accessible with arrow key navigation.
o Built-in lazyLoad, and multiple breakpoint options.
o Random, autoplay, pagers, arrows, dots/text/tabs/thumbnail pagers etc...
o Supports pure text, responsive image, iframe, video carousels with
  aspect ratio. No extra jQuery plugin FitVids is required. Just CSS.
o Works with Views, core and contrib fields: Image, Media Entity.
o Optional and modular skins, e.g.: Carousel, Classic, Fullscreen, Fullwidth,
  Split, Grid or a multi row carousel.
o Various slide layouts are built with pure CSS goodness.
o Nested sliders/overlays, or multiple slicks within a single Slick via Views.
o Some useful hooks and drupal_alters for advanced works.
o Modular integration with various contribs to build carousels with multimedia
  lightboxes or inline multimedia.
o Media switcher: Image linked to content, Image to iframe, Image to colorbox,
  Image to photobox.
o Cacheability + lazyload = light + fast.


INSTALLATION
--------------------------------------------------------------------------------
Install the module as usual, more info can be found on:
http://drupal.org/documentation/install/modules-themes/modules-7

The Slick module has several sub-modules:
- slick_ui, included, to manage optionsets, can be uninstalled at production.

- slick_media [1], to get richer contents using Media entity.

- slick_video [2], to get video carousels using Video Embed Field.

- slick_paragraphs [3], to get more complex slides at field level.

- slick_views [4], to get more complex slides.

- slick_devel, if you want to help testing and developing the Slick.
- slick_example, to get up and running quickly.
  Both are included in slick_extras [5].


[1] http://dgo.to/slick_media
[2] http://dgo.to/slick_video
[3] http://dgo.to/slick_paragraphs
[4] http://dgo.to/slick_views
[5] http://dgo.to/slick_extras



OPTIONAL INTEGRATION
--------------------------------------------------------------------------------
Slick supports enhancements and more complex layouts.

- Colorbox, to have grids/slides that open up image/video in overlay.
- Photobox, idem ditto.
- Responsive image, in core, to get truly responsive image.
- Media Entity, to have richer contents: image, video, or a mix of em.
  http://dgo.to/media_entity
- Video Embed Media, idem ditto.
  http://dgo.to/video_embed_field
- Paragraphs, to get more complex slides at field level.
  http://dgo.to/paragraphs
- Mousewheel, download from https://github.com/brandonaaron/jquery-mousewheel,
  so it is available at:
  /libraries/mousewheel/jquery.mousewheel.min.js



OPTIONSETS
--------------------------------------------------------------------------------
To create optionsets, go to:

  admin/config/media/slick

Be sure to enable Slick UI sub-module first, otherwise regular "Access denied".
They will be available at field formatter "Manage display", and Views UI.


VIEWS AND FIELDS
--------------------------------------------------------------------------------
Slick works with Views and as field display formatters.
Slick Views is available as a style plugin included at slick_views.module.
Slick field formatter included as a plugin which supports core: Image, Text.


PROGRAMATICALLY
--------------------------------------------------------------------------------
See slick.api.php for samples.


NESTED SLICKS
--------------------------------------------------------------------------------
Nested slick is a parent Slick containing slides which contain individual child
slick per slide. The child slicks are basically regular slide overlays like
a single video over the large background image, only with nested slicks it can
be many videos displayed as a slideshow as well.
Use Slick Paragraphs or Views to build one.
Supported multi-value fields for nested slicks: Image, Text, VEF, Media entity.


SKINS
--------------------------------------------------------------------------------
The main purpose of skins are to demonstrate that often some CSS lines are
enough to build fairly variant layouts. No JS needed. Unless, of course, for
more sophisticated slider like spiral 3D carousel which is beyond what CSS can
do. But more often CSS will do.

Skins allow swappable layouts like next/prev links, split image or caption, etc.
with just CSS. However a combination of skins and options may lead to
unpredictable layouts, get yourself dirty. Use the provided samples to see
the working skins.

Some default complex layout skins applied to desktop only, adjust for the mobile
accordingly. The provided skins are very basic to support the necessary layouts.
It is not the module job to match your awesome design requirements.

Optional skins:
--------------
- None
  It is all about DIY.
  Doesn't load any extra CSS other than the basic styles required by slick.
  Skins at the optionset are ignored, only useful to fetch description and
  your own custom work when not using the sub-modules, nor plugins.
  If using individual slide layout, do the layouts yourself.

- Classic
  Adds dark background color over white caption, only good for slider (single
  slide visible), not carousel (multiple slides visible), where small captions
  are placed over images, and animated based on their placement.

- Full screen
  Works best with 1 slidesToShow. Use z-index layering > 8 to position elements
  over the slides, and place it at large regions. Currently only works with
  Slick fields, use Views to make it a block. Use Slick Paragraphs to
  have more complex contents inside individual slide, and assign it to Slide
  caption fields.

- Full width
  Adds additional wrapper to wrap overlay video and captions properly.
  This is designated for large slider in the header or spanning width to window
  edges at least 1170px width for large monitor. To have a custom full width
  skin, simply prefix your skin with "full", e.g.: fullstage, fullwindow, etc.

- Split
  Caption and image/media are split half, and placed side by side. This requires
  any layout containing "split", otherwise useless.

- Grid
  Only reasonable if you have considerable amount of slides.
  Uses the Foundation 5.5 block-grid, and disabled if you choose your own skin
  not named Grid. Otherwise overrides skin Grid accordingly.

  Requires:
  Visible slides, Skin Grid for starter, A reasonable amount of slides,
  Optionset with Rows and slidesPerRow = 1.
  Avoid variableWidth and adaptiveHeight. Use consistent dimensions.
  This is module feature, older than core Rows, and offers more flexibility.
  Available at slick_views, and configurable via Views UI.

If you want to attach extra 3rd libraries, e.g.: image reflection, image zoomer,
more advanced 3d carousels, etc, simply put them into js array of the target
skin. Be sure to add proper weight, if you are acting on existing slick events,
normally < 0 (slick.load.min.js) is the one.

Use hook_slick_skins_info() and implement \Drupal\slick\SlickSkinInterface
to register ones. Clear the cache once.

See slick.api.php for more info on skins.
See \Drupal\slick\SlickSkinInterface.

Other skins are available at http://dgo.to/slick_extras
Some extra skins are WIP which may not work as expected.


GRID
--------------------------------------------------------------------------------
To create Slick grid or multiple rows carousel, there are 3 options:

1. One row grid managed by library:
   Visit admin/config/media/slick,
   Edit current optionset, and set
   slidesToShow > 1, and Rows and slidesperRow = 1

2. Multiple rows grid managed by library:
   Visit admin/config/media/slick,
   Edit current optionset, and set
   slidesToShow = 1, Rows > 1 and slidesPerRow > 1

3. Multiple rows grid managed by Module:
   Visit admin/structure/views/view/slick_x/edit/block_grid from slick_example,
   Be sure to install the Slick example sub-module first.
   Requires skin "Grid", and slidesToShow, Rows and slidesPerRow = 1.

The first 2 are supported by core library using pure JS approach.
The last is the Module feature using pure CSS Foundation block-grid.

The key is:
The total amount of Views results must be bigger than Visible slides, otherwise
broken Grid, see skin Grid above for more details.


HTML STRUCTURE
--------------------------------------------------------------------------------
Note, non-BEM classes are added by JS.

<div class="slick">
  <div class="slick__slider slick-initialized slick-slider">
    <div class="slick__slide"></div>
  </div>
  <nav class="slick__arrow"></nav>
</div>

asNavFor should target slick-initialized class/ID attributes.


BUG REPORTS OR SUPPORT REQUESTS
--------------------------------------------------------------------------------
A basic knowledge of Drupal site building is required. If you get stuck:

  o consult the provided READMEs,
  o descriptions on each form item,
  o the relevant guidelines from the supported modules,
  o consider the project issue queues, your problem may be already addressed,
  o install slick_example.

If you do have bug reports, we love bugs, please:
  o provide steps to reproduce it,
  o provide detailed info, a screenshot of the output and Slick form, or words
    to identify it any better,
  o make sure that the bug is caused by the module.

For the Slick library bug, please report it to the actual library:
  https://github.com/kenwheeler/slick

You can create a fiddle to isolate the bug if reproduceable outside the module:
  http://jsfiddle.net/

For the support requests, a screenshot of the output and Slick form are helpful.
Shortly, you should kindly help the maintainers with detailed info to help you.
Thanks.


TROUBLESHOOTING
--------------------------------------------------------------------------------
- When upgrading from Slick v1.3.6 to later version, try to resave options at:
  o admin/config/media/slick
  o admin/structure/types/manage/CONTENT_TYPE/display
  o admin/structure/views/view/VIEW_NAME
  only if trouble to see the new options, or when options don't apply properly.
  Most likely true when the library adds/changes options, or the module
  does something new. This is normal for any library even commercial ones, so
  bear with it.

- Always clear the cache, and re-generate JS (if aggregation is on) when
  updating the module to ensure things are picked up:
  o admin/config/development/performance

- If you are customizing template files, or theme functions, be sure to re-check
  against the latest.

- Be sure Slick release is similar, or later than Blazy.


KNOWN ISSUES
--------------------------------------------------------------------------------
- Slick admin CSS may not be compatible with private or contrib admin
  themes. Only if trouble with admin display, please disable it at:
  admin/config/media/blazy

- The Slick lazyLoad is not supported with Responsive image. Slick only
  facilitates Responsive image to get in. The image formatting is taken over by
  Responsive image.
  Some other options such as Aspect ratio is currently not supported either.

- Photobox is best for:
  - infinite true + slidesToShow 1
  - infinite false + slidesToShow N
  If "infinite true + slidesToShow > 1" is a must, but you don't want dup
  thumbnails, simply override the JS to disable 'thumbs' option.

- The following is not module related, but worth a note:
  o lazyLoad ondemand has issue with dummy image excessive height.
    Added fixes to suppress it via option Aspect ratio (fluid | enforced).
    Or use Blazy lazyload for more advanced options.
  o Aspect ratio is not compatible with Responsive image or multi-serving
    images.
    However if you can stick to one Aspect ratio, choose 'enforced' instead.
    Otherwise disable Aspect ratio for multi-serving images.
  o If the total < slidesToShow, Slick behaves. Previously added a workaround to
    fix this, but later dropped and handed over to the core instead.
    Brought back the temp fix for 1.6+ as per 10/18/16:
    See https://github.com/kenwheeler/slick/issues/262
  o Fade option with slideToShow > 1 will screw up.
  o variableWidth ignores slidesToShow.
  o Too much centerPadding at small device affects slidesToShow.
  o Infinite option will create duplicates or clone slides which look more
    obvious if slidesToShow > 1. Simply disable it if not desired.
  o If thumbnail display is Infinite, the main one must be infinite too, else
    incorrect syncing.
  o adaptiveHeight is no good for vertical.


CURRENT DEVELOPMENT STATUS
--------------------------------------------------------------------------------
A full release should be reasonable after proper feedbacks from the community,
some code cleanup, and optimization where needed. Patches are very much welcome.

Alpha and Beta releases are for developers only. Be aware of possible breakage.

However if it is broken, unless an update is explicitly required, clearing cache
should fix most issues during DEV phases. Prior to any update, always visit:
/admin/config/development/performance

And hit "Clear all caches" button once the new Slick is in place. Regenerate CSS
and JS as the latest fixes may contain changes to the assets.
Have the latest or similar release Blazy to avoid trouble in the first place.


ROADMAP
--------------------------------------------------------------------------------
- Bug fixes, code cleanup, optimization, and full release.


HOW CAN YOU HELP?
--------------------------------------------------------------------------------
Please consider helping in the issue queue, provide improvement, or helping with
documentation.

If you find this module helpful, please help back spread the love. Thanks.


AUTHOR/MAINTAINER/CREDITS
--------------------------------------------------------------------------------
Slick 8.x-1.x by gausarts, and other authors below.
Slick 7.x-2.x by gausarts, inspired by Flexslider with CTools integration.
Slick 7.x-1.x by arshadcn, the original author.

- https://www.drupal.org/node/2232779/committers
- CHANGELOG.txt for helpful souls with their patches, suggestions and reports.


READ MORE
--------------------------------------------------------------------------------
See the project page on drupal.org: http://drupal.org/project/slick.

More info relevant to each option is available at their form display by hovering
over them, and click a dark question mark.

See the Slick docs at:
- http://kenwheeler.github.io/slick/
- https://github.com/kenwheeler/slick/
