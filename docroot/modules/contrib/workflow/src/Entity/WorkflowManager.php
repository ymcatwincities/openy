<?php

/**
 * @file
 * Contains Drupal\workflow\Entity\WorkflowManager.
 */

namespace Drupal\workflow\Entity;

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
 *
 */
class WorkflowManager implements WorkflowManagerInterface {
  use StringTranslationTrait;
  use UrlGeneratorTrait;

  /**
   * The entity_type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity query factory.
   *
   * @var \Drupal\Core\Entity\Query\QueryFactory
   */
  protected $queryFactory;

  /**
   * The user settings config object.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $userConfig;

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * Construct the WorkflowManager object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity_type manager service.
   * @param \Drupal\Core\Entity\Query\QueryFactory $query_factory
   *   The entity query factory.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translation service.
   * @param \Drupal\Core\Routing\UrlGeneratorInterface $url_generator
   *   The url generator service.
   *  @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler service.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, QueryFactory $query_factory, ConfigFactoryInterface $config_factory, TranslationInterface $string_translation, UrlGeneratorInterface $url_generator, ModuleHandlerInterface $module_handler, AccountInterface $current_user) {
    $this->entityTypeManager = $entity_type_manager;
    $this->queryFactory = $query_factory;
    $this->userConfig = $config_factory->get('user.settings');
    $this->stringTranslation = $string_translation;
    $this->urlGenerator = $url_generator;
    $this->moduleHandler = $module_handler;
    $this->currentUser = $current_user;
  }

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

      // Make sure transition is still valid: the entity must still be in
      // the state it was in, when the transition was scheduled.
      // Scheduling on comments is a testing error, and leads to 'recoverable error'.
      $current_sid = '';
      if ($entity && ($entity->getEntityTypeId() !== 'comment')) {
        $current_sid = workflow_node_current_state($entity, $field_name);
      }
      if ($current_sid && ($current_sid == $scheduled_transition->getFromSid())) {

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

        if (!$field_name) {
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

  /**
   * {@inheritdoc}
   */
  public static function executeTransitionsOfEntity(EntityInterface $entity) {
    // Avoid this hook on workflow objects.
    if (in_array($entity->getEntityTypeId(), [
      'workflow_type',
      'workflow_state',
      'workflow_config_transition',
      'workflow_transition',
      'workflow_scheduled_transition',
    ])) {
      return;
    }

    $user = workflow_current_user();

    foreach (_workflow_info_fields($entity) as $field_info) {
      $field_name = $field_info->getName();
      /* @var $transition WorkflowTransitionInterface */
      $transition = $entity->$field_name->__get('workflow_transition');
      if (!$transition) {
        // We come from creating an entity via entity_form, with core widget.
        $comment = '';
        $old_sid = workflow_node_previous_state($entity, $field_name);
        if (!$new_sid = $entity->$field_name->value) {
          $workflow = Workflow::load($wid = $field_info->getSetting('workflow_type'));
          $new_sid = $workflow->getFirstSid($entity, $field_name, $user);
        };
        $transition = WorkflowTransition::create([$old_sid, 'field_name' => $field_name]);
        $transition->setValues($new_sid, $user->id(), REQUEST_TIME, $comment, TRUE);
      }
      else {
        // Transition already created in widget.
        // or: we come from WorkflowTransitionForm.
      }

      if ($transition) {
        if ($entity->getEntityTypeId() !== 'comment') {
          // We come from Content edit page, from widget.
          // Set the just-saved entity explicitly. Not necessary for update,
          // but upon insert, the old version didn't have an ID, yet.
          $transition->setTargetEntity($entity);
        }
        $transition->execute();
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function deleteUser(AccountInterface $account) {
    self::cancelUser([], $account, 'user_cancel_delete');
  }

  /**
   * {@inheritdoc}
   */
  public static function cancelUser($edit, AccountInterface $account, $method) {

    switch ($method) {
      case 'user_cancel_block': // Disable the account and keep its content.
      case 'user_cancel_block_unpublish': // Disable the account and unpublish its content.
        // Do nothing.
        break;
      case 'user_cancel_reassign': // Delete the account and make its content belong to the Anonymous user.
      case 'user_cancel_delete': // Delete the account and its content.

        // Update tables for deleted account, move account to user 0 (anon.)
        // ALERT: This may cause previously non-Anonymous posts to suddenly
        // be accessible to Anonymous.

        /**
         * Given a user id, re-assign history to the new user account. Called by user_delete().
         */
        $uid = $account->id();
        $new_uid = 0;

        db_update('workflow_transition_history')
          ->fields(array('uid' => $new_uid))
          ->condition('uid', $uid, '=')
          ->execute();
        db_update('workflow_transition_schedule')
          ->fields(array('uid' => $new_uid))
          ->condition('uid', $uid, '=')
          ->execute();

        break;
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function participateUserRoles(Workflow $workflow) {
    $type_id = $workflow->id();
    foreach (user_roles() as $rid => $role) {
      $perms = array("create $type_id workflow_transition" => 1);
      user_role_change_permissions($rid, $perms);  // <=== Enable Roles.
    }
  }

  /**
   * {@inheritdoc}
   */
//  public function getFields($entity_type_id) {
//    $entity_type = $this->entityTypeManager->getDefinition($entity_type_id);
//    if (!$entity_type->isSubclassOf('\Drupal\Core\Entity\FieldableEntityInterface')) {
//      return array();
//    }
//
//    $map = $this->entityTypeManager->getFieldMapByFieldType('workflow');
//    return isset($map[$entity_type_id]) ? $map[$entity_type_id] : array();
//  }

  /**
   * {@inheritdoc}
   */
  public function getAttachedFields($entity_type_id, $bundle) {
    // Determine the new fields added by Field UI.
    $entityfield_manager = \Drupal::service('entity_field.manager');
    //$extra_fields = $this->entityFieldManager->getExtraFields($entity_type_id, $bundle);
    $base_fields = $entityfield_manager->getBaseFieldDefinitions($entity_type_id, $bundle);
    $ui_fields = $entityfield_manager->getFieldDefinitions($entity_type_id, $bundle);
    $added_fields = array_diff(array_keys($ui_fields), array_keys($base_fields));
    return $added_fields;
  }

  /**
   * {@inheritdoc}
   */
  public static function getCurrentStateId(EntityInterface $entity, $field_name = '') {
    $sid = '';

    if (!$entity) {
      return $sid;
    }

    // If $field_name is not known, yet, determine it.
    $field_name = ($field_name) ? $field_name : workflow_get_field_name($entity, $field_name);
    // If $field_name is found, get more details.
    if (!$field_name || !isset($entity->$field_name)) {
      // Return the initial value.
      return $sid;
    }

    // Normal situation: get the value.
    $sid = $entity->$field_name->value;

    // Entity is new or in preview or there is no current state. Use previous state.
    // (E.g., content was created before adding workflow.)
    if ( !$sid || !empty($entity->isNew()) || !empty($entity->in_preview) ) {
      $sid = self::getPreviousStateId($entity, $field_name);
    }

    return $sid;
  }

  /**
   * {@inheritdoc}
   */
  public static function getPreviousStateId(EntityInterface $entity, $field_name = '') {
    $sid = '';

    if (!$entity) {
      return $sid;
    }

    // If $field_name is not known, yet, determine it.
    $field_name = ($field_name) ? $field_name : workflow_get_field_name($entity, $field_name);
    // If $field_name is found, get more details.
    if (!$field_name) {
      // Return the initial value.
      return $sid;
    }

    // A node may not have a Workflow attached.
    if ($entity->isNew()) {
      // A new Node. D7: $is_new is not set when saving terms, etc.
      $sid = self::getCreationStateId($entity, $field_name);
    }
    else {
      // @todo?: Read the history with an explicit langcode.
      // @todo D8: #2373383 add integration with older revisions via Revisioning module.
      $langcode = ''; // $entity->language()->getId();
      $entity_type = $entity->getEntityTypeId();
      if ($last_transition = WorkflowTransition::loadByProperties($entity_type, $entity->id(), [], $field_name, $langcode, 'DESC')) {
        $sid = $last_transition->getToSid(); // @see #2637092, #2612702
      }
    }

    if (!$sid) {
      // No history found on an existing entity.
      $sid = self::getCreationStateId($entity, $field_name);
    }

    return $sid;
  }

  /**
   * Gets the creation sid for a given $entity and $field_name.
   *
   * Is a helper function for:
   * - workflow_node_current_state()
   * - workflow_node_previous_state()
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   * @param string $field_name
   *
   * @return string $sid
   *   The ID of the creation State for the Workflow of the field.
   */
  private static function getCreationStateId($entity, $field_name) {
    $sid = '';

    $field_config = $entity->get($field_name)->getFieldDefinition();
    $field_storage = $field_config->getFieldStorageDefinition();
    $wid = $field_storage->getSetting('workflow_type');
    $workflow = Workflow::load($wid);

    if ($workflow) {
      $sid = $workflow->getCreationSid();
    }
    else {
      drupal_set_message(t('Workflow !wid cannot be loaded. Contact your system administrator.', array('!wid' => $wid)), 'error');
    }
    return $sid;
  }

  /**
   * {@inheritdoc}
   */
  public static function isOwner(AccountInterface $account, EntityInterface $entity = NULL) {
    $is_owner = FALSE;

    // @todo: Keep below code aligned between WorkflowState, ~Transition, ~TransitionListController
    // Determine if user is owner of the entity.
    $uid = ($account) ? $account->id() : -1;
    // Get the entity's ID and Author ID.
    $entity_id = ($entity) ? $entity->id() : '';
    // Some entities (e.g., taxonomy_term) do not have a uid.
    // $entity_uid = $entity->get('uid'); // isset($entity->uid) ? $entity->uid : 0;
    $entity_uid = (method_exists($entity, 'getOwnerId')) ? $entity->getOwnerId() : -1;

    if (!$entity_id) {
      // This is a new entity. User is author. Add 'author' role to user.
      $is_owner = TRUE;
    }
    elseif (($entity_uid > 0) && ($uid > 0) && ($entity_uid == $uid)) {
      // This is an existing entity. User is author.
      // D8: use "access own" permission. D7: Add 'author' role to user.
      // N.B.: If 'anonymous' is the author, don't allow access to History Tab,
      // since anyone can access it, and it will be published in Search engines.
      $is_owner = TRUE;
    }
    else {
      // This is an existing entity. User is not the author. Do nothing.
    }
    return $is_owner;
  }

}
