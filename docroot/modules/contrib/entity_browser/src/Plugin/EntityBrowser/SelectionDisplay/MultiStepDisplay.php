<?php

/**
 * Contains \Drupal\entity_browser\Plugin\EntityBrowser\SelectionDisplay\MultiStepDisplay.
 */

namespace Drupal\entity_browser\Plugin\EntityBrowser\SelectionDisplay;

use Drupal\Core\Form\FormStateInterface;
use Drupal\entity_browser\SelectionDisplayBase;

/**
 * Show current selection and delivers selected entities.
 *
 * @EntityBrowserSelectionDisplay(
 *   id = "multi_step_display",
 *   label = @Translation("Multi step selection display"),
 *   description = @Translation("Show current selection display and delivers selected entities.")
 * )
 */
class MultiStepDisplay extends SelectionDisplayBase {

  /**
   * {@inheritdoc}
   */
  public function getForm(array &$original_form, FormStateInterface $form_state) {
    $selected_entities = $form_state->get(['entity_browser', 'selected_entities']);

    $form = [];
    $form['#attached']['library'][] = 'entity_browser/multi_step_display';
    $form['selected'] = [
      '#theme_wrappers' => ['container'],
      '#attributes' => ['class' => ['selected-entities-list']],
      '#tree' => TRUE
    ];
    foreach ($selected_entities as $id => $entity) {
      $form['selected']['items_'. $entity->id()] = [
        '#theme_wrappers' => ['container'],
        '#attributes' => [
          'class' => ['selected-item-container'],
          'data-entity-id' => $entity->id()
        ],
        'display' => ['#markup' => $entity->label()],
        'remove_button' => [
          '#type' => 'submit',
          '#value' => $this->t('Remove'),
          '#submit' => [[get_class($this), 'removeItemSubmit']],
          '#name' => 'remove_' . $entity->id(),
          '#attributes' => [
            'data-row-id' => $id,
            'data-remove-entity' => 'items_' . $entity->id(),
          ]
        ],
        'weight' => [
          '#type' => 'hidden',
          '#default_value' => $id,
          '#attributes' => ['class' => ['weight']]
        ]
      ];
    }
    $form['use_selected'] = array(
      '#type' => 'submit',
      '#value' => t('Use selected'),
      '#name' => 'use_selected',
    );

    return $form;
  }

  /**
   * Submit callback for remove buttons.
   *
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   */
  public static function removeItemSubmit(array &$form, FormStateInterface $form_state) {
    $triggering_element = $form_state->getTriggeringElement();

    // Remove weight of entity being removed.
    $form_state->unsetValue(['selected', $triggering_element['#attributes']['data-remove-entity']]);

    // Remove entity itself.
    $selected_entities = &$form_state->get(['entity_browser', 'selected_entities']);
    unset($selected_entities[$triggering_element['#attributes']['data-row-id']]);

    static::saveNewOrder($form_state);
    $form_state->setRebuild();
  }

  /**
   * {@inheritdoc}
   */
  public function submit(array &$form, FormStateInterface $form_state) {
    $this->saveNewOrder($form_state);
    if ($form_state->getTriggeringElement()['#name'] == 'use_selected') {
      $this->selectionDone($form_state);
    }
  }

  /**
   * Saves new ordering of entities based on weight.
   *
   * @param FormStateInterface $form_state
   *   Form state.
   */
  public static function saveNewOrder(FormStateInterface $form_state) {
    $selected = $form_state->getValue('selected');
    if (!empty($selected)) {
      $weights = array_column($selected, 'weight');
      $selected_entities = $form_state->get(['entity_browser', 'selected_entities']);

      // If we added new entities to the selection at this step we won't have
      // weights for them so we have to fake them.
      if (sizeof($weights) < sizeof($selected_entities)) {
        for ($new_weigth = (max($weights) + 1); $new_weigth < sizeof($selected_entities); $new_weigth++) {
          $weights[] = $new_weigth;
        }
      }

      $ordered = array_combine($weights, $selected_entities);
      ksort($ordered);
      $form_state->set(['entity_browser', 'selected_entities'], $ordered);
    }
  }

}
