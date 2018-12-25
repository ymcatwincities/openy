<?php

/**
 * @file
 * Contains \Drupal\page_manager_ui\Form\VariantPluginEditBlockForm.
 */

namespace Drupal\page_manager_ui\Form;

/**
 * Provides a form for editing a block plugin of a variant.
 */
class VariantPluginEditBlockForm extends VariantPluginConfigureBlockFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'page_manager_variant_edit_block_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function prepareBlock($block_id) {
    return $this->getVariantPlugin()->getBlock($block_id);
  }

  /**
   * {@inheritdoc}
   */
  protected function submitText() {
    return $this->t('Update block');
  }

}
