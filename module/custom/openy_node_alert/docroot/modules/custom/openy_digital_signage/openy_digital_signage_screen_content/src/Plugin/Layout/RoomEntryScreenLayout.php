<?php

namespace Drupal\openy_digital_signage_screen_content\Plugin\Layout;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Layout\LayoutDefault;
use Drupal\Core\Plugin\PluginFormInterface;

/**
 * Room Entry Screen layout settings.
 */
class RoomEntryScreenLayout extends LayoutDefault implements PluginFormInterface {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return parent::defaultConfiguration() + [
      'color_scheme' => 'orange',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $configuration = $this->getConfiguration();
    $form['color_scheme'] = [
      '#type' => 'select',
      '#title' => $this->t('Color Scheme'),
      '#default_value' => $configuration['color_scheme'],
      '#options' => [
        'orange' => $this->t('Orange'),
        'blue' => $this->t('Blue'),
        'purple' => $this->t('Purple'),
      ],
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
    $this->configuration['color_scheme'] = $form_state->getValue('color_scheme');
  }

  /**
   * {@inheritdoc}
   */
  public function build(array $regions) {
    $build = parent::build($regions);
    $build['classes'] = [
      'openy-res-layout',
      'scheme-' . $build['#settings']['color_scheme'],
    ];

    return $build;
  }

}
