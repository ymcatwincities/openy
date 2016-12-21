<?php

namespace Drupal\panels\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\ctools\Form\AjaxFormTrait;
use Drupal\user\SharedTempStoreFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form for editing a panel variant display's content.
 */
class PanelsContentForm extends FormBase {

  use AjaxFormTrait;

  /**
   * Tempstore factory.
   *
   * @var \Drupal\user\SharedTempStoreFactory
   */
  protected $tempstore;

  /**
   * The tempstore ID.
   *
   * @var string
   */
  protected $tempstore_id;

  /**
   * Constructs a new VariantPluginContentForm.
   *
   * @param \Drupal\user\SharedTempStoreFactory $tempstore
   *   The tempstore factory.
   */
  public function __construct(SharedTempStoreFactory $tempstore) {
    $this->tempstore = $tempstore;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('user.shared_tempstore')
    );
  }

  /**
   * Get the tempstore ID.
   *
   * @return string
   */
  protected function getTempstoreId() {
    return $this->tempstore_id;
  }

  /**
   * Get the tempstore.
   *
   * @return \Drupal\user\SharedTempStore
   */
  protected function getTempstore() {
    return $this->tempstore->get($this->getTempstoreId());
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'panels_block_page_content';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['#attached']['library'][] = 'block/drupal.block';
    $this->tempstore_id = $form_state->getFormObject()->getTempstoreId();
    $cached_values = $form_state->getTemporaryValue('wizard');
    /** @var \Drupal\panels\Plugin\DisplayVariant\PanelsDisplayVariant $variant_plugin */
    $variant_plugin = $cached_values['plugin'];
    // Allow to configure the page title, even when adding a new display.
    // Default to the page label in that case.
    $form['page_title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Page title'),
      '#description' => $this->t('Configure the page title that will be used for this display.'),
      '#default_value' => $variant_plugin->getConfiguration()['page_title'] ?: '',
    ];
    $pattern_plugin = $variant_plugin->getPattern();
    $machine_name = $pattern_plugin->getMachineName($cached_values);

    // Set up the attributes used by a modal to prevent duplication later.
    $attributes = $this->getAjaxAttributes();
    $add_button_attributes = $this->getAjaxButtonAttributes();

    if ($block_assignments = $variant_plugin->getRegionAssignments()) {
      // Build a table of all blocks used by this variant.
      $form['add'] = [
        '#type' => 'link',
        '#title' => $this->t('Add new block'),
        '#url' => $pattern_plugin->getBlockListUrl($this->tempstore_id, $machine_name, NULL, $this->getRequest()->getRequestUri()),
        '#attributes' => $add_button_attributes,
        '#attached' => [
          'library' => [
            'core/drupal.ajax',
          ],
        ],
      ];
      $form['blocks'] = [
        '#type' => 'table',
        '#header' => [
          $this->t('Label'),
          $this->t('Plugin ID'),
          $this->t('Region'),
          $this->t('Weight'),
          $this->t('Operations'),
        ],
        '#attributes' => array(
          'id' => 'blocks',
        ),
        '#empty' => $this->t('There are no regions for blocks.'),
      ];
      // Loop through the blocks per region.
      foreach ($block_assignments as $region => $blocks) {
        // Add a section for each region and allow blocks to be dragged between
        // them.
        $form['blocks']['#tabledrag'][] = [
          'action' => 'match',
          'relationship' => 'sibling',
          'group' => 'block-region-select',
          'subgroup' => 'block-region-' . $region,
          'hidden' => FALSE,
        ];
        $form['blocks']['#tabledrag'][] = [
          'action' => 'order',
          'relationship' => 'sibling',
          'group' => 'block-weight',
          'subgroup' => 'block-weight-' . $region,
        ];
        $form['blocks']['region-' . $region] = [
          '#attributes' => [
            'class' => ['region-title', 'region-title-' . $region],
            'no_striping' => TRUE,
          ],
        ];
        $form['blocks']['region-' . $region]['title'] = [
          '#markup' => $variant_plugin->getRegionName($region),
          '#wrapper_attributes' => [
            'colspan' => 5,
          ],
        ];
        $form['blocks']['region-' . $region . '-message'] = [
          '#attributes' => [
            'class' => [
              'region-message',
              'region-' . $region . '-message',
              empty($blocks) ? 'region-empty' : 'region-populated',
            ],
          ],
        ];
        $form['blocks']['region-' . $region . '-message']['message'] = [
          '#markup' => '<em>' . $this->t('No blocks in this region') . '</em>',
          '#wrapper_attributes' => [
            'colspan' => 5,
          ],
        ];

        /** @var \Drupal\Core\Block\BlockPluginInterface[] $blocks */
        foreach ($blocks as $block_id => $block) {
          $row = [
            '#attributes' => [
              'class' => ['draggable'],
            ],
          ];
          $row['label']['#markup'] = $block->label();
          $row['id']['#markup'] = $block->getPluginId();
          // Allow the region to be changed for each block.
          $row['region'] = [
            '#title' => $this->t('Region'),
            '#title_display' => 'invisible',
            '#type' => 'select',
            '#options' => $variant_plugin->getRegionNames(),
            '#default_value' => $variant_plugin->getRegionAssignment($block_id),
            '#attributes' => [
              'class' => ['block-region-select', 'block-region-' . $region],
            ],
          ];
          // Allow the weight to be changed for each block.
          $configuration = $block->getConfiguration();
          $row['weight'] = [
            '#type' => 'weight',
            '#default_value' => isset($configuration['weight']) ? $configuration['weight'] : 0,
            '#title' => $this->t('Weight for @block block', ['@block' => $block->label()]),
            '#title_display' => 'invisible',
            '#attributes' => [
              'class' => ['block-weight', 'block-weight-' . $region],
            ],
          ];
          // Add the operation links.
          $operations = [];
          $operations['edit'] = [
            'title' => $this->t('Edit'),
            'url' => $pattern_plugin->getBlockEditUrl($this->tempstore_id, $machine_name, $block_id, $this->getRequest()->getRequestUri()),
            'attributes' => $attributes,
          ];
          $operations['delete'] = [
            'title' => $this->t('Delete'),
            'url' => $pattern_plugin->getBlockDeleteUrl($this->tempstore_id, $machine_name, $block_id, $this->getRequest()->getRequestUri()),
            'attributes' => $attributes,
          ];

          $row['operations'] = [
            '#type' => 'operations',
            '#links' => $operations,
          ];
          $form['blocks'][$block_id] = $row;
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
    /** @var \Drupal\page_manager\Plugin\DisplayVariant\PageBlockDisplayVariant $variant_plugin */
    $variant_plugin = $cached_values['plugin'];

    // If the blocks were rearranged, update their values.
    if (!$form_state->isValueEmpty('blocks')) {
      foreach ($form_state->getValue('blocks') as $block_id => $block_values) {
        $variant_plugin->updateBlock($block_id, $block_values);
      }
    }
    // Page Variant title handling.
    if ($form_state->hasValue('page_title')) {
      $configuration = $variant_plugin->getConfiguration();
      $configuration['page_title'] = $form_state->getValue('page_title');
      $variant_plugin->setConfiguration($configuration);
    }
  }

}
