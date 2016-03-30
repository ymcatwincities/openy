<?php

namespace Drupal\ymca_groupex\Form;

use Drupal\Core\Form\FormStateInterface;

/**
 * Implements Groupex form for location.
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
    if (\Drupal::routeMatch()->getRouteName() == 'entity.node.canonical') {
      $node = \Drupal::routeMatch()->getParameter('node');
    }
    if (\Drupal::routeMatch()->getRouteName() == 'entity.node.preview') {
      $node = \Drupal::routeMatch()->getParameter('node_preview');
    }

    $mappings = \Drupal::config('ymca_groupex.mapping')->get('locations');
    $location_id = FALSE;
    foreach ($mappings as $item) {
      if ($item['entity_id'] == $node->id()) {
        $location_id = $item['geid'];
      }
    }

    // Form should not be shown if there is no Location.
    if (!$location_id) {
      \Drupal::logger('ymca_groupex')->error("Location ID could not be found.");
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
      'ymca_groupex.schedules_search_results',
      ['node' => $form_state->getValue('nid')],
      ['query' => $this->getRedirectParams($form, $form_state)]
    );
  }

}
