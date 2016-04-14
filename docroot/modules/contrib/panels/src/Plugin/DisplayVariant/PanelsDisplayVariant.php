<?php

/**
 * @file
 * Contains \Drupal\panels\Plugin\DisplayVariant\PanelsDisplayVariant.
 */

namespace Drupal\panels\Plugin\DisplayVariant;

use Drupal\Component\Utility\NestedArray;
use Drupal\Component\Uuid\UuidInterface;
use Drupal\Core\Block\BlockManager;
use Drupal\Core\Condition\ConditionManager;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormState;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\Context\ContextHandlerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Utility\Token;
use Drupal\ctools\Plugin\BlockPluginCollection;
use Drupal\ctools\Plugin\DisplayVariant\BlockDisplayVariant;
use Drupal\layout_plugin\Layout;
use Drupal\layout_plugin\Plugin\Layout\LayoutInterface;
use Drupal\layout_plugin\Plugin\Layout\LayoutPluginManagerInterface;
use Drupal\panels\Plugin\DisplayBuilder\DisplayBuilderInterface;
use Drupal\panels\Plugin\DisplayBuilder\DisplayBuilderManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a display variant that simply contains blocks.
 *
 * @DisplayVariant(
 *   id = "panels_variant",
 *   admin_label = @Translation("Panels")
 * )
 */
class PanelsDisplayVariant extends BlockDisplayVariant {

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The display builder plugin manager.
   *
   * @var \Drupal\panels\Plugin\DisplayBuilder\DisplayBuilderManagerInterface
   */
  protected $builderManager;

  /**
   * The display builder plugin.
   *
   * @var \Drupal\panels\Plugin\DisplayBuilder\DisplayBuilderInterface
   */
  protected $builder;

  /**
   * The layout plugin manager.
   *
   * @var \Drupal\layout_plugin\Plugin\Layout\LayoutPluginManagerInterface;
   */
  protected $layoutManager;

  /**
   * The layout plugin.
   *
   * @var \Drupal\layout_plugin\Plugin\Layout\LayoutInterface
   */
  protected $layout;

  /**
   * Constructs a new PanelsDisplayVariant.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Plugin\Context\ContextHandlerInterface $context_handler
   *   The context handler.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The current user.
   * @param \Drupal\Component\Uuid\UuidInterface $uuid_generator
   *   The UUID generator.
   * @param \Drupal\Core\Utility\Token $token
   *   The token service.
   * @param \Drupal\Core\Block\BlockManager $block_manager
   *   The block manager.
   * @param \Drupal\Core\Condition\ConditionManager $condition_manager
   *   The condition manager.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\panels\Plugin\DisplayBuilder\DisplayBuilderManagerInterface $builder_manager
   *   The display builder plugin manager.
   * @param \Drupal\layout_plugin\Plugin\Layout\LayoutPluginManagerInterface $layout_manager
   *   The layout plugin manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ContextHandlerInterface $context_handler, AccountInterface $account, UuidInterface $uuid_generator, Token $token, BlockManager $block_manager, ConditionManager $condition_manager, ModuleHandlerInterface $module_handler, DisplayBuilderManagerInterface $builder_manager, LayoutPluginManagerInterface $layout_manager) {
    $this->moduleHandler = $module_handler;
    $this->builderManager = $builder_manager;
    $this->layoutManager = $layout_manager;

    parent::__construct($configuration, $plugin_id, $plugin_definition, $context_handler, $account, $uuid_generator, $token, $block_manager, $condition_manager);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('context.handler'),
      $container->get('current_user'),
      $container->get('uuid'),
      $container->get('token'),
      $container->get('plugin.manager.block'),
      $container->get('plugin.manager.condition'),
      $container->get('module_handler'),
      $container->get('plugin.manager.panels.display_builder'),
      $container->get('plugin.manager.layout_plugin')
    );
  }

  /**
   * Returns the builder assigned to this display variant.
   *
   * @return \Drupal\panels\Plugin\DisplayBuilder\DisplayBuilderInterface
   *   A display builder plugin instance.
   */
  public function getBuilder() {
    if (!isset($this->builder)) {
      $this->builder = $this->builderManager->createInstance($this->configuration['builder'], []);
    }
    return $this->builder;
  }

