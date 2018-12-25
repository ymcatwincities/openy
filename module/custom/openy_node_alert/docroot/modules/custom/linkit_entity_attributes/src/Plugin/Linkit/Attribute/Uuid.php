<?php

namespace Drupal\linkit_entity_attributes\Plugin\Linkit\Attribute;

use Drupal\Core\Form\FormStateInterface;
use Drupal\linkit\ConfigurableAttributeBase;

/**
 * Title attribute.
 *
 * @Attribute(
 *   id = "linkit_entity_uuid",
 *   label = @Translation("UUID"),
 *   html_name = "data-drupal-entity-uuid",
 *   description = @Translation("Entity uuid for relationship tracking.")
 * )
 */
class Uuid extends ConfigurableAttributeBase {

  /**
   * {@inheritdoc}
   */
  public function buildFormElement($default_value) {
    $element = [
      '#type' => 'textfield',
      '#default_value' => $default_value,
      '#maxlength' => 255,
      '#size' => 40,
      '#placeholder' => t('The "data-drupal-entity-uuid" attribute value'),
      '#attributes' => array('class' => array('hidden')),
    ];

    if ($this->configuration['automatic_uuid']) {
      $element['#placeholder'] = t('The "data-drupal-entity-uuid" attribute value (auto populated)');
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return parent::defaultConfiguration() + [
      'automatic_uuid' => TRUE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['uuid'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Automatically populate Entity UUID'),
      '#default_value' => $this->configuration['automatic_uuid'],
      '#description' => $this->t('Automatically populate the Entity UUID attribute with the Entity UUID from the match selection.'),
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
    $this->configuration['automatic_uuid'] = $form_state->getValue('automatic_uuid');
  }

}
