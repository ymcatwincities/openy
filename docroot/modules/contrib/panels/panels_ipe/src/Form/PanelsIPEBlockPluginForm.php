<?php

namespace Drupal\panels_ipe\Form;

use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Block\BlockPluginInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormState;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\Context\ContextHandlerInterface;
use Drupal\Core\Plugin\ContextAwarePluginAssignmentTrait;
use Drupal\Core\Plugin\ContextAwarePluginInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Render\RendererInterface;
use Drupal\panels\Plugin\DisplayVariant\PanelsDisplayVariant;
use Drupal\panels_ipe\PanelsIPEBlockRendererTrait;
use Drupal\user\SharedTempStoreFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form for adding a block plugin temporarily using AJAX.
 *
 * Unlike most forms, this never saves a block plugin instance or persists it
 * from state to state. This is only for the initial addition to the Layout.
 */
class PanelsIPEBlockPluginForm extends FormBase {

  use ContextAwarePluginAssignmentTrait;

  use PanelsIPEBlockRendererTrait;

  /**
   * @var \Drupal\Component\Plugin\PluginManagerInterface $blockManager
   */
  protected $blockManager;

  /**
   * @var \Drupal\Core\Render\RendererInterface $renderer
   */
  protected $renderer;

  /**
   * @var \Drupal\user\SharedTempStore
   */
  protected $tempStore;

  /**
   * The Panels storage manager.
   *
   * @var \Drupal\panels\Plugin\DisplayVariant\PanelsDisplayVariant
   */
  protected $panelsDisplay;

  /**
   * Constructs a new PanelsIPEBlockPluginForm.
   *
   * @param \Drupal\Component\Plugin\PluginManagerInterface $block_manager
   * @param \Drupal\Core\Plugin\Context\ContextHandlerInterface $context_handler
   * @param \Drupal\Core\Render\RendererInterface $renderer
   * @param \Drupal\user\SharedTempStoreFactory $temp_store_factory
   */
  public function __construct(PluginManagerInterface $block_manager, ContextHandlerInterface $context_handler, RendererInterface $renderer, SharedTempStoreFactory $temp_store_factory) {
    $this->blockManager = $block_manager;
    $this->contextHandler = $context_handler;
    $this->renderer = $renderer;
    $this->tempStore = $temp_store_factory->get('panels_ipe');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.block'),
      $container->get('context.handler'),
      $container->get('renderer'),
      $container->get('user.shared_tempstore')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'panels_ipe_block_plugin_form';
  }

  /**
   * Builds a form that constructs a unsaved instance of a Block for the IPE.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param string $plugin_id
   *   The requested Block Plugin ID.
   * @param \Drupal\panels\Plugin\DisplayVariant\PanelsDisplayVariant $panels_display
   *   The current PageVariant ID.
   * @param string $uuid
   *   An optional Block UUID, if this is an existing Block.
   *
   * @return array
   *   The form structure.
   */
  public function buildForm(array $form, FormStateInterface $form_state, $plugin_id = NULL, PanelsDisplayVariant $panels_display = NULL, $uuid = NULL) {
    // We require these default arguments.
    if (!$plugin_id || !$panels_display) {
      return FALSE;
    }

    // Save the panels display for later.
    $this->panelsDisplay = $panels_display;

    // Grab the current layout's regions.
    $regions = $panels_display->getRegionNames();

    // If $uuid is present, a block should exist.
    if ($uuid) {
      /** @var \Drupal\Core\Block\BlockBase $block_instance */
      $block_instance = $panels_display->getBlock($uuid);
    }
    else {
      // Create an instance of this Block plugin.
      /** @var \Drupal\Core\Block\BlockBase $block_instance */
      $block_instance = $this->blockManager->createInstance($plugin_id);
    }

    // Determine the current region.
    $block_config = $block_instance->getConfiguration();
    if (isset($block_config['region']) && isset($regions[$block_config['region']])) {
      $region = $block_config['region'];
    }
    else {
      $region = reset($regions);
    }

    // Wrap the form so that our AJAX submit can replace its contents.
    $form['#prefix'] = '<div id="panels-ipe-block-plugin-form-wrapper">';
    $form['#suffix'] = '</div>';

    // Add our various card wrappers.
    $form['flipper'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => 'flipper',
      ],
    ];

