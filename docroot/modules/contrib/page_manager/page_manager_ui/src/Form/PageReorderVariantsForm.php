<?php

/**
 * @file
 * Contains Drupal\page_manager_ui\Form\PageReorderVariantsForm.
 */

namespace Drupal\page_manager_ui\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\page_manager\Entity\PageVariant;
use Drupal\page_manager\PageInterface;
use Drupal\user\SharedTempStoreFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form for adding a variant.
 */
class PageReorderVariantsForm extends FormBase {

  /**
   * Tempstore factory.
   *
   * @var \Drupal\user\SharedTempStoreFactory
   */
  protected $tempstore;

  /**
   * Constructs a new DisplayVariantAddForm.
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
   * Get the tempstore id.
   *
   * @return string
   */
  protected function getTempstoreId() {
    return 'page_manager.page';
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'page_manager_reorder_variants_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $machine_name = '') {
    $cached_values = $this->tempstore->get($this->getTempstoreId())->get($machine_name);
    $form_state->setTemporaryValue('wizard', $cached_values);
    /** @var $page \Drupal\page_manager\Entity\Page */
    $page = $cached_values['page'];

    $form['variants'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('Label'),
        $this->t('Plugin'),
        $this->t('Weight'),
      ],
      '#empty' => $this->t('There are no variants.'),
      '#tabledrag' => [[
        'action' => 'order',
        'relationship' => 'sibling',
        'group' => 'variant-weight',
      ]],
    ];

    $variants = $page->getVariants();
    // Variants can be resorted, but the getVariants() method is still cached
    // so manually invoke the sorting for this UI.
    @uasort($variants, [$page, 'variantSortHelper']);
    if (!empty($cached_values['deleted_variants'])) {
      foreach ($cached_values['deleted_variants'] as $page_variant) {
        unset($variants[$page_variant->id()]);
      }
    }
    /** @var \Drupal\page_manager\PageVariantInterface $page_variant */
    foreach ($variants as $page_variant) {
      $row = [
        '#attributes' => [
          'class' => ['draggable'],
        ],
      ];
      $row['label']['#markup'] = $page_variant->label();
      $row['id']['#markup'] = $page_variant->getVariantPlugin()->adminLabel();
      $row['weight'] = [
        '#type' => 'weight',
        '#default_value' => $page_variant->getWeight(),
        '#title' => $this->t('Weight for @page_variant variant', ['@page_variant' => $page_variant->label()]),
        '#title_display' => 'invisible',
        '#attributes' => [
          'class' => ['variant-weight'],
        ],
      ];
      $form['variants'][$page_variant->id()] = $row;
    }

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Update'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $cached_values = $form_state->getTemporaryValue('wizard');
    /** @var \Drupal\page_manager\Entity\Page $page */
    $page = $cached_values['page'];

    foreach ($form_state->getValue('variants') as $id => $values) {
      if ($page_variant = $page->getVariant($id)) {
        $page_variant->setWeight($values['weight']);
      }
    }

    $form_state->setRedirect('entity.page.edit_form', [
      'machine_name' => $page->id(),
      'step' => 'general',
    ]);

    $this->tempstore->get($this->getTempstoreId())->set($page->id(), $cached_values);
  }

}
