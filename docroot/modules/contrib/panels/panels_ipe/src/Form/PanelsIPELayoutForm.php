<?php

namespace Drupal\panels_ipe\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormState;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\Html;
use Drupal\Core\Layout\LayoutPluginManagerInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Render\RendererInterface;
use Drupal\panels\Plugin\DisplayVariant\PanelsDisplayVariant;
use Drupal\user\SharedTempStoreFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form for configuring a layout for use with the IPE.
 */
class PanelsIPELayoutForm extends FormBase {

  /**
   * @var \Drupal\Core\Render\RendererInterface $renderer
   */
  protected $renderer;

  /**
   * @var \Drupal\user\SharedTempStore
   */
  protected $tempStore;

  /**
   * @var \Drupal\Core\Layout\LayoutPluginManagerInterface
   */
  protected $layoutManager;

  /**
   * The Panels storage manager.
   *
   * @var \Drupal\panels\Plugin\DisplayVariant\PanelsDisplayVariant
   */
  protected $panelsDisplay;

  /**
   * The current layout.
   *
   * @var \Drupal\Core\Layout\LayoutInterface
   */
  protected $layout;

  /**
   * Constructs a new PanelsIPEBlockPluginForm.
   *
   * @param \Drupal\Core\Layout\LayoutPluginManagerInterface $layout_manager
   * @param \Drupal\Core\Render\RendererInterface $renderer
   * @param \Drupal\user\SharedTempStoreFactory $temp_store_factory
   */
  public function __construct(LayoutPluginManagerInterface $layout_manager, RendererInterface $renderer, SharedTempStoreFactory $temp_store_factory) {
    $this->layoutManager = $layout_manager;
    $this->renderer = $renderer;
    $this->tempStore = $temp_store_factory->get('panels_ipe');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.core.layout'),
      $container->get('renderer'),
      $container->get('user.shared_tempstore')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'panels_ipe_layout_form';
  }

  /**
   * Builds a form that configure an existing or new layout for the IPE.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param string $layout_id
   *   The requested Layout ID.
   * @param \Drupal\panels\Plugin\DisplayVariant\PanelsDisplayVariant $panels_display
   *   The current PageVariant ID.
   *
   * @return array
   *   The form structure.
   */
  public function buildForm(array $form, FormStateInterface $form_state, $layout_id = NULL, PanelsDisplayVariant $panels_display = NULL) {
    // We require these default arguments.
    if (!$layout_id || !$panels_display) {
      return FALSE;
    }

    // Save the panels display for later.
    $this->panelsDisplay = $panels_display;

    // Check if this is the current layout, and if not create an instance.
    $layout = $this->panelsDisplay->getLayout();
    $current = $layout->getPluginId() == $layout_id;
    if (!$current) {
      // Create a new layout instance.
      $layout = $this->layoutManager->createInstance($layout_id, []);
    }

    // Save the layout for future use.
    $this->layout = $layout;

    if ($layout instanceof PluginFormInterface) {
      $form['settings'] = $layout->buildConfigurationForm([], $form_state);
    }
    $form['settings']['#tree'] = TRUE;

    // If the form is empty, inform the user or auto-submit if they are changing
    // layouts.
    if (empty(Element::getVisibleChildren($form['settings']))) {
      if ($current) {
        $form['settings'][] = [
          '#markup' => $this->t('<h5>This layout does not provide any configuration.</h5>'),
        ];
      }
      else {
        $this->submitForm($form, $form_state);
      }
    }

    // Add an add button, which is only used by our App.
    $form['submit'] = [
      '#type' => 'button',
      '#value' => $current ? $this->t('Update') : $this->t('Change Layout'),
      '#ajax' => [
        'callback' => '::submitForm',
        'wrapper' => 'panels-ipe-layout-form-wrapper',
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
    if ($this->layout instanceof PluginFormInterface) {
      $layout_form_state = (new FormState())->setValues($form_state->getValue('settings', []));
      $this->layout->validateConfigurationForm($form, $layout_form_state);
      // Update the original form values.
      $form_state->setValue('settings', $layout_form_state->getValues());
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Return early if there are any errors.
    if ($form_state->hasAnyErrors()) {
      return $form;
    }

    $panels_display = $this->panelsDisplay;

    // Submit the layout form.
    if ($this->layout instanceof PluginFormInterface) {
      $layout_form_state = (new FormState())->setValues($form_state->getValue('settings', []));
      $this->layout->submitConfigurationForm($form, $layout_form_state);
    }
    $layout_config = $this->layout->getConfiguration();

    // Shift our blocks to the first available region. The IPE can control
    // re-assigning blocks in a smarter way.
    $first_region = $this->layout->getPluginDefinition()->getDefaultRegion();

    // For each block, set the region to match the new layout.
    foreach ($panels_display->getRegionAssignments() as $region => $region_assignment) {
      /** @var \Drupal\Core\Block\BlockPluginInterface $block */
      foreach ($region_assignment as $block_id => $block) {
        $block_config = $block->getConfiguration();
        // If the new layout does not have a region with the same name, use the
        // first available region.
        if (!isset($region_definitions[$block_config['region']])) {
          $block_config['region'] = $first_region;
          $panels_display->updateBlock($block_id, $block_config);
        }
      }
    }

    // Have our panels display use the new layout.
    $this->panelsDisplay->setLayout($this->layout, $layout_config);

    // Update tempstore.
    $this->tempStore->set($panels_display->getTempStoreId(), $panels_display->getConfiguration());

    $region_data = [];
    $region_content = [];

    // Compile region content and metadata.
    $regions = $panels_display->getRegionAssignments();
    foreach ($regions as $id => $label) {
      // Wrap the region with a class/data attribute that our app can use.
      $region_name = Html::getClass("block-region-$id");
      $region_content[$id] = [
        '#prefix' => '<div class="' . $region_name . '" data-region-name="' . $id . '">',
        '#suffix' => '</div>',
      ];

      // Format region metadata.
      $region_data[] = [
        'name' => $id,
        'label' => $label,
      ];
    }

    $build = $panels_display->getLayout()->build($region_content);
    $form['build'] = $build;

    $data = [
      'id' => $this->layout->getPluginId(),
      'label' => $this->layout->getPluginDefinition()->getLabel(),
      'current' => TRUE,
      'html' => $this->renderer->render($build),
      'regions' => $region_data,
    ];

    // Add Block metadata and HTML as a drupalSetting.
    $form['#attached']['drupalSettings']['panels_ipe']['updated_layout'] = $data;

    return $form;
  }

}