  /**
   * Assigns a builder to this display variant.
   *
   * @param string|\Drupal\panels\Plugin\DisplayBuilder\DisplayBuilderInterface $builder
   *   The builder object or plugin id.
   *
   * @return $this
   *
   * @throws \Exception
   *   If $build isn't a string or DisplayBuilderInterface object.
   */
  public function setBuilder($builder) {
    if ($builder instanceof DisplayBuilderInterface) {
      $this->builder = $builder;
      $this->configuration['builder'] = $builder->getPluginId();
    }
    elseif (is_string($builder)) {
      $this->builder = NULL;
      $this->configuration['builder'] = $builder;
    }
    else {
      throw new \Exception("Builder must be a string or DisplayBuilderInterface object");
    }

    return $this;
  }

  /**
   * Returns instance of the layout plugin used by this page variant.
   *
   * @return \Drupal\layout_plugin\Plugin\Layout\LayoutInterface
   *   A layout plugin instance.
   */
  public function getLayout() {
    if (!isset($this->layout)) {
      $this->layout = $this->layoutManager->createInstance($this->configuration['layout'], $this->configuration['layout_settings']);
    }
    return $this->layout;
  }

  /**
   * Assigns the layout plugin to this variant.
   *
   * @param string|\Drupal\layout_plugin\Plugin\Layout\LayoutInterface $layout
   *   The layout plugin object or plugin id.
   * @param array $layout_settings
   *   The layout configuration.
   *
   * @return $this
   *
   * @throws \Exception
   *   If $layout isn't a string or LayoutInterface object.
   */
  public function setLayout($layout, array $layout_settings = []) {
    if ($layout instanceof LayoutInterface) {
      $this->layout = $layout;
      $this->configuration['layout'] = $layout->getPluginId();
      $this->configuration['layout_settings'] = $layout_settings;
    }
    elseif (is_string($layout)) {
      $this->layout = NULL;
      $this->configuration['layout'] = $layout;
      $this->configuration['layout_settings'] = $layout_settings;
    }
    else {
      throw new \Exception("Layout must be a string or LayoutInterface object");
    }

    return $this;
  }

  /**
   * Configures how this Panel is being stored.
   *
   * @param string $type
   *   The storage type used by the storage plugin.
   * @param string $id
   *   The id within the storage plugin for this Panels display.
   *
   * @return $this
   */
  public function setStorage($type, $id) {
    $this->configuration['storage_type'] = $type;
    $this->configuration['storage_id'] = $id;
    return $this;
  }

  /**
   * Gets the id of the storage plugin which can save this.
   *
   * @return string|NULL
   */
  public function getStorageType() {
    return $this->configuration['storage_type'] ?: NULL;
  }

