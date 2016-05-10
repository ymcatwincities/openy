<?php

namespace Drupal\ymca_workflow;

use Drupal\workflow\Entity\WorkflowManager;
use Drupal\workflow\Entity\WorkflowManagerInterface;
use Drupal\workflow\Entity\WorkflowTransitionInterface;
use Drupal\workflow\Entity\WorkflowScheduledTransition;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Routing\UrlGeneratorInterface;
use Drupal\Core\Routing\UrlGeneratorTrait;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;

/**
 * Manages entity type plugin definitions.
 */
class YmcaWorkflowManager extends WorkflowManager implements WorkflowManagerInterface {

  /**
   * {@inheritdoc}
   */
  public static function executeTransition(WorkflowTransitionInterface $transition, $force = FALSE) {
    if ($force) {
      $transition->force($force);
    }

    $update_entity = (!$transition->isScheduled() && !$transition->isExecuted());

    // Save the (scheduled) transition.
    if ($update_entity) {
      // Update the workflow field of the entity.
      $entity = $transition->getTargetEntity();

      // Here we should always load the latest revision (with the greatest ID).
      $storage = \Drupal::entityManager()->getStorage('node');
      $greatestId = max($storage->revisionIds($entity));
      $entity = $storage->loadRevision($greatestId);

      $field_name = $transition->getFieldName();
      $to_sid = $transition->getToSid();

      // N.B. Align the following functions:
      // - WorkflowDefaultWidget::massageFormValues();
      // - WorkflowManager::executeTransition().
      $entity->$field_name->workflow_transition = $transition;
      $entity->$field_name->value = $to_sid;

      $entity->save();
    }
    else {
      // We create a new transition, or update an existing one.
      // Do not update the entity itself.
      // Validate transition, save in history table and delete from schedule table.
      $to_sid = $transition->execute();
    }

    return $to_sid;
  }

  /**
   * {@inheritdoc}
   */
  public static function executeScheduledTransitionsBetween($start = 0, $end = 0) {
    $clear_cache = FALSE;

    // If the time now is greater than the time to execute a transition, do it.
    foreach (WorkflowScheduledTransition::loadBetween($start, $end) as $scheduled_transition) {
      $field_name = $scheduled_transition->getFieldName();
      $entity = $scheduled_transition->getTargetEntity();

      // Here we should always load the latest revision (with the greatest ID).
      $storage = \Drupal::entityManager()->getStorage('node');
      $greatestId = max($storage->revisionIds($entity));
      $entity = $storage->loadRevision($greatestId);

      // Make sure transition is still valid: the entity must still be in
      // the state it was in, when the transition was scheduled.
      // Scheduling on comments is a testing error, and leads to 'recoverable error'.
      $current_sid = '';
      if ($entity && ($entity->getEntityTypeId() !== 'comment')) {
        $current_sid = workflow_node_current_state($entity, $field_name);
      }

      $proceed_transition = FALSE;
      if ($current_sid == 'workflow_published' || $current_sid == 'workflow_unpublished') {
        $proceed_transition = TRUE;
      }

      if ($proceed_transition || ($current_sid && ($current_sid == $scheduled_transition->getFromSid()))) {

        // If user didn't give a comment, create one.
        $comment = $scheduled_transition->getComment();
        if (empty($comment)) {
          $scheduled_transition->addDefaultComment();
        }

        // Do transition. Force it because user who scheduled was checked.
        // The scheduled transition is not scheduled anymore, and is also deleted from DB.
        // A watchdog message is created with the result.
        $scheduled_transition->schedule(FALSE);
        $scheduled_transition->force(TRUE);
        workflow_execute_transition($scheduled_transition, TRUE);

        if (!$field_name || $proceed_transition) {
          $clear_cache = TRUE;
        }
      }
      else {
        // Entity is not in the same state it was when the transition
        // was scheduled. Defer to the entity's current state and
        // abandon the scheduled transition.
        $scheduled_transition->delete();
      }
    }

    if ($clear_cache) {
      // Clear the cache so that if the transition resulted in a entity
      // being published, the anonymous user can see it.
      Cache::invalidateTags(array('rendered'));
    }
  }

}
