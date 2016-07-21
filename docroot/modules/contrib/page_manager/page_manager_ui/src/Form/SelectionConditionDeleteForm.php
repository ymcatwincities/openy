<?php

/**
 * @file
 * Contains \Drupal\page_manager_ui\Form\SelectionConditionDeleteForm.
 */

namespace Drupal\page_manager_ui\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\page_manager\PageVariantInterface;

/**
 * Provides a form for deleting a selection condition.
 */
class SelectionConditionDeleteForm extends ConfirmFormBase {

  /**
   * The page entity this selection condition belongs to.
   *
   * @var \Drupal\page_manager\PageVariantInterface
   */
  protected $pageVariant;

  /**
   * The selection condition used by this form.
   *
   * @var \Drupal\Core\Condition\ConditionInterface
   */
  protected $selectionCondition;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'page_manager_selection_condition_delete_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete the selection condition %name?', ['%name' => $this->selectionCondition->getPluginDefinition()['label']]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return $this->pageVariant->toUrl('edit-form');
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
  public function buildForm(array $form, FormStateInterface $form_state, PageVariantInterface $page_variant = NULL, $condition_id = NULL) {
    $this->pageVariant = $page_variant;
    $this->selectionCondition = $page_variant->getSelectionCondition($condition_id);
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->pageVariant->removeSelectionCondition($this->selectionCondition->getConfiguration()['uuid']);
    $this->pageVariant->save();
    drupal_set_message($this->t('The selection condition %name has been removed.', ['%name' => $this->selectionCondition->getPluginDefinition()['label']]));
    $form_state->setRedirectUrl($this->getCancelUrl());
  }

}
