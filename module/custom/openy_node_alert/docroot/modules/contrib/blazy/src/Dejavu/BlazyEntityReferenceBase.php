<?php

namespace Drupal\blazy\Dejavu;

use Drupal\Core\Form\FormStateInterface;

/**
 * Base class for entity reference formatters with field details.
 */
abstract class BlazyEntityReferenceBase extends BlazyEntityBase {

  use BlazyEntityTrait;

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return BlazyDefault::extendedSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function buildElement(array &$build, $entity, $langcode) {
    $settings = &$build['settings'];

    if (!empty($settings['vanilla'])) {
      return parent::buildElement($build, $entity, $langcode);
    }

    $delta     = $settings['delta'];
    $item_id   = $settings['item_id'];
    $view_mode = empty($settings['view_mode']) ? 'full' : $settings['view_mode'];
    $element   = ['settings' => $settings];

    // Built early before stage to allow custom highres video thumbnail later.
    // Implementor must import: Drupal\blazy\Dejavu\BlazyVideoTrait.
    $this->getMediaItem($element, $entity);

    // Build the main stage.
    $this->buildStage($element, $entity, $langcode);

    // If Image rendered is picked, render image as is.
    if (!empty($settings['image']) && (!empty($settings['media_switch']) && $settings['media_switch'] == 'rendered')) {
      $element['content'][] = $this->getFieldRenderable($entity, $settings['image'], $view_mode);
    }

    // Optional image with responsive image, lazyLoad, and lightbox supports.
    $element[$item_id] = empty($element['item']) ? [] : $this->formatter->getImage($element);

    // Captions if so configured.
    $this->getCaption($element, $entity, $langcode);

    // Layouts can be builtin, or field, if so configured.
    if (!empty($settings['layout'])) {
      $layout = $settings['layout'];
      if (strpos($layout, 'field_') !== FALSE) {
        $settings['layout'] = $this->getFieldString($entity, $layout, $langcode);
      }
      $element['settings']['layout'] = $settings['layout'];
    }

    // Classes, if so configured.
    if (!empty($settings['class'])) {
      $element['settings']['class'] = $this->getFieldString($entity, $settings['class'], $langcode);
    }

    // Build the main item.
    $build['items'][$delta] = $element;

    // Build the thumbnail item.
    if (!empty($settings['nav'])) {
      // Thumbnail usages: asNavFor pagers, dot, arrows, photobox thumbnails.
      $element[$item_id]  = empty($settings['thumbnail_style']) ? [] : $this->formatter->getThumbnail($element['settings']);
      $element['caption'] = empty($settings['thumbnail_caption']) ? [] : $this->getFieldRenderable($entity, $settings['thumbnail_caption'], $view_mode);

      $build['thumb']['items'][$delta] = $element;
    }
  }

  /**
   * Builds slide captions with possible multi-value fields.
   */
  public function getCaption(array &$element, $entity, $langcode) {
    $settings  = $element['settings'];
    $view_mode = $settings['view_mode'];

    // Title can be plain text, or link field.
    if (!empty($settings['title'])) {
      $field_title = $settings['title'];
      if (isset($entity->{$field_title})) {
        if ($entity->hasTranslation($langcode)) {
          // If the entity has translation, fetch the translated value.
          $title = $entity->getTranslation($langcode)->get($field_title)->getValue();
        }
        else {
          // Entity doesn't have translation, fetch original value.
          $title = $entity->get($field_title)->getValue();
        }
        if (!empty($title[0]['value']) && !isset($title[0]['uri'])) {
          // Prevents HTML-filter-enabled text from having bad markups (h2 > p),
          // except for a few reasonable tags acceptable within H2 tag.
          $element['caption']['title']['#markup'] = strip_tags($title[0]['value'], '<a><strong><em><span><small>');
        }
        elseif (isset($title[0]['uri']) && !empty($title[0]['title'])) {
          $element['caption']['title'] = $this->getFieldRenderable($entity, $field_title, $view_mode)[0];
        }
      }
    }

    // Other caption fields, if so configured.
    if (!empty($settings['caption'])) {
      $caption_items = [];
      foreach ($settings['caption'] as $i => $field_caption) {
        if (!isset($entity->{$field_caption})) {
          continue;
        }
        $caption_items[$i] = $this->getFieldRenderable($entity, $field_caption, $view_mode);
      }
      if ($caption_items) {
        $element['caption']['data'] = $caption_items;
      }
    }

    // Link, if so configured.
    if (!empty($settings['link'])) {
      $field_link = $settings['link'];
      if (isset($entity->{$field_link})) {
        $links = $this->getFieldRenderable($entity, $field_link, $view_mode);

        // Only simplify markups for known formatters registered by link.module.
        if ($links && isset($links['#formatter']) && in_array($links['#formatter'], ['link'])) {
          $links = [];
          foreach ($entity->{$field_link} as $i => $link) {
            $links[$i] = $link->view($view_mode);
          }
        }
        $element['caption']['link'] = $links;
      }
    }

    if (!empty($settings['overlay'])) {
      $element['caption']['overlay'] = $this->getOverlay($settings, $entity, $langcode);
    }
  }

