<?php

/**
 * @file
 * Contains \Drupal\page_manager_ui\Form\SelectionConditionFormBase.
 */

namespace Drupal\page_manager_ui\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\page_manager\PageVariantInterface;

/**
 * Provides a base form for editing and adding a selection condition.
 */
abstract class SelectionConditionFormBase extends ConditionFormBase {

  /**
   * The page variant entity.
   *
   * @var \Drupal\page_manager\PageVariantInterface
   */
  protected $pageVariant;

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, PageVariantInterface $page_variant = NULL, $condition_id = NULL) {
    $this->pageVariant = $page_variant;
    return parent::buildForm($form, $form_state, $condition_id, $page_variant->getContexts());
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $configuration = $this->condition->getConfiguration();
    // If this selection condition is new, add it to the page.
    if (!isset($configuration['uuid'])) {
      $this->pageVariant->addSelectionCondition($configuration);
    }

    // Save the page entity.
    $this->pageVariant->save();

    $form_state->setRedirectUrl($this->pageVariant->toUrl('edit-form'));
  }

}
