<?php
/**
 * @file
 * Contains \Drupal\page_manager_ui\Form\PageParametersForm.
 */

namespace Drupal\page_manager_ui\Form;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\OpenModalDialogCommand;
use Drupal\Core\Display\ContextAwareVariantInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\Context\ContextDefinition;
use Drupal\Core\Plugin\Context\ContextDefinitionInterface;
use Drupal\Core\TypedData\ComplexDataDefinitionInterface;
use Drupal\Core\TypedData\ListDataDefinitionInterface;
use Drupal\Core\Url;

class PageParametersForm extends FormBase {

  /**
   * @var string
   */
  protected $machine_name;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'page_manager_parameters_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $cached_values = $form_state->getTemporaryValue('wizard');
    $this->machine_name = $cached_values['id'];
    $form['items'] = [
      '#type' => 'markup',
      '#prefix' => '<div id="available-parameters">',
      '#suffix' => '</div>',
      '#theme' => 'table',
      '#header' => [
        $this->t('Machine name'),
        $this->t('Label'),
        $this->t('Type'),
        $this->t('Operations'),
      ],
      '#rows' => $this->renderRows($cached_values),
      '#empty' => $this->t('There are no parameters defined for this page.')
    ];
    return $form;
  }

  protected function renderRows($cached_values) {
    $rows = [];
    /** @var $page \Drupal\page_manager\Entity\Page */
    $page = $cached_values['page'];
    /**
     * @var string $parameter
     */
    foreach ($page->getParameterNames() as $parameter_name) {
      $parameter = $page->getParameter($parameter_name);
      $row = [];
      $row['machine_name'] = $parameter['machine_name'];
      if ($label = $parameter['label']) {
        $row['label'] = $label;
      }
      else {
        $row['type']['colspan'] = 2;
      }
      $row['type']['data'] = $parameter['type'] ?: $this->t('<em>No context assigned</em>');

      list($route_partial, $route_parameters) = $this->getOperationsRouteInfo($cached_values, $cached_values['id'], $parameter_name);
      $build = [
        '#type' => 'operations',
        '#links' => $this->getOperations($route_partial, $route_parameters),
      ];
      $row['operations']['data'] = $build;
      $rows[$parameter_name] = $row;
    }

    return $rows;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $cached_values = $form_state->getTemporaryValue('wizard');
  }

  protected function getOperations($route_name_base, array $route_parameters = array()) {
    $operations['edit'] = array(
      'title' => t('Edit'),
      'url' => new Url($route_name_base . '.edit', $route_parameters),
      'weight' => 10,
      'attributes' => array(
        'class' => ['use-ajax'],
        'data-dialog-type' => 'modal',
        'data-dialog-options' => Json::encode([
          'width' => 700,
        ]),
      ),
    );
    return $operations;
  }

  /**
   * Returns the tempstore id to use.
   *
   * @return string
   */
  protected function getTempstoreId() {
    return 'page_manager.page';
  }

  protected function getOperationsRouteInfo($cached_values, $machine_name, $row) {
    return ['page_manager.parameter', ['machine_name' => $machine_name, 'name' => $row]];
  }

}
