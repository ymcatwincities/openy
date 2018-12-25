ImageWidgetCrop module
======================

[![Build Status](https://travis-ci.org/woprrr/image_widget_crop.svg?branch=8.x-1.x)](https://travis-ci.org/woprrr/image_widget_crop)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/woprrr/image_widget_crop/badges/quality-score.png?b=8.x-1.x)](https://scrutinizer-ci.com/g/woprrr/image_widget_crop/?branch=8.x-1.x)
[![SensioLabsInsight](https://insight.sensiolabs.com/projects/0e2f44af-6837-4772-b3e0-c373faa95ae6/big.png)](https://insight.sensiolabs.com/projects/0e2f44af-6837-4772-b3e0-c373faa95ae6)

Provides an interface for using the features of the [Crop API]. Module is still 
under heavy development.

[Crop API]: https://github.com/drupal-media/crop

Try me
------
You can Test ImageWidgetCrop in action directly with the sub-module,
"ImageWidgetCrop example" to test differents usecase of this module.

Configuration
-------------
@see Drupal Média D8 Guide [ImageWidgetCrop Guide].
[ImageWidgetCrop Guide]: https://drupal-media.gitbooks.io/drupal8-guide/content/modules/image_widget_crop/index.html
* Create a Crop Type (`admin/structure/crop`)
* Create ImageStyles 
    * add Manual crop effect, using your Crop Type,
      (to apply your crop selection).
* Create an Image field.
* In its form display, at `admin/structure/types/manage/page/form-display`:
    * set the widget for your field to ImageWidgetCrop 
    * at select your crop types in the Crop settings list. You can configure 
      the widget to create different crops on each crop types. For example, if 
      you have an editorial site, you need to display an image on different 
      places. With this option, you can set an optimal crop zone for each of the
      image styles applied to the image
* Set the display formatter Image and choose your image style,
  or responsive image styles.
* Go add an image with your widget and crop your picture,
  by crop types used for this image.
