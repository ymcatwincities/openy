<?php

/**
 * @file
 * Contains Drupal\page_manager_ui\Form\PageVariantEditForm.
 */

namespace Drupal\page_manager_ui\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\ctools\Form\AjaxFormTrait;
use Drupal\ctools\Plugin\BlockVariantInterface;

/**
 * Provides a form for editing a variant.
 */
class PageVariantEditForm extends PageVariantFormBase {

  use AjaxFormTrait;

  /**
   * {@inheritdoc}
   */
  protected function submitText() {
    return $this->t('Update variant');
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    if ($this->getVariantPlugin() instanceof BlockVariantInterface) {
      $form['variant_settings']['block_section'] = $this->buildBlockForm();
    }

    $form['selection_section'] = $this->buildSelectionForm();

    $form['context'] = $this->buildContextForm();

    return $form;
  }

  /**
   * Builds the block form for a variant.
   *
   * @return array
   */
  protected function buildBlockForm() {
    $variant_plugin = $this->getVariantPlugin();
    if (!$variant_plugin instanceof BlockVariantInterface) {
      return [];
    }

    /** @var \Drupal\page_manager\PageVariantInterface $page_variant */
    $page_variant = $this->getEntity();

    // Set up the attributes used by a modal to prevent duplication later.
    $attributes = $this->getAjaxAttributes();
    $add_button_attributes = $this->getAjaxButtonAttributes();

    $form = [];
    if ($block_assignments = $variant_plugin->getRegionAssignments()) {
      // Build a table of all blocks used by this variant.
      $form = [
        '#type' => 'details',
        '#title' => $this->t('Blocks'),
        '#open' => TRUE,
      ];
      $form['add'] = [
        '#type' => 'link',
        '#title' => $this->t('Add new block'),
        '#url' => Url::fromRoute('page_manager.variant_select_block', [
          'page' => $page_variant->get('page'),
          'page_variant' => $page_variant->id(),
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
        // @todo This should utilize https://drupal.org/node/2065485.
        '#parents' => ['variant_plugin', 'blocks'],
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
            'url' => Url::fromRoute('page_manager.variant_edit_block', [
              'page' => $page_variant->get('page'),
              'page_variant' => $page_variant->id(),
              'block_id' => $block_id,
            ]),
            'attributes' => $attributes,
          ];
          $operations['delete'] = [
            'title' => $this->t('Delete'),
            'url' => Url::fromRoute('page_manager.variant_delete_block', [
              'page' => $page_variant->get('page'),
              'page_variant' => $page_variant->id(),
              'block_id' => $block_id,
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
   * Builds the selection form for a variant.
   *
   * @return array
   */
  protected function buildSelectionForm() {
    // Set up the attributes used by a modal to prevent duplication later.
    $attributes = $this->getAjaxAttributes();
    $add_button_attributes = $this->getAjaxButtonAttributes();

    /** @var \Drupal\page_manager\PageVariantInterface $page_variant */
    $page_variant = $this->getEntity();

    // Selection conditions.
    $form = [
      '#type' => 'details',
      '#title' => $this->t('Selection Conditions'),
      '#open' => TRUE,
    ];
    $form['add'] = [
      '#type' => 'link',
      '#title' => $this->t('Add new selection condition'),
      '#url' => Url::fromRoute('page_manager.selection_condition_select', [
        'page' => $page_variant->get('page'),
        'page_variant' => $page_variant->id(),
      ]),
      '#attributes' => $add_button_attributes,
      '#attached' => [
        'library' => [
          'core/drupal.ajax',
        ],
      ],
    ];
    $form['table'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('Label'),
        $this->t('Description'),
        $this->t('Operations'),
      ],
      '#empty' => $this->t('There are no selection conditions.'),
    ];

    $form['selection_logic'] = [
      '#type' => 'radios',
      '#options' => [
        'and' => $this->t('All conditions must pass'),
        'or' => $this->t('Only one condition must pass'),
      ],
      '#default_value' => $page_variant->getSelectionLogic(),
    ];

    $form['selection'] = [
      '#tree' => TRUE,
    ];
    foreach ($page_variant->getSelectionConditions() as $selection_id => $selection_condition) {
      $row = [];
      $row['label']['#markup'] = $selection_condition->getPluginDefinition()['label'];
      $row['description']['#markup'] = $selection_condition->summary();
      $operations = [];
      $operations['edit'] = [
        'title' => $this->t('Edit'),
        'url' => Url::fromRoute('page_manager.selection_condition_edit', [
          'page' => $page_variant->get('page'),
          'page_variant' => $page_variant->id(),
          'condition_id' => $selection_id,
        ]),
        'attributes' => $attributes,
      ];
      $operations['delete'] = [
        'title' => $this->t('Delete'),
        'url' => Url::fromRoute('page_manager.selection_condition_delete', [
          'page' => $page_variant->get('page'),
          'page_variant' => $page_variant->id(),
          'condition_id' => $selection_id,
        ]),
        'attributes' => $attributes,
      ];
      $row['operations'] = [
        '#type' => 'operations',
        '#links' => $operations,
      ];
      $form['table'][$selection_id] = $row;
    }

    return $form;
  }

  /**
   * Builds the context form for a variant.
   *
   * @return array
   */
  protected function buildContextForm() {
    /** @var \Drupal\page_manager\PageVariantInterface $page_variant */
    $page_variant = $this->getEntity();

    // Set up the attributes used by a modal to prevent duplication later.
    $attributes = $this->getAjaxAttributes();
    $add_button_attributes = $this->getAjaxButtonAttributes();

    $form = [
      '#type' => 'details',
      '#title' => $this->t('Available context'),
      '#open' => TRUE,
    ];
    $form['add'] = [
      '#type' => 'link',
      '#title' => $this->t('Add new static context'),
      '#url' => Url::fromRoute('page_manager.static_context_add', [
        'page' => $page_variant->get('page'),
        'page_variant' => $page_variant->id(),
      ]),
      '#attributes' => $add_button_attributes,
      '#attached' => [
        'library' => [
          'core/drupal.ajax',
        ],
      ],
    ];
    $form['available_context'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('Label'),
        $this->t('Name'),
        $this->t('Type'),
        $this->t('Operations'),
      ],
      '#empty' => $this->t('There is no available context.'),
    ];
    $contexts = $page_variant->getContexts();
    foreach ($contexts as $name => $context) {
      $context_definition = $context->getContextDefinition();

      $row = [];
      $row['label'] = [
        '#markup' => $context_definition->getLabel(),
      ];
      $row['machine_name'] = [
        '#markup' => $name,
      ];
      $row['type'] = [
        '#markup' => $context_definition->getDataType(),
      ];

      // Add operation links if the context is a static context.
      $operations = [];
      if ($page_variant->getStaticContext($name)) {
        $operations['edit'] = [
          'title' => $this->t('Edit'),
          'url' => Url::fromRoute('page_manager.static_context_edit', [
            'page' => $page_variant->get('page'),
            'page_variant' => $page_variant->id(),
            'name' => $name,
          ]),
          'attributes' => $attributes,
        ];
        $operations['delete'] = [
          'title' => $this->t('Delete'),
          'url' => Url::fromRoute('page_manager.static_context_delete', [
            'page' => $page_variant->get('page'),
            'page_variant' => $page_variant->id(),
            'name' => $name,
          ]),
          'attributes' => $attributes,
        ];
      }
      $row['operations'] = [
        '#type' => 'operations',
        '#links' => $operations,
      ];

      $form['available_context'][$name] = $row;
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    // @todo This feels very wrong.
    $variant_plugin = $this->getVariantPlugin();
    if ($variant_plugin instanceof BlockVariantInterface) {
      // If the blocks were rearranged, update their values.
      if (!$form_state->isValueEmpty(['variant_plugin', 'blocks'])) {
        foreach ($form_state->getValue(['variant_plugin', 'blocks']) as $block_id => $block_values) {
          $variant_plugin->updateBlock($block_id, $block_values);
        }
      }
    }

    parent::save($form, $form_state);

    $form_state->setRedirect('entity.page.edit_form', ['page' => $this->entity->get('page')]);
  }

}
