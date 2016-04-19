<?php

/**
 * @file
 * Contains \Drupal\page_manager_ui\Form\SelectionConditionEditForm.
 */

namespace Drupal\page_manager_ui\Form;

/**
 * Provides a form for editing an selection condition.
 */
class SelectionConditionEditForm extends SelectionConditionFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'page_manager_selection_condition_edit_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function prepareCondition($condition_id) {
    // Load the selection condition directly from the variant.
    return $this->pageVariant->getSelectionCondition($condition_id);
  }

  /**
   * {@inheritdoc}
   */
  protected function submitButtonText() {
    return $this->t('Update selection condition');
  }

  /**
   * {@inheritdoc}
   */
  protected function submitMessageText() {
    return $this->t('The %label selection condition has been updated.', ['%label' => $this->condition->getPluginDefinition()['label']]);
  }

}
