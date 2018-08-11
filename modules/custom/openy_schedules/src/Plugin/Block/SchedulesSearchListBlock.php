<?php

namespace Drupal\openy_schedules\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'Form' block, listing only.
 *
 * @Block(
 *   id = "schedules_search_list_block",
 *   admin_label = @Translation("Schedules Search List Block"),
 *   category = @Translation("Paragraph Blocks")
 * )
 */
class SchedulesSearchListBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $query = \Drupal::request()->query->all();
    $form = \Drupal::formBuilder()->getForm('\Drupal\openy_schedules\Form\SchedulesSearchForm', $query, 'list');
    $render = \Drupal::service('renderer')->render($form, FALSE);
    return [
      '#markup' => $render,
    ];
  }

}
