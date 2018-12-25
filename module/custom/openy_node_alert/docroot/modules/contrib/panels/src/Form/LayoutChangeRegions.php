<?php

namespace Drupal\panels\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Layout\LayoutPluginManagerInterface;
use Drupal\user\SharedTempStoreFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form for mapping old regions into the regions of a new layout.
 */
class LayoutChangeRegions extends FormBase {

  /**
   * The layout plugin manager.
   *
   * @var \Drupal\Core\Layout\LayoutPluginManagerInterface
   */
  protected $manager;

  /**
   * The tempstore factory.
   *
   * @var \Drupal\user\SharedTempStoreFactory
   */
  protected $tempstore;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.core.layout'),
      $container->get('user.shared_tempstore')
    );
  }

  /**
   * LayoutChangeRegions constructor.
   *
   * @param \Drupal\Core\Layout\LayoutPluginManagerInterface $manager
   *   The layout plugin manager.
   * @param \Drupal\user\SharedTempStoreFactory $tempstore
   *   The tempstore factory.
   */
  public function __construct(LayoutPluginManagerInterface $manager, SharedTempStoreFactory $tempstore) {
    $this->manager = $manager;
    $this->tempstore = $tempstore;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'panels_layout_regions_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $cached_values = $form_state->getTemporaryValue('wizard');

    /* @var $variant_plugin \Drupal\panels\Plugin\DisplayVariant\PanelsDisplayVariant */
    $variant_plugin = $cached_values['plugin'];

    $form['#attached']['library'][] = 'block/drupal.block';

    $form['old_layout'] = [
      '#title' => $this->t('Old Layout'),
      '#type' => 'select',
      '#options' => $this->manager->getLayoutOptions(),
      '#default_value' => $cached_values['layout_change']['old_layout'],
      '#disabled' => TRUE,
    ];

    $form['new_layout'] = [
      '#title' => $this->t('New Layout'),
      '#type' => 'select',
      '#options' => $this->manager->getLayoutOptions(),
      '#default_value' => $cached_values['layout_change']['new_layout'],
      '#disabled' => TRUE,
    ];

    $layout_settings = !empty($cached_values['layout_change']['layout_settings']) ? $cached_values['layout_change']['layout_settings'] : [];
    $old_layout = $this->manager->createInstance($cached_values['layout_change']['old_layout'], []);
    $new_layout = $this->manager->createInstance($cached_values['layout_change']['new_layout'], $layout_settings);

    if ($block_assignments = $variant_plugin->getRegionAssignments()) {
      // Build a table of all blocks used by this variant.

      $form['blocks'] = [
        '#type' => 'table',
        '#header' => [
          $this->t('Label'),
          $this->t('ID'),
          $this->t('Region'),
          $this->t('Weight'),
        ],
        '#attributes' => array(
          'id' => 'blocks',
        ),
        '#empty' => $this->t('There are no regions for blocks.'),
      ];

      // Loop through the blocks per region.
      $new_regions = $new_layout->getPluginDefinition()->getRegionLabels();
      $new_regions['__unassigned__'] = $this->t('Unassigned');

      $regions = [];
      foreach ($old_layout->getPluginDefinition()->getRegions() as $region => $region_definition) {
        if (empty($block_assignments[$region])) {
          continue;
        }
        $label = $region_definition['label'];
        // Prevent region names clashing with new regions.
        $region_id = 'old_'.$region;
        $new_region = isset($new_regions[$region]) ? $region : '__unassigned__';
        $row['label']['#markup'] = $label;
        $row['id']['#markup'] = $region;
        // Allow the region to be changed for each block.
        $row['region'] = [
          '#title' => $this->t('Region'),
          '#title_display' => 'invisible',
          '#type' => 'select',
          '#options' => $new_regions,
          '#default_value' => $new_region,
          '#attributes' => [
            'class' => ['block-region-select', 'block-region-' . $new_region],
          ],
        ];
        // Allow the weight to be changed for each region.
        $row['weight'] = [
          '#type' => 'weight',
          '#default_value' => 0,
          '#title' => $this->t('Weight for @block block', ['@block' => $label]),
          '#title_display' => 'invisible',
          '#attributes' => [
            'class' => ['block-weight', 'block-weight-' . $region],
          ],
        ];
        $form['blocks'][$region_id] = $row;
        $regions[$new_region][] = $region_id;
      }

      foreach ($new_regions as $region => $label) {

        // Add a section for each region and allow blocks to be dragged between
        // them.
        $form['blocks']['region-' . $region] = [
          '#attributes' => [
            'class' => ['region-title', 'region-title-' . $region],
            'no_striping' => TRUE,
          ],
        ];
        $form['blocks']['region-' . $region]['title'] = [
          '#markup' => $label,
          '#wrapper_attributes' => [
            'colspan' => 4,
          ],
        ];
        $form['blocks']['region-' . $region . '-message'] = [
          '#attributes' => [
            'class' => [
              'region-message',
              'region-' . $region . '-message',
              empty($regions[$region]) ? 'region-empty' : 'region-populated',
            ],
          ],
        ];
        if (empty($regions[$region])) {
          $form['blocks']['region-' . $region . '-message']['message'] = [
            '#markup' => '<em>' . $this->t('No blocks in this region') . '</em>',
            '#wrapper_attributes' => [
              'colspan' => 4,
            ],
          ];
        }
      }
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $cached_values = $form_state->getTemporaryValue('wizard');
    /** @var \Drupal\panels\Plugin\DisplayVariant\PanelsDisplayVariant $plugin */
    $plugin = $cached_values['plugin'];
    $blocks = $plugin->getRegionAssignments();
    /**
     * @var string $region
     * @var \Drupal\Core\Block\BlockPluginInterface[] $block_group
     */
    foreach ($blocks as $region => $block_group) {
      foreach ($block_group as $uuid => $block) {
        $new_region = $form_state->getValue(['blocks', 'old_' . $region, 'region']);
        $block->setConfiguration(['region' => $new_region] + $block->getConfiguration());
      }
    }
    $layout_id = !empty($cached_values['layout_change']['new_layout']) ? $cached_values['layout_change']['new_layout'] : $plugin->getConfiguration()['layout'];
    $layout_settings = !empty($cached_values['layout_change']['layout_settings']) ? $cached_values['layout_change']['layout_settings'] : [];
    $plugin->setLayout($layout_id, $layout_settings);
    unset($cached_values['layout_change']);
    $form_state->setTemporaryValue('wizard', $cached_values);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    foreach ($form_state->getValue('blocks') as $old_region => $values) {
      if ($values['region'] == '__unassigned__') {
        $form_state->setErrorByName('blocks][' . $old_region, $this->t('You must assign your old regions to an available new region.'));
      }
    }
  }

}
