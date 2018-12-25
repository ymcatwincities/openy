<?php

/**
 * @file
 * Contains \Drupal\workflow\Controller\WorkflowTransitionListController.
 */

namespace Drupal\workflow\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Datetime\DateFormatter;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\Controller\EntityListController;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\taxonomy\Entity\Term;
use Drupal\workflow\Entity\Workflow;
use Drupal\workflow\Entity\WorkflowManager;
use Drupal\workflow\Entity\WorkflowTransition;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Returns responses for Workflow routes.
 */
class WorkflowTransitionListController extends EntityListController implements ContainerInjectionInterface {

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatter
   */
  protected $dateFormatter;

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Constructs an object.
   *
   * @param \Drupal\Core\Datetime\DateFormatter $date_formatter
   *   The date formatter service.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   */
  public function __construct(DateFormatter $date_formatter, RendererInterface $renderer) {
    // These parameters are taken from some random other controller.
    $this->dateFormatter = $date_formatter;
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('date.formatter'),
      $container->get('renderer')
    );
  }

  /**
   * Generates an overview table of older revisions of a node,
   * but only if this::historyAccess() allows it.
   *
   * @param \Drupal\node\NodeInterface $node
   *   A node object.
   *
   * @return array
   *   An array as expected by drupal_render().
   */
  public function historyOverview(EntityInterface $node = NULL) {
    $form = array();

    /*
     * Get data from parameters.
     */

    // TODO D8-port: make Workflow History tab happen for every entity_type.
    // For workflow_tab_page with multiple workflows, use a separate view. See [#2217291].
    // @see workflow.routing.yml, workflow.links.task.yml, WorkflowTransitionListController.
    //    workflow_debug(__FILE__, __FUNCTION__, __LINE__);  // @todo D8-port: still test this snippet.
    // ATM it only works for Nodes and Terms.
    // This is a hack. The Route should always pass an object.
    // On view tab, $entity is object,
    // On workflow tab, $entity is id().
    // Get the entity for this form.
    if (!$entity = workflow_url_get_entity($node)) {
      return $form;
    }

    /*
     * Get derived data from parameters.
     */
    if (!$field_name = workflow_get_field_name($entity, workflow_url_get_field_name())) {
      return $form;
    }

    /*
     * Step 1: generate the Transition Form.
     */
    // Create a transition, to pass to the form. No need to use setValues().
    $current_sid = workflow_node_current_state($entity, $field_name);
    $transition = WorkflowTransition::create([$current_sid, 'field_name' => $field_name]);
    $transition->setTargetEntity($entity);
    // Add the WorkflowTransitionForm to the page.
    $form = $this->entityFormBuilder()->getForm($transition, 'add');

    /*
     * Step 2: generate the Transition History List.
     */
    $entity_type = 'workflow_transition';
    // $form = $this->listing('workflow_transition');
    $list_builder = $this->entityManager()->getListBuilder($entity_type);
    // Add the Node explicitly, since $list_builder expects a Transition.
    $list_builder->workflow_entity = $entity;

    $form += $list_builder->render();

    /*
     * Finally: sort the elements (overriding their weight).
     */
    // $form['#weight'] = 10;
    $form['actions']['#weight'] = 100;
    $form['table']['#weight'] = 201;

    return $form;
  }

  /**
   * Menu access control callback. Checks access to Workflow tab.
   *
   * This used to be D7-function workflow_tab_access($user, $entity).
   *
   * The History tab should not be used with multiple workflows per entity.
   * Use the dedicated view for this use case.
   * @todo D8: remove this in favour of View 'Workflow history per entity'.
   * @todo D8-port: make this workf for non-Node entity types.
   *
   * @param \Drupal\workflow\Controller\AccountInterface $account
   *   Run access checks for this account.
   *
   * @return \Drupal\Core\Access\AccessResult
   */

  public function historyAccess(AccountInterface $account) {
    static $access = array();

    $uid = ($account) ? $account->id() : -1;

    // TODO D8-port: make Workflow History tab happen for every entity_type.
    // @see workflow.routing.yml, workflow.links.task.yml, WorkflowTransitionListController.
    // ATM it only works for Nodes and Terms.
    // This is a hack. The Route should always pass an object.
    // On view tab, $entity is object,
    // On workflow tab, $entity is id().
    // Get the entity for this form.
    $entity = workflow_url_get_entity();

    /* @var $entity EntityInterface */
    // Figure out the $entity's bundle and id.
    $entity_type = $entity->getEntityTypeId();
    $entity_bundle = $entity->bundle();
    $entity_id = ($entity) ? $entity->id() : '';
    $field_name = workflow_url_get_field_name();

    if (isset($access[$uid][$entity_type][$entity_id][$field_name ? $field_name : 'no_field'])) {
      return $access[$uid][$entity_type][$entity_id][$field_name ? $field_name : 'no_field'];
    }

    $access_result = AccessResult::forbidden();

    // When having multiple workflows per bundle, use Views display
    // 'Workflow history per entity' instead!
    $fields = _workflow_info_fields($entity, $entity_type, $entity_bundle, $field_name);
    if (!$fields) {
      return AccessResult::forbidden();
    }
    else {
      // @todo: Keep below code aligned between WorkflowState, ~Transition, ~TransitionListController
      $uid = ($account) ? $account->id() : -1;
      $entity_id = ($entity) ? $entity->id() : '';
      // Determine if user is owner of the entity.
      $is_owner = WorkflowManager::isOwner($account, $entity);

      /**
       * Determine if user has Access. Fill the cache.
       */
      // @todo: what to do with multiple workflow_fields per bundle? Use Views instead! Or introduce a setting.
      // @TODO D8-port: workflow_tab_access: use proper 'WORKFLOW_TYPE' permissions
      foreach ($fields as $definition) {
        $type_id = $definition->getSetting('workflow_type');
        if ($account->hasPermission("access any $type_id workflow_transion overview")) {
          $access_result = AccessResult::allowed();
        }
        elseif ($is_owner && $account->hasPermission("access own $type_id workflow_transion overview")) {
          $access_result = AccessResult::allowed();
        }
        elseif ($account->hasPermission('administer nodes')) {
          $access_result = AccessResult::allowed();
        }
        $access[$uid][$entity_type][$entity_id][$field_name ? $field_name : 'no_field'] = $access_result;
      }
    }
    return $access_result;
  }

}
