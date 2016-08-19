<?php

/**
 * @file
 * Contains \Drupal\workflow\Plugin\Block\WorkflowTransitionBlock.
 */

namespace Drupal\workflow\Plugin\Block;

use Drupal\block\BlockAccessControlHandler;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Form\FormBuilder;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Url;
use Drupal\taxonomy\Entity\Term;
use Drupal\workflow\Entity\WorkflowTransition;

/**
 * Provides a 'Workflow Transition form' block.
 * Credits to workflow_extensions module.
 *
 * @TODO D8-port: add cache options in configuration.
 *    'cache' => DRUPAL_NO_CACHE, // DRUPAL_CACHE_PER_ROLE will be assumed.
 *
 * @Block(
 *   id = "workflow_transition_form_block",
 *   admin_label = @Translation("Workflow Transition form"),
 *   category = @Translation("Forms")
 * )
 */
class WorkflowTransitionBlock extends BlockBase  {

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account) {
    /* @var $entity EntityInterface */
    if (!$entity = workflow_url_get_entity()) {
      return AccessResult::forbidden();
    }

    // Only show block on entity view page (when default operation = '').
    if ($operation = workflow_url_get_operation()) {
      return AccessResult::forbidden();
    }

    // Only show block if entity has workflow, and user has permission.
    foreach (_workflow_info_fields($entity) as $definition) {
      $type_id = $definition->getSetting('workflow_type');
      if ($account->hasPermission("access $type_id workflow_transition form")) {
        return AccessResult::allowed();
      }
    }

    return AccessResult::forbidden();
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $form = [];

    // Get the entity for this form.
    /* @var $entity EntityInterface */
    if (!$entity = workflow_url_get_entity()) {
      return $form;
    }
    // Get the field name. Avoid error on Node Add page.
    if (!$field_name = workflow_get_field_name($entity)) {
      return $form;
    }

    /*
     * Output: generate the Transition Form.
     */
    // Create a transition, to pass to the form. No need to use setValues().
    $current_sid = workflow_node_current_state($entity, $field_name);
    $transition = WorkflowTransition::create([$current_sid, 'field_name' => $field_name]);
    $transition->setTargetEntity($entity);
    // Add the WorkflowTransitionForm to the page.
    $form = $this->entityFormBuilder()->getForm($transition, 'add');

    return $form;
  }

  /**
   * Retrieves the entity form builder.
   *
   * @return \Drupal\Core\Entity\EntityFormBuilderInterface
   *   The entity form builder.
   */
  protected function entityFormBuilder() {
    return \Drupal::getContainer()->get('entity.form_builder');
  }
}
