<?php

/**
 * @file
 * Contains \Drupal\workflow_ui\Controller\WorkflowListBuilder.
 */

namespace Drupal\workflow_ui\Controller;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Config\Entity\ConfigEntityListBuilder;

/**
 * Defines a class to build a listing of Workflow entities.
 *
 * @see \Drupal\workflow\Entity\Workflow
 */
class WorkflowListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    // return 'workflow_form';
    return parent::getFormId();
  }

  /**
   * {@inheritdoc}
   *
   * Building the header and content lines for the contact list.
   *
   * Calling the parent::buildHeader() adds a column for the possible actions
   * and inserts the 'edit' and 'delete' links as defined for the entity type.
   */
  public function buildHeader() {
    $header['id'] = $this->t('ID');
    $header['label'] = $this->t('Label');
    $header['status'] = $this->t('Status');

    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\workflow\Entity\Workflow */
    $row['id'] = $entity->id();
    $row['label'] = $this->getLabel($entity);
    $row['status'] = ''; // TODO $entity->getStatus();

    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultOperations(EntityInterface $entity) {
    $operations = parent::getDefaultOperations($entity);

    /* @var $workflow \Drupal\workflow\Entity\Workflow */
    $workflow = $entity;

    // Do not delete a Workflow if it contains content.
    if (isset($operations['delete']) && !$workflow->isDeletable()) {
      unset ($operations['delete']);
    }

    /**
     * Allow modules to insert their own workflow operations to the list.
     */
    // This is what EntityListBuilder::getOperations() does:
    // $operations = $this->getDefaultOperations($entity);
    // $operations += $this->moduleHandler()->invokeAll('entity_operation', array($entity));
    // $this->moduleHandler->alter('entity_operation', $operations, $entity);

    // In D8, the interface of below hook_workflow_operations has changed a bit.
    // @see EntityListBuilder::getOperations, workflow_operations, workflow.api.php.
    $operations += $this->moduleHandler()->invokeAll('workflow_operations', array('workflow', $workflow));

    return $operations;
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $build = parent::render();

    /**
     * Allow modules to insert their own top_action links to the list, like cleanup module.
     *
     * This is not done anymore via the workflow hook.
     * Instead, for an example:
     *   @see workflow_ui.links.action.yml
     *   @see workflow.api.php under 'hook_workflow_operations'.
     */
    // $top_actions = \Drupal::moduleHandler()
    //   ->invokeAll('workflow_operations', array('top_actions', NULL));
    // $top_actions_args = array(
    //   'links' => $top_actions,
    //   'attributes' => array('class' => array('inline', 'action-links')),
    // );

    return $build;
  }

}
