<?php

namespace Drupal\openy_system;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\entity_browser\Element\EntityBrowserElement;

/**
 * Provides helpers for adding an entity browser element to a form.
 *
 * Source - https://git.drupalcode.org/project/helper/blob/8.x-1.x/src/EntityBrowserFormTrait.php
 */
trait EntityBrowserFormTrait {

  /**
   * Adds the Entity Browser element to a form.
   *
   * @param string $entity_browser_id
   *   The ID of the entity browser to use.
   * @param string $default_value
   *   The default value for the entity browser.
   * @param int $cardinality
   *   The cardinality of the entity browser.
   * @param string $view_mode
   *   The view mode to use when displaying the selected entity in the table.
   * @param string|bool $form_mode
   *   The form mode to use when showing the edit button for the selected
   *   entity in the table. Use FALSE to disable the edit button.
   *
   * @return array
   *   The form element containing the entity browser.
   */
  public function getEntityBrowserForm($entity_browser_id, $default_value, $cardinality = EntityBrowserElement::CARDINALITY_UNLIMITED, $view_mode = 'default', $form_mode = 'default') {
    // We need a wrapping container for AJAX operations.
    $element = [
      '#type' => 'container',
      '#attributes' => [
        'id' => Html::getUniqueId('entity-browser-' . $entity_browser_id . '-wrapper'),
      ],
    ];

    $element['browser'] = [
      '#type' => 'entity_browser',
      '#entity_browser' => $entity_browser_id,
      '#process' => [
        [self::class, 'processEntityBrowser'],
      ],
      '#cardinality' => $cardinality,
      '#selection_mode' => $cardinality === 1 ? EntityBrowserElement::SELECTION_MODE_PREPEND : EntityBrowserElement::SELECTION_MODE_APPEND,
      '#default_value' => $default_value,
      '#wrapper_id' => &$element['#attributes']['id'],
    ];
    $element['selected'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('Item'),
        $this->t('Operations'),
      ],
      '#empty' => $this->t('No items selected yet'),
      '#process' => [
        [self::class, 'processEntityBrowserSelected'],
      ],
      '#view_mode' => $view_mode,
      '#form_mode' => $form_mode,
      '#wrapper_id' => &$element['#attributes']['id'],
    ];

    return $element;
  }

  /**
   * Loads entity based on an ID in the format entity_type:entity_id.
   *
   * @param string $id
   *   An ID.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   A loaded entity.
   */
  public static function loadEntityBrowserEntity($id) {
    $entities = static::loadEntityBrowserEntitiesByIds($id);
    return reset($entities);
  }

  /**
   * Loads entities based on an ID in the format entity_type:entity_id.
   *
   * @param array|string $ids
   *   An array of IDs.
   *
   * @return \Drupal\Core\Entity\EntityInterface[]
   *   An array of loaded entities, keyed by an ID.
   */
  public static function loadEntityBrowserEntitiesByIds($ids) {
    if (!is_array($ids)) {
      $ids = explode(' ', $ids);
    }
    $ids = array_filter($ids);

    $storage = [];
    $entities = [];
    foreach ($ids as $id) {
      list($entity_type_id, $entity_id) = explode(':', $id);
      if (!isset($storage[$entity_type_id])) {
        $storage[$entity_type_id] = \Drupal::entityTypeManager()->getStorage($entity_type_id);
      }
      $entities[$entity_type_id . ':' . $entity_id] = $storage[$entity_type_id]->load($entity_id);
    }
    return $entities;
  }

  /**
   * Gets the entity browser form value.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   * @param array|string $parents
   *   The parents of the containing form element.
   *
   * @return array
   *   The entity browser value.
   */
  public function getEntityBrowserValue(FormStateInterface $form_state, $parents) {
    $parents = is_array($parents) ? $parents : [$parents];
    return $form_state->getValue(array_merge($parents, ['browser', 'entity_ids']));
  }

  /**
   * Render API callback: Processes the entity browser element.
   */
  public static function processEntityBrowser(&$element, FormStateInterface $form_state, &$complete_form) {
    if (!is_array($element['#default_value'])) {
      $element['#default_value'] = static::loadEntityBrowserEntitiesByIds($element['#default_value']);
    }
    $element = EntityBrowserElement::processEntityBrowser($element, $form_state, $complete_form);
    $element['entity_ids']['#ajax'] = [
      'callback' => [self::class, 'updateEntityBrowserSelected'],
      'wrapper' => $element['#wrapper_id'],
      'event' => 'entity_browser_value_updated',
    ];
    $element['entity_ids']['#default_value'] = implode(' ', array_keys($element['#default_value']));

    return $element;
  }

  /**
   * Render API callback: Processes the table element.
   */
  public static function processEntityBrowserSelected(&$element, FormStateInterface $form_state, &$complete_form) {
    $parents = array_slice($element['#parents'], 0, count($element['#parents']) - 1);
    $entity_ids = $form_state->getValue(array_merge($parents, ['browser', 'entity_ids']), '');
    $entities = empty($entity_ids) ? [] : self::loadEntityBrowserEntitiesByIds($entity_ids);
    $entity_type_manager = \Drupal::entityTypeManager();

    foreach ($entities as $id => $entity) {
      $entity_type_id = $entity->getEntityTypeId();
      if ($entity_type_manager->hasHandler($entity_type_id, 'view_builder')) {
        $preview = $entity_type_manager->getViewBuilder($entity_type_id)->view($entity, $element['#view_mode']);
      }
      else {
        $preview = ['#markup' => $entity->label()];
      }
      $edit_button_access = !empty($element['#form_mode']) && $entity->access('update', \Drupal::currentUser());
      $element[$id] = [
        '#attributes' => [
          'data-entity-id' => $id,
        ],
        'item' => $preview,
        'operations' => [
          'edit_button' => [
            '#type' => 'submit',
            '#value' => t('Edit'),
            '#name' => 'entity_browser_edit_' . $entity->id() . '_' . md5(json_encode($element['#parents'])),
            '#ajax' => [
              'url' => Url::fromRoute(
                'entity_browser.edit_form', [
                  'entity_type' => $entity->getEntityTypeId(),
                  'entity' => $entity->id(),
                  'form_mode' => $element['#form_mode'],
                ]
              ),
            ],
            '#attributes' => [
              'class' => ['edit-button'],
            ],
            '#access' => $edit_button_access,
          ],
          'remove' => [
            '#type' => 'button',
            '#value' => t('Remove'),
            '#op' => 'remove',
            '#name' => 'entity_browser_remove_' . $id,
            '#ajax' => [
              'callback' => [self::class, 'updateEntityBrowserSelected'],
              'wrapper' => $element['#wrapper_id'],
            ],
          ],
        ],
      ];
    }
    return $element;
  }

  /**
   * AJAX callback: Re-renders the Entity Browser button/table.
   */
  public static function updateEntityBrowserSelected(array &$form, FormStateInterface $form_state) {
    $trigger = $form_state->getTriggeringElement();
    if (isset($trigger['#op']) && $trigger['#op'] === 'remove') {
      $parents = array_slice($trigger['#array_parents'], 0, -4);
      $selection = NestedArray::getValue($form, $parents);
      $id = str_replace('entity_browser_remove_', '', $trigger['#name']);
      unset($selection['selected'][$id]);
      $value = explode(' ', $selection['browser']['entity_ids']['#value']);
      $selection['browser']['entity_ids']['#value'] = array_diff($value, [$id]);
    }
    else {
      $parents = array_slice($trigger['#array_parents'], 0, -2);
      $selection = NestedArray::getValue($form, $parents);
    }
    return $selection;
  }

}
