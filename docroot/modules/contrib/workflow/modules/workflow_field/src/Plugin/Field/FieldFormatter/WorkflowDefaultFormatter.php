<?php

/**
 * @file
 * Contains \Drupal\workflowfield\Plugin\Field\FieldFormatter\WorkflowDefaultFormatter.
 */

namespace Drupal\workflowfield\Plugin\Field\FieldFormatter;

use Drupal\Core\Entity\EntityFormBuilderInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\workflow\Entity\WorkflowManagerInterface;
use Drupal\workflow\Entity\WorkflowState;
use Drupal\workflow\Entity\WorkflowTransition;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a default workflow formatter.
 *
 * @FieldFormatter(
 *   id = "workflow_default",
 *   module = "workflow",
 *   label = @Translation("Workflow form"),
 *   field_types = {
 *     "workflow"
 *   },
 *   quickedit = {
 *     "editor" = "disabled"
 *   }
 * )
 */
class WorkflowDefaultFormatter extends FormatterBase implements ContainerFactoryPluginInterface {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return array(
    ) + parent::defaultSettings();
  }

  /**
   * The workflow storage.
   *
   * @var \Drupal\workflow\WorkflowStorageInterface
   */
  protected $storage;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The  render controller.
   *
   * @var \Drupal\Core\Entity\EntityViewBuilderInterface
   */
  protected $viewBuilder;

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * The entity form builder.
   *
   * @var \Drupal\Core\Entity\EntityFormBuilderInterface
   */
  protected $entityFormBuilder;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('current_user'),
      $container->get('entity.manager'),
      $container->get('entity.form_builder')
    );
  }

  /**
   * Constructs a new WorkflowDefaultFormatter.
   *
   * @param string $plugin_id
   *   The plugin_id for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the formatter is associated.
   * @param array $settings
   *   The formatter settings.
   * @param string $label
   *   The formatter label display setting.
   * @param string $view_mode
   *   The view mode.
   * @param array $third_party_settings
   *   Third party settings.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager
   * @param \Drupal\Core\Entity\EntityFormBuilderInterface $entity_form_builder
   *   The entity form builder.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, AccountInterface $current_user, EntityManagerInterface $entity_manager, EntityFormBuilderInterface $entity_form_builder) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);
    $this->viewBuilder = $entity_manager->getViewBuilder('workflow_transition');
    $this->storage = $entity_manager->getStorage('workflow_transition');
    $this->currentUser = $current_user;
    $this->entityManager = $entity_manager;
    $this->entityFormBuilder = $entity_form_builder;
  }

  /**
   * {@inheritdoc}
   *
   * N.B. A large part of this function is taken from CommentDefaultFormatter.
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = array();
    $output = array();

    $field_name = $this->fieldDefinition->getName();
    $entity = $items->getEntity();
    $entity_type = $entity->getEntityTypeId();

    $status = $items->status;

    $workflow_settings = $this->getFieldSettings();

    $user = \Drupal::currentUser(); // @todo #2287057: OK?
    // @todo: Perhaps global user is not always the correct user.
    // E.g., on ScheduledTransition->execute()? But this function is mostly used in UI.

    $current_sid = $this->workflowManager()->getCurrentStateId($entity, $field_name);
    /* @var $current_state WorkflowState */
    $current_state = WorkflowState::load($current_sid);

    // First compose the current value with the normal formatter from list.module.
    $elements = workflow_state_formatter($entity, $field_name, $current_sid);

    // The state must not be deleted, or corrupted.
    if (!$current_state) {
      return $elements;
    }

    // Check permission, so that even with state change rights,
    // the form can be suppressed from the entity view (#1893724).
    $type_id = $current_state->getWorkflowId();
    if (!\Drupal::currentUser()->hasPermission("access $type_id workflow_transition form")) {
      return $elements;
    }

    // Workflows are added to the search results and search index by
    // workflow_node_update_index() instead of by this formatter, so don't
    // return anything if the view mode is search_index or search_result.
    if (in_array($this->viewMode, array('search_result', 'search_index'))) {
      return $elements;
    }

    if ($entity_type == 'comment') {
      // No Workflow form allowed on a comment display.
      // (Also, this avoids a lot of error messages.)
      return $elements;
    }

    // Only build form if user has possible target state(s).
    if (!$current_state->showWidget($entity, $field_name, $user, FALSE)) {
      return $elements;
    }

    // Create a transition, to pass to the form. No need to use setValues().
    $transition = WorkflowTransition::create([$current_sid, 'field_name' => $field_name]);
    $transition->setTargetEntity($entity);

    // Remove the default formatter. We are now building the widget.
    $elements = array();

    // BEGIN Copy from CommentDefaultFormatter
    $elements['#cache']['contexts'][] = 'user.permissions';
    $output['workflows'] = [];
    // Add the WorkflowTransitionForm to the page.
    // $build = $this->viewBuilder->viewMultiple($workflows);
    $build = $this->entityFormBuilder()->getForm($transition, 'add');
    $output['workflows'] += $build;

    // Only show the add workflow form if the user has permission.
    $elements['#cache']['contexts'][] = 'user.roles';
    // Do not show the form for the print view mode.
    $elements[] = $output + array(
      '#workflow_type' => $this->getFieldSetting('workflow_type'),
      '#workflow_display_mode' => $this->getFieldSetting('default_mode'),
      'workflows' => array(),
    );
    // END Copy from CommentDefaultFormatter

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element = array();
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    return array();
  }

  /**
   * Retrieves the entity form builder.
   *
   * @return \Drupal\Core\Entity\EntityFormBuilderInterface
   *   The entity form builder.
   */
  protected function entityFormBuilder() {
    if (!$this->entityFormBuilder) {
      $this->entityFormBuilder = $this->container()->get('entity.form_builder');
    }
    return $this->entityFormBuilder;
  }

  /**
   * Retrieves the WorkflowManager.
   *
   * @return WorkflowManagerInterface
   */
  protected function workflowManager() {
    return \Drupal::service('workflow.manager');
  }
}
