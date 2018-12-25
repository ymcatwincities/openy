<?php
/**
 * @file
 * Contains \Drupal\page_manager_ui\Form\PageVariantConfigureForm.
 */

namespace Drupal\page_manager_ui\Form;


use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormState;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

class PageVariantConfigureForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    // @todo this should vary by step/variant plugin id.
    return 'page_manage_variant_configure_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $cached_values = $form_state->getTemporaryValue('wizard');
    /** @var \Drupal\page_manager\PageInterface $page */
    $page = $cached_values['page'];
    /** @var \Drupal\page_manager\PageVariantInterface $page_variant */
    $page_variant = $cached_values['page_variant'];

    $form['page_variant_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#required' => TRUE,
      '#size' => 32,
      '#maxlength' => 255,
      '#default_value' => $page_variant->label(),
    ];

    $variant_plugin = $page_variant->getVariantPlugin();
    $form['variant_settings'] = $variant_plugin->buildConfigurationForm([], (new FormState())->setValues($form_state->getValue('variant_settings', [])));
    $form['variant_settings']['#tree'] = TRUE;

    if (!$page->isNew()) {
      $form['delete'] = [
        '#type' => 'link',
        '#title' => $this->t('Delete this variant'),
        '#attributes' => [
          'class' => ['button', 'use-ajax'],
          'data-dialog-type' => 'modal',
        ],
        '#url' => new Url('entity.page_variant.delete_form', [
          'machine_name' => $page->id(),
          'variant_machine_name' => $page_variant->id(),
        ]),
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $cached_values = $form_state->getTemporaryValue('wizard');
    /** @var $page_variant \Drupal\page_manager\Entity\PageVariant */
    $page_variant = $cached_values['page_variant'];

    $variant_plugin = $page_variant->getVariantPlugin();
    $variant_plugin->validateConfigurationForm($form['variant_settings'], (new FormState())->setValues($form_state->getValue('variant_settings', [])));
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $cached_values = $form_state->getTemporaryValue('wizard');
    /** @var $page \Drupal\page_manager\Entity\Page */
    $page = $cached_values['page'];
    /** @var $page_variant \Drupal\page_manager\Entity\PageVariant */
    $page_variant = $cached_values['page_variant'];
    $variant_plugin = $page_variant->getVariantPlugin();
    $variant_plugin->submitConfigurationForm($form['variant_settings'], (new FormState())->setValues($form_state->getValue('variant_settings', [])));
    $configuration = $variant_plugin->getConfiguration();
    $page_variant->set('variant_settings', $configuration);
    $page_variant->set('label', $form_state->getValue('page_variant_label'));
  }

}
