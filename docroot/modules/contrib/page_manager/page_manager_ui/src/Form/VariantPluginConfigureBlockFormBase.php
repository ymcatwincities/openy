<?php

/**
 * @file
 * Contains \Drupal\page_manager_ui\Form\VariantPluginConfigureBlockFormBase.
 */

namespace Drupal\page_manager_ui\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormState;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContextAwarePluginAssignmentTrait;
use Drupal\Core\Plugin\ContextAwarePluginInterface;
use Drupal\page_manager\PageVariantInterface;

/**
 * Provides a base form for configuring a block as part of a variant.
 */
abstract class VariantPluginConfigureBlockFormBase extends FormBase {

  use ContextAwarePluginAssignmentTrait;

  /**
   * The page entity.
   *
   * @var \Drupal\page_manager\PageVariantInterface
   */
  protected $pageVariant;

  /**
   * The plugin being configured.
   *
   * @var \Drupal\Core\Block\BlockPluginInterface
   */
  protected $block;

  /**
   * Prepares the block plugin based on the block ID.
   *
   * @param string $block_id
   *   Either a block ID, or the plugin ID used to create a new block.
   *
   * @return \Drupal\Core\Block\BlockPluginInterface
   *   The block plugin.
   */
  abstract protected function prepareBlock($block_id);

  /**
   * Returns the text to use for the submit button.
   *
   * @return string
   *   The submit button text.
   */
  abstract protected function submitText();

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, PageVariantInterface $page_variant = NULL, $block_id = NULL) {
    $this->pageVariant = $page_variant;
    $this->block = $this->prepareBlock($block_id);
    $form_state->set('page_variant_id', $page_variant->id());
    $form_state->set('block_id', $this->block->getConfiguration()['uuid']);

    $form['#tree'] = TRUE;
    $form['settings'] = $this->block->buildConfigurationForm([], $form_state);
    $form['settings']['id'] = [
      '#type' => 'value',
      '#value' => $this->block->getPluginId(),
    ];
    $form['region'] = [
      '#title' => $this->t('Region'),
      '#type' => 'select',
      '#options' => $this->getVariantPlugin()->getRegionNames(),
      '#default_value' => $this->getVariantPlugin()->getRegionAssignment($this->block->getConfiguration()['uuid']),
      '#required' => TRUE,
    ];

    if ($this->block instanceof ContextAwarePluginInterface) {
      $form['context_mapping'] = $this->addContextAssignmentElement($this->block, $this->pageVariant->getContexts());
    }

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->submitText(),
      '#button_type' => 'primary',
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // The page might have been serialized, resulting in a new variant
    // collection. Refresh the block object.
    $this->block = $this->getVariantPlugin()->getBlock($form_state->get('block_id'));

    $settings = (new FormState())->setValues($form_state->getValue('settings'));
    // Call the plugin validate handler.
    $this->block->validateConfigurationForm($form, $settings);
    // Update the original form values.
    $form_state->setValue('settings', $settings->getValues());
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $settings = (new FormState())->setValues($form_state->getValue('settings'));

    // Call the plugin submit handler.
    $this->block->submitConfigurationForm($form, $settings);
    // Update the original form values.
    $form_state->setValue('settings', $settings->getValues());

    if ($this->block instanceof ContextAwarePluginInterface) {
      $this->block->setContextMapping($form_state->getValue('context_mapping', []));
    }

    $this->getVariantPlugin()->updateBlock($this->block->getConfiguration()['uuid'], ['region' => $form_state->getValue('region')]);
    $this->pageVariant->save();

    $form_state->setRedirectUrl($this->pageVariant->toUrl('edit-form'));
  }

  /**
   * Gets the variant plugin for this page variant entity.
   *
   * @return \Drupal\ctools\Plugin\BlockVariantInterface
   */
  protected function getVariantPlugin() {
    return $this->pageVariant->getVariantPlugin();
  }

}
