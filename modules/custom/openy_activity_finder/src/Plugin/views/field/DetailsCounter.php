<?php

namespace Drupal\openy_activity_finder\Plugin\views\field;

use Drupal\openy_activity_finder\Entity\ProgramSearchCheckLog;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;

/**
 * Display counter of Details clicks.
 *
 * @ViewsField("program_search_log_details_counter")
 */
class DetailsCounter extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    /** @var \Drupal\openy_repeat\Entity\ProgramSearchLog $revision */
    $log = $values->_entity;

    $query = \Drupal::entityTypeManager()->getStorage('program_search_log_check')->getQuery();

    $details_ids = $query
      ->condition('log_id', $log->id())
      ->condition('type', ProgramSearchCheckLog::TYPE_DETAILS)
      ->execute();

    return ['counter' => [
      '#markup' => count($details_ids),
    ]];
  }

  /**
   * {@inheritdoc}
   */
  public function query() {}

}
