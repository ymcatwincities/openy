<?php

/**
 * @file
 * Contains \Drupal\workflow_ui\Form\WorkflowForm.
 */

namespace Drupal\workflow_ui\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides the base form for workflow add and edit forms.
 */
class WorkflowForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $noyes = array(0 => t('No'), 1 => t('Yes'));
    $fieldset_options = array(0 => t('No fieldset'), 1 => t('Collapsible fieldset'), 2 => t('Collapsed fieldset'));
    $workflow = $this->entity;

    $form['label'] = [
      '#type' => 'textfield',
      '#description' => t('The human-readable label of the workflow. This will be used as a label when
         the workflow status is shown during editing of content.'),
      '#title' => $this->t('Label'),
      '#default_value' => $this->entity->label(),
      '#required' => TRUE,
    ];

     $form['id'] = [
      '#type' => 'machine_name',
      '#description' => t('A unique machine-readable name. Can only contain lowercase letters, numbers, and underscores.'),
      '#disabled' => !$this->entity->isNew(),
      '#default_value' => $this->entity->id(),
      '#machine_name' => [
        'exists' => [$this, 'exists'],
        'replace_pattern' =>'([^a-z0-9_]+)|(^custom$)',
        'error' => $this->t('The machine-readable name must be unique, and can only contain lowercase letters, numbers, and underscores. Additionally, it can not be the reserved word "custom".'),
      ],
    ];

    $form['permissions'] = array(
      '#type' => 'details',
      '#title' => t('Workflow permissions'),
      '#open' => TRUE, // Controls the HTML5 'open' attribute. Defaults to FALSE.
      '#description' => t("To enable further Workflow functionality, go to the
        /admin/people/permissions page and select any roles that should have
        access to below and other functionalities."),
    );
    $form['permissions']['transition_execute'] = array(
      '#type' => 'item',
      '#title' => t('Participate in workflow (create, execute transitions)'),
      '#markup' => t("You can determine which roles are enabled on the
        'Workflow Transitions & roles' configuration page. Use this to enable
        only the relevant roles. Some sites have too many roles to show on
        the configuration page."),
    );
    $form['permissions']['transition_schedule'] = array(
      '#type' => 'item',
      '#title' => t('Schedule state transition'),
      '#markup' => t("Workflow transitions may be scheduled to a moment in the
        future. Soon after the desired moment, the transition is executed by
        Cron. This may be hidden by settings in widgets, formatters or permissions."
      ),
    );
    $form['permissions']['history_tab'] = array(
      '#type' => 'item',
      '#title' => t('Access Workflow history tab'),
      '#markup' => t("You can determine if a tab 'Workflow history' is
         shown on the entity view page, which gives access to the History of
         the workflow.
         If you have multiple workflows per bundle, better disable this feature,
         and use, clone & adapt the Views display 'Workflow history per Entity'."),
    );

    $form['basic'] = array(
      '#type' => 'details',
      '#title' => t('Workflow form settings'),
      // '#description' => t('Lorem ipsum.'),
      '#open' => TRUE, // Controls the HTML5 'open' attribute. Defaults to FALSE.
     );

    $form['basic']['fieldset'] = array(
      '#type' => 'select',
      '#options' => $fieldset_options,
      '#title' => t('Show the form in a fieldset?'),
      '#default_value' => isset($workflow->options['fieldset']) ? $workflow->options['fieldset'] : 0,
    );
    $form['basic']['options'] = array(
      '#type' => 'select',
      '#title' => t('How to show the available states'),
      '#required' => FALSE,
      '#default_value' => isset($workflow->options['options']) ? $workflow->options['options'] : 'radios',
      // '#multiple' => TRUE / FALSE,
      '#options' => array(
        // These options are taken from options.module
        'select' => 'Select list',
        'radios' => 'Radio buttons',
        // This option does not work properly on Comment Add form.
        'buttons' => 'Action buttons',
      ),
      '#description' => t("The Widget shows all available states. Decide which
      is the best way to show them."
      ),
    );

    $form['basic']['name_as_title'] = array(
      '#type' => 'checkbox',
      '#attributes' => array('class' => array('container-inline')),
      '#title' => t('Use the workflow name as the title of the workflow form'),
      '#default_value' => isset($workflow->options['name_as_title']) ? $workflow->options['name_as_title'] : 0,
      '#description' => t(
        'The workflow section of the editing form is in its own fieldset.
             Checking the box will add the workflow name as the title of workflow
             section of the editing form.'
      ),
    );

    $form['basic']['schedule_timezone'] = array(
      '#type' => 'checkbox',
      '#title' => t('Show a timezone when scheduling a transition.'),
      '#required' => FALSE,
      '#default_value' => isset($workflow->options['schedule_timezone']) ? $workflow->options['schedule_timezone'] : 1,
    );

    $form['comment'] = array(
      '#type' => 'details',
      '#title' => t('Comment'),
      '#description' => t(
        'A Comment form can be shown on the Workflow Transition form so that the person
         making a state change can record reasons for doing so. The comment is
         then included in the content\'s workflow history.'
      ),
      '#open' => TRUE, // Controls the HTML5 'open' attribute. Defaults to FALSE.
    );

    /*
    $form['comment']['comment'] = array(
      '#type' => 'select',
      '#title' => t('Allow adding a comment to workflow transitions'),
      '#required' => FALSE,
      '#options' => array(
        // Use 0/1/2 to stay compatible with previous checkbox.
        0 => t('hidden'),
        1 => t('optional'),
        2 => t('required'),
      ),
      '#default_value' => $settings['widget']['comment'],
      '#description' => t('On the Workflow form, a Comment form can be included
            so that the person making the state change can record reasons for doing
            so. The comment is then included in the content\'s workflow history. This
            may be altered by settings in widgets, formatters or permissions.'
      ),
    );
    */

    $form['comment']['comment_log_node'] = array(
      '#type' => 'select',
      '#required' => FALSE,
      '#options' => array(
        // Use 0/1/2 to stay compatible with previous checkbox.
        0 => t('hidden'),
        1 => t('optional'),
        2 => t('required'),
      ),
      '#attributes' => array('class' => array('container-inline')),
      '#title' => t('Show comment on the Content edit form'),
      '#default_value' => isset($workflow->options['comment_log_node']) ? $workflow->options['comment_log_node'] : 1,
//      '#description' => t(
//        'On the node editing form.'
//      ),
    );

    $form['comment']['comment_log_tab'] = array(
      '#type' => 'select',
      '#required' => FALSE,
      '#options' => array(
        // Use 0/1/2 to stay compatible with previous checkbox.
        0 => t('hidden'),
        1 => t('optional'),
        2 => t('required'),
      ),
      '#attributes' => array('class' => array('container-inline')),
      '#title' => t('Show comment on the Workflow history tab of content'),
      '#default_value' => isset($workflow->options['comment_log_tab']) ? $workflow->options['comment_log_tab'] : 1,
//      '#description' => t(
//        'On the workflow tab.'
//      ),
    );

    $form['watchdog'] = array(
      '#type' => 'details',
      '#title' => t('Watchdog'),
      '#description' => t('Informational watchdog messages can be logged when a transition is executed (state of a node is changed).'),
      '#open' => TRUE, // Controls the HTML5 'open' attribute. Defaults to FALSE.
    );

    $form['watchdog']['watchdog_log'] = array(
      '#type' => 'checkbox',
      '#title' => t('Log watchdog messages upon state change'),
      '#default_value' => isset($workflow->options['watchdog_log']) ? $workflow->options['watchdog_log'] : 0,
      '#description' => t(''),
    );

    return parent::form($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions = parent::actions($form, $form_state);
    // $actions['submit']['#value'] = $this->t('Save');
    return $actions;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    /* @var $entity \Drupal\workflow\Entity\Workflow */
    $entity = $this->entity;

    // Prevent leading and trailing spaces.
    $entity->set('label', trim($entity->label()));

    $entity->set('options', array(
        'name_as_title' => $form_state->getValue('name_as_title'),
        'fieldset' => $form_state->getValue('fieldset'),
        'options' => $form_state->getValue('options'),
        'schedule_timezone' => $form_state->getValue('schedule_timezone'),
        'comment_log_node' => $form_state->getValue('comment_log_node'),
        'comment_log_tab' => $form_state->getValue('comment_log_tab'),
        'watchdog_log' => $form_state->getValue('watchdog_log'),
      )
    );

    $status = parent::save($form, $form_state);
    $action = $status == SAVED_UPDATED ? 'updated' : 'added';

    // Tell the user we've updated the data.
    $args = [
      '%label' => $entity->label(),
      '%action' => $action,
      'link' => $entity->link(t('Edit'))
    ];
    drupal_set_message($this->t('Workflow %label has been %action. Please maintain the permissions, states and transitions.', $args));
    $this->logger('workflow')->notice('Workflow %label has been %action.', $args);

    if ($status == SAVED_NEW) {
      $form_state->setRedirect('entity.workflow_type.edit_form', ['workflow_type' => $entity->id()]);
    }

  }

  /**
   * Form validation handler.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $workflow = $this->entity;
    $name = $workflow->id();

    // Make sure workflow name is not numeric.
    // TODO: this was a prerequisite in D7. Remove in D8?
    if (ctype_digit($name)) {
      $form_state->setErrorByName('id', t('Please choose a non-numeric name for your workflow.'));
    }

    return parent::validateForm($form, $form_state);
  }

  /**
   * Machine name exists callback.
   *
   * @param string $id
   *   The machine name ID.
   *
   * @return bool
   *   TRUE if an entity with the same name already exists, FALSE otherwise.
   */
  public function exists($id) {
    $type = $this->entity->getEntityTypeId();
    return (bool) $this->entityManager->getStorage($type)->load($id);
  }

}
