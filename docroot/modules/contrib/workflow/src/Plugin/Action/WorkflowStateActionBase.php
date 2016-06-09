<?php

/**
 * @file
 * Contains \Drupal\workflow\Plugin\Action\WorkflowStateActionBase.
 *
 * This is an abstract Action. Derive your own from this.
 *
 */

namespace Drupal\workflow\Plugin\Action;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Action\ConfigurableActionBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\KernelEvents;
use Drupal\workflow\Entity\Workflow;
use Drupal\workflow\Entity\WorkflowTransition;
use Drupal\workflow\Entity\WorkflowTransitionInterface;
use Drupal\workflow\Element\WorkflowTransitionElement;

/**
 * Sets an entity to a new, given state.
 *
 * Example Annotation @ Action(
 *   id = "workflow_given_state_action",
 *   label = @Translation("Change a node to new Workflow state"),
 *   type = "workflow"
 * )
 */
abstract class WorkflowStateActionBase extends ConfigurableActionBase implements ContainerFactoryPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies(){
    return [
      'module' => array('workflow',),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $configuration = $this->configuration + array(
      'field_name' => '',
      'to_sid' => '',
      'comment' => "New state is set by a triggered Action.",
      'force' => 0,
    );
    return $configuration;
  }

  /**
   * @return WorkflowTransitionInterface
   */
  protected function getTransitionForExecution(EntityInterface $entity) {
    $user = workflow_current_user();

    if (!$entity) {
      \Drupal::logger('workflow_action')->notice('Unable to get current entity - entity is not defined.', []);
      return NULL;
    }

    // Get the entity type and numeric ID.
    $entity_id = $entity->id();
    if (!$entity_id) {
      \Drupal::logger('workflow_action')->notice('Unable to get current entity ID - entity is not yet saved.', []);
      return NULL;
    }

    // In 'after saving new content', the node is already saved. Avoid second insert.
    // Todo: clone?
    $entity->enforceIsNew(FALSE);

    $config = $this->configuration;
    $field_name = workflow_get_field_name($entity, $config['field_name']);
    $current_sid = workflow_node_current_state($entity, $field_name);
    if (!$current_sid) {
      \Drupal::logger('workflow_action')->notice('Unable to get current workflow state of entity %id.', array('%id' => $entity_id));
      return NULL;
    }

    $to_sid = isset($config['to_sid']) ? $config['to_sid'] : '';
    // Get the Comment. Parse the $comment variables.
    $comment_string = $this->configuration['comment'];
    $comment = t($comment_string, array(
      '%title' => $entity->label(),
      // "@" and "%" will automatically run check_plain().
      '%state' => workflow_get_sid_name($to_sid),
      '%user' => $user->getUsername(),
    ));
    $force = $this->configuration['force'];

    $transition = WorkflowTransition::create([$current_sid, 'field_name' => $field_name]);
    $transition->setTargetEntity($entity);
    $transition->setValues($to_sid, $user->id(), REQUEST_TIME, $comment);
    $transition->force($force);

    return $transition;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = [];

    // If we are on admin/config/system/actions and use CREATE AN ADVANCED ACTION
    // Then $context only contains:
    // - $context['actions_label'] = "Change workflow state of post to new state"
    // - $context['actions_type'] = "entity"
    //
    // If we are on a VBO action form, then $context only contains:
    // - $context['entity_type'] = "node"
    // - $context['view'] = "(Object) view"
    // - $context['settings'] = "array()"

    $config = $this->configuration;
    $field_name = $config['field_name'];
    $wids = workflow_get_workflow_names();

    if (empty($field_name)) {
      if (count($wids) > 1) {
        drupal_set_message('You have more then one workflow in the system. Please first select the fieldname
          and save the form. Then, revisit the form to set the correct state value.', 'warning');
      }
      $wid = count($wids) ? array_keys($wids)[0] : '';
    }
    else {
      $fields = _workflow_info_fields($entity = NULL, $entity_type = $config['type'], $entity_bundle = '', $field_name);
      $wid = count($fields) ? reset($fields)->getSetting('workflow_type') : '';
    }

    // Get the common Workflow, or create a dummy Workflow.
    $workflow = $wid ? Workflow::load($wid) : Workflow::create(['id' => 'dummy_action', 'label' => 'dummy_action']);
    $current_state = $workflow->getCreationState();

    /* // @TODO D8-port for VBO
    // Show the current state and the Workflow form to allow state changing.
    // N.B. This part is replicated in hook_node_view, workflow_tab_page, workflow_vbo.
    if ($workflow) {
      $field = _workflow_info_field($field_name, $workflow);
      $field_name = $field['field_name'];
      $field_id = $field['id'];
      $instance = field_info_instance($entity_type, $field_name, $entity_bundle);

      // Hide the submit button. VBO has its own 'next' button.
      $instance['widget']['settings']['submit_function'] = '';
      if (!$field_id) {
        // This is a Workflow Node workflow. Set widget options as in v7.x-1.2
        $field['settings']['widget']['comment'] = isset($workflow->options['comment_log_tab']) ? $workflow->options['comment_log_tab'] : 1; // vs. ['comment_log_node'];
        $field['settings']['widget']['current_status'] = TRUE;
        // As stated above, the options list is probably very long, so let's use select list.
        $field['settings']['widget']['options'] = 'select';
        // Do not show the default [Update workflow] button on the form.
        $instance['widget']['settings']['submit_function'] = '';
      }
    }

    // Add the form/widget to the formatter, and include the nid and field_id in the form id,
    // to allow multiple forms per page (in listings, with hook_forms() ).
    // Ultimately, this is a wrapper for WorkflowDefaultWidget.
    // $form['workflow_current_state'] = workflow_state_formatter($entity_type, $entity, $field, $instance);
    $form_id = implode('_', array(
      'workflow_transition_form',
      $entity_type,
      $entity_id,
      $field_id
    ));
*/
    $to_sid = $config['to_sid'];
    $user = workflow_current_user();
    $comment = $config['comment'];
    $force = $config['force'];
    $transition = WorkflowTransition::create([$current_state, 'field_name' => $field_name]);
    $transition->setValues($to_sid, $user->id(), REQUEST_TIME, $comment, TRUE);

    // Add the WorkflowTransitionForm to the page.

    // Here, not the $element is added, but the entity form.
    $element = []; // Just to be explicit.
    $element['#default_value'] = $transition;
    $form += WorkflowTransitionElement::transitionElement($element, $form_state, $form);
    // Remove the transition: generates an error upon saving the action definition.
    unset($form['workflow_transition']);

    // Todo D8: add the entity form.
    //$form = \Drupal::getContainer()->get('entity.form_builder')->getForm($transition, 'add');
    // Remove the action button. The Entity itself has one.
    //unset($element['actions']);

    // Make adaptations for VBO-form:
    $entity = $transition->getTargetEntity();
    $field_name = $transition->getFieldName();
    $force = $this->configuration['force'];

    // Override the options widget.
    $form['to_sid']['#description'] = t('Please select the state that should be assigned when this action runs.');

    // Add Field_name. @todo?? Add 'field_name' to WorkflowTransitionElement?
    $form['field_name'] = array(
      '#type' => 'select',
      '#title' => t('Field name'),
      '#description' => t('Choose the field name.'),
      '#options' => workflow_get_workflow_field_names($entity),
      '#default_value' => $field_name,
      '#required' => TRUE,
      '#weight' => -20,
    );
    // Add Force. @todo?? Add 'force' to WorkflowTransitionElement?
    $form['force'] = array(
      '#type' => 'checkbox',
      '#title' => t('Force transition'),
      '#description' => t('If this box is checked, the new state will be assigned even if workflow permissions disallow it.'),
      '#default_value' => $force,
      '#weight' => -19,
    );
    // Change comment field.
    $form['comment'] = array(
      '#title' => t('Message'),
      '#description' => t('This message will be written into the workflow history log when the action
      runs. You may include the following variables: %state, %title, %user.'),
    ) + $form['comment'];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $configuration = $form_state->getValues();
    unset($configuration['transition']); // No cluttered objects in datastorage.
    $this->configuration = $configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    $access = AccessResult::allowed();
    return $return_as_object ? $access : $access->isAllowed();
  }

}
