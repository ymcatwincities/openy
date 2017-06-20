<?php

namespace Drupal\blazy\Dejavu;

use Drupal\Core\Url;
use Drupal\file\Entity\File;
use Drupal\blazy\BlazyMedia;

/**
 * A Trait common for optional Media Entity and Video Embed Media integration.
 *
 * The basic idea is to display videos along with images even within core Image,
 * and to re-associate VEF/ME video thumbnails beyond their own entity display.
 * For editors, use Slick Browser, or Blazy Views field.
 * For client-side, Blazy Views field, BlazyFileFormatter, SlickFileFormatter.
 * Why bother? This addresses a mix of images/videos beyond field formatters
 * or when ME and VEM integration is optional.
 *
 * For more robust VEM/ME integration, use Slick Media instead.
 *
 * @see Drupal\blazy\Plugin\views\field\BlazyViewsFieldPluginBase
 * @see Drupal\slick_browser\SlickBrowser::widgetEntityBrowserFileFormAlter()
 * @see Drupal\slick_browser\Plugin\EntityBrowser\FieldWidgetDisplay\...
 */
trait BlazyVideoTrait {

  /**
   * Returns the image factory.
   */
  public function imageFactory() {
    return \Drupal::service('image.factory');
  }

  /**
   * Returns the optional VEF service to avoid dependency for optional plugins.
   */
  public static function videoEmbedMediaManager() {
    if (function_exists('video_embed_field_theme')) {
      return \Drupal::service('video_embed_field.provider_manager');
    }
    return FALSE;
  }

  /**
   * Builds relevant video embed field settings based on the given media url.
   *
   * @param array $settings
   *   An array of settings to be passed into theme_blazy().
   * @param string $external_url
   *   A video URL.
   */
  public function buildVideo(array &$settings = [], $external_url = '') {
    /** @var \Drupal\video_embed_field\ProviderManagerInterface $video */
    if (!($video = self::videoEmbedMediaManager())) {
      return;
    }

    if (!($provider = $video->loadProviderFromInput($external_url))) {
      return;
    }

    // Ensures thumbnail is available.
    $provider->downloadThumbnail();

    // @todo extract URL from the SRC of final rendered TWIG instead.
    $render    = $provider->renderEmbedCode(640, 360, '0');
    $old_url   = isset($render['#attributes']) && isset($render['#attributes']['src']) ? $render['#attributes']['src'] : '';
    $embed_url = isset($render['#url']) ? $render['#url'] : $old_url;
    $query     = isset($render['#query']) ? $render['#query'] : [];

    // Prevents complication with multiple videos by now.
    unset($query['autoplay'], $query['auto_play']);

    $settings['video_id']  = $provider::getIdFromInput($external_url);
    $settings['embed_url'] = Url::fromUri($embed_url, ['query' => $query])->toString();
    $settings['scheme']    = $video->loadDefinitionFromInput($external_url)['id'];
    $settings['uri']       = $provider->getLocalThumbnailUri();
    $settings['type']      = 'video';

    // Adds autoplay for media URL on lightboxes, saving another click.
    $url = $settings['embed_url'];
    if (strpos($url, 'autoplay') === FALSE || strpos($url, 'autoplay=0') !== FALSE) {
      $settings['autoplay_url'] = strpos($url, '?') === FALSE ? $url . '?autoplay=1' : $url . '&autoplay=1';
    }
    if ($settings['scheme'] == 'soundcloud') {
      $settings['autoplay_url'] = strpos($url, '?') === FALSE ? $url . '?auto_play=true' : $url . '&auto_play=true';
    }

    // Only applies when Image style is empty, no file API, no $item,
    // with unmanaged VEF image without image_style.
    // Prevents 404 warning when video thumbnail missing for a reason.
    if (empty($settings['image_style'])) {
      if ($data = @getimagesize($settings['uri'])) {
        list($settings['width'], $settings['height']) = $data;
      }
    }
  }

