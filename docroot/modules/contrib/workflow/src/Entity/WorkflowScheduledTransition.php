<?php

/**
 * @file
 * Contains Drupal\workflow\Entity\WorkflowScheduledTransition.
 *
 * Implements (scheduled/executed) state transitions on entities.
 */

namespace Drupal\workflow\Entity;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\EntityConstraintViolationList;

/**
 * Implements a scheduled transition, as shown on Workflow form.
 *
 * @ContentEntityType(
 *   id = "workflow_scheduled_transition",
 *   label = @Translation("Workflow scheduled transition"),
 *   bundle_label = @Translation("Workflow type"),
 *   module = "workflow",
 *   handlers = {
 *     "access" = "Drupal\workflow\WorkflowAccessControlHandler",
 *     "list_builder" = "Drupal\workflow\WorkflowTransitionListBuilder",
 *     "views_data" = "Drupal\workflow\WorkflowScheduledTransitionViewsData",
 *   },
 *   base_table = "workflow_transition_schedule",
 *   data_table = "workflow_transition_field_data",
 *   fieldable = TRUE,
 *   translatable = FALSE,
 *   entity_keys = {
 *     "id" = "tid",
 *     "bundle" = "wid",
 *     "langcode" = "langcode",
 *   },
 *   links = {
 *     "canonical" = "/workflow_transition/{workflow_transition}",
 *     "delete-form" = "/workflow_transition/{workflow_transition}/delete",
 *     "edit-form" = "/workflow_transition/{workflow_transition}/edit",
 *   },
 * )
 */
class WorkflowScheduledTransition extends WorkflowTransition {

