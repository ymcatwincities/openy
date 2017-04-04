<?php

namespace Drupal\panelizer\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\OpenModalDialogCommand;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\TypedData\TypedDataManagerInterface;
use Drupal\ctools\Form\ManageContext;
use Drupal\user\SharedTempStoreFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Simple wizard step form.
 */
class PanelizerWizardContextForm extends ManageContext {

  /**
   * {@inheritdoc}
   */
  protected $relationships = FALSE;

  /**
   * The shared temp store factory.
   *
   * @var \Drupal\user\SharedTempStoreFactory
   */
  protected $tempstoreFactory;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('typed_data_manager'),
      $container->get('form_builder'),
      $container->get('user.shared_tempstore')
    );
  }

  /**
   * ManageContext constructor.
   *
   * @param \Drupal\Core\TypedData\TypedDataManagerInterface $typed_data_manager
   *   The typed data manager.
   * @param \Drupal\Core\Form\FormBuilderInterface $form_builder
   *   The form builder.
   * @param \Drupal\user\SharedTempStoreFactory $tempstore_factory
   *   Shared user tempstore factory.
   */
  public function __construct(TypedDataManagerInterface $typed_data_manager, FormBuilderInterface $form_builder, SharedTempStoreFactory $tempstore_factory) {
    parent::__construct($typed_data_manager, $form_builder);
    $this->tempstoreFactory = $tempstore_factory;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'panelizer_wizard_context_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getContextClass($cached_values) {
    return PanelizerWizardContextConfigure::class;
  }

  /**
   * {@inheritdoc}
   */
  protected function getRelationshipClass($cached_values) {}

  /**
   * {@inheritdoc}
   */
  protected function getContextAddRoute($cached_values) {
    return 'panelizer.wizard.step.context.add';
  }

  /**
   * {@inheritdoc}
   */
  protected function getRelationshipAddRoute($cached_values) {
    return 'panelizer.wizard.step.context.add';
  }

  /**
   * {@inheritdoc}
   */
  protected function getContexts($cached_values) {
    return $cached_values['plugin']->getPattern()->getDefaultContexts($this->tempstoreFactory, $this->getTempstoreId(), $this->machine_name);
  }

  /**
   * {@inheritdoc}
   */
  protected function getTempstoreId() {
    return 'panelizer.wizard';
  }

  /**
   * {@inheritdoc}
   */
  protected function getContextOperationsRouteInfo($cached_values, $machine_name, $row) {
    return ['panelizer.wizard.step.context', [
      'machine_name' => $machine_name,
      'context_id' => $row,
    ]];
  }

  /**
   * {@inheritdoc}
   */
  protected function getRelationshipOperationsRouteInfo($cached_values, $machine_name, $row) {
    return ['panelizer.wizard.step.context', [
      'machine_name' => $machine_name,
      'context_id' => $row,
    ]];
  }

  /**
   * {@inheritdoc}
   */
  protected function isEditableContext($cached_values, $row) {
    if (!isset($cached_values['contexts'][$row])) {
      return FALSE;
    }
    $context = $cached_values['contexts'][$row];
    return !empty($context['value']);
  }

  /**
   * {@inheritdoc}
   */
  public function addContext(array &$form, FormStateInterface $form_state) {
    $cached_values = $form_state->getTemporaryValue('wizard');
    $context = $form_state->getValue('context');
    $content = $this->formBuilder->getForm($this->getContextClass($cached_values), $context, $this->getTempstoreId(), $this->machine_name);
    $content['#attached']['library'][] = 'core/drupal.dialog.ajax';
    list(, $route_parameters) = $this->getContextOperationsRouteInfo($cached_values, $this->machine_name, $context);
    $content['submit']['#attached']['drupalSettings']['ajax'][$content['submit']['#id']]['url'] = $this->url($this->getContextAddRoute($cached_values), $route_parameters, ['query' => [FormBuilderInterface::AJAX_FORM_REQUEST => TRUE]]);
    $response = new AjaxResponse();
    $response->addCommand(new OpenModalDialogCommand($this->t('Add new context'), $content, ['width' => '700']));
    return $response;
  }

}
