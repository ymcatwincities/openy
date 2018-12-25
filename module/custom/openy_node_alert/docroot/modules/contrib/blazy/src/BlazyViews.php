<?php

namespace Drupal\blazy;

use Drupal\Component\Utility\NestedArray;

/**
 * Provides optional Views integration.
 */
class BlazyViews {

  /**
   * Implements hook_views_pre_render().
   */
  public static function viewsPreRender($view) {
    // Load Blazy library once, not per field, if any Blazy Views field found.
    if ($blazy = self::viewsField($view)) {
      $plugin_id = $view->getStyle()->getPluginId();
      $settings = $blazy->mergedViewsSettings();
      $load = $blazy->blazyManager()->attach($settings);

      // Enforce Blazy to work with hidden element such as with EB selection.
      $load['drupalSettings']['blazy']['loadInvisible'] = TRUE;
      $view->element['#attached'] = isset($view->element['#attached']) ? NestedArray::mergeDeep($view->element['#attached'], $load) : $load;

      $grid = $plugin_id == 'blazy';
      if ($options = $view->getStyle()->options) {
        $grid = empty($options['grid']) ? $grid : TRUE;
      }

      // Prevents dup [data-LIGHTBOX-gallery] if the Views style supports Grid.
      if (!$grid) {
        $view->element['#attributes']['class'][] = 'blazy';
        $view->element['#attributes']['data-blazy'] = TRUE;
        if (!empty($settings['media_switch'])) {
          $switch = str_replace('_', '-', $settings['media_switch']);
          $view->element['#attributes']['data-' . $switch . '-gallery'] = TRUE;
        }
      }
    }
  }

  /**
   * Returns one of the Blazy Views fields, if available.
   */
  public static function viewsField($view) {
    foreach (['file', 'media'] as $entity) {
      if (isset($view->field['blazy_' . $entity])) {
        return $view->field['blazy_' . $entity];
      }
    }
    return FALSE;
  }

}