  /**
   * Constructor.
   */
  public function __construct(array $values = array(), $entityType = 'WorkflowScheduledTransition', $bundle = FALSE, $translations = array()) {
    // Please be aware that $entity_type and $entityType are different things!
    parent::__construct($values, $entityType, $bundle, $translations);

    // This transition is scheduled.
    $this->is_scheduled = TRUE;
    // This transition is not executed.
    $this->is_executed = FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function setValues($to_sid, $uid = NULL, $scheduled = REQUEST_TIME, $comment = '') {
    parent::setValues($to_sid, $uid, $scheduled, $comment);
  }

  /**
   * {@inheritdoc}
   *
   * This is a hack to avoid the following error, because ScheduledTransition is not a bundle of Workflow:
   *   Drupal\Component\Plugin\Exception\PluginNotFoundException: The "entity:workflow_scheduled_transition:eerste" plugin does not exist. in Drupal\Core\Plugin\DefaultPluginManager->doGetDefinition() (line 60 of core\lib\Drupal\Component\Plugin\Discovery\DiscoveryTrait.php).
   */
  function validate() {
    // return parent::validate();
    $this->validated = TRUE;
    // $constraints = $this->getTypedData()->getConstraints();
    // $violations = $this->getTypedData()->validate();
    $violations = NULL; // new \Traversable();
    // return new EntityConstraintViolationList($this, iterator_to_array($violations));
    return new EntityConstraintViolationList($this, iterator_to_array($violations));
  }

  /**
   * CRUD functions.
   */

  /**
   * {@inheritdoc}
   *
   * Saves a scheduled transition. If the transition is executed, save in history.
   */
  public function save() {

    // If executed, save in history.
    if ($this->is_executed) {
      // Be careful, we are not a WorkflowScheduleTransition anymore!
      // No fuzzling around, just copy the ScheduledTransition to a normal one.
      $current_sid = $this->getFromSid();
      $field_name = $this->getFieldName();
      $executed_transition = WorkflowTransition::create([$current_sid, 'field_name' => $field_name]);
      $executed_transition->setTargetEntity($this->getTargetEntity());
      $executed_transition->setValues($this->getToSid(), $this->getOwnerId(), REQUEST_TIME, $this->getComment());
      return $executed_transition->save();  // <-- exit !!
    }

    $hid = $this->id();
    if (!$hid) {
      // Insert the transition. Make sure it hasn't already been inserted.
      // @todo: Allow a scheduled transition per revision.
      $entity = $this->getTargetEntity();
      $found_transition = self::loadByProperties($entity->getEntityTypeId(), $entity->id(), [], $this->getFieldName(), $this->getLangcode());
      if ($found_transition) {
        // Avoid duplicate entries.
        $found_transition->delete();
        $result = parent::save();
      }
      else {
        $result = parent::save();
      }
    }
    else {
      workflow_debug(__FILE__, __FUNCTION__, __LINE__ );  // @todo D8-port: still test this snippet.
      // Update the transition.
      $result = parent::save();
    }

    // Create user message.
    if ($state = $this->getToState()) {
      $entity = $this->getTargetEntity();
      $message = '%entity_title scheduled for state change to %state_name on %scheduled_date';
      $args = array(
        '%entity_title' => $entity->label(),
        '%state_name' => $state->label(),
        '%scheduled_date' => $this->getTimestampFormatted(),
        'link' => ($this->getTargetEntityId()) ? $this->getTargetEntity()->link(t('View')) : '',
      );
      \Drupal::logger('workflow')->notice($message, $args);
      drupal_set_message(t($message, $args));
    }

    return $result;
  }

  /**
   * {@inheritdoc}
   */
//  public static function loadMultiple(array $ids = NULL) {
//    return parent::loadMultiple($ids);
//  }

  /**
   * {@inheritdoc}
   */
  public static function loadByProperties($entity_type, $entity_id, array $revision_ids = [], $field_name = '', $langcode = '', $sort = 'ASC', $transition_type = 'workflow_scheduled_transition') {
    // N.B. $transition_type is set as parameter default.
    return parent::loadByProperties($entity_type, $entity_id, $revision_ids, $field_name, $langcode, $sort, $transition_type);
  }

  /**
   * {@inheritdoc}
   */
  public static function loadMultipleByProperties($entity_type, array $entity_ids, array $revision_ids = [], $field_name = '', $langcode = '', $limit = NULL, $sort = 'ASC', $transition_type = 'workflow_scheduled_transition') {
    // N.B. $transition_type is set as parameter default.
    return parent::loadMultipleByProperties($entity_type, $entity_ids, $revision_ids, $field_name, $langcode, $limit, $sort, $transition_type);
  }

  /**
   * Given a timeframe, get all scheduled transitions.
   *
   * @param int $start
   * @param int $end
   *
   * @return WorkflowScheduledTransition[] $transitions
   *   An array of transitions.
   */
  public static function loadBetween($start = 0, $end = 0) {
    $transition_type = 'workflow_scheduled_transition'; // TODO get this from annotation.

    /* @var $query \Drupal\Core\Entity\Query\QueryInterface */
    $query = \Drupal::entityQuery($transition_type)
      ->sort('timestamp', 'ASC')
      ->addTag($transition_type);
    if ($start) {
      $query->condition('timestamp', $start, '>');
    }
    if ($end) {
      $query->condition('timestamp', $end, '<');
    }

    $ids = $query->execute();
    $transitions = self::loadMultiple($ids);
    return $transitions;
  }


  /**
   * Property functions.
   */

  /**
   * If a scheduled transition has no comment, a default comment is added before executing it.
   */
  public function addDefaultComment() {
    $this->setComment(t('Scheduled by user @uid.', array('@uid' => $this->getOwnerId())));
  }

  /**
   * Define the fields. Modify the parent fields.
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = array();

    // Add the specific ID-field : tid vs. hid.
    $fields['tid'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Transition ID'))
      ->setDescription(t('The transition ID.'))
      ->setReadOnly(TRUE)
      ->setSetting('unsigned', TRUE);

    // Get the rest of the fields.
    $fields += parent::baseFieldDefinitions($entity_type);

    // The timestamp has a different description.
    $fields['timestamp'] = []; // Reset old value.
    $fields['timestamp'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Scheduled'))
      ->setDescription(t('The date+time this transition is scheduled for.'))
      ->setQueryable(FALSE)
//      ->setTranslatable(TRUE)
//      ->setDisplayOptions('view', array(
//        'label' => 'hidden',
//        'type' => 'timestamp',
//        'weight' => 0,
//      ))
//      ->setDisplayOptions('form', array(
//        'type' => 'datetime_timestamp',
//        'weight' => 10,
//      ))
//      ->setDisplayConfigurable('form', TRUE);
      ->setRevisionable(TRUE);


    // Remove the specific ID-field : tid vs. hid.
    unset($fields['hid']);

    return $fields;
  }

}
