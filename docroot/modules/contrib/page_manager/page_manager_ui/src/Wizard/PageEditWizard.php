<?php
/**
 * @file
 * Contains \Drupal\page_manager_ui\Wizard\PageEditWizard.
 */

namespace Drupal\page_manager_ui\Wizard;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Display\ContextAwareVariantInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Form\FormInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\ctools\Plugin\PluginWizardInterface;
use Drupal\page_manager\PageInterface;
use Drupal\page_manager\PageVariantInterface;
use Drupal\page_manager_ui\Form\PageVariantConfigureForm;
use Drupal\page_manager_ui\Form\PageVariantContextsForm;
use Drupal\page_manager_ui\Form\PageVariantSelectionForm;

class PageEditWizard extends PageWizardBase {

  /**
   * {@inheritdoc}
   */
  public function getOperations($cached_values) {
    $operations = parent::getOperations($cached_values);

    /** @var $page \Drupal\page_manager\Entity\Page */
    $page = $cached_values['page'];

    if (!empty($page)) {
      // Get variants and re-sort by weight or remove variants if the user
      // has edited the variant.
      $variants = $page->getVariants();
      if (!empty($cached_values['deleted_variants'])) {
        foreach (array_keys($cached_values['deleted_variants']) as $page_variant_id) {
          // @todo There's a bug that adds non-variants to the deleted_variants
          // key in the cached_values. This has something to do with adding a
          // block_page variant to a page in tempstore that's already had a
          // variant previously deleted and then reordering the blocks in a
          // region. It's pretty weird, and as we rebuild that UI, I suspect it
          // will go away, but the keys aren't manipulated, so we use them
          // instead of the entity.
          unset($variants[$page_variant_id]);
        }
      }
      // Suppress errors because of https://bugs.php.net/bug.php?id=50688.
      @uasort($variants, '\Drupal\page_manager\Entity\PageVariant::sort');

      foreach ($variants as $page_variant) {
        $page_variant->setPageEntity($page);
        foreach ($this->getVariantOperations($page_variant, $cached_values) as $name => $operation) {
          $operation['values']['page_variant'] = $page_variant;
          $operation['breadcrumbs'] = [
            $this->t('Variants'),
            $page_variant->label() ?: $this->t('Variant'),
          ];
          $operations['page_variant__' . $page_variant->id() . '__' . $name] = $operation;
        }
      }
    }

    return $operations;
  }

  /**
   * Get operations for the variant.
   *
   * @param \Drupal\page_manager\PageVariantInterface $page_variant
   *   The page variant entity.
   * @param mixed $cached_values
   *   The cached values.
   *
   * @returns array
   */
  protected function getVariantOperations(PageVariantInterface $page_variant, $cached_values) {
    $operations = [];
    $operations['general'] = [
      'title' => $this->t('General'),
      'form' => PageVariantConfigureForm::class,
    ];
    $operations['contexts'] = [
      'title' => $this->t('Contexts'),
      'form' => PageVariantContextsForm::class,
    ];
    $operations['selection'] = [
      'title' => $this->t('Selection criteria'),
      'form' => PageVariantSelectionForm::class,
    ];

    // Add any wizard operations from the plugin itself.
    $variant_plugin = $page_variant->getVariantPlugin();
    if ($variant_plugin instanceof PluginWizardInterface) {
      if ($variant_plugin instanceof ContextAwareVariantInterface) {
        $variant_plugin->setContexts($page_variant->getContexts());
      }
      $cached_values['plugin'] = $variant_plugin;
      foreach ($variant_plugin->getWizardOperations($cached_values) as $name => $operation) {
        $operation['values']['plugin'] = $variant_plugin;
        $operation['submit'][] = '::submitVariantStep';
        $operations[$name] = $operation;
      }
    }

    return $operations;
  }

  /**
   * Get action links for the page.
   *
   * @return array
   *   An array of associative arrays with the following keys:
   *   - title: The link text
   *   - url: A URL object
   */
  protected function getPageActionLinks(PageInterface $page) {
    $links = [];

    $links[] = [
      'title' => $this->t('Delete page'),
      'url' => new Url('entity.page.delete_form', [
        'page' => $this->getMachineName(),
      ]),
    ];

    $links[] = [
      'title' => $this->t('Add variant'),
      'url' => new Url('entity.page_variant.add_form', [
        'page' => $this->getMachineName(),
      ]),
    ];

    $links[] = [
      'title' => $this->t('Reorder variants'),
      'url' => new Url('entity.page.reorder_variants_form', [
        'machine_name' => $this->getMachineName(),
      ]),
    ];

    return $links;
  }