    $form['flipper']['front'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => 'front',
      ],
    ];

    $form['flipper']['back'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => 'back',
      ],
    ];

    $form['#attributes']['class'][] = 'flip-container';

    // Get the base configuration form for this block.
    $form['flipper']['front']['settings'] = $block_instance->buildConfigurationForm([], $form_state);
    $form['flipper']['front']['settings']['context_mapping'] = $this->addContextAssignmentElement($block_instance, $this->panelsDisplay->getContexts());
    $form['flipper']['front']['settings']['#tree'] = TRUE;

    // Add the block ID, variant ID to the form as values.
    $form['plugin_id'] = ['#type' => 'value', '#value' => $plugin_id];
    $form['variant_id'] = ['#type' => 'value', '#value' => $panels_display->id()];
    $form['uuid'] = ['#type' => 'value', '#value' => $uuid];

    // Add a select list for region assignment.
    $form['flipper']['front']['settings']['region'] = [
      '#title' => $this->t('Region'),
      '#type' => 'select',
      '#options' => $regions,
      '#required' => TRUE,
      '#default_value' => $region
    ];

    // Add an add button, which is only used by our App.
    $form['submit'] = [
      '#type' => 'button',
      '#value' => $uuid ? $this->t('Update') : $this->t('Add'),
      '#ajax' => [
        'callback' => '::submitForm',
        'wrapper' => 'panels-ipe-block-plugin-form-wrapper',
        'method' => 'replace',
        'progress' => [
          'type' => 'throbber',
          'message' => '',
        ],
      ],
    ];

    // Add a preview button.
    $form['preview'] = [
      '#type' => 'button',
      '#value' => $this->t('Toggle Preview'),
      '#ajax' => [
        'callback' => '::submitPreview',
        'wrapper' => 'panels-ipe-block-plugin-form-wrapper',
        'method' => 'replace',
        'progress' => [
          'type' => 'throbber',
          'message' => '',
        ],
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $block_instance = $this->getBlockInstance($form_state);

    // Validate the block configuration form.
    $block_form_state = (new FormState())->setValues($form_state->getValue('settings'));
    $block_instance->validateConfigurationForm($form, $block_form_state);
    // Update the original form values.
    $form_state->setValue('settings', $block_form_state->getValues());
  }

  /**
   * Executes the block plugin's submit handlers.
   *
   * @param \Drupal\Core\Block\BlockPluginInterface $block_instance
   *   The block instance.
   * @param array $form
   *   The full form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The full form state.
   */
  protected function submitBlock(BlockPluginInterface $block_instance, array $form, FormStateInterface $form_state) {
    $block_form_state = (new FormState())->setValues($form_state->getValue('settings'));
    $block_instance->submitConfigurationForm($form['flipper']['front']['settings'], $block_form_state);
    if ($block_instance instanceof ContextAwarePluginInterface) {
      $block_instance->setContextMapping($block_form_state->getValue('context_mapping', []));
    }
    // Update the original form values.
    $form_state->setValue('settings', $block_form_state->getValues());
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Return early if there are any errors.
    if ($form_state->hasAnyErrors()) {
      return $form;
    }

    // If a temporary configuration for this variant exists, use it.
    $temp_store_key = $this->panelsDisplay->id();
    if ($variant_config = $this->tempStore->get($temp_store_key)) {
      $this->panelsDisplay->setConfiguration($variant_config);
    }

    $block_instance = $this->getBlockInstance($form_state);

    // Submit the block configuration form.
    $this->submitBlock($block_instance, $form, $form_state);

    // Set the block region appropriately.
    $block_config = $block_instance->getConfiguration();
    $block_config['region'] = $form_state->getValue(array('settings', 'region'));

    // Determine if we need to update or add this block.
    if ($uuid = $form_state->getValue('uuid')) {
      $this->panelsDisplay->updateBlock($uuid, $block_config);
    }
    else {
      $uuid = $this->panelsDisplay->addBlock($block_config);
    }

    // Set the tempstore value.
    $this->tempStore->set($this->panelsDisplay->id(), $this->panelsDisplay->getConfiguration());

    // Assemble data required for our App.
    $build = $this->buildBlockInstance($block_instance, $this->panelsDisplay);

    // Bubble Block attributes to fix bugs with the Quickedit and Contextual
    // modules.
    $this->bubbleBlockAttributes($build);

    // Add our data attribute for the Backbone app.
    $build['#attributes']['data-block-id'] = $uuid;

    $plugin_definition = $block_instance->getPluginDefinition();

    $block_model = [
      'uuid' => $uuid,
      'label' => $block_instance->label(),
      'id' => $block_instance->getPluginId(),
      'region' => $block_config['region'],
      'provider' => $block_config['provider'],
      'plugin_id' => $plugin_definition['id'],
      'html' => $this->renderer->render($build),
    ];

    $form['build'] = $build;

    // Add Block metadata and HTML as a drupalSetting.
    $form['#attached']['drupalSettings']['panels_ipe']['updated_block'] = $block_model;

    return $form;
  }

  /**
   * Previews our current Block configuration.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array $form
   *   The form structure.
   */
  public function submitPreview(array &$form, FormStateInterface $form_state) {
    // Return early if there are any errors.
    if ($form_state->hasAnyErrors()) {
      return $form;
    }

    // Get the Block instance.
    $block_instance = $this->getBlockInstance($form_state);

    // Submit the block configuration form.
    $this->submitBlock($block_instance, $form, $form_state);

    // Gather a render array for the block.
    $build = $this->buildBlockInstance($block_instance, $this->panelsDisplay);

    // Disable any nested forms from the render array.
    $build['content'] = $this->removeFormWrapperRecursive($build['content']);

    // Add the preview to the backside of the card and inform JS that we need to
    // be flipped.
    $form['flipper']['back']['preview'] = $build;

    // Add a cleafix element to the end of the preview. This prevents overlaps
    // with nested float elements.
    $build['clearfix'] = [
      '#markup' => '<div class="clearfix"></div>'
    ];

    $form['#attached']['drupalSettings']['panels_ipe']['toggle_preview'] = TRUE;

    return $form;
  }

  /**
   * Loads or creates a Block Plugin instance suitable for rendering or testing.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return \Drupal\Core\Block\BlockPluginInterface
   *   The Block Plugin instance.
   */
  protected function getBlockInstance(FormStateInterface $form_state) {
    // If a UUID is provided, the Block should already exist.
    if ($uuid = $form_state->getValue('uuid')) {
      // If a temporary configuration for this variant exists, use it.
      $temp_store_key = $this->panelsDisplay->id();
      if ($variant_config = $this->tempStore->get($temp_store_key)) {
        $this->panelsDisplay->setConfiguration($variant_config);
      }

      // Load the existing Block instance.
      $block_instance = $this->panelsDisplay->getBlock($uuid);
    }
    else {
      // Create an instance of this Block plugin.
      /** @var \Drupal\Core\Block\BlockBase $block_instance */
      $block_instance = $this->blockManager->createInstance($form_state->getValue('plugin_id'));
    }

    return $block_instance;
  }

  /**
   * Removes the "form" theme wrapper from all nested elements of the given
   * render array.
   *
   * @param array $content
   *   A render array that could potentially contain a nested form.
   *
   * @return array
   *   The potentially modified render array.
   */
  protected function removeFormWrapperRecursive(array $content) {
    if (is_array($content)) {
      // If this block is rendered as a form, we'll need to disable its wrapping
      // element.
      if (isset($content['#theme_wrappers'])
        && ($key = array_search('form', $content['#theme_wrappers'])) !== FALSE) {
        unset($content['#theme_wrappers'][$key]);
      }

      // Perform the same operation on child elements.
      foreach (Element::getVisibleChildren($content) as $key) {
        $content[$key] = $this->removeFormWrapperRecursive($content[$key]);
      }
    }

    return $content;
  }

}
