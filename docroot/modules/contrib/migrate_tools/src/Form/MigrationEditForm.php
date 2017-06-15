<?php

namespace Drupal\migrate_tools\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Class MigrationEditForm
 *
 * Provides the edit form for our Migration entity.
 *
 * @package Drupal\migrate_tools\Form
 *
 * @ingroup migrate_tools
 */
class MigrationEditForm extends MigrationFormBase {

  /**
   * Returns the actions provided by this form.
   *
   * For the edit form, we only need to change the text of the submit button.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   An associative array containing the current state of the form.
   *
   * @return array
   *   An array of supported actions for the current entity form.
   */
  public function actions(array $form, FormStateInterface $form_state) {
    $actions = parent::actions($form, $form_state);
    $actions['submit']['#value'] = t('Update Migration');

    return $actions;
  }

  /**
   * @param \Drupal\Core\Url $url
   *   The URL associated with an operation.
   *
   * @param $migration_group
   *   The migration's parent group.
   */
  protected function addGroupParameter(Url $url, $migration_group) {
    $route_parameters = $url->getRouteParameters() + array('migration_group' => $migration_group);
    $url->setRouteParameters($route_parameters);
  }

}
