<?php

/**
 * @file
 * Contains \Drupal\workflow_ui\Form\WorkflowConfigTransitionFormBase.
 */

namespace Drupal\workflow_ui\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines a class to build a draggable listing of Workflow Config Transitions entities.
 *
 * @see \Drupal\workflow\Entity\WorkflowConfigTransition
 */
abstract class WorkflowConfigTransitionFormBase extends ConfigFormBase {

  /**
   * The key to use for the form element containing the entities.
   *
   * @var string
   */
  protected $entitiesKey = 'entities';

  /**
   * The WorkflowConfigTransition form type.
   *
   * @var string
   */
  protected $type;

  /**
   * The entities being listed.
   *
   * @var \Drupal\Core\Entity\EntityInterface[]
   */
  protected $entities = array();

  /**
   * The workflow object.
   *
   * @var \Drupal\workflow\Entity\Workflow
   */
  protected $workflow;

  /**
   * {@inheritdoc}
   */
  public function __construct() {
    // The $this->type and $this->entitiesKey must be set in the var section.

    // Get the Workflow from the page.
    $this->workflow = workflow_ui_url_get_workflow();
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'workflow_config_transition_' . $this->type . '_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [];
  }

  /**
   * {@inheritdoc}
   *
   * Create an $entity for every ConfigTransition.
   */
  public function load() {
    $entities = array();

    $entity_type = $this->entitiesKey;
    $workflow = $this->workflow;
    $states = $workflow->getStates($all = 'CREATION');

    if ($states) {
      switch ($entity_type) {
        case 'workflow_state':
          foreach ($states as $from_state) {
            $from_sid = $from_state->id();
            $entities[$from_sid] = $from_state;
          }
          break;

        case 'workflow_config_transition':
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
          break;

        default:
          drupal_set_message(t('Improper type provided in load method.'), 'error');
          \Drupal::logger('workflow_ui')->notice('Improper type provided in load method.', []);
          return $entities;
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

    if (!$this->workflow) {
      return $form;
    }

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

    $form = parent::buildForm($form, $form_state);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    return parent::validateForm($form, $form_state);
  }

}
