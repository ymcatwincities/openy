<?php

namespace Drupal\blazy\Dejavu;

use Drupal\Core\Entity\EntityInterface;

/**
 * A Trait common for supported entities.
 *
 * This file can be imported along with Drupal\blazy\Dejavu\BlazyVideoTrait
 * to optionally support File/Media Entity where available.
 */
trait BlazyEntityTrait {

  /**
   * Returns the string value of the fields: link, or text.
   */
  public function getFieldString($entity, $field_name = '', $langcode = NULL) {
    $value = '';
    if (empty($field_name)) {
      return $value;
    }

    if ($entity->hasTranslation($langcode)) {
      // If the entity has translation, fetch the translated value.
      $values = $entity->getTranslation($langcode)->get($field_name)->getValue();
    }
    else {
      // Entity doesn't have translation, fetch original value.
      $values = $entity->get($field_name)->getValue();
    }

    $value = isset($values[0]['uri']) ? $values[0]['uri'] : (isset($values[0]['value']) ? $values[0]['value'] : '');
    $value = strip_tags($value);

    return trim($value);
  }

  /**
   * Returns the formatted renderable array of the field.
   */
  public function getFieldRenderable($entity, $field_name = '', $view_mode = 'full') {
    $view = [];
    $has_field = !empty($field_name) && isset($entity->{$field_name});
    if ($has_field && !empty($entity->{$field_name}->view($view_mode)[0])) {
      $view = $entity->get($field_name)->view($view_mode);

      // Prevents quickedit to operate here as otherwise JS error.
      // @see 2314185, 2284917, 2160321.
      // @see quickedit_preprocess_field().
      // @todo: Remove when it respects plugin annotation.
      $view['#view_mode'] = '_custom';
    }
    return $view;
  }

  /**
   * Build image/video preview either using theme_blazy(), or view builder.
   *
   * This is alternative to Drupal\blazy\BlazyFormatterManager used outside
   * field formatters, such as Views field, or Entity Browser displays, etc.
   *
   * @param array $data
   *   An array of data containing settings, and image item.
   * @param object $entity
   *   The media entity, else file entity to be associated to media if any.
   * @param string $fallback
   *   The fallback string to display such as file name or entity label.
   *
   * @return array
   *   The renderable array of theme_blazy(), or view builder, else empty.
   */
  public function buildPreview(array $data, $entity, $fallback = '') {
    $build = [];

    if (!$entity instanceof EntityInterface) {
      return [];
    }

    // Supports VEM/ME if Drupal\blazy\Dejavu\BlazyVideoTrait is imported.
    if (method_exists($this, 'getMediaItem')) {
      $this->getMediaItem($data, $entity);
    }

    $settings = &$data['settings'];
    if (!empty($data['item'])) {
      if (!empty($settings['media_switch'])) {
        $is_lightbox = $this->blazyManager()->getLightboxes() && in_array($settings['media_switch'], $this->blazyManager()->getLightboxes());
        $settings['lightbox'] = $is_lightbox ? $settings['media_switch'] : FALSE;
      }
      if (empty($settings['uri'])) {
        $settings['uri'] = ($file = $data['item']->entity) && empty($data['item']->uri) ? $file->getFileUri() : $data['item']->uri;
      }

      // Provide simple Blazy, if required.
      if (empty($settings['_basic'])) {
        $build = $this->blazyManager()->getImage($data);
      }
      else {
        $build = [
          '#theme'    => 'blazy',
          '#item'     => $data['item'],
          '#settings' => $settings,
        ];
      }

      // Provides a shortcut to get URI.
      $build['#uri'] = empty($settings['uri']) ? '' : $settings['uri'];

      // Allows top level elements to load Blazy once rather than per field.
      // This is still here for non-supported Views style plugins, etc.
      if (empty($settings['_detached'])) {
        $load = $this->blazyManager()->attach($settings);

        // Enforces loading elements hidden by EB "Show selected" button.
        $load['drupalSettings']['blazy']['loadInvisible'] = TRUE;
        $build['#attached'] = $load;
      }
    }
    else {
      $build = $this->blazyManager()->getEntityView($entity, $settings, $fallback);
    }

    return $build;
  }

}
