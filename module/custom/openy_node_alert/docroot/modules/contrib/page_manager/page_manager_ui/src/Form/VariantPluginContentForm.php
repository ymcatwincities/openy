<?php

/**
 * @file
 * Contains Drupal\page_manager_ui\Form\VariantPluginContentForm.
 */

namespace Drupal\page_manager_ui\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\ctools\Form\AjaxFormTrait;
use Drupal\user\SharedTempStoreFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form for editing a variant.
 */
class VariantPluginContentForm extends FormBase {

  use AjaxFormTrait;

  /**
   * Tempstore factory.
   *
   * @var \Drupal\user\SharedTempStoreFactory
   */
  protected $tempstore;

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
    return 'page_manager.block_display';
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
    return 'page_manager_block_page_content';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $cached_values = $form_state->getTemporaryValue('wizard');
    /** @var \Drupal\page_manager\Plugin\DisplayVariant\PageBlockDisplayVariant $variant_plugin */
    $variant_plugin = $cached_values['plugin'];

    // Store the block display plugin so we can get it in our dialogs.
    if (!empty($this->getTempstore()->get($variant_plugin->id())['plugin'])) {
      $variant_plugin->setConfiguration($this->getTempstore()->get($variant_plugin->id())['plugin']->getConfiguration());
      $form_state->setTemporaryValue('wizard', $cached_values);
    }
    $context_definitions = [];
    foreach ($variant_plugin->getContexts() as $context_name => $context) {
      $context_definitions[$context_name] = $context->getContextDefinition();
    }
    $this->getTempstore()->set($variant_plugin->id(), [
      'plugin' => $variant_plugin,
      'access' => $cached_values['access'],
      'contexts' => $context_definitions,
    ]);

    // Set up the attributes used by a modal to prevent duplication later.
    $attributes = $this->getAjaxAttributes();
    $add_button_attributes = $this->getAjaxButtonAttributes();

    if ($block_assignments = $variant_plugin->getRegionAssignments()) {
      // Build a table of all blocks used by this variant.
      $form['add'] = [
        '#type' => 'link',
        '#title' => $this->t('Add new block'),
        '#url' => Url::fromRoute('page_manager.block_display_select_block', [
          'block_display' => $variant_plugin->id(),
          'destination' => $this->getRequest()->getRequestUri(),
        ]),
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
        $form['blocks'][$region] = [
          '#attributes' => [
            'class' => ['region-title', 'region-title-' . $region],
            'no_striping' => TRUE,
          ],
        ];
        $form['blocks'][$region]['title'] = [
          '#markup' => $variant_plugin->getRegionName($region),
          '#wrapper_attributes' => [
            'colspan' => 5,
          ],
        ];
        $form['blocks'][$region . '-message'] = [
          '#attributes' => [
            'class' => [
              'region-message',
              'region-' . $region . '-message',
              empty($blocks) ? 'region-empty' : 'region-populated',
            ],
          ],
        ];
        $form['blocks'][$region . '-message']['message'] = [
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
            'url' => Url::fromRoute('page_manager.block_display_edit_block', [
              'block_display' => $variant_plugin->id(),
              'block_id' => $block_id,
              'destination' => $this->getRequest()->getRequestUri(),
            ]),
            'attributes' => $attributes,
          ];
          $operations['delete'] = [
            'title' => $this->t('Delete'),
            'url' => Url::fromRoute('page_manager.block_display_delete_block', [
              'block_display' => $variant_plugin->id(),
              'block_id' => $block_id,
              'destination' => $this->getRequest()->getRequestUri(),
            ]),
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

    // Remove from the tempstore so we refresh from the database the next time
    // we come here.
    $this->getTempstore()->delete($variant_plugin->id());
  }

}
