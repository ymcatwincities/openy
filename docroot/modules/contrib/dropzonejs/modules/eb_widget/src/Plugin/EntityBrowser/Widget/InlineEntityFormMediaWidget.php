<?php

namespace Drupal\dropzonejs_eb_widget\Plugin\EntityBrowser\Widget;

use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Utility\Token;
use Drupal\dropzonejs\DropzoneJsUploadSaveInterface;
use Drupal\dropzonejs\Events\DropzoneMediaEntityCreateEvent;
use Drupal\dropzonejs\Events\Events;
use Drupal\entity_browser\WidgetBase;
use Drupal\entity_browser\WidgetValidationManager;
use Drupal\inline_entity_form\Element\InlineEntityForm;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Provides an Entity Browser widget that uploads and edit new files.
 *
 * @EntityBrowserWidget(
 *   id = "dropzonejs_media_entity_inline_entity_form",
 *   label = @Translation("Media Entity DropzoneJS with edit"),
 *   description = @Translation("Adds DropzoneJS upload integration that saves Media entities and allows to edit them."),
 *   auto_select = FALSE
 * )
 */
class InlineEntityFormMediaWidget extends MediaEntityDropzoneJsEbWidget {

  /**
   * The entity display repository.
   *
   * @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface
   */
  protected $entityDisplayRepository;

