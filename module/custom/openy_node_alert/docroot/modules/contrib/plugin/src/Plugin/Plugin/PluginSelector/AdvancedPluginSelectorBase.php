<?php

namespace Drupal\plugin\Plugin\Plugin\PluginSelector;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\SubformState;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\plugin\DefaultPluginResolver\DefaultPluginResolverInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a default base for most plugin selectors.
 *
 * This class takes care of everything, except the actual selection element.
 *
 * @internal
 */
abstract class AdvancedPluginSelectorBase extends PluginSelectorBase implements ContainerFactoryPluginInterface, PluginFormInterface {

  /**
   * Constructs a new class instance.
   *
   * @param array $configuration
   * @param string $plugin_id
   * @param array $plugin_definition
   * @param \Drupal\plugin\DefaultPluginResolver\DefaultPluginResolverInterface $default_plugin_resolver
   *   The default plugin resolver.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, DefaultPluginResolverInterface $default_plugin_resolver, TranslationInterface $string_translation) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $default_plugin_resolver);
    $this->stringTranslation = $string_translation;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($configuration, $plugin_id, $plugin_definition, $container->get('plugin.default_plugin_resolver'), $container->get('string_translation'));
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'show_selector_for_single_availability' => FALSE,
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildSelectorForm(array $plugin_selector_form, FormStateInterface $plugin_selector_form_state) {
    $this->assertSubformState($plugin_selector_form_state);
    $plugin_selector_form = parent::buildSelectorForm($plugin_selector_form, $plugin_selector_form_state);
    $plugin_selector_form['#tree'] = TRUE;

    $available_plugins = [];
    $cacheability_metadata = CacheableMetadata::createFromRenderArray($plugin_selector_form);
    foreach (array_keys($this->selectablePluginDiscovery->getDefinitions()) as $plugin_id) {
      $available_plugin = $this->selectablePluginFactory->createInstance($plugin_id);
      if ($available_plugin instanceof PluginInspectionInterface) {
        $available_plugins[] = $available_plugin;
        $cacheability_metadata = $cacheability_metadata->merge(CacheableMetadata::createFromObject($available_plugin));
      }
    }
    $cacheability_metadata->applyTo($plugin_selector_form);

    $plugin_selector_form_state_key = static::setPluginSelector($plugin_selector_form_state, $this);
    $plugin_selector_form['container'] = array(
      '#attributes' => array(
        'class' => array('plugin-selector-' . Html::getClass($this->getPluginId())),
      ),
      '#available_plugins' => $available_plugins,
      '#plugin_selector_form_state_key' => $plugin_selector_form_state_key,
      '#process' => [[get_class(), 'processBuildSelectorForm']],
      '#type' => 'container',
    );

    return $plugin_selector_form;
  }

  /**
   * Stores a plugin selector in the form state.
   *
   * @param \Drupal\Core\Form\FormStateInterface
   * @param \Drupal\plugin\Plugin\Plugin\PluginSelector\PluginSelectorInterface
   *
   * @return string[]
   *   The form state storage key that contains the plugin selector.
   *
   * @throws \InvalidArgumentException
   */
  protected static function setPluginSelector(FormStateInterface $form_state, PluginSelectorInterface $plugin_selector) {
    do {
      $key = [get_class(), mt_rand()];
    } while ($form_state->has($key));

    $form_state->set($key, $plugin_selector);

    return $key;
  }

  /**
   * Gets a plugin selector from the form state.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   * @param string[] $form_state_key
   *   The key under which the plugin selector is stored.
   *
   * @return \Drupal\plugin\Plugin\Plugin\PluginSelector\PluginSelectorInterface
   */
  protected static function getPluginSelector(FormStateInterface $form_state, array $form_state_key) {
    return $form_state->get($form_state_key);
  }

