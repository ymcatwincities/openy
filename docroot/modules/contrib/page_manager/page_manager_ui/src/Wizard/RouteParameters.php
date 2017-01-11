<?php
/**
 * @file
 * Contains \Drupal\page_manager_ui\Wizard\RouteParameters.
 */

namespace Drupal\page_manager_ui\Wizard;


use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CloseModalDialogCommand;
use Drupal\Core\Ajax\RedirectCommand;
use Drupal\Core\Form\FormStateInterface;
use Drupal\ctools\Ajax\OpenModalWizardCommand;
use Drupal\ctools\Wizard\FormWizardBase;
use Drupal\page_manager_ui\Form\ParameterAssignContextForm;
use Drupal\page_manager_ui\Form\ParameterSettingsForm;

class RouteParameters extends FormWizardBase {

  /**
   * The parameter to configure.
   *
   * @var string
   */
  protected $parameter;

  /**
   * {@inheritdoc}
   */
  public function getOperations() {
    return [
      'assign' => [
        'title' => $this->t('Assign Parameter Context'),
        'form' => ParameterAssignContextForm::class,
      ],
      'settings' => [
        'title' => $this->t('Parameter Settings'),
        'form' => ParameterSettingsForm::class,
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getRouteName() {
    return 'page_manager.route.parameters.configure';
  }

  /**
   * Override to get the parameter from the URL and make it available to steps.
   */
  public function buildForm(array $form, FormStateInterface $form_state, $parameter = NULL) {
    $this->parameter = $parameter;
    return parent::buildForm($form, $form_state);
  }

  public function getNextParameters($cached_values) {
    $parameters = parent::getNextParameters($cached_values);
    $parameters['parameter'] = $this->parameter;
    return $parameters;
  }

  public function getPreviousParameters($cached_values) {
    $parameters = parent::getPreviousParameters($cached_values);
    $parameters['parameter'] = $this->parameter;
    return $parameters;
  }

  /**
   * Save the values to the tempstore.
   */
  public function finish(array &$form, FormStateInterface $form_state) {
    $this->getTempstore()->set($this->getMachineName(), $form_state->getTemporaryValue('wizard'));
  }

  public function ajaxFinish(array $form, FormStateInterface $form_state) {
    $cached_values = $form_state->getTemporaryValue('wizard');
    $response = new AjaxResponse();
    $response->addCommand(new RedirectCommand($this->url('entity.page.edit_form', ['machine_name' => $cached_values['id'], 'step' => 'parameters'])));
    $response->addCommand(new CloseModalDialogCommand());
    return $response;
  }

}
