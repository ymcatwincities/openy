<?php

/**
 * @file
 * Contains \Drupal\page_manager_ui\Form\AddVariantContextsForm.
 */

namespace Drupal\page_manager_ui\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\OpenModalDialogCommand;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\ctools\Form\ManageContext;

class AddVariantContextsForm extends ManageContext {

  /**
   * We don't currently support relationships in PM, so don't use them.
   *
   * @var bool
   */
  protected $relationships = FALSE;

  /**
   * Override to add the variant id.
   *
   * {@inheritdoc}
   */
  public function addContext(array &$form, FormStateInterface $form_state) {
    $cached_values = $form_state->getTemporaryValue('wizard');
    /** @var $page_variant \Drupal\page_manager\Entity\PageVariant */
    $page_variant = $cached_values['page_variant'];
    $context = $form_state->getValue('context');
    $content = $this->formBuilder->getForm($this->getContextClass(), $context, $this->getTempstoreId(), $this->machine_name, $page_variant->id());
    $content['#attached']['library'][] = 'core/drupal.dialog.ajax';
    list(, $route_parameters) = $this->getContextOperationsRouteInfo($cached_values, $this->machine_name, $context);
    $content['submit']['#attached']['drupalSettings']['ajax'][$content['submit']['#id']]['url'] = $this->url($this->getContextAddRoute($cached_values), $route_parameters, ['query' => [FormBuilderInterface::AJAX_FORM_REQUEST => TRUE]]);
    $response = new AjaxResponse();
    $response->addCommand(new OpenModalDialogCommand($this->t('Add new context'), $content, array('width' => '700')));
    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'page_manager_variant_context_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getContextClass($cached_values) {
    return AddVariantStaticContextConfigure::class;
  }

  /**
   * {@inheritdoc}
   */
  protected function getRelationshipClass($cached_values) {
    //return AddVariantRelationshipConfigure::class;
  }

  /**
   * {@inheritdoc}
   */
  protected function getContextAddRoute($cached_values) {
    return 'entity.page_variant.add_step_form.context.add';
  }

  /**
   * {@inheritdoc}
   */
  protected function getRelationshipAddRoute($cached_values) {
    return 'entity.page_variant.add_step_form.context.add';
  }

  /**
   * {@inheritdoc}
   */
  protected function getTempstoreId() {
    return 'page_manager.page_variant';
  }

  /**
   * {@inheritdoc}
   */
  protected function getContexts($cached_values) {
    /** @var $page_variant \Drupal\page_manager\Entity\PageVariant */
    $page_variant = $cached_values['page_variant'];
    return $page_variant->getContexts();
  }

  /**
   * {@inheritdoc}
   */
  protected function getContextOperationsRouteInfo($cached_values, $machine_name, $row) {
    /** @var \Drupal\page_manager\PageVariantInterface $page_variant */
    $page_variant = $cached_values['page_variant'];
    return ['entity.page_variant.add_step_form.context', [
      'page' => $page_variant->getPage()->id(),
      'machine_name' => $machine_name,
      'context_id' => $row
    ]];
  }

  /**
   * {@inheritdoc}
   */
  protected function getRelationshipOperationsRouteInfo($cached_values, $machine_name, $row) {
    /** @var \Drupal\page_manager\PageVariantInterface $page_variant */
    $page_variant = $cached_values['page_variant'];
    return ['entity.page_variant.add_step_form.context', [
      'page' => $page_variant->getPage()->id(),
      'machine_name' => $machine_name,
      'context_id' => $row
    ]];
  }

  protected function isEditableContext($cached_values, $row) {
    /** @var \Drupal\page_manager\PageVariantInterface $page_variant */
    $page_variant = $cached_values['page_variant'];
    $page = $page_variant->getPage();
    return empty($page->getContexts()[$row]) && !empty($page_variant->getContexts()[$row]);
  }


}