  /**
   * Builds overlay placed within the caption.
   */
  public function getOverlay(array $settings, $entity, $langcode) {
    return $entity->get($settings['overlay'])->view($settings['view_mode']);
  }

  /**
   * Build the main background/stage, image or video.
   *
   * Main image can be separate image item from video thumbnail for highres.
   * Fallback to default thumbnail if any, which has no file API.
   */
  public function buildStage(array &$element, $entity, $langcode) {
    $settings = &$element['settings'];
    $stage    = empty($settings['source_field']) ? '' : $settings['source_field'];
    $stage    = empty($settings['image']) ? $stage : $settings['image'];

    // The actual video thumbnail has already been downloaded earlier.
    // This fetches the highres image if provided and available.
    // With a mix of image and video, image is not always there.
    if ($stage && isset($entity->{$stage})) {
      /** @var \Drupal\file\Plugin\Field\FieldType\FileFieldItemList $file */
      $file = $entity->get($stage);
      $value = $file->getValue();

      // Do not proceed if it is a Media entity video.
      if (isset($value[0]) && $value[0]) {
        // If image, even if multi-value, we can only have one stage per slide.
        if (isset($value[0]['target_id']) && !empty($value[0]['target_id'])) {
          if (method_exists($file, 'referencedEntities') && isset($file->referencedEntities()[0])) {
            /** @var \Drupal\image\Plugin\Field\FieldType\ImageItem $item */
            $element['item'] = $file->get(0);

            // Collects cache tags to be added for each item in the field.
            $settings['file_tags'] = $file->referencedEntities()[0]->getCacheTags();
            $settings['uri'] = $file->referencedEntities()[0]->getFileUri();
          }
        }
        // If a VEF with a text, or link field.
        elseif (isset($value[0]['value']) || isset($value[0]['uri'])) {
          $external_url = $this->getFieldString($entity, $stage, $langcode);

          if ($external_url) {
            $this->buildVideo($settings, $external_url);
            $element['item'] = $value;
          }
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element = parent::settingsForm($form, $form_state);

    if (isset($element['layout'])) {
      $layout_description = $element['layout']['#description'];
      $element['layout']['#description'] = $this->t('Create a dedicated List (text - max number 1) field related to the caption placement to have unique layout per slide with the following supported keys: top, right, bottom, left, center, center-top, etc. Be sure its formatter is Key.') . ' ' . $layout_description;
    }

    if (isset($element['media_switch'])) {
      $element['media_switch']['#options']['rendered'] = $this->t('Image rendered by its formatter');
      $element['media_switch']['#description'] .= ' ' . $this->t('Be sure the enabled fields here are not hidden/disabled at its view mode.');
    }

    if (isset($element['caption'])) {
      $element['caption']['#description'] = $this->t('Check fields to be treated as captions, even if not caption texts.');
    }

    if (isset($element['image']['#description'])) {
      $element['image']['#description'] .= ' ' . $this->t('For video, this allows separate highres image, be sure the same field used for Image to have a mix of videos and images. Leave empty to fallback to the video provider thumbnails. The formatter/renderer is managed by <strong>@namespace</strong> formatter. Meaning original formatter ignored. If you want original formatters, check <strong>Vanilla</strong> option. Alternatively choose <strong>Media switcher &gt; Image rendered </strong>, other image-related settings here will be ignored. <strong>Supported fields</strong>: Image, Video Embed Field.', ['@namespace' => $this->getPluginId()]);
    }

    if (isset($element['overlay']['#description'])) {
      $element['overlay']['#description'] .= ' ' . $this->t('The formatter/renderer is managed by the child formatter. <strong>Supported fields</strong>: Image, Video Embed Field, Media Entity.');
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function getScopedFormElements() {
    $admin       = $this->admin();
    $target_type = $this->getFieldSetting('target_type');
    $views_ui    = $this->getFieldSetting('handler') == 'default';
    $bundles     = $views_ui ? [] : $this->getFieldSetting('handler_settings')['target_bundles'];
    $strings     = ['text', 'string', 'list_string'];
    $strings     = $admin->getFieldOptions($bundles, $strings, $target_type);
    $texts       = ['text', 'text_long', 'string', 'string_long', 'link'];
    $texts       = $admin->getFieldOptions($bundles, $texts, $target_type);
    $links       = ['text', 'string', 'link'];

    return [
      'background'        => TRUE,
      'box_captions'      => TRUE,
      'breakpoints'       => BlazyDefault::getConstantBreakpoints(),
      'captions'          => $admin->getFieldOptions($bundles, [], $target_type),
      'classes'           => $strings,
      'fieldable_form'    => TRUE,
      'images'            => $admin->getFieldOptions($bundles, ['image'], $target_type),
      'image_style_form'  => TRUE,
      'layouts'           => $strings,
      'links'             => $admin->getFieldOptions($bundles, $links, $target_type),
      'media_switch_form' => TRUE,
      'multimedia'        => TRUE,
      'thumb_captions'    => $texts,
      'thumb_positions'   => TRUE,
      'nav'               => TRUE,
      'titles'            => $texts,
      'vanilla'           => TRUE,
    ] + parent::getScopedFormElements();
  }

}
