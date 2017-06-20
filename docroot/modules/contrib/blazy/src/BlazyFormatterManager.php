<?php

namespace Drupal\blazy;

/**
 * Provides common field formatter-related methods: Blazy, Slick.
 */
class BlazyFormatterManager extends BlazyManager {

  /**
   * Returns the field formatter settings inherited by child elements.
   *
   * @param array $build
   *   The array containing: settings, or potential optionset for extensions.
   * @param object $items
   *   The items to prepare settings for.
   */
  public function buildSettings(array &$build, $items) {
    $settings = &$build['settings'];

    // Sniffs for Views to allow block__no_wrapper, views_no_wrapper, etc.
    if (function_exists('views_get_current_view') && $view = views_get_current_view()) {
      $settings['view_name'] = $view->storage->id();
      $settings['current_view_mode'] = $view->current_display;
    }

    $count          = $items->count();
    $field          = $items->getFieldDefinition();
    $entity         = $items->getEntity();
    $entity_type_id = $entity->getEntityTypeId();
    $entity_id      = $entity->id();
    $bundle         = $entity->bundle();
    $field_name     = $field->getName();
    $field_type     = $field->getType();
    $field_clean    = str_replace("field_", '', $field_name);
    $target_type    = $field->getFieldStorageDefinition()->getSetting('target_type');
    $view_mode      = empty($settings['current_view_mode']) ? '_custom' : $settings['current_view_mode'];
    $namespace      = $settings['namespace'] = empty($settings['namespace']) ? 'blazy' : $settings['namespace'];
    $id             = isset($settings['id']) ? $settings['id'] : '';
    $id             = Blazy::getHtmlId("{$namespace}-{$entity_type_id}-{$entity_id}-{$field_clean}-{$view_mode}", $id);
    $switch         = empty($settings['media_switch']) ? '' : $settings['media_switch'];
    $internal_path  = $absolute_path = NULL;

    // Deals with UndefinedLinkTemplateException such as paragraphs type.
    // @see #2596385, or fetch the host entity.
    if (!$entity->isNew() && method_exists($entity, 'hasLinkTemplate')) {
      if ($entity->hasLinkTemplate('canonical')) {
        $url = $entity->toUrl();
        $internal_path = $url->getInternalPath();
        $absolute_path = $url->setAbsolute()->toString();
      }
    }

    $settings += [
      'absolute_path'  => $absolute_path,
      'bundle'         => $bundle,
      'content_url'    => $absolute_path,
      'count'          => $count,
      'entity_id'      => $entity_id,
      'entity_type_id' => $entity_type_id,
      'field_type'     => $field_type,
      'field_name'     => $field_name,
      'internal_path'  => $internal_path,
      'target_type'    => $target_type,
      'cache_metadata' => ['keys' => [$id, $count]],
    ];

    unset($entity, $field);

    $settings['id']          = $id;
    $settings['lightbox']    = ($switch && in_array($switch, $this->getLightboxes())) ? $switch : FALSE;
    $settings['breakpoints'] = isset($settings['breakpoints']) && empty($settings['responsive_image_style']) ? $settings['breakpoints'] : [];

    // @todo: Enable after proper checks.
    // $settings = array_filter($settings);
    if (!empty($settings['vanilla'])) {
      $settings = array_filter($settings);
      return;
    }

    if (!empty($settings['breakpoints'])) {
      $this->cleanUpBreakpoints($settings);
    }

    $settings['caption']    = empty($settings['caption']) ? [] : array_filter($settings['caption']);
    $settings['resimage']   = function_exists('responsive_image_get_image_dimensions');
    $settings['background'] = empty($settings['responsive_image_style']) && !empty($settings['background']);
    $resimage_lazy          = $this->configLoad('responsive_image') && !empty($settings['responsive_image_style']);
    $settings['blazy']      = $resimage_lazy || !empty($settings['blazy']);

    if (!empty($settings['blazy'])) {
      $settings['lazy'] = 'blazy';
    }

    // Aspect ratio isn't working with Responsive image, yet.
    // However allows custom work to get going with an enforced.
    $ratio = FALSE;
    if (!empty($settings['ratio'])) {
      $ratio = empty($settings['responsive_image_style']);
      if ($settings['ratio'] == 'enforced' || $settings['background']) {
        $ratio = TRUE;
      }
    }

    $settings['ratio'] = $ratio ? $settings['ratio'] : FALSE;

    // Sets dimensions once, if cropped, to reduce costs with ton of images.
    // This is less expensive than re-defining dimensions per image.
    if (!empty($settings['image_style']) && !$resimage_lazy) {
      if ($field_type == 'image' && $items[0]) {
        $settings['item'] = $items[0];
        $settings['uri']  = $items[0]->entity->getFileUri();
      }

      if (!empty($settings['uri'])) {
        $this->setDimensionsOnce($settings);
      }
    }

    $this->getModuleHandler()->alter($namespace . '_settings', $build, $items);
  }

}
