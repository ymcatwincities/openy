<?php

namespace Drupal\paragraphs;

use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\Core\Entity\EntityViewBuilder;

/**
 * Render controller for paragraphs.
 */
class ParagraphViewBuilder extends EntityViewBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildMultiple(array $build_list) {
    $build_list = parent::buildMultiple($build_list);

    // Allow enabled behavior plugin to alter the rendering.
    foreach ($build_list as $key => $value) {
      $display = EntityViewDisplay::load('paragraph.' . $value['#paragraph']->bundle() . '.' . $value['#view_mode']) ?: EntityViewDisplay::load('paragraph.' . $value['#paragraph']->bundle() . '.default');
      $paragraph_type = $value['#paragraph']->getParagraphType();
      foreach ($paragraph_type->getEnabledBehaviorPlugins() as $plugin_id => $plugin_value) {
        $plugin_value->view($build_list[$key], $value['#paragraph'], $display, $value['#view_mode']);
      }
    }
    return $build_list;
  }

}
