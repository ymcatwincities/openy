<?php

/**
 * @file
 * Contains \Drupal\block_page\Plugin\DisplayVariant\BlockDisplayVariant.
 */

namespace Drupal\block_page_layout\Plugin\DisplayVariant;

use Drupal\Component\Utility\NestedArray;
use Drupal\Component\Uuid\UuidInterface;
use Drupal\Core\Block\BlockManager;
use Drupal\Core\Condition\ConditionManager;
use Drupal\Core\Form\FormState;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\Context\ContextHandlerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Utility\Token;
use Drupal\layout_plugin\Plugin\Layout\LayoutPluginManagerInterface;
use Drupal\page_manager\Plugin\DisplayVariant\PageBlockDisplayVariant;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a page variant that simply contains blocks.
 *
 * @DisplayVariant(
 *   id = "block_page_layout",
 *   admin_label = @Translation("Block page (with Layout plugin integration)")
 * )
 */
class BlockPageLayoutVariant extends PageBlockDisplayVariant {

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
   * Constructs a new BlockPageLayoutVariant.
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
   * @param \Drupal\Component\Uuid\UuidInterface $uuid_generator
   *   The UUID generator.
   * @param \Drupal\Core\Utility\Token $token
   *   The token service.
   * @param \Drupal\Core\Condition\ConditionManager $condition_manager
   *   The condition manager.
   * @param \Drupal\panels\Plugin\DisplayBuilder\DisplayBuilderManagerInterface $builder_manager
   *   The display builder plugin manager.
   * @param \Drupal\layout_plugin\Plugin\Layout\LayoutPluginManagerInterface $layout_manager
   *   The layout plugin manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ContextHandlerInterface $context_handler, AccountInterface $account, UuidInterface $uuid_generator, Token $token, BlockManager $block_manager, ConditionManager $condition_manager, LayoutPluginManagerInterface $layout_manager) {
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
      $container->get('plugin.manager.layout_plugin')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return parent::defaultConfiguration() + [
      'layout' => '',
      'layout_settings' => [],
    ];
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
   * {@inheritdoc}
   */
  public function getRegionNames() {
    return $this->getLayout()->getPluginDefinition()['region_names'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    // Only allow selecting the layout when creating a new variant.
    if (empty($this->configuration['layout'])) {
      $form['layout'] = [
        '#title' => $this->t('Layout'),
        '#type' => 'select',
        '#options' => $this->layoutManager->getLayoutOptions(array('group_by_category' => TRUE)),
      ];
    }
    else {
      $form['layout'] = [
        '#type' => 'value',
        '#value' => $this->configuration['layout'],
      ];

      // If a layout is already selected, show the layout settings.
      $form['layout_settings_wrapper'] = [
        '#type' => 'fieldset',
        '#title' => $this->t('Layout settings'),
      ];
      $form['layout_settings_wrapper']['layout_settings'] = [];

      // Get settings form from layout plugin.
      $layout = $this->layoutManager->createInstance($this->configuration['layout'], $this->configuration['layout_settings'] ?: []);
      $form['layout_settings_wrapper']['layout_settings'] = $layout->buildConfigurationForm($form['layout_settings_wrapper']['layout_settings'], $form_state);

      // Process callback to configure #parents correctly on settings, since
      // we don't know where in the form hierarchy our settings appear.
      $form['#process'][] = [$this, 'layoutSettingsProcessCallback'];
    }

    return $form;
  }

  /**
   * Form API #process callback: expands form with hierarchy information.
   */
  public function layoutSettingsProcessCallback(array &$element, FormStateInterface $form_state, array &$complete_form) {
    $settings_element =& $element['layout_settings_wrapper']['layout_settings'];

    // Set the #parents on the layout_settings so they end up as a sibling of
    // layout.
    $layout_settings_parents = array_merge($element['#parents'], ['layout_settings']);
    $settings_element['#parents'] = $layout_settings_parents;
    $settings_element['#tree'] = TRUE;

    // Store the array parents for our element so that we can use it to pull out
    // the layout settings in the validate and submit functions.
    $complete_form['#variant_array_parents'] = $element['#array_parents'];

    return $element;
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
      $layout = $this->layoutManager->createInstance($form_state->getValue('layout'), $this->configuration['layout_settings']);
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

    $this->configuration['layout'] = $form_state->getValue('layout');

    // Submit layout settings.
    if ($form_state->hasValue('layout_settings')) {
      $layout = $form_state->has('layout_plugin') ? $form_state->get('layout_plugin') : $this->getLayout();
      list ($layout_settings_form, $layout_settings_form_state) = $this->getLayoutSettingsForm($form, $form_state);
      $layout->submitConfigurationForm($layout_settings_form, $layout_settings_form_state);
      $this->configuration['layout_settings'] = $layout->getConfiguration();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildRegions(array $build) {
    $regions = parent::buildRegions($build);
    $layout = $this->getLayout();
    return $layout->build($regions);
  }

}
