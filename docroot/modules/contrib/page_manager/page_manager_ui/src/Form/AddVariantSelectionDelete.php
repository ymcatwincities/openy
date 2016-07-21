<?php

/**
 * @file
 * Contains \Drupal\page_manager_ui\Form\AddVariantSelectionDelete.
 */

namespace Drupal\page_manager_ui\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ctools\Form\ConditionDelete;

class AddVariantSelectionDelete extends ConditionDelete {

  /**
   * Get the page variant.
   *
   * @param array $cached_values
   *   The cached values from the wizard.
   *
   * @return \Drupal\page_manager\PageVariantInterface
   */
  protected function getPageVariant($cached_values) {
    return $cached_values['page_variant'];
  }

  /**
   * {@inheritdoc}
   */
  protected function getParentRouteInfo($cached_values) {
    $page_variant = $this->getPageVariant($cached_values);
    return ['entity.page_variant.add_step_form', [
      'page' => $page_variant->getPage()->id(),
      'machine_name' => $this->machine_name,
      'step' => 'selection',
    ]];
  }

  /**
   * {@inheritdoc}
   */
  protected function getConditions($cached_values) {
    $page_variant = $this->getPageVariant($cached_values);
    return $page_variant->get('selection_criteria');
  }

  /**
   * {@inheritdoc}
   */
  protected function setConditions($cached_values, $conditions) {
    $page_variant = $this->getPageVariant($cached_values);
    $page_variant->set('selection_criteria', $conditions);
    return $cached_values;
  }

  /**
   * {@inheritdoc}
   */
  protected function getContexts($cached_values) {
    $page_variant = $this->getPageVariant($cached_values);
    return $page_variant->getContexts();
  }

}
