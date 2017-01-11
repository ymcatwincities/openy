<?php
/**
 * @file
 * Contains \Drupal\page_manager_ui\Form\PageAccessForm.
 */

namespace Drupal\page_manager_ui\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ctools\Form\ManageConditions;
use Drupal\page_manager_ui\Form\AccessConfigure;

class PageAccessForm extends ManageConditions {

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
    return AccessConfigure::class;
  }

  /**
   * {@inheritdoc}
   */
  protected function getTempstoreId() {
    return 'page_manager.page';
  }

  /**
   * {@inheritdoc}
   */
  protected function getOperationsRouteInfo($cached_values, $machine_name, $row) {
    return ['entity.page.condition', ['machine_name' => $machine_name, 'condition' => $row]];
  }

  /**
   * {@inheritdoc}
   */
  protected function getConditions($cached_values) {
    /** @var $page \Drupal\page_manager\Entity\Page */
    $page = $cached_values['page'];
    return $page->get('access_conditions');
  }

  /**
   * {@inheritdoc}
   */
  protected function getContexts($cached_values) {
    /** @var $page \Drupal\page_manager\Entity\Page */
    $page = $cached_values['page'];
    return $page->getContexts();
  }

  /**
   * The route to which condition 'add' actions should submit.
   *
   * @return string
   */
  protected function getAddRoute($cached_values) {
    return 'entity.page.condition.add';
  }


  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $triggering_element = $form_state->getTriggeringElement();
    if ($triggering_element['#value']->getUntranslatedString() == 'Update') {
      return;
    }
    parent::submitForm($form, $form_state);
  }

}
