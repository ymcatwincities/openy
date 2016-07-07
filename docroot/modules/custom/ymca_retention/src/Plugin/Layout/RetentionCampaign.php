<?php

namespace Drupal\ymca_retention\Plugin\Layout;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation;
use Drupal\layout_plugin\Plugin\Layout\LayoutBase;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Ymca Retention Campaign layout settings.
 */
class RetentionCampaign extends LayoutBase {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return parent::defaultConfiguration() + [
      'extra_classes' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $configuration = $this->getConfiguration();
    $form['extra_classes'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Extra classes'),
      '#default_value' => $configuration['extra_classes'],
    ];
    return $form;
  }

  /**
   * @inheritDoc
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);

    $this->configuration['extra_classes'] = $form_state->getValue('extra_classes');
  }
}