  /**
   * Constructs widget plugin.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   Event dispatcher service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\entity_browser\WidgetValidationManager $validation_manager
   *   The Widget Validation Manager service.
   * @param \Drupal\dropzonejs\DropzoneJsUploadSaveInterface $dropzonejs_upload_save
   *   The upload saving dropzonejs service.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current user service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler service.
   * @param \Drupal\Core\Entity\EntityDisplayRepositoryInterface $entity_display_repository
   *   The entity display repository service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EventDispatcherInterface $event_dispatcher, EntityTypeManagerInterface $entity_type_manager, WidgetValidationManager $validation_manager, DropzoneJsUploadSaveInterface $dropzonejs_upload_save, AccountProxyInterface $current_user, Token $token, ModuleHandlerInterface $module_handler, EntityDisplayRepositoryInterface $entity_display_repository) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $event_dispatcher, $entity_type_manager, $validation_manager, $dropzonejs_upload_save, $current_user, $token, $module_handler);

    $this->entityDisplayRepository = $entity_display_repository;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('event_dispatcher'),
      $container->get('entity_type.manager'),
      $container->get('plugin.manager.entity_browser.widget_validation'),
      $container->get('dropzonejs.upload_save'),
      $container->get('current_user'),
      $container->get('token'),
      $container->get('module_handler'),
      $container->get('entity_display.repository')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'form_mode' => 'default',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function getForm(array &$original_form, FormStateInterface $form_state, array $additional_widget_parameters) {
    $form = parent::getForm($original_form, $form_state, $additional_widget_parameters);

    // @todo Remove this when/if EB provides a way to define dependencies.
    if (!$this->moduleHandler->moduleExists('inline_entity_form')) {
      return [
        '#type' => 'container',
        'error' => [
          '#markup' => $this->t('Missing requirement: in order to use this widget you have to install Inline entity form module first'),
        ],
      ];
    }

    $form['#attached']['library'][] = 'dropzonejs_eb_widget/ief_edit';
    $form['edit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Edit'),
      '#attributes' => [
        'class' => ['js-hide'],
      ],
      '#ajax' => [
        'wrapper' => 'ief-dropzone-upload',
        'callback' => [static::class, 'onEdit'],
        'effect' => 'fade',
      ],
      '#submit' => [
        [$this, 'submitEdit'],
      ],
    ];

    $form['entities']['#prefix'] = '<div id="ief-dropzone-upload">';
    $form['entities']['#suffix'] = '</div>';

    $form += ['entities' => []];
    if ($entities = $form_state->get('uploaded_entities')) {
      foreach ($entities as $entity) {
        /** @var \Drupal\Core\Entity\EntityInterface $entity */
        $form['entities'][$entity->uuid()] = [
          '#type' => 'inline_entity_form',
          '#entity_type' => $entity->getEntityTypeId(),
          '#bundle' => $entity->bundle(),
          '#default_value' => $entity,
          '#form_mode' => $this->configuration['form_mode'],
        ];
      }
    }

    if (!empty(Element::children($form['entities']))) {
      // Make it possible to select those submitted entities.
      $pos = array_search('dropzonejs-disable-submit', $original_form['#attributes']['class']);
      if ($pos !== FALSE) {
        unset($original_form['#attributes']['class'][$pos]);
      }
    }

    $form['actions']['submit'] += ['#submit' => []];

    return $form;
  }

  /**
   * Submit callback for the edit button.
   *
   * @param array $form
   *   Form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form object.
   */
  public function submitEdit(array $form, FormStateInterface $form_state) {
    $form_state->setRebuild(TRUE);

    // Files have to saved before they can be viewed in the IEF form.
    $media_entities = $this->prepareEntities($form, $form_state);
    $source_field = $this->getBundle()->getTypeConfiguration()['source_field'];
    foreach ($media_entities as $media_entity) {
      /** @var \Drupal\file\Entity\File $file */
      $file = $media_entity->$source_field->entity;
      $file->save();
      $media_entity->$source_field->target_id = $file->id();
    }

    $form_state->set('uploaded_entities', $media_entities);
  }

  /**
   * Ajax callback triggered when hitting the edit button.
   *
   * @param array $form
   *   The form.
   *
   * @return array
   *   Returns the entire form.
   */
  public static function onEdit(array $form) {
    return $form['widget']['entities'];
  }

  /**
   * {@inheritdoc}
   */
  public function validate(array &$form, FormStateInterface $form_state) {
    // Skip the DropzoneJsEbWidget specific validations.
    WidgetBase::validate($form, $form_state);
  }

  /**
   * Prepares entities from the form.
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return \Drupal\media_entity\MediaInterface[]
   *   The prepared media entities.
   */
  protected function prepareEntitiesFromForm(array $form, FormStateInterface $form_state) {
    $media_entities = [];
    foreach (Element::children($form['widget']['entities']) as $key) {
      /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
      $entity = $form['widget']['entities'][$key]['#entity'];
      $inline_entity_form_handler = InlineEntityForm::getInlineFormHandler($entity->getEntityTypeId());
      $inline_entity_form_handler->entityFormSubmit($form['widget']['entities'][$key], $form_state);
      $media_entities[] = $entity;
    }
    return $media_entities;
  }

  /**
   * {@inheritdoc}
   */
  public function submit(array &$element, array &$form, FormStateInterface $form_state) {
    $media_entities = $this->prepareEntitiesFromForm($form, $form_state);
    $source_field = $this->getBundle()->getTypeConfiguration()['source_field'];

    foreach ($media_entities as $media_entity) {
      $file = $media_entity->{$source_field}->entity;
      /** @var \Drupal\dropzonejs\Events\DropzoneMediaEntityCreateEvent $event */
      $event = $this->eventDispatcher->dispatch(Events::MEDIA_ENTITY_CREATE, new DropzoneMediaEntityCreateEvent($media_entity, $file, $form, $form_state, $element));
      $media_entity = $event->getMediaEntity();
      $media_entity->save();
    }

    if (!empty(array_filter($media_entities))) {
      $this->selectEntities($media_entities, $form_state);
      $this->clearFormValues($element, $form_state);
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function clearFormValues(array &$element, FormStateInterface $form_state) {
    parent::clearFormValues($element, $form_state);
    // Clear uploaded_entities to get a clean form in multi-step selection.
    $form_state->set('uploaded_entities', []);
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    $form['form_mode'] = [
      '#type' => 'select',
      '#title' => $this->t('Form mode'),
      '#options' => $this->entityDisplayRepository->getFormModeOptions('media'),
      '#default_value' => $this->configuration['form_mode'],
    ];
    return $form;
  }

}