  /**
   * Gets id within the storage plugin for this Panels display.
   *
   * @return string|NULL
   */
  public function getStorageId() {
    return $this->configuration['storage_id'] ?: NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getRegionNames() {
    return $this->getLayout()->getPluginDefinition()['region_names'];
  }

  /**
   * Returns the configured page title.
   *
   * @return string
   */
  public function getPageTitle() {
    return $this->configuration['page_title'];
  }

  /**
   * Sets the page title.
   *
   * @param string $title
   *   The desired page title.
   *
   * @return $this
   */
  public function setPageTitle($title) {
    $this->configuration['page_title'] = $title;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = $this->getBuilder()->build($this);
    $build['#title'] = $this->renderPageTitle($this->configuration['page_title']);

    // Allow other module to alter the built panel.
    $this->moduleHandler->alter('panels_build', $build, $this);

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    // Don't call VariantBase::buildConfigurationForm() on purpose, because it
    // adds a 'Label' field that we don't actually want to use - we store the
    // label on the page variant entity.
    //$form = parent::buildConfigurationForm($form, $form_state);

    // Allow to configure the page title, even when adding a new display.
    // Default to the page label in that case.
    $form['page_title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Page title'),
      '#description' => $this->t('Configure the page title that will be used for this display.'),
      '#default_value' => $this->configuration['page_title'] ?: '',
    ];

    if (empty($this->configuration['builder'])) {
      $plugins = $this->builderManager->getDefinitions();
      $options = array();
      foreach ($plugins as $id => $plugin) {
        $options[$id] = $plugin['label'];
      }
      // Only allow the IPE if the storage information is set.
      if (!$this->getStorageType()) {
        unset($options['ipe']);
      }
      $form['builder'] = [
        '#title' => $this->t('Builder'),
        '#type' => 'select',
        '#options' => $options,
        '#default_value' => 'standard',
      ];
    }

    $form['layout'] = [
      '#title' => $this->t('Layout'),
      '#type' => 'select',
      '#options' => Layout::getLayoutOptions(['group_by_category' => TRUE]),
      '#default_value' => $this->configuration['layout'] ?: NULL,
    ];

    if (!empty($this->configuration['layout'])) {
      $form['layout']['#ajax'] = [
        'callback' => [$this, 'layoutSettingsAjaxCallback'],
        'wrapper' => 'layout-settings-wrapper',
        'effect' => 'fade',
      ];

      // If a layout is already selected, show the layout settings.
      $form['layout_settings_wrapper'] = [
        '#type' => 'fieldset',
        '#title' => $this->t('Layout settings'),
        '#prefix' => '<div id="layout-settings-wrapper">',
        '#suffix' => '</div>',
      ];
      $form['layout_settings_wrapper']['layout_settings'] = [];

      // Process callback to configure #parents correctly on settings, since
      // we don't know where in the form hierarchy our settings appear.
      $form['#process'][] = [$this, 'layoutSettingsProcessCallback'];
    }

    return $form;
  }

  /**
   * Render API callback: builds the layout settings elements.
   */
  public function layoutSettingsProcessCallback(array &$element, FormStateInterface $form_state, array &$complete_form) {
    $parents_base = $element['#parents'];
    $layout_parent = array_merge($parents_base, ['layout']);
    $layout_settings_parent = array_merge($parents_base, ['layout_settings']);

    $settings_element =& $element['layout_settings_wrapper']['layout_settings'];

    // Set the #parents on the layout_settings so they end up as a sibling of
    // layout.
    $layout_settings_parents = array_merge($element['#parents'], ['layout_settings']);
    $settings_element['#parents'] = $layout_settings_parents;
    $settings_element['#tree'] = TRUE;

    // Get the layout name in a way that works regardless of whether we're
    // getting the value via AJAX or not.
    $layout_name = NestedArray::getValue($form_state->getUserInput(), $layout_parent) ?: $element['layout']['#default_value'];

    // Place the layout settings on the form if a layout is selected.
    if ($layout_name) {
      $layout = Layout::layoutPluginManager()->createInstance($layout_name, $form_state->getValue($layout_settings_parent, $this->configuration['layout_settings'] ?: []));
      $settings_element = $layout->buildConfigurationForm($settings_element, $form_state);
    }

    // Store the array parents for our element so that we can use it to pull out
    // the layout settings in the validate and submit functions.
    $complete_form['#variant_array_parents'] = $element['#array_parents'];

    return $element;
  }

  /**
   * Render API callback: gets the layout settings elements.
   */
  public function layoutSettingsAjaxCallback(array $form, FormStateInterface $form_state) {
    $variant_array_parents = $form['#variant_array_parents'];
    return NestedArray::getValue($form, array_merge($variant_array_parents, ['layout_settings_wrapper']));
  }

