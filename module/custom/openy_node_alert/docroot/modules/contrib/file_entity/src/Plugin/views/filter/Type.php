<?php

namespace Drupal\file_entity\Plugin\views\filter;

use Drupal\file_entity\Entity\FileType;
use Drupal\views\Plugin\views\filter\InOperator;

/**
 * Filter by type.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("file_entity_type")
 */
class Type extends InOperator {

  /**
   * Gets the values of the options.
   *
   * @return array
   *   Returns options.
   */
  public function getValueOptions() {
    if (!isset($this->valueOptions)) {
      // Load entity File.
      $types = FileType::loadMultiple();

      // Creates associative array of candidates.
      $candidates = array();
      foreach ($types as $type) {
        $candidates[$type->id()] = $type->label();
      }

      // Returns candidates.
      $this->valueOptions = $candidates;
    }
  }

}
