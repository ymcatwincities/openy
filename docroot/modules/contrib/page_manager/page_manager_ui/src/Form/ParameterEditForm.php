<?php

/**
 * @file
 * Contains \Drupal\page_manager_ui\Form\ParameterEditForm.
 */

namespace Drupal\page_manager_ui\Form;

use Drupal\Core\Entity\EntityTypeRepositoryInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\TypedData\PrimitiveInterface;
use Drupal\Core\TypedData\TypedDataManagerInterface;
use Drupal\page_manager\PageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form for editing a parameter.
 */
class ParameterEditForm extends FormBase {

  /**
   * The form key for unsetting a parameter context.
   *
   * @var string
   */
  const NO_CONTEXT_KEY = '__no_context';

  /**
   * The page entity this static context belongs to.
   *
   * @var \Drupal\page_manager\PageInterface
   */
  protected $page;

  /**
   * The entity type repository.
   *
   * @var \Drupal\Core\Entity\EntityTypeRepositoryInterface
   */
  protected $entityTypeRepository;

  /**
   * The typed data manager.
   *
   * @var \Drupal\Core\TypedData\TypedDataManagerInterface
   */
  protected $typedDataManager;

  /**
   * Constructs a new ParameterEditForm.
   *
   * @param \Drupal\Core\Entity\EntityTypeRepositoryInterface $entity_type_repository
   *   The entity type repository.
   * @param \Drupal\Core\TypedData\TypedDataManagerInterface $typed_data_manager
   *   The typed data manager.
   */
  public function __construct(EntityTypeRepositoryInterface $entity_type_repository, TypedDataManagerInterface $typed_data_manager) {
    $this->entityTypeRepository = $entity_type_repository;
    $this->typedDataManager = $typed_data_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.repository'),
      $container->get('typed_data_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'page_manager_parameter_edit_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, PageInterface $page = NULL, $name = '') {
    $this->page = $page;
    $parameter = $this->page->getParameter($name);

    $form['machine_name'] = [
      '#type' => 'value',
      '#value' => $name,
    ];

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#default_value' => $parameter['label'] ?: ucfirst($parameter['machine_name']),
      '#states' => [
        'invisible' => [
          ':input[name="type"]' => ['value' => static::NO_CONTEXT_KEY],
        ],
      ],
    ];

    $form['type'] = [
      '#type' => 'select',
      '#title' => $this->t('Type'),
      '#required' => TRUE,
      '#options' => $this->buildParameterTypeOptions(),
      '#default_value' => $parameter['type'],
    ];

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Update parameter'),
      '#button_type' => 'primary',
    ];

    return $form;
  }

  /**
   * Builds an array of options for the parameter type.
   *
   * @return array[]
   *   A multidimensional array. The top level is keyed by group ('Content',
   *   'Configuration', 'Typed Data'). Those values are an array of type labels,
   *   keyed by the machine name.
   */
  protected function buildParameterTypeOptions() {
    $options = [static::NO_CONTEXT_KEY => $this->t('No context selected')];

    // Make a grouped, sorted list of entity type options. Key the inner array
    // to use the typed data format of 'entity:$entity_type_id'.
    foreach ($this->entityTypeRepository->getEntityTypeLabels(TRUE) as $group_label => $grouped_options) {
      foreach ($grouped_options as $key => $label) {
        $options[$group_label]['entity:' . $key] = $label;
      }
    }

    $primitives_label = (string) $this->t('Primitives');
    foreach ($this->typedDataManager->getDefinitions() as $key => $definition) {
      if (is_subclass_of($definition['class'], PrimitiveInterface::class)) {
        $options[$primitives_label][$key] = $definition['label'];
      }
    }
    asort($options[$primitives_label], SORT_NATURAL);

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $name = $form_state->getValue('machine_name');
    $type = $form_state->getValue('type');

    if ($type === static::NO_CONTEXT_KEY) {
      $this->page->removeParameter($name);
      $label = NULL;
    }
    else {
      $label = $form_state->getValue('label');
      $this->page->setParameter($name, $type, $label);
    }
    $this->page->save();

    // Set the submission message.
    drupal_set_message($this->t('The %label parameter has been updated.', ['%label' => $label ?: $name]));

    $form_state->setRedirectUrl($this->page->toUrl('edit-form'));
  }

}
