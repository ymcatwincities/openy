<?php

/**
 * @file
 * Contains \Drupal\layout_plugin_example\Plugin\Layout\LayoutExampleTest.
 */

namespace Drupal\layout_plugin_example\Plugin\Layout;
use Drupal\Core\Form\FormStateInterface;
use Drupal\layout_plugin\Plugin\Layout\LayoutBase;

/**
 * The plugin that handles the default layout template.
 *
 * @ingroup layout_template_plugins
 *
 * @Layout(
 *   id = "layout_example_test",
 *   label = @Translation("Test layout (with settings)"),
 *   category = @Translation("Examples"),
 *   description = @Translation("Test1 sample description"),
 *   type = "page",
 *   help = @Translation("Layout"),
 *   template = "templates/layout-example-test",
 *   regions = {
 *     "top" = {
 *       "label" = @Translation("Top Region"),
 *       "plugin_id" = "default"
 *     },
 *    "bottom" = {
 *       "label" = @Translation("Bottom Region"),
 *       "plugin_id" = "default"
 *     }
 *   }
 * )
 */
class LayoutExampleTest extends LayoutBase {

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
