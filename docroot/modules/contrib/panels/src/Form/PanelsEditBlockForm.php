<?php

namespace Drupal\panels\Form;

/**
 * Provides a form for editing a block plugin of a variant.
 */
class PanelsEditBlockForm extends PanelsBlockConfigureFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'panels_edit_block_form';
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
