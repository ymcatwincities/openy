<?php

/**
 * @file
 * Contains \Drupal\page_manager_ui\Form\VariantPluginDeleteBlockForm.
 */

namespace Drupal\page_manager_ui\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\page_manager\PageVariantInterface;

/**
 * Provides a form for deleting an access condition.
 */
class VariantPluginDeleteBlockForm extends ConfirmFormBase {

  /**
   * @var \Drupal\ctools\Plugin\BlockVariantInterface
   */
  protected $plugin;

  /**
   * The plugin being configured.
   *
   * @var \Drupal\Core\Block\BlockPluginInterface
   */
  protected $block;

  /**
   * Get the tempstore id.
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
    return \Drupal::service('user.shared_tempstore')->get($this->getTempstoreId());
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'page_manager_variant_delete_block_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete the block %label?', ['%label' => $this->block->label()]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return \Drupal::request()->attributes->get('destination');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Delete');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $block_display = NULL, $block_id = NULL) {
    $this->plugin = $this->getTempstore()->get($block_display)['plugin'];
    $this->block = $this->plugin->getBlock($block_id);
    $form['block_display'] = [
      '#type' => 'value',
      '#value' => $block_display
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->plugin->removeBlock($this->block->getConfiguration()['uuid']);
    $cached_values = $this->getTempstore()->get($form_state->getValue('block_display'));
    $cached_values['plugin'] = $this->plugin;
    $this->getTempstore()->set($form_state->getValue('block_display'), $cached_values);
    drupal_set_message($this->t('The block %label has been removed.', ['%label' => $this->block->label()]));
  }

}
