<?php

namespace Drupal\custom_formatters;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginBase;

/**
 * Class FormatterTypeBase.
 *
 * @package Drupal\custom_formatters
 */
abstract class FormatterTypeBase extends PluginBase implements FormatterTypeInterface {

  /**
   * The Formatter entity.
   *
   * @var \Drupal\custom_formatters\FormatterInterface
   */
  protected $entity = NULL;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    $this->entity = $configuration['entity'];
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array &$form, FormStateInterface $form_state) {
    $form['data'] = [
      '#title'         => $this->t('Formatter'),
      '#type'          => 'textarea',
      '#default_value' => $this->entity->get('data'),
      '#required'      => TRUE,
      '#rows'          => 10,
    ];

    return $form;
  }

  /**
   * Acts on loaded entities.
   */
  public function postLoad() {
  }

  /**
   * Acts on a saved entity before the insert or update hook is invoked.
   *
   * Used after the entity is saved, but before invoking the insert or update
   * hook. Note that in case of translatable content entities this callback is
   * only fired on their current translation. It is up to the developer to
   * iterate over all translations if needed.
   */
  public function preSave() {
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array $form, FormStateInterface $form_state) {
  }

}
