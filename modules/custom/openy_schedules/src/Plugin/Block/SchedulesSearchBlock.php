<?php

namespace Drupal\openy_schedules\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'Form' block.
 *
 * @Block(
 *   id = "schedules_search_block",
 *   admin_label = @Translation("Schedules Search Block"),
 *   category = @Translation("Paragraph Blocks")
 * )
 */
class SchedulesSearchBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $query = \Drupal::request()->query->all();
    $form = \Drupal::formBuilder()->getForm('\Drupal\openy_schedules\Form\SchedulesSearchForm', $query);
    $render = \Drupal::service('renderer')->render($form, FALSE);
    return [
      '#markup' => $render,
    ];
  }

}
