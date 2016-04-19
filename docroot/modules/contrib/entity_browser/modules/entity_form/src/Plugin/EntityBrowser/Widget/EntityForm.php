<?php

/**
 * Contains \Drupal\entity_browser_entity_form\Plugin\EntityBrowser\Widget\EntityForm.
 */

namespace Drupal\entity_browser_entity_form\Plugin\EntityBrowser\Widget;

use Drupal\Core\Form\FormStateInterface;
use Drupal\entity_browser\WidgetBase;

/**
 * Uses a view to provide entity listing in a browser's widget.
 *
 * @EntityBrowserWidget(
 *   id = "entity_form",
 *   label = @Translation("Entity form"),
 *   description = @Translation("Provides entity form widget.")
 * )
 */
class EntityForm extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return array(
      'entity_type' => NULL,
      'bundle' => NULL,
    ) + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function getForm(array &$original_form, FormStateInterface $form_state, array $aditional_widget_parameters) {
    if (empty($this->configuration['entity_type']) || empty($this->configuration['bundle'])) {
      return [
        '#markup' => t('Entity type or bundle are no configured correctly.'),
      ];
    }

    return [
      'inline_entity_form' => [
        '#type' => 'inline_entity_form',
        '#op' => 'add',
        '#handle_submit' => FALSE,
        '#entity_type' => $this->configuration['entity_type'],
        '#bundle' => $this->configuration['bundle'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function submit(array &$element, array &$form, FormStateInterface $form_state) {
    // We handle submit on our own in order to take control over what's going on.
    foreach ($element['inline_entity_form']['#ief_element_submit'] as $function) {
      $function($element['inline_entity_form'], $form_state);
    }

    $this->selectEntities([$element['inline_entity_form']['#entity']], $form_state);
  }

}
