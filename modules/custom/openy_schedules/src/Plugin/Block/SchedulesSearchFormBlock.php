<?php

namespace Drupal\openy_schedules\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'Form' block, form only.
 *
 * @Block(
 *   id = "schedules_search_form_block",
 *   admin_label = @Translation("Schedules Search Block"),
 *   category = @Translation("Paragraph Blocks")
 * )
 */
class SchedulesSearchFormBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $query = \Drupal::request()->query->all();
    $form = \Drupal::formBuilder()->getForm('\Drupal\openy_schedules\Form\SchedulesSearchForm', $query, 'form');
    $render = \Drupal::service('renderer')->render($form, FALSE);
    return [
      '#markup' => $render,
    ];
  }

}
