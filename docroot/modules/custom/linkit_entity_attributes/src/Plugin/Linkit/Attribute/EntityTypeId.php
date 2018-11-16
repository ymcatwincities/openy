<?php

namespace Drupal\linkit_entity_attributes\Plugin\Linkit\Attribute;

use Drupal\Core\Form\FormStateInterface;
use Drupal\linkit\ConfigurableAttributeBase;

/**
 * Title attribute.
 *
 * @Attribute(
 *   id = "entity_type_id",
 *   label = @Translation("Entity Type Id"),
 *   html_name = "data-drupal-entity-type-id",
 *   description = @Translation("Entity type id for relationship tracking.")
 * )
 */
class EntityTypeId extends ConfigurableAttributeBase {

  /**
   * {@inheritdoc}
   */
  public function buildFormElement($default_value) {
    $element = [
      '#type' => 'textfield',
      '#default_value' => $default_value,
      '#maxlength' => 255,
      '#size' => 40,
      '#placeholder' => t('The "data-drupal-entity-type-id" attribute value'),
      '#attributes' => array('class' => array('hidden')),
    ];

    if ($this->configuration['automatic_entity_type_id']) {
      $element['#placeholder'] = t('The "data-drupal-entity-type-id" attribute value (auto populated)');
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return parent::defaultConfiguration() + [
      'automatic_entity_type_id' => TRUE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['entity_type_id'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Automatically populate Entity Type Id'),
      '#default_value' => $this->configuration['automatic_entity_type_id'],
      '#description' => $this->t('Automatically populate the Entity Type Id attribute with the Entity Type Id from the match selection.'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['automatic_entity_type_id'] = $form_state->getValue('automatic_entity_type_id');
  }

}
