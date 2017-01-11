<?php

/**
 * @file
 * Contains \Drupal\page_manager_ui\Form\StaticContextDeleteForm.
 */

namespace Drupal\page_manager_ui\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\ctools\Form\ContextDelete;

/**
 * Provides a form for deleting an access condition.
 */
class StaticContextDeleteForm extends ContextDelete {

  /**
   * The machine-name of the variant.
   *
   * @var string
   */
  protected $variantMachineName;

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
    $cached_values = $this->getTempstore();
    /** @var $page \Drupal\page_manager\PageInterface */
    $page_variant = $this->getPageVariant($cached_values);
    return $this->t('Are you sure you want to delete the static context %label?', ['%label' => $page_variant->getStaticContext($this->context_id)['label']]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    $cached_values = $this->getTempstore();
    /** @var $page \Drupal\page_manager\PageInterface */
    $page = $cached_values['page'];

    if ($page->isNew()) {
      return new Url('entity.page.add_step_form', [
        'machine_name' => $this->machine_name,
        'step' => 'contexts',
      ]);
    }
    else {
      $page_variant = $this->getPageVariant($cached_values);
      return new Url('entity.page.edit_form', [
        'machine_name' => $this->machine_name,
        'step' => 'page_variant__' . $page_variant->id() . '__contexts',
      ]);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $tempstore_id = NULL, $machine_name = NULL, $context_id = NULL, $variant_machine_name = NULL) {
    $this->variantMachineName = $variant_machine_name;
    return parent::buildForm($form, $form_state, $tempstore_id, $machine_name, $context_id);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $cached_values = $this->getTempstore();
    /** @var $page \Drupal\page_manager\PageInterface */
    $page_variant = $this->getPageVariant($cached_values);
    drupal_set_message($this->t('The static context %label has been removed.', ['%label' => $page_variant->getStaticContext($this->context_id)['label']]));
    $page_variant->removeStaticContext($this->context_id);
    $this->setTempstore($cached_values);
    parent::submitForm($form, $form_state);
  }

  /**
   * Get the page variant.
   *
   * @param array $cached_values
   *   The cached values from the wizard.
   *
   * @return \Drupal\page_manager\PageVariantInterface
   */
  protected function getPageVariant($cached_values) {
    if (isset($cached_values['page_variant'])) {
      return $cached_values['page_variant'];
    }

    /** @var $page \Drupal\page_manager\PageInterface */
    $page = $cached_values['page'];
    return $page->getVariant($this->variantMachineName);
  }

}
