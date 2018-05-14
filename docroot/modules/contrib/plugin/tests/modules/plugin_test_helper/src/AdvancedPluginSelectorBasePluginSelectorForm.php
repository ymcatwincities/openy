<?php

namespace Drupal\plugin_test_helper;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Form\FormInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\SubformState;
use Drupal\plugin\Plugin\Plugin\PluginSelector\PluginSelectorManagerInterface;
use Drupal\plugin\PluginDiscovery\LimitedPluginDiscoveryDecorator;
use Drupal\plugin\PluginManager\PluginManagerDecorator;
use Drupal\plugin\PluginType\PluginTypeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form to test plugin selector plugins based on AdvancedPluginSelectorBase.
 */
class AdvancedPluginSelectorBasePluginSelectorForm implements ContainerInjectionInterface, FormInterface {

  use DependencySerializationTrait;

  /**
   * The plugin selector manager.
   *
   * @var \Drupal\plugin\Plugin\Plugin\PluginSelector\PluginSelectorManagerInterface
   */
  protected $pluginSelectorManager;

  /**
   * A selectable plugin type.
   *
   * @var \Drupal\plugin\PluginType\PluginTypeInterface
   */
  protected $selectablePluginType;

  /**
   * Constructs a new class instance.
   */
  function __construct(PluginTypeInterface $selectable_plugin_type, PluginSelectorManagerInterface $plugin_selector_manager) {
    $this->selectablePluginType = $selectable_plugin_type;
    $this->pluginSelectorManager = $plugin_selector_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    /** @var \Drupal\plugin\PluginType\PluginTypeManagerInterface $plugin_type_manager */
    $plugin_type_manager = $container->get('plugin.plugin_type_manager');

    return new static($plugin_type_manager->getPluginType('plugin_test_helper_mock'), $container->get('plugin.manager.plugin.plugin_selector'));
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'plugin_test_helper_advanced_plugin_selector_base';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $allowed_selectable_plugin_ids = NULL, $plugin_id = NULL, $tree = FALSE, $always_show_selector = FALSE) {
    if ($form_state->has('plugin_selector')) {
      $plugin_selector = $form_state->get('plugin_selector');
    }
    else {
      $selectable_plugin_discovery = new LimitedPluginDiscoveryDecorator($this->selectablePluginType->getPluginManager());
      $selectable_plugin_discovery->setDiscoveryLimit(explode(',', $allowed_selectable_plugin_ids));
      $selectable_plugin_manager = new PluginManagerDecorator($this->selectablePluginType->getPluginManager(), $selectable_plugin_discovery);
      /** @var \Drupal\plugin\Plugin\Plugin\PluginSelector\AdvancedPluginSelectorBase $plugin_selector */
      $plugin_selector = $this->pluginSelectorManager->createInstance($plugin_id);
      $plugin_selector->setSelectablePluginType($this->selectablePluginType);
      $plugin_selector->setSelectablePluginDiscovery($selectable_plugin_manager);
      $plugin_selector->setSelectablePluginFactory($selectable_plugin_manager);
      $plugin_selector->setRequired();
      $plugin_selector->setSelectorVisibilityForSingleAvailability($always_show_selector);
      $form_state->set('plugin_selector', $plugin_selector);
    }

    $plugin_selector_form = [];
    $plugin_selector_form_state = SubformState::createForSubform($plugin_selector_form, $form, $form_state);
    $form['plugin'] = $plugin_selector->buildSelectorForm($plugin_selector_form, $plugin_selector_form_state);
    // Nest the selector in a tree if that's required.
    if ($tree) {
      $form['tree'] = array(
        '#tree' => TRUE,
        'plugin' => $form['plugin'],
      );
      unset($form['plugin']);
    }
    $form['actions'] = array(
      '#type' => 'actions',
    );
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Submit'),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\plugin\Plugin\Plugin\PluginSelector\PluginSelectorInterface $plugin_selector */
    $plugin_selector = $form_state->get('plugin_selector');
    $plugin_selector_form = isset($form['tree']) ? $form['tree']['plugin'] : $form['plugin'];
    $plugin_selector_form_state = SubformState::createForSubform($plugin_selector_form, $form, $form_state);
    $plugin_selector->validateSelectorForm($plugin_selector_form, $plugin_selector_form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\plugin\Plugin\Plugin\PluginSelector\PluginSelectorInterface $plugin_selector */
    $plugin_selector = $form_state->get('plugin_selector');
    $plugin_selector_form = isset($form['tree']) ? $form['tree']['plugin'] : $form['plugin'];
    $plugin_selector_form_state = SubformState::createForSubform($plugin_selector_form, $form, $form_state);
    $plugin_selector->submitSelectorForm($plugin_selector_form, $plugin_selector_form_state);
    \Drupal::state()->set($this->getFormId(), $plugin_selector->getSelectedPlugin());
  }
}
