<?php
/**
 * @file
 * Contains \Drupal\page_manager_ui\Form\SelectionDelete.
 */

namespace Drupal\page_manager_ui\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ctools\Form\ConditionDelete;

class SelectionDelete extends ConditionDelete {

  /**
   * The machine-name of the variant.
   *
   * @var string
   */
  protected $variantMachineName;

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

  /**
   * {@inheritdoc}
   */
  protected function getParentRouteInfo($cached_values) {
    /** @var $page \Drupal\page_manager\PageInterface */
    $page = $cached_values['page'];

    if ($page->isNew()) {
      return ['entity.page.add_step_form', [
        'machine_name' => $this->machine_name,
        'step' => 'selection',
      ]];
    }
    else {
      $page_variant = $this->getPageVariant($cached_values);
      return ['entity.page.edit_form', [
        'machine_name' => $this->machine_name,
        'step' => 'page_variant__' . $page_variant->id() . '__selection',
      ]];
    }
  }

  /**
   * @inheritDoc
   */
  public function buildForm(array $form, FormStateInterface $form_state, $id = NULL, $tempstore_id = NULL, $machine_name = NULL, $variant_machine_name = NULL) {
    $this->variantMachineName = $variant_machine_name;
    return parent::buildForm($form, $form_state, $id, $tempstore_id, $machine_name);
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
