<?php

/**
 * @file
 * Contains \Drupal\workflow\WorkflowTransitionListBuilder.
 */

namespace Drupal\workflow;

use Drupal\Component\Utility\Html;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\workflow\Entity\WorkflowTransition;
use Drupal\workflow\Entity\WorkflowTransitionInterface;

define('WORKFLOW_MARK_STATE_IS_DELETED', '*');

/**
 * Defines a class to build a draggable listing of Workflow State entities.
 *
 * @see \Drupal\workflow\Entity\WorkflowState
 */
class WorkflowTransitionListBuilder extends EntityListBuilder implements FormInterface {

  /**
   * A variable to pass the entity of a transition to the ListBuilder.
   * @todo: There should be a better way.
   *
   * @var EntityInterface
   */
  public $workflow_entity;

  /**
   * {@inheritdoc}
   */
  protected $limit = 50;

  /**
   * Indicates if a column 'Field name' must be generated.
   *
   * @var bool
   */
  protected $show_column_fieldname = NULL;

  /**
   * Indicates if a footer must be generated.
   *
   * @var bool
   */
  protected $footer_needed = FALSE;

  /**
   * {@inheritdoc}
   */
  public function load() {
    $entities = array();

    // TODO: D8-port: get entity from proper core methods.
    /* @var $entity EntityInterface */
    $entity = $this->workflow_entity; // N.B. This is a custom variable.
    $field_name = workflow_url_get_field_name();

    $entity_type = $entity->getEntityTypeId();
    $entity_id = $entity->id();

    // @todo D8-port: document $limit.
    // @todo d8-port: $limit should be used in pager, not in load().
    $this->limit = \Drupal::config('workflow.settings')->get('workflow_states_per_page');
    $limit = $this->limit;
    // Get Transitions with highest timestamp first.
    $entities = WorkflowTransition::loadMultipleByProperties($entity_type, array($entity_id), [], $field_name, '', $limit, 'DESC');
    return $entities;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'workflow_transiton_history_form';
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

    $entity = $this->workflow_entity; // N.B. This is a custom variable.
    $field_name = workflow_url_get_field_name();

    $header['timestamp'] = $this->t('Date');
    if ($this->showColumnFieldname($entity)) {
      $header['field_name'] = $this->t('Field name');
    }
    $header['from_state'] = $this->t('From State');
    $header['to_state'] = $this->t('To State');
    $header['user_name'] = $this->t('By');
    $header['comment'] = $this->t('Comment');

    // column 'Operations' is now added by core.
    //$header['operations'] = $this->t('Operations');

    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   *
   * @todo D8-port: add D7-theming to TransitionListBuilder.
   */
  public function buildRow(EntityInterface $transition) {

    // Show the history table.
    $current_themed = FALSE;
    /* @var $transition WorkflowTransitionInterface */
    $entity = $transition->getTargetEntity();
    $field_name = $transition->getFieldName();
    $current_sid = workflow_node_current_state($entity, $field_name);

    $to_state = $transition->getToState();
    if (!$to_state) {
      // This is an invalid/deleted state.
      $to_label = WORKFLOW_MARK_STATE_IS_DELETED;
      // Add a footer to explain the addition.
      $this->footer_needed = TRUE;
    }
    else {
      $label = Html::escape($this->t($to_state->label()));
      if ($transition->getToSid() == $current_sid && $to_state->isActive() && !$current_themed) {
        $to_label = $label;

        if (!$current_themed) {
          // Make a note that we have themed the current state; other times in the history
          // of this entity where the entity was in this state do not need to be specially themed.
          $current_themed = TRUE;
        }
      }
      elseif (!$to_state->isActive()) {
        $to_label = $label . WORKFLOW_MARK_STATE_IS_DELETED;
        // Add a footer to explain the addition.
        $this->footer_needed = TRUE;
      }
      else {
        // Regular state.
        $to_label = $label;
      }
    }
    unset($to_state); // Not needed anymore.

    $from_state = $transition->getFromState();
    if (!$from_state) {
      // This is an invalid/deleted state.
      $from_label = WORKFLOW_MARK_STATE_IS_DELETED;
      // Add a footer to explain the addition.
      $this->footer_needed = TRUE;
    }
    else {
      $label = Html::escape($this->t($from_state->label()));
      if (!$from_state->isActive()) {
        $from_label = $label . WORKFLOW_MARK_STATE_IS_DELETED;
        // Add a footer to explain the addition.
        $this->footer_needed = TRUE;
      }
      else {
        // Regular state.
        $from_label = $label;
      }
    }
    unset($from_state); // Not needed anymore.

    $owner = $transition->getOwner();
    $field_name = $transition->getFieldName();
    $field_label = $transition->getFieldName();
    $variables = array(
      'transition' => $transition,
      'extra' => '',
      'from_label' => $from_label,
      'to_label' => $to_label,
      'user' => $owner,
    );
    // Allow other modules to modify the row.
    \Drupal::moduleHandler()->alter('workflow_history', $variables);

//     'class' => array('workflow_history_row'), // TODO D8-port
    $row['timestamp']['data'] = $transition->getTimestampFormatted(); // 'class' => array('timestamp')
    // html_entity_decode() transforms chars like '&' correctly.
    if ($this->showColumnFieldname($entity)) {
      $row['field_name']['data'] = html_entity_decode($field_label);
    }
    $row['from_state']['data'] = html_entity_decode($from_label); // 'class' => array('previous-state-name'))
    $row['to_state']['data'] = html_entity_decode($to_label); // 'class' => array('state-name'))
    $row['user_name']['data'] = $owner->getUsername(); // 'class' => array('user-name')
    $row['comment']['data'] = html_entity_decode($transition->getComment()); // 'class' => array('log-comment')
//    $row['comment'] = array(
//      '#type' => 'textarea',
//      '#default_value' => $transition->getComment(),
//    );

    // Column 'Operations' is now added by core.
    // D7: $row['operations']['data'] = $this->buildOperations($entity);
    $row += parent::buildRow($transition);

    return $row;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    workflow_debug(__FILE__, __FUNCTION__, __LINE__);  // @todo D8-port: still test this snippet.
    $form = parent::buildForm($form, $form_state);

    return $form;
  }

  /**
   * {@inheritdoc}
   *
   * Builds the entity listing as renderable array for table.html.twig.
   */
  public function render() {
    $build = array();

    // @todo d8-port: get pager working.
    $this->limit = \Drupal::config('workflow.settings')->get('workflow_states_per_page');  // TODO D8-port
    // $output .= theme('pager', array('tags' => $limit)); // TODO D8-port

    $build += parent::render();

    // Add a footer. This is not yet added in EntityListBilder::render()
    if ($this->footer_needed) {  // TODO D8-port: test this.
      // Two variants. First variant is official, but I like 2nd better.
      /*
      $build['table']['#footer'] = array(
        array(
          'class' => array('footer-class'),
          'data' => array(
            array(
              'data' => WORKFLOW_MARK_STATE_IS_DELETED . ' ' . t('State is no longer available.'),
              'colspan' => count($build['table']['#header']),
            ),
          ),
        ),
      );
    */
      $build['workflow_footer'] = array(
        '#markup' => WORKFLOW_MARK_STATE_IS_DELETED . ' ' . t('State is no longer available.'),
        '#weight' => 500, // @todo Make this better.
      );
    }

    return $build;
  }

  /**
   * Just for reference: parent::getOperations calls 2 hooks/alters.
   * {@inheritdoc}
   */
//  public function getOperations(EntityInterface $entity) {
//    $operations = $this->getDefaultOperations($entity);
//    $operations += $this->moduleHandler()->invokeAll('entity_operation', array($entity));
//    $this->moduleHandler->alter('entity_operation', $operations, $entity);
//    uasort($operations, '\Drupal\Component\Utility\SortArray::sortByWeightElement');
//
//    return $operations;
//  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultOperations(EntityInterface $entity) {
    $operations = parent::getDefaultOperations($entity);

    /* @var $transition WorkflowTransitionInterface */
    $transition = $entity;

    if (isset($operations['edit'])) {
      $destination = \Drupal::destination()->getAsArray();
      $operations['edit']['query'] = $destination;
    }

    return $operations;
  }

  /**
   * Gets the title of the page.
   *
   * @return string
   *   A string title of the page.
   */
  protected function getTitle() {
    return $this->t('Workflow history');
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    return;
  }

  /**
   * {@inheritdoc}
   *
   * Overrides DraggableListBuilder::submitForm().
   * The WorkflowState entities are always saved.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * Determines if the column 'Field name' must be shown.
   *
   * @param EntityInterface $entity
   *
   * @return bool
   */
  protected function showColumnFieldname(EntityInterface $entity) {
    if (is_null($this->show_column_fieldname)) {
      // @todo: also remove when field_name is set in route??
      if (count(_workflow_info_fields($entity)) > 1) {
        $this->show_column_fieldname = TRUE;
      }
    }
    return $this->show_column_fieldname;
  }

}
