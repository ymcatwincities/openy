<?php

/**
 * Contains \Drupal\entity_browser\Plugin\EntityBrowser\SelectionDisplay\View.
 */

namespace Drupal\entity_browser\Plugin\EntityBrowser\SelectionDisplay;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\entity_browser\SelectionDisplayBase;

/**
 * Displays current selection in a View.
 *
 * @EntityBrowserSelectionDisplay(
 *   id = "view",
 *   label = @Translation("View selection display"),
 *   description = @Translation("Displays current selection in a View.")
 * )
 */
class View extends SelectionDisplayBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return array(
      'view' => NULL,
      'view_display' => NULL,
    ) + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function getForm(array &$original_form, FormStateInterface $form_state) {
    $form = [];

    // TODO - do we need better error handling for view and view_display (in case
    // either of those is nonexistent or display not of correct type)?
    $storage = &$form_state->getStorage();
    if (empty($storage['selection_display_view']) || $form_state->isRebuilding()) {
      $storage['selection_display_view'] = $this->entityManager
        ->getStorage('view')
        ->load($this->configuration['view'])
        ->getExecutable();
    }

    // TODO - if there are entities that are selected multiple times this displays
    // them only once. Reason for that is how SQL and Views work and we probably
    // can't do much about it.
    if (!empty($this->selectedEntities)) {
      $ids = array_map(function(EntityInterface $item) {return $item->id();}, $this->selectedEntities);
      $storage['selection_display_view']->setArguments([implode(',', $ids)]);
    }

    $form['view'] = $storage['selection_display_view']->executeDisplay($this->configuration['view_display']);

    $form['use_selected'] = array(
      '#type' => 'submit',
      '#value' => t('Use selection'),
      '#name' => 'use_selected',
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submit(array &$form, FormStateInterface $form_state) {
    if ($form_state->getTriggeringElement()['#name'] == 'use_selected') {
      $this->selectionDone($form_state);
    }
  }

}
