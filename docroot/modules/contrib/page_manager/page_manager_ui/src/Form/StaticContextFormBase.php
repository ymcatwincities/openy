<?php

/**
 * @file
 * Contains \Drupal\page_manager_ui\Form\StaticContextFormBase.
 */

namespace Drupal\page_manager_ui\Form;

use Drupal\Core\Entity\Entity;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityTypeRepositoryInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\page_manager\PageVariantInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a base form for editing and adding an access condition.
 */
abstract class StaticContextFormBase extends FormBase {

  /**
   * The page variant entity this static context belongs to.
   *
   * @var \Drupal\page_manager\PageVariantInterface
   */
  protected $pageVariant;

  /**
   * The static context configuration.
   *
   * @var array
   */
  protected $staticContext;

  /**
   * The entity repository.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   */
  protected $entityRepository;

  /**
   * The entity type repository.
   *
   * @var \Drupal\Core\Entity\EntityTypeRepositoryInterface
   */
  protected $entityTypeRepository;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Construct a new StaticContextFormBase.
   *
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   The entity repository.
   * @param \Drupal\Core\Entity\EntityTypeRepositoryInterface $entity_type_repository
   *   The entity type repository.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityRepositoryInterface $entity_repository, EntityTypeRepositoryInterface $entity_type_repository, EntityTypeManagerInterface $entity_type_manager) {
    $this->entityRepository = $entity_repository;
    $this->entityTypeRepository = $entity_type_repository;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.repository'),
      $container->get('entity_type.repository'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * Returns the text to use for the submit button.
   *
   * @return string
   *   The submit button text.
   */
  abstract protected function submitButtonText();

  /**
   * Returns the text to use for the submit message.
   *
   * @return string
   *   The submit message text.
   */
  abstract protected function submitMessageText();

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, PageVariantInterface $page_variant = NULL, $name = '') {
    $this->pageVariant = $page_variant;
    $this->staticContext = $this->pageVariant->getStaticContext($name);

    // Allow the condition to add to the form.
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#default_value' => isset($this->staticContext['label']) ? $this->staticContext['label'] : '',
      '#required' => TRUE,
    ];
    $form['machine_name'] = [
      '#type' => 'machine_name',
      '#maxlength' => 64,
      '#required' => TRUE,
      '#machine_name' => [
        'exists' => [$this, 'contextExists'],
        'source' => ['label'],
      ],
      '#default_value' => $name,
    ];
    $form['entity_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Entity type'),
      '#options' => $this->entityTypeRepository->getEntityTypeLabels(TRUE),
      '#limit_validation_errors' => array(array('entity_type')),
      '#submit' => ['::rebuildSubmit'],
      '#executes_submit_callback' => TRUE,
      '#ajax' => array(
        'callback' => '::updateEntityType',
        'wrapper' => 'add-static-context-wrapper',
        'method' => 'replace',
      ),
    ];

    $entity = NULL;
    if ($form_state->hasValue('entity_type')) {
      $entity_type = $form_state->getValue('entity_type');
      if ($this->staticContext['value']) {
        $entity = $this->entityRepository->loadEntityByUuid($entity_type, $this->staticContext['value']);
      }
    }
    elseif (!empty($this->staticContext['type'])) {
      list(, $entity_type) = explode(':', $this->staticContext['type']);
      $entity = $this->entityRepository->loadEntityByUuid($entity_type, $this->staticContext['value']);
    }
    elseif ($this->entityTypeManager->hasDefinition('node')) {
      $entity_type = 'node';
    }
    else {
      $entity_type = 'user';
    }

    $form['entity_type']['#default_value'] = $entity_type;

    $form['selection'] = [
      '#type' => 'entity_autocomplete',
      '#prefix' => '<div id="add-static-context-wrapper">',
      '#suffix' => '</div>',
      '#required' => TRUE,
      '#target_type' => $entity_type,
      '#default_value' => $entity,
      '#title' => $this->t('Select entity'),
    ];

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->submitButtonText(),
      '#button_type' => 'primary',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $selection = $form_state->getValue('selection');
    $entity_type = $form_state->getValue('entity_type');
    $entity = $this->getEntityFromSelection($entity_type, $selection);

    $this->staticContext = [
      'label' => $form_state->getValue('label'),
      'type' => 'entity:' . $entity_type,
      'value' => $entity->uuid(),
    ];
    $this->pageVariant->setStaticContext($form_state->getValue('machine_name'), $this->staticContext);
    $this->pageVariant->save();

    // Set the submission message.
    drupal_set_message($this->submitMessageText());

    $form_state->setRedirectUrl($this->pageVariant->toUrl('edit-form'));
  }

  /**
   * Get the entity from the selection.
   *
   * @param string $entity_type
   *   The entity type ID.
   * @param string $selection
   *   The value from the selection box.
   *
   * @return \Drupal\Core\Entity\Entity|null
   *   The entity reference in selection.
   */
  protected function getEntityFromSelection($entity_type, $selection) {
    if (!isset($selection)) {
      return NULL;
    }
    return $this->entityTypeManager->getStorage($entity_type)->load($selection);
  }

  /**
   * Determines if a context with that name already exists.
   *
   * @param string $name
   *   The context name.
   *
   * @return bool
   *   TRUE if the format exists, FALSE otherwise.
   */
  public function contextExists($name) {
    return isset($this->pageVariant->getContexts()[$name]);
  }

  /**
   * Submit handler for the entity_type select field.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   *
   * @return $this
   */
  public function rebuildSubmit(array $form, FormStateInterface $form_state) {
    return $form_state->setRebuild();
  }

  /**
   * AJAX callback for the entity_type select field.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   *
   * @return array
   *   The updated entity auto complete widget on the form.
   */
  public function updateEntityType(array $form, FormStateInterface $form_state) {
    return $form['selection'];
  }

}