  /**
   * Gets the faked image item out of file entity, or ER, if applicable.
   *
   * @param object $file
   *   The expected file entity, or ER, to get image item from.
   *
   * @return array
   *   The array of image item and settings if a file image, else empty.
   */
  public function getImageItem($file) {
    $data = [];
    $entity = $file;

    /** @var Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem $file */
    if (isset($file->entity) && !isset($file->alt)) {
      $entity = $file->entity;
    }

    if (!$entity instanceof File) {
      return $data;
    }

    /** @var \Drupal\file\Entity\File $entity */
    list($type,) = explode('/', $entity->getMimeType(), 2);
    $uri = $entity->getFileUri();

    if ($type == 'image' && ($image = $this->imageFactory()->get($uri)) && $image->isValid()) {
      $item            = new \stdClass();
      $item->target_id = $entity->id();
      $item->width     = $image->getWidth();
      $item->height    = $image->getHeight();
      $item->alt       = $entity->getFilename();
      $item->title     = $entity->getFilename();
      $item->uri       = $uri;
      $settings        = (array) $item;
      $item->entity    = $entity;

      $settings['type'] = 'image';

      // Build item and settings.
      $data['item']     = $item;
      $data['settings'] = $settings;
      unset($item);
    }

    return $data;
  }

  /**
   * Gets the Media item thumbnail, or re-associate the file entity to ME.
   *
   * @param array $data
   *   An array of data containing settings, and potential video thumbnail item.
   * @param object $entity
   *   The media entity, else file entity to be associated to media, if any.
   */
  public function getMediaItem(array &$data = [], $entity = NULL) {
    $settings = $data['settings'];

    $media = $entity;
    // Core File stores Media thumbnails, re-associate it to Media entity.
    // @todo: If any proper method to get video URL from image URI, or FID.
    if ($entity->getEntityTypeId() == 'file' && !empty($settings['uri']) && strpos($settings['uri'], 'video_thumbnails') !== FALSE) {
      if ($media_id = \Drupal::entityQuery('media')->condition('thumbnail.target_id', $entity->id())->execute()) {
        $media_id = reset($media_id);

        /** @var \Drupal\media_entity\Entity\Media $entity */
        $media = $this->blazyManager()->getEntityTypeManager()->getStorage('media')->load($media_id);
      }
    }

    // Only proceed if we do have ME.
    if ($media->getEntityTypeId() != 'media') {
      return;
    }

    $bundle = $media->bundle();
    $fields = $media->getFields();

    $source_field[$bundle]    = $media->getType()->getConfiguration()['source_field'];
    $settings['bundle']       = $bundle;
    $settings['source_field'] = $source_field[$bundle];
    $settings['media_url']    = $media->url();
    $settings['media_id']     = $media->id();
    $settings['view_mode']    = empty($settings['view_mode']) ? 'default' : $settings['view_mode'];

    // If Media entity has a defined thumbnail, add it to data item.
    if (isset($fields['thumbnail'])) {
      $data['item'] = $fields['thumbnail']->get(0);
      $settings['file_tags'] = ['file:' . $data['item']->target_id];

      // Provides thumbnail URI for EB selection with various Media entities.
      if (empty($settings['uri'])) {
        $settings['uri'] = File::load($data['item']->target_id)->getFileUri();
      }
    }

    $source = empty($settings['source_field']) ? '' : $settings['source_field'];
    if ($source && isset($media->{$source})) {
      $value     = $media->{$source}->getValue();
      $input_url = isset($value[0]['uri']) ? $value[0]['uri'] : (isset($value[0]['value']) ? $value[0]['value'] : '');

      if ($input_url) {
        $settings['input_url'] = $input_url;
        $this->buildVideo($settings, $input_url);
      }
      elseif (isset($value[0]['alt'])) {
        $settings['type'] = 'image';
      }

      // Do not proceed if it has type, already managed by theme_blazy().
      // Supports other Media entities: Facebook, Instagram, Twitter, etc.
      if (empty($settings['type'])) {
        if ($build = BlazyMedia::build($media, $settings)) {
          $data['content'][] = $build;
        }
      }
    }

    $data['settings'] = $settings;
  }

}
