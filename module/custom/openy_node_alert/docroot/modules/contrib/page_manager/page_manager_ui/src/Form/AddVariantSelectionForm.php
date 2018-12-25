<?php

/**
 * @file
 * Contains \Drupal\page_manager_ui\Form\AddVariantSelectionForm.
 */

namespace Drupal\page_manager_ui\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\OpenModalDialogCommand;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\ctools\Form\ManageConditions;
use Drupal\page_manager_ui\Form\AddVariantSelectionConfigure;

class AddVariantSelectionForm extends ManageConditions {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'page_manager_access_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getConditionClass() {
    return AddVariantSelectionConfigure::class;
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
  protected function getOperationsRouteInfo($cached_values, $machine_name, $row) {
    /** @var \Drupal\page_manager\PageVariantInterface $page_variant */
    $page_variant = $cached_values['page_variant'];
    return ['entity.page_variant.add_step_form.condition', [
      'page' => $page_variant->getPage()->id(),
      'machine_name' => $machine_name,
      'condition' => $row
    ]];
  }

  /**
   * {@inheritdoc}
   */
  protected function getConditions($cached_values) {
    /** @var $page \Drupal\page_manager\Entity\PageVariant */
    $page_variant = $cached_values['page_variant'];
    return $page_variant->get('selection_criteria');
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
  protected function getAddRoute($cached_values) {
    return 'entity.page_variant.add_step_form.condition.add';
  }

  /**
   * {@inheritdoc}
   */
  public function add(array &$form, FormStateInterface $form_state) {
    $cached_values = $form_state->getTemporaryValue('wizard');
    $page_variant = $cached_values['page_variant'];
    $condition = $form_state->getValue('conditions');
    $content = \Drupal::formBuilder()->getForm($this->getConditionClass(), $condition, $this->getTempstoreId(), $this->machine_name, $page_variant->id());
    $content['#attached']['library'][] = 'core/drupal.dialog.ajax';
    list(, $route_parameters) = $this->getOperationsRouteInfo($cached_values, $this->machine_name, $form_state->getValue('conditions'));
    $content['submit']['#attached']['drupalSettings']['ajax'][$content['submit']['#id']]['url'] = $this->url($this->getAddRoute($cached_values), $route_parameters, ['query' => [FormBuilderInterface::AJAX_FORM_REQUEST => TRUE]]);
    $response = new AjaxResponse();
    $response->addCommand(new OpenModalDialogCommand($this->t('Configure Required Context'), $content, array('width' => '700')));
    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $triggering_element = $form_state->getTriggeringElement();
    if ($triggering_element['#value']->getUntranslatedString() != 'Add Condition') {
      return;
    }
    parent::submitForm($form, $form_state);
  }

}
