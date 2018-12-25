<?php

/**
 * @file
 * Contains \Drupal\views_test_data\Plugin\views\display_extender\DisplayExtenderTest.
 */

namespace Drupal\views_advanced_routing\Plugin\views\display_extender;

use Drupal\Component\Serialization\Exception\InvalidDataTypeException;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\display\DisplayRouterInterface;
use Drupal\views\Plugin\views\display_extender\DisplayExtenderPluginBase;
use Drupal\Component\Serialization\Yaml;
use Symfony\Component\Routing\Route;

/**
 * Advanced route editor.
 *
 * @ViewsDisplayExtender(
 *   id = "views_advanced_routing_route",
 *   title = @Translation("Route")
 * )
 */
class AdvancedRouting extends DisplayExtenderPluginBase {

  /**
   * Stores some state booleans to be sure a certain method got called.
   *
   * @var array
   */
  public $testState;

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();

    // YAML.
    $options['route'] = ['default' => ''];

    return $options;
  }

  /**
   * Overrides Drupal\views\Plugin\views\display\DisplayPluginBase::optionsSummary().
   *
   * $options keys is section as used by buildOptionsForm().
   */
  public function optionsSummary(&$categories, &$options) {
    if (!$this->displayHandler instanceof DisplayRouterInterface) {
      return;
    }

    parent::optionsSummary($categories, $options);

    $categories['views_advanced_routing'] = [
      'title' => '',
      'column' => 'second',
      'build' => [
        // Page settings is -10, Access is -5.
        '#weight' => -6,
      ],
    ];

    $message = [];
    $route = $this->options['route'];
    if (!empty($route['defaults'])) {
      $message[] = $this->t('Defaults');
    }
    if (!empty($route['requirements'])) {
      $message[] = $this->t('Requirements');
    }
    if (!empty($route['options'])) {
      $message[] = $this->t('Options');
    }

    $options['views_advanced_routing_route'] = [
      'category' => 'views_advanced_routing',
      'title' => $this->t('Route'),
      'value' => implode(' | ', $message) ?: $this->t('None'),
    ];
  }

  /**
   * Overrides Drupal\views\Plugin\views\display_extender\DisplayExtenderPluginBase::buildOptionsForm().
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    $section = $form_state->get('section');
    if ($section == 'views_advanced_routing_route') {
      $route = $this->options['route'];
      $form['route'] = [
        '#title' => t('Route YAML'),
        '#type' => 'fieldset',
        '#tree' => TRUE,
      ];
      $form['route']['defaults'] = [
        '#type' => 'textarea',
        '#title' => t('Defaults'),
        '#default_value' => !empty($route['defaults']) ? Yaml::encode($route['defaults']) : '',
      ];
      $form['route']['requirements'] = [
        '#type' => 'textarea',
        '#title' => t('Requirements'),
        '#default_value' => !empty($route['requirements']) ? Yaml::encode($route['requirements']) : '',
      ];
      $form['route']['options'] = [
        '#type' => 'textarea',
        '#title' => t('Options'),
        '#default_value' => !empty($route['options']) ? Yaml::encode($route['options']) : '',
      ];
    }
  }

  /**
   * @inheritDoc
   */
  public function validateOptionsForm(&$form, FormStateInterface $form_state) {
    $section = $form_state->get('section');
    if ($section == 'views_advanced_routing_route') {
      $route_info = [
        'defaults' => [],
        'requirements' => [],
        'options' => [],
      ];

      // $key: defaults, requirements, options.
      $route = $form_state->getValue('route');
      foreach ($route as $key => $value) {
        try {
          $route_data = Yaml::decode($route[$key]) ?: [];
          if (is_array($route_data)) {
            $route_info[$key] = $route_data;
          }
          else {
            $form_state->setError($form['route'][$key], $this->t('Value must be an array.'));
          }
        }
        catch (InvalidDataTypeException $e) {
          $form_state->setError($form['route'][$key], $this->t('YAML does not validate: @exception', [
            '@exception' => $e->getMessage(),
          ]));
        }
      }

      try {
        new Route('<none>', $route_info['defaults'], $route_info['requirements'], $route_info['options']);
      }
      catch (\Exception $e) {
        // Creating the route can throw exceptions.
        $form_state->setError($form['route'], $e->getMessage());
      }

      $form_state->set('route_info', $route_info);
    }
  }

  /**
   * Overrides Drupal\views\Plugin\views\display\DisplayExtenderPluginBase::submitOptionsForm().
   */
  public function submitOptionsForm(&$form, FormStateInterface $form_state) {
    $section = $form_state->get('section');
    if ($section == 'views_advanced_routing_route') {
      if ($route_info = $form_state->get('route_info')) {
        $this->options['route'] = $route_info;
      }
    }
  }

}
