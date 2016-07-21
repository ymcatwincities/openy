<?php

/**
 * @file
 * Contains Drupal\page_manager_ui\Form\PageVariantAddForm.
 */

namespace Drupal\page_manager_ui\Form;

use Drupal\Core\Display\VariantManager;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\page_manager\Entity\PageVariant;
use Drupal\page_manager\PageInterface;
use Drupal\user\SharedTempStoreFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form for adding a variant.
 */
class PageVariantAddForm extends FormBase {

  /**
   * The variant manager.
   *
   * @var \Drupal\Core\Display\VariantManager
   */
  protected $variantManager;

  /**
   * Tempstore factory.
   *
   * @var \Drupal\user\SharedTempStoreFactory
   */
  protected $tempstore;

  /**
   * Constructs a new DisplayVariantAddForm.
   *
   * @param \Drupal\Core\Display\VariantManager $variant_manager
   *   The variant manager.
   */
  public function __construct(VariantManager $variant_manager, SharedTempStoreFactory $tempstore) {
    $this->variantManager = $variant_manager;
    $this->tempstore = $tempstore;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.display_variant'),
      $container->get('user.shared_tempstore')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'page_manager_add_variant_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $machine_name = '') {
    $cached_values = $form_state->getTemporaryValue('wizard');
    // The name label for variants is not required and can be changed later.
    $form['name']['label']['#required'] = FALSE;
    $form['name']['label']['#disabled'] = FALSE;

    $variant_plugin_options = [];
    foreach ($this->variantManager->getDefinitions() as $plugin_id => $definition) {
      // The following two variants are provided by Drupal Core. They are not
      // configurable and therefore not compatible with Page Manager but have
      // similar and confusing labels. Skip them so that they are not shown in
      // the UI.
      if (in_array($plugin_id, ['simple_page', 'block_page'])) {
        continue;
      }

      $variant_plugin_options[$plugin_id] = $definition['admin_label'];
    }
    $form['variant_plugin_id'] = [
      '#title' => $this->t('Type'),
      '#type' => 'select',
      '#options' => $variant_plugin_options,
      '#default_value' => !empty($cached_values['variant_plugin_id']) ? $cached_values['variant_plugin_id'] : '',
    ];
    $form['wizard_options'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Optional features'),
      '#description' => $this->t('Check any optional features you need to be presented with forms for configuring them. If you do not check them here you will still be able to utilize these features once the new variant is created.'),
      '#options' => [
        'selection' => $this->t('Selection criteria'),
        'contexts' => $this->t('Contexts'),
      ],
      '#default_value' => !empty($cached_values['wizard_options']) ? $cached_values['wizard_options'] : [],
    ];

    return $form;
  }

  /**
   * Check if a variant id is taken.
   *
   * @param \Drupal\page_manager\PageInterface $page
   *   The page entity.
   * @param string $variant_id
   *   The page variant id to check.
   *
   * @return bool
   *   TRUE if the ID is available; FALSE otherwise.
   */
  protected function variantExists(PageInterface $page, $variant_id) {
    return isset($page->getVariants()[$variant_id]) || PageVariant::load($variant_id);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // If the label is not present or is an empty string.
    if (!$form_state->hasValue('label') || !$form_state->getValue('label')) {
      $cached_values = $form_state->getTemporaryValue('wizard');
      /** @var $page_variant \Drupal\page_manager\Entity\PageVariant */
      $page_variant = $cached_values['page_variant'];
      $plugin = $page_variant->getVariantPlugin();
      /** @var \Drupal\Core\StringTranslation\TranslatableMarkup $admin_label */
      $admin_label = $plugin->getPluginDefinition()['admin_label'];
      $form_state->setValue('label', (string) $admin_label);
    }
    // Currently the parent does nothing, but that could change.
    parent::validateForm($form, $form_state);
  }


  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $cached_values = $form_state->getTemporaryValue('wizard');
    /** @var $page \Drupal\page_manager\Entity\Page */
    $page = $cached_values['page'];
    $variant_plugin_id = $cached_values['variant_plugin_id'] = $form_state->getValue('variant_plugin_id');

    /** @var $page_variant \Drupal\page_manager\Entity\PageVariant */
    $page_variant = $cached_values['page_variant'];
    $page_variant->setVariantPluginId($variant_plugin_id);
    $page_variant->set('label', $form_state->getValue('label'));
    $page_variant->set('page', $page->id());

    // Loop over variant ids until one is available.
    $variant_id_base = "{$page->id()}-{$variant_plugin_id}";
    $key = 0;
    while ($this->variantExists($page, "{$variant_id_base}-{$key}")) {
      $key++;
    }

    $cached_values['id'] = "{$variant_id_base}-{$key}";
    $page_variant->set('id', $cached_values['id']);
    $cached_values['wizard_options'] = $form_state->getValue('wizard_options');
    $form_state->setTemporaryValue('wizard', $cached_values);
  }

}
