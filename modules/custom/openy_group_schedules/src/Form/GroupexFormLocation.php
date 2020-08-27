<?php

namespace Drupal\openy_group_schedules\Form;

use Drupal\Core\Form\FormStateInterface;

/**
 * Implements GroupEx Pro form for location.
 */
class GroupexFormLocation extends GroupexFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'groupex_form_location';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    // Get current node.
    $node = \Drupal::routeMatch()->getParameter('node');

    $mapping_id = \Drupal::entityQuery('mapping')
      ->condition('type', 'location')
      ->condition('field_location_ref', $node->id())
      ->execute();
    $mapping_id = reset($mapping_id);
    $location_id = FALSE;
    if ($mapping = \Drupal::entityTypeManager()
    ->getStorage('mapping')->load($mapping_id)) {
      $field_groupex_id = $mapping->field_groupex_id->getValue();
      $location_id = isset($field_groupex_id[0]['value']) ? $field_groupex_id[0]['value'] : FALSE;
    }

    // Form should not be shown if there is no Location.
    if (!$location_id) {
      \Drupal::logger('openy_group_schedules')->error("Location ID could not be found.");
      return [
        '#markup' => $this->t('Sorry, the search is not supported.'),
      ];
    }

    $form['note'] = [
      '#markup' => '<p>' . $this->t('Search dates and times for drop-in classes (no registration required). Choose a specific category or time of day, or simply click through to view all.') . '</p>',
      '#weight' => -100,
    ];

    $form['location'] = [
      '#type' => 'hidden',
      '#value' => [$location_id],
    ];

    $form['nid'] = [
      '#type' => 'hidden',
      '#value' => $node->id(),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_state->setRedirect(
      'openy_group_schedules.schedules_search_results',
      ['node' => $form_state->getValue('nid')],
      ['query' => $this->getRedirectParams($form, $form_state)]
    );
  }

}