  /**
   * Extracts the layout settings form and form state from the full form.
   *
   * @param array $form
   *   Full form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Full form state.
   *
   * @return array
   *   An array with two values: the new form array and form state object.
   */
  protected function getLayoutSettingsForm(array &$form, FormStateInterface $form_state) {
    $layout_settings_form = NestedArray::getValue($form, array_merge($form['#variant_array_parents'], ['layout_settings_wrapper', 'layout_settings']));
    $layout_settings_form_state = (new FormState())->setValues($form_state->getValue('layout_settings'));
    return [$layout_settings_form, $layout_settings_form_state];
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::validateConfigurationForm($form, $form_state);

    // Validate layout settings.
    if ($form_state->hasValue('layout_settings')) {
      $layout_settings = $this->configuration['layout'] == $form_state->getValue('layout') ? $this->configuration['layout_settings'] : [];
      $layout = $this->layoutManager->createInstance($form_state->getValue('layout'), $layout_settings);
      list ($layout_settings_form, $layout_settings_form_state) = $this->getLayoutSettingsForm($form, $form_state);
      $layout->validateConfigurationForm($layout_settings_form, $layout_settings_form_state);

      // Save the layout plugin for later (so we don't have to instantiate again
      // on submit.
      $form_state->set('layout_plugin', $layout);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);

    if ($form_state->hasValue('layout')) {
      $this->configuration['layout'] = $form_state->getValue('layout');
    }

    // Submit layout settings.
    if ($form_state->hasValue('layout_settings')) {
      $layout_settings = $this->configuration['layout'] == $form_state->getValue('layout') ? $this->configuration['layout_settings'] : [];
      $layout = $form_state->has('layout_plugin') ? $form_state->get('layout_plugin') : $this->layoutManager->createInstance($form_state->getValue('layout'), $layout_settings);
      list ($layout_settings_form, $layout_settings_form_state) = $this->getLayoutSettingsForm($form, $form_state);
      $layout->submitConfigurationForm($layout_settings_form, $layout_settings_form_state);
      $this->configuration['layout_settings'] = $layout->getConfiguration();
    }

    if ($form_state->hasValue('builder')) {
      $this->configuration['builder'] = $form_state->getValue('builder');
    }

    if ($form_state->hasValue('page_title')) {
      $this->configuration['page_title'] = $form_state->getValue('page_title');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function access(AccountInterface $account = NULL) {
    // If no blocks are configured for this variant, deny access.
    if (empty($this->configuration['blocks'])) {
      return FALSE;
    }

    return parent::access($account);
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return parent::defaultConfiguration() + [
      'uuid' => $this->uuidGenerator()->generate(),
      'layout' => '',
      'layout_settings' => [],
      'page_title' => '',
      'storage_type' => '',
      'storage_id' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    if (empty($configuration['uuid'])) {
      $configuration['uuid'] = $this->uuidGenerator()->generate();
    }

    // Make sure blocks are mapped to valid regions, and if not, map them to the
    // first available region. This is a work-around the fact that we're not
    // totally in control of the block placement UI from page_manager.
    // @todo Replace after https://www.drupal.org/node/2550879
    if (!empty($configuration['layout']) && !empty($configuration['blocks'])) {
      $layout_definition = $this->layoutManager->getDefinition($configuration['layout']);
      $valid_regions = $layout_definition['regions'];
      $first_region = array_keys($valid_regions)[0];
      foreach ($configuration['blocks'] as &$block) {
        if (!isset($valid_regions[$block['region']])) {
          $block['region'] = $first_region;
        }
      }
    }

    return parent::setConfiguration($configuration);
  }

  /**
   * Renders the page title and replaces tokens.
   *
   * @param string $page_title
   *   The page title that should be rendered.
   *
   * @return string
   *   The page title after replacing any tokens.
   */
  protected function renderPageTitle($page_title) {
    $data = $this->getContextAsTokenData();
    return $this->token->replace($page_title, $data);
  }

  /**
   * Returns available context as token data.
   *
   * @return array
   *   An array with token data values keyed by token type.
   */
  protected function getContextAsTokenData() {
    $data = array();
    foreach ($this->getContexts() as $context) {
      // @todo Simplify this when token and typed data types are unified in
      //   https://drupal.org/node/2163027.
      if (strpos($context->getContextDefinition()->getDataType(), 'entity:') === 0) {
        $token_type = substr($context->getContextDefinition()->getDataType(), 7);
        if ($token_type == 'taxonomy_term') {
          $token_type = 'term';
        }
        $data[$token_type] = $context->getContextValue();
      }
    }
    return $data;
  }

}
