<?php

/**
 * @file
 * Contains \Drupal\page_manager_ui\Form\AccessConditionFormBase.
 */

namespace Drupal\page_manager_ui\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\page_manager\PageInterface;

/**
 * Provides a base form for editing and adding an access condition.
 */
abstract class AccessConditionFormBase extends ConditionFormBase {

  /**
   * The page entity this condition belongs to.
   *
   * @var \Drupal\page_manager\PageInterface
   */
  protected $page;

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, PageInterface $page = NULL, $condition_id = NULL) {
    $this->page = $page;
    return parent::buildForm($form, $form_state, $condition_id, $page->getContexts());
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $configuration = $this->condition->getConfiguration();
    // If this access condition is new, add it to the page.
    if (!isset($configuration['uuid'])) {
      $this->page->addAccessCondition($configuration);
    }

    // Save the page entity.
    $this->page->save();

    $form_state->setRedirectUrl($this->page->toUrl('edit-form'));
  }

}