  /**
   * {@inheritdoc}
   */
  protected function customizeForm(array $form, FormStateInterface $form_state) {
    $cached_values = $form_state->getTemporaryValue('wizard');
    /** @var $page \Drupal\page_manager\Entity\Page */
    $page = $cached_values['page'];

    // The page actions.
    $form['wizard_actions'] = [
      '#theme' => 'links',
      '#links' => [],
      '#attributes' => [
        'class' => ['inline'],
      ]
    ];
    foreach ($this->getPageActionLinks($page) as $action) {
      $form['wizard_actions']['#links'][] = $action + [
        'attributes' => [
          'class' => 'use-ajax',
          'data-dialog-type' => 'modal',
          'data-dialog-options' => Json::encode([
            'width' => 700,
          ]),
        ],
      ];
    }

    // The tree of wizard steps.
    $form['wizard_tree'] = [
      '#theme' => ['page_manager_wizard_tree'],
      '#wizard' => $this,
      '#cached_values' => $form_state->getTemporaryValue('wizard'),
    ];

    $form['#theme'] = 'page_manager_wizard_form';
    $form['#attached']['library'][] = 'page_manager_ui/admin';
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(FormInterface $form_object, FormStateInterface $form_state) {
    $cached_values = $form_state->getTemporaryValue('wizard');
    $operation = $this->getOperation($cached_values);

    $actions = [];

    $actions['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Update'),
      '#validate' => [
        '::populateCachedValues',
        [$form_object, 'validateForm'],
      ],
      '#submit' => [
        [$form_object, 'submitForm'],
      ],
    ];

    $actions['update_and_save'] = [
      '#type' => 'submit',
      '#value' => $this->t('Update and save'),
      '#button_type' => 'primary',
      '#validate' => [
        '::populateCachedValues',
        [$form_object, 'validateForm'],
      ],
      '#submit' => [
        [$form_object, 'submitForm'],
      ],
    ];

    $actions['finish'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
      '#validate' => [
        '::populateCachedValues',
        [$form_object, 'validateForm'],
      ],
      '#submit' => [
        [$form_object, 'submitForm'],
      ],
    ];

    $actions['cancel'] = [
      '#type' => 'submit',
      '#value' => $this->t('Cancel'),
      '#submit' => [
        '::clearTempstore'
      ],
    ];

    // Add any submit or validate functions for the step and the global ones.
    foreach (['submit', 'update_and_save', 'finish'] as $button) {
      if (isset($operation['validate'])) {
        $actions[$button]['#validate'] = array_merge($actions[$button]['#validate'], $operation['validate']);
      }
      $actions[$button]['#validate'][] = '::validateForm';
      if (isset($operation['submit'])) {
        $actions[$button]['#submit'] = array_merge($actions[$button]['#submit'], $operation['submit']);
      }
      $actions[$button]['#submit'][] = '::submitForm';
    }
    $actions['update_and_save']['#submit'][] = '::finish';
    $actions['finish']['#submit'][] = '::finish';

    if ($form_state->get('ajax')) {
      $cached_values = $form_state->getTemporaryValue('wizard');
      $ajax_parameters = $this->getNextParameters($cached_values);
      $ajax_parameters['step'] = $this->getStep($cached_values);
      $actions['submit']['#ajax'] = [
        'callback' => '::ajaxSubmit',
        'url' => Url::fromRoute($this->getRouteName(), $ajax_parameters),
        'options' => ['query' => \Drupal::request()->query->all() + [FormBuilderInterface::AJAX_FORM_REQUEST => TRUE]],
      ];
      $actions['update_and_save']['#ajax'] = [
        'callback' => '::ajaxFinish',
        'url' => Url::fromRoute($this->getRouteName(), $ajax_parameters),
        'options' => ['query' => \Drupal::request()->query->all() + [FormBuilderInterface::AJAX_FORM_REQUEST => TRUE]],
      ];
      $actions['finish']['#ajax'] = [
        'callback' => '::ajaxFinish',
        'url' => Url::fromRoute($this->getRouteName(), $ajax_parameters),
        'options' => ['query' => \Drupal::request()->query->all() + [FormBuilderInterface::AJAX_FORM_REQUEST => TRUE]],
      ];
    }

    return $actions;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $cached_values = $form_state->getTemporaryValue('wizard');
    // Normally, the wizard only saves the data when the 'Next' button is
    // clicked, but we want to save the data always when editing.
    $this->getTempstore()->set($this->getMachineName(), $cached_values);
  }

  /**
   * @inheritDoc
   */
  public function finish(array &$form, FormStateInterface $form_state) {
    // Delete any of the variants marked for deletion.
    $cached_values = $form_state->getTemporaryValue('wizard');
    /** @var \Drupal\page_manager\Entity\Page $page */
    $page = $cached_values['page'];
    if (!empty($cached_values['deleted_variants'])) {
      foreach (array_keys($cached_values['deleted_variants']) as $page_variant_id) {
        $page->removeVariant($page_variant_id);
      }
    }

    parent::finish($form, $form_state);
  }

  /**
   * Clears the temporary store.
   *
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   */
  public function clearTempstore(array &$form, FormStateInterface $form_state) {
    $this->getTempstore()->delete($this->getMachineName());
  }

}
