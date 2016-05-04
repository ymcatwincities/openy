<?php

/**
 * @file
 * Contains \Drupal\workflow_ui\Form\WorkflowConfigTransitionFormBase.
 */

namespace Drupal\workflow_ui\Form;

use Drupal\Core\Form\FormInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\workflow\Entity\Workflow;

/**
 * Defines a class to build a draggable listing of Workflow Config Transitions entities.
 *
 * @see \Drupal\workflow\Entity\WorkflowConfigTransition
 */
abstract class WorkflowConfigTransitionFormBase implements FormInterface {
  /**
   * The key to use for the form element containing the entities.
   *
   * @var string
   */
  protected $entitiesKey = 'entities';

  /**
   * The entities being listed.
   *
   * @var \Drupal\Core\Entity\EntityInterface[]
   */
  protected $entities = array();

  /**
   * The form builder.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
//  protected $formBuilder;

  /**
   * The WorkflowConfigTransition form type.
   *
   * @var string
   */
  protected $type;

  /**
   * The workflow object.
   *
   * @var \Drupal\workflow\Entity\Workflow
   */
  protected $workflow;

  /**
   * {@inheritdoc}
   */
  public function __construct($type, Workflow $workflow) {
    $this->type = $type;
    $this->workflow = $workflow;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'workflow_config_transition_' . $this->type . '_form';
  }

  /**
   * {@inheritdoc}
   *
   * Create an $entity for every ConfigTransition.
   */
  public function load() {
    $entities = array();

    $workflow = $this->workflow;
    $states = $workflow->getStates($all = 'CREATION');
    if ($states) {
      foreach ($states as $from_state) {
        $from_sid = $from_state->id();
        foreach ($states as $to_state) {
          $to_sid = $to_state->id();

          // Don't allow transition TO (creation).
          if ($to_state->isCreationState()) {
            continue;
          }
//          // Only  allow transitions from $from_state.
//          if ($state->id() <> $from_state->id()) {
//            continue;
//          }

          // Load existing config_transitions. Create if not found.
          $config_transitions = $workflow->getTransitionsByStateId($from_sid, $to_sid);
          if (!$config_transition = reset($config_transitions)) {
            $config_transition = $workflow->createTransition($from_sid, $to_sid);
          }
          $entities[] = $config_transition;
        }
      }
    }
    return $entities;
  }

  /**
   * {@inheritdoc}
   */
//  abstract public function buildHeader();

  /**
   * {@inheritdoc}
   */
//  abstract public function buildRow(EntityInterface $entity);

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = array();

    /*
     * Begin of copied code DraggableListBuilder::buildForm()
     */
    $form[$this->entitiesKey] = array(
      '#type' => 'table',
      '#header' => $this->buildHeader(),
      '#empty' => t('There is no @label yet.', array('@label' => 'Transition')),
      '#tabledrag' => array(array('action' => 'order', 'relationship' => 'sibling', 'group' => 'weight',),),
    );

    $this->entities = $this->load();
    $delta = 10;
    // Change the delta of the weight field if have more than 20 entities.
    if (!empty($this->weightKey)) {
      $count = count($this->entities);
      if ($count > 20) {
        $delta = ceil($count / 2);
      }
    }
    foreach ($this->entities as $entity) {
      $row = $this->buildRow($entity);
      if (isset($row['label'])) {
        $row['label'] = array('#markup' => $row['label']);
      }
      if (isset($row['weight'])) {
        $row['weight']['#delta'] = $delta;
      }
      $form[$this->entitiesKey][$entity->id()] = $row;
    }
    /*
     * End of copied code DraggableListBuilder::buildForm()
     */

    $form['actions']['#type'] = 'actions';
    // Add 'submit' button.
    $form['actions']['submit'] = ['#type' => 'submit', '#value' => t('Save'), '#button_type' => 'primary',];

    return $form;
  }
}
