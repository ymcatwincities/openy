<?php

namespace Drupal\panels\Plugin\DisplayVariant;

use Drupal\Component\Render\HtmlEscapedText;
use Drupal\Component\Uuid\UuidInterface;
use Drupal\Core\Block\BlockManager;
use Drupal\Core\Condition\ConditionManager;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\Context\ContextHandlerInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Core\Render\Markup;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Utility\Token;
use Drupal\ctools\Plugin\DisplayVariant\BlockDisplayVariant;
use Drupal\ctools\Plugin\PluginWizardInterface;
use Drupal\layout_plugin\Plugin\Layout\LayoutInterface;
use Drupal\layout_plugin\Plugin\Layout\LayoutPluginManagerInterface;
use Drupal\panels\Form\LayoutChangeRegions;
use Drupal\panels\Form\LayoutChangeSettings;
use Drupal\panels\Form\LayoutPluginSelector;
use Drupal\panels\Plugin\DisplayBuilder\DisplayBuilderInterface;
use Drupal\panels\Plugin\DisplayBuilder\DisplayBuilderManagerInterface;
use Drupal\panels\Plugin\PanelsPattern\PanelsPatternInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a display variant that simply contains blocks.
 *
 * @DisplayVariant(
 *   id = "panels_variant",
 *   admin_label = @Translation("Panels")
 * )
 */
class PanelsDisplayVariant extends BlockDisplayVariant implements PluginWizardInterface {

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
      if (empty($this->configuration['builder'])) {
        $this->builder = $this->builderManager->createInstance('standard', []);
      }
      else {
        $this->builder = $this->builderManager->createInstance($this->configuration['builder'], []);
      }
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
   * Gets the assigned PanelsPattern or falls back to the default pattern.
   *
   * @return \Drupal\panels\Plugin\PanelsPattern\PanelsPatternInterface
   */
  public function getPattern() {
    if (!isset($this->pattern)) {
      if (empty($this->configuration['pattern'])) {
        $this->pattern = \Drupal::service('plugin.manager.panels.pattern')->createInstance('default');
      }
      else {
        $this->pattern = \Drupal::service('plugin.manager.panels.pattern')->createInstance($this->configuration['pattern']);
      }
    }
    return $this->pattern;
  }

  /**
   * Assign the pattern for panels content operations and default contexts.
   *
   * @param mixed string|\Drupal\panels\Plugin\PanelsPattern\PanelsPatternInterface $pattern
   *
   * @return $this
   *
   * @throws \Exception
   *   If $pattern isn't a string or PanelsPatternInterface object.
   */
  public function setPattern($pattern) {
    if ($pattern instanceof PanelsPatternInterface) {
      $this->pattern = $pattern;
      $this->configuration['pattern'] = $pattern->getPluginId();
    }
    elseif (is_string($pattern)) {
      $this->pattern = NULL;
      $this->configuration['pattern'] = $pattern;
    }
    else {
      throw new \Exception("Pattern must be a string or PanelsPatternInterface object");
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
      '#default_value' => !empty($this->configuration['builder']) ? $this->configuration['builder'] : 'standard',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);

    if ($form_state->hasValue('builder')) {
      $this->configuration['builder'] = $form_state->getValue('builder');
    }
    $this->configuration['page_title'] = $form_state->getValue('page_title');
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
  public function getWizardOperations($cached_values) {
    $operations = [];
    $operations['layout'] = [
      'title' => $this->t('Layout'),
      'form' => LayoutPluginSelector::class
    ];
    if (!empty($this->getConfiguration()['layout']) && $cached_values['plugin']->getLayout() instanceof PluginFormInterface) {
      /** @var \Drupal\layout_plugin\Plugin\Layout\LayoutInterface $layout */
      if (empty($cached_values['layout_change']['new_layout'])) {
        $layout = $cached_values['plugin']->getLayout();
        $r = new \ReflectionClass(get_class($layout));
      }
      else {
        $layout_definition = \Drupal::service('plugin.manager.layout_plugin')->getDefinition($cached_values['layout_change']['new_layout']);
        $r = new \ReflectionClass($layout_definition['class']);
      }
      // If the layout uses the LayoutBase::buildConfigurationForm() method we
      // know it is not truly UI configurable, so there's no reason to include
      // the wizard step for displaying that UI.
      $method = $r->getMethod('buildConfigurationForm');
      if ($method->class != 'Drupal\layout_plugin\Plugin\Layout\LayoutBase') {
        $operations['settings'] = [
          'title' => $this->t('Layout Settings'),
          'form' => LayoutChangeSettings::class,
        ];
      }
    }
    if (!empty($cached_values['layout_change']['old_layout'])) {
      $operations['regions'] = [
        'title' => $this->t('Layout Regions'),
        'form' => LayoutChangeRegions::class,
      ];
    }
    /** @var \Drupal\panels\Plugin\DisplayVariant\PanelsDisplayVariant $plugin */
    $plugin = $cached_values['plugin'];
    $builder = $plugin->getBuilder();
    if ($builder instanceof PluginWizardInterface) {
      $operations = array_merge($operations, $builder->getWizardOperations($cached_values));
    }
    return $operations;
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
    // Token replace only escapes replacement values, ensure a consistent
    // behavior by also escaping the input and then returning it as a Markup
    // object to avoid double escaping.
    // @todo: Simplify this when core provides an API for this in
    //   https://www.drupal.org/node/2580723.
    $title = (string) $this->token->replace(new HtmlEscapedText($page_title), $data);
    return Markup::create($title);
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
