<?php
/**
 * @file
 * Contains \Drupal\page_manager_ui\Form\AccessDelete.
 */

namespace Drupal\page_manager_ui\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ctools\Form\ConditionDelete;
use Drupal\page_manager\PageInterface;

class AccessDelete extends ConditionDelete {

  /**
   * {@inheritdoc}
   */
  protected function getParentRouteInfo($cached_values) {
    /** @var $page \Drupal\page_manager\PageInterface */
    $page = $cached_values['page'];

    $route_name = $page->isNew() ? 'entity.page.add_step_form' : 'entity.page.edit_form';
    return [$route_name, [
      'machine_name' => $this->machine_name,
      'step' => 'access',
    ]];
  }

  /**
   * {@inheritdoc}
   */
  protected function getConditions($cached_values) {
    /** @var $page \Drupal\page_manager\PageInterface */
    $page = $cached_values['page'];
    return $page->get('access_conditions');
  }

  /**
   * {@inheritdoc}
   */
  protected function setConditions($cached_values, $conditions) {
    /** @var $page \Drupal\page_manager\PageInterface */
    $page = $cached_values['page'];
    $page->set('access_conditions', $conditions);
    return $cached_values;
  }

  /**
   * {@inheritdoc}
   */
  protected function getContexts($cached_values) {
    /** @var $page \Drupal\page_manager\PageInterface */
    $page = $cached_values['page'];
    return $page->getContexts();
  }

}
