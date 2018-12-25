<?php

namespace Drupal\fontyourface\Entity;

use Drupal\views\EntityViewsData;
use Drupal\views\EntityViewsDataInterface;

/**
 * Provides Views data for Font entities.
 */
class FontViewsData extends EntityViewsData implements EntityViewsDataInterface {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    $data['fontyourface_font']['pid']['filter'] = [
      'id' => 'fontyourface_font_pid',
    ];

    $data['fontyourface_font']['css_style']['filter'] = [
      'id' => 'fontyourface_font_style',
    ];

    $data['fontyourface_font']['css_weight']['filter'] = [
      'id' => 'fontyourface_font_weight',
    ];

    return $data;
  }

}
