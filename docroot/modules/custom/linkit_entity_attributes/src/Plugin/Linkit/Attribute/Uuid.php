<?php

/**
 * @file
 * Contains \Drupal\linkit\Plugin\Linkit\Attribute\Title.
 */

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
      '#title' => t('UUID'),
      '#default_value' => $default_value,
      '#maxlength' => 255,
      '#size' => 40,
      '#placeholder' => t('The "data-drupal-entity-uuid" attribute value'),
      '#access' => FALSE,
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
  }

}
