<?php

namespace Drupal\openy_style_guide\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\Core\Link;

/**
 * Provides route responses for the Open Y Style Guide module.
 */
class OpenyStyleGuideController extends ControllerBase {

  /**
   * Renders content.
   */
  public function content() {
    $query = \Drupal::service('entity.query')
      ->get('menu_link_content')
      ->condition('menu_name', 'style-guide');
    $entity_ids = $query->execute();

    /** @var \Drupal\Core\Entity\Sql\SqlContentEntityStorage $storageManager */
    $storageManager = \Drupal::entityTypeManager()->getStorage('menu_link_content');
    $activeTheme = \Drupal::service('theme.manager')->getActiveTheme();

    $items = [];
    foreach ($entity_ids as $id) {
      $entity = $storageManager->load($id);
      $value = $entity->get('link')->getValue();
      $items[] = [
        'title' => $entity->get('title')->value,
        'link' => Url::fromUri($value[0]['uri']),
      ];
    }
    if (empty($items)) {
      return [
        '#markup' => '<p>No Style Guide items found.</p>',
      ];
    }

    function compareByTitle($a, $b){
      return strcmp($a["title"], $b["title"]);
    }

    usort($items, 'compareByTitle');

    return [
      '#theme' => 'style_guide',
      '#items' => $items,
      '#active_theme' => $activeTheme->getName(),
    ];
  }
}
