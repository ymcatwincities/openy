<?php

namespace Drupal\layout_test\Plugin\Layout;

use Drupal\Core\Form\FormStateInterface;
use Drupal\layout_plugin\Plugin\Layout\LayoutBase;

/**
 * The plugin that handles the default layout template.
 *
 * @ingroup layout_template_plugins
 *
 * @Layout(
 *   id = "layout_test_plugin",
 *   label = @Translation("Layout plugin (with settings)"),
 *   category = @Translation("Layout test"),
 *   description = @Translation("Test layout"),
 *   template = "templates/layout-test-plugin",
 *   regions = {
 *     "main" = {
 *       "label" = @Translation("Main Region")
 *     }
 *   }
 * )
 */
class LayoutTestPlugin extends LayoutBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return parent::defaultConfiguration() + [
      'setting_1' => 'Default',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $configuration = $this->getConfiguration();
    $form['setting_1'] = [
      '#type' => 'textfield',
      '#title' => 'Blah',
      '#default_value' => $configuration['setting_1'],
    ];
    return $form;
  }

  /**
   * @inheritDoc
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);

    $this->configuration['setting_1'] = $form_state->getValue('setting_1');
  }

}