  /**
   * Implements a Form API #process callback.
   */
  public static function processBuildSelectorForm(array $plugin_selector_form, FormStateInterface $complete_form_state, array $complete_form) {
    /** @var static $plugin_selector */
    $plugin_selector = static::getPluginSelector($complete_form_state, $plugin_selector_form['#plugin_selector_form_state_key']);

    $plugin_selector_form_state = SubformState::createForSubform($plugin_selector_form, $complete_form, $complete_form_state);

    if (count($plugin_selector_form['#available_plugins']) == 0) {
      return $plugin_selector->buildNoAvailablePlugins($plugin_selector_form, $plugin_selector_form_state);
    }
    elseif (count($plugin_selector_form['#available_plugins']) == 1 && !$plugin_selector->getSelectorVisibilityForSingleAvailability()) {
      return $plugin_selector->buildOneAvailablePlugin($plugin_selector_form, $plugin_selector_form_state);
    }
    else {
      return $plugin_selector->buildMultipleAvailablePlugins($plugin_selector_form, $plugin_selector_form_state);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function validateSelectorForm(array &$plugin_selector_form, FormStateInterface $plugin_selector_form_state) {
    $this->assertSubformState($plugin_selector_form_state);
    $plugin_id = $plugin_selector_form_state->getValue(['container', 'select', 'container', 'plugin_id']);
    $selected_plugin = $this->getSelectedPlugin();
    if (!$selected_plugin && $plugin_id || $selected_plugin && $plugin_id != $selected_plugin->getPluginId()) {
      // Keep track of all previously selected plugins so their configuration
      // does not get lost.
      if (isset($this->getPreviouslySelectedPlugins()[$plugin_id])) {
        $this->setSelectedPlugin($this->getPreviouslySelectedPlugins()[$plugin_id]);
      }
      elseif ($plugin_id) {
        $this->setSelectedPlugin($this->selectablePluginFactory->createInstance($plugin_id));
      }
      else {
        $this->resetSelectedPlugin();
      }

      // If a (different) plugin was chosen and its form must be displayed,
      // rebuild the form.
      if ($this->getCollectPluginConfiguration() && $this->getSelectedPlugin() instanceof PluginFormInterface) {
        $plugin_selector_form_state->setRebuild();
      }
    }
    // If no (different) plugin was chosen, delegate validation to the plugin.
    elseif ($this->getCollectPluginConfiguration() && $selected_plugin instanceof PluginFormInterface) {
      $selected_plugin_form = &$plugin_selector_form['container']['plugin_form'];
      $selected_plugin_form_state = SubformState::createForSubform($selected_plugin_form, $plugin_selector_form, $plugin_selector_form_state);
      $selected_plugin->validateConfigurationForm($selected_plugin_form, $selected_plugin_form_state);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitSelectorForm(array &$plugin_selector_form, FormStateInterface $plugin_selector_form_state) {
    $this->assertSubformState($plugin_selector_form_state);
    $selected_plugin = $this->getSelectedPlugin();
    if ($this->getCollectPluginConfiguration() && $selected_plugin instanceof PluginFormInterface) {
      $selected_plugin_form = &$plugin_selector_form['container']['plugin_form'];
      $selected_plugin_form_state = SubformState::createForSubform($selected_plugin_form, $plugin_selector_form, $plugin_selector_form_state);
      $selected_plugin->submitConfigurationForm($selected_plugin_form, $selected_plugin_form_state);
    }
  }

  /**
   * Implements form API's #submit.
   */
  public static function rebuildForm(array $complete_form, FormStateInterface $complete_form_state) {
    $complete_form_state->setRebuild();
  }

  /**
   * Implements form AJAX callback.
   */
  public static function ajaxRebuildForm(array &$complete_form, FormStateInterface $complete_form_state) {
    $triggering_element = $complete_form_state->getTriggeringElement();
    $form_parents = array_slice($triggering_element['#array_parents'], 0, -3);
    $root_element = NestedArray::getValue($complete_form, $form_parents);

    $response = new AjaxResponse();
    $response->addCommand(new ReplaceCommand(sprintf('[data-drupal-selector="%s"]', $root_element['plugin_form']['#attributes']['data-drupal-selector']), $root_element['plugin_form']));

    return $response;
  }

  /**
   * Builds the plugin configuration form elements.
   *
   * @param mixed[] $plugin_selector_form
   *   The plugin selector form.
   * @param \Drupal\Core\Form\FormStateInterface $plugin_selector_form_state
   *
   * @return array
   */
  protected function buildPluginForm(array $plugin_selector_form, FormStateInterface $plugin_selector_form_state) {
    $selected_plugin_form = [
      '#attributes' => array(
        'class' => [Html::getClass(sprintf('plugin-selector-%s-plugin-form', $this->getPluginId()))],
      ),
      '#type' => 'container',
    ];
    $selected_plugin = $this->getSelectedPlugin();
    if ($this->getCollectPluginConfiguration() && $selected_plugin instanceof PluginFormInterface) {
      $selected_plugin_form_state = SubformState::createForSubform($selected_plugin_form, $plugin_selector_form, $plugin_selector_form_state);
      $selected_plugin_form = $selected_plugin->buildConfigurationForm($selected_plugin_form, $selected_plugin_form_state);
    }

    return $selected_plugin_form;
  }

  /**
   * Builds the form elements for when there are no available plugins.
   */
  public function buildNoAvailablePlugins(array $element, FormStateInterface $plugin_selector_form_state) {
    $element['select']['container'] = array(
      '#type' => 'container',
    );
    $element['select']['container']['plugin_id'] = array(
      '#type' => 'value',
      '#value' => NULL,
    );
    $element['select']['message'] = array(
      '#markup' => $this->t('There are no available options.'),
      '#title' => $this->getLabel(),
      '#type' => 'item',
    );

    return $element;
  }

  /**
   * Builds the form elements for one plugin.
   */
  public function buildOneAvailablePlugin(array $plugin_selector_form, FormStateInterface $plugin_selector_form_state) {
    $plugin = reset($plugin_selector_form['#available_plugins']);

    // Use the only available plugin if no other was configured before, or the
    // configured plugin is not available.
    if (is_null($this->getSelectedPlugin()) || $this->getSelectedPlugin()->getPluginId() != $plugin->getPluginId()) {
      $this->setSelectedPlugin($plugin);
    }

    $plugin_selector_form['select']['message'] = array(
      '#title' => $this->getLabel(),
      '#type' => 'item',
    );
    $plugin_selector_form['select']['container'] = array(
      '#type' => 'container',
    );
    $plugin_selector_form['select']['container']['plugin_id'] = array(
      '#type' => 'value',
      '#value' => $this->getSelectedPlugin()->getPluginId(),
    );
    $plugin_selector_form['plugin_form'] = $this->buildPluginForm($plugin_selector_form, $plugin_selector_form_state);

    return $plugin_selector_form;
  }

  /**
   * Builds the form elements for multiple plugins.
   */
  protected function buildMultipleAvailablePlugins(array $plugin_selector_form, FormStateInterface $plugin_selector_form_state) {
    $plugins = $plugin_selector_form['#available_plugins'];

    $plugin_selector_form['select'] = $this->buildSelector($plugin_selector_form, $plugin_selector_form_state, $plugins);
    $plugin_selector_form['plugin_form'] = $this->buildPluginForm($plugin_selector_form, $plugin_selector_form_state);

    return $plugin_selector_form;
  }

  /**
   * Builds the form elements for the actual plugin selector.
   *
   * @param array $plugin_selector_form
   *   The plugin selector form.
   * @param \Drupal\Core\Form\FormStateInterface $plugin_selector_form_state
   *   The form's state.
   * @param \Drupal\Component\Plugin\PluginInspectionInterface[] $plugins
   *   The available plugins.
   *
   * @return array
   *   The selector's form elements.
   */
  protected function buildSelector(array $plugin_selector_form, FormStateInterface $plugin_selector_form_state, array $plugins) {
    $build['container'] = array(
      '#attributes' => array(
        'class' => array('plugin-selector-' . Html::getClass($this->getPluginId() . '-selector')),
      ),
      '#type' => 'container',
    );
    $build['container']['plugin_id'] = array(
      '#markup' => 'This element must be overridden to provide the plugin ID.',
    );
    $root_element_parents = $plugin_selector_form['#parents'];
    // Compute the button's name based on its position in the form, but we
    // cannot use "][" to indicate nesting as we would usually do, because then
    // \Drupal\Core\Form\FormBuilder::buttonWasClicked() cannot recognize the
    // button when it is clicked.
    $change_button_name_parts = array_merge($root_element_parents, ['select', 'container', 'change']);
    $change_button_name = implode('__', $change_button_name_parts);
    $build['container']['change'] = array(
      '#ajax' => array(
        'callback' => array(get_class(), 'ajaxRebuildForm'),
      ),
      '#attributes' => array(
        'class' => array('js-hide')
      ),
      '#limit_validation_errors' => array(array_merge($plugin_selector_form['#parents'], array('select', 'plugin_id'))),
      '#name' => $change_button_name,
      '#submit' => array(array(get_class(), 'rebuildForm')),
      '#type' => 'submit',
      '#value' => $this->t('Choose'),
    );

    return $build;
  }

  /**
   * Toggles whether or not to show the selection elements for single plugins.
   *
   * @param bool $show
   *   TRUE to show selection elements or FALSE to hide them for single plugins.
   */
  public function setSelectorVisibilityForSingleAvailability($show) {
    $this->configuration['show_selector_for_single_availability'] = $show;
  }

  /**
   * Gets whether or not to show the selection elements for single plugins.
   *
   * @return bool
   *   TRUE to show selection elements or FALSE to hide them for single plugins.
   */
  public function getSelectorVisibilityForSingleAvailability() {
    return $this->configuration['show_selector_for_single_availability'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = [];
    $form['show_selector_for_single_availability'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Hide selector if only a single plugin is available'),
      '#default_value' => $this->configuration['show_selector_for_single_availability'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    // No elements need validation.
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['show_selector_for_single_availability'] = $form_state->getValue('show_selector_for_single_availability');
  }

}
