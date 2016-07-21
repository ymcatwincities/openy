<?php

/**
 * @file
 * Contains \Drupal\page_manager_ui\Form\StaticContextDeleteForm.
 */

namespace Drupal\page_manager_ui\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\page_manager\PageVariantInterface;

/**
 * Provides a form for deleting an access condition.
 */
class StaticContextDeleteForm extends ConfirmFormBase {

  /**
   * The page variant entity this selection condition belongs to.
   *
   * @var \Drupal\page_manager\PageVariantInterface
   */
  protected $pageVariant;

  /**
   * The static context's machine name.
   *
   * @var array
   */
  protected $staticContext;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'page_manager_static_context_delete_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete the static context %label?', ['%label' => $this->pageVariant->getStaticContext($this->staticContext)['label']]);
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
  public function buildForm(array $form, FormStateInterface $form_state, PageVariantInterface $page_variant = NULL, $name = NULL) {
    $this->pageVariant = $page_variant;
    $this->staticContext = $name;
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    drupal_set_message($this->t('The static context %label has been removed.', ['%label' => $this->pageVariant->getStaticContext($this->staticContext)['label']]));
    $this->pageVariant->removeStaticContext($this->staticContext);
    $this->pageVariant->save();
    $form_state->setRedirectUrl($this->getCancelUrl());
  }

}
