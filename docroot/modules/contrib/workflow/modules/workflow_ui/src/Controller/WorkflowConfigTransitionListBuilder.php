<?php

/**
 * @file
 * Contains \Drupal\workflow_ui\Controller\WorkflowConfigTransitionListBuilder.
 */

namespace Drupal\workflow_ui\Controller;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\workflow_ui\Form\WorkflowConfigTransitionLabelForm;
use Drupal\workflow_ui\Form\WorkflowConfigTransitionRoleForm;

/**
 * Defines a class to build a draggable listing of Workflow Config Transitions entities.
 *
 * @see \Drupal\workflow\Entity\WorkflowConfigTransition
 */
class WorkflowConfigTransitionListBuilder extends ConfigEntityListBuilder {

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
  protected $formBuilder;

  /**
   * The ConfigTransitions type form, to have 2 forms for maintaining ConfigTransitions.
   */
  protected $type;

  /**
   * {@inheritdoc}
   */
  public function render() {

    // Get the Workflow from the page.
    /* @var $workflow \Drupal\workflow\Entity\Workflow */
    if (!$workflow = workflow_ui_url_get_workflow()) {
      return;
    }

    $form_type = workflow_ui_url_get_form_type();
    switch ($form_type) {
      case 'transition_roles':
        $form = new WorkflowConfigTransitionRoleForm();
        return $this->formBuilder()->getForm($form);

      case 'transition_labels':
        $form = new WorkflowConfigTransitionLabelForm();
        return $this->formBuilder()->getForm($form);

      default:
        drupal_set_message(t('Improper form type provided.'), 'error');
        \Drupal::logger('workflow_ui')->notice('Improper form type provided.', []);
        return;

    }
//    return parent::render();
  }

  /**
   * Returns the form builder.
   *
   * @return \Drupal\Core\Form\FormBuilderInterface
   *   The form builder.
   */
  protected function formBuilder() {
    if (!$this->formBuilder) {
      $this->formBuilder = \Drupal::formBuilder();
    }
    return $this->formBuilder;
  }

}
