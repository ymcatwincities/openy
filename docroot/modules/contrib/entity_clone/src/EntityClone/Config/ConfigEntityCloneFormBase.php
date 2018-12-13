<?php

namespace Drupal\entity_clone\EntityClone\Config;

use Drupal\Core\Entity\EntityHandlerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslationManager;
use Drupal\entity_clone\EntityClone\EntityCloneFormInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class ConfigEntityCloneFormBase.
 */
class ConfigEntityCloneFormBase implements EntityHandlerInterface, EntityCloneFormInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\StringTranslation\TranslationManager
   */
  protected $translationManager;

  protected $entityTypeManager;

  /**
   * Constructs a new ConfigEntityCloneFormBase.
   *
   * @param \Drupal\Core\Entity\EntityTypeManager $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\StringTranslation\TranslationManager $translation_manager
   *   The string translation manager.
   */
  public function __construct(EntityTypeManager $entity_type_manager, TranslationManager $translation_manager) {
    $this->entityTypeManager = $entity_type_manager;
    $this->translationManager = $translation_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('string_translation')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(EntityInterface $entity) {
    $form = [];

    if ($this->entityTypeManager->getDefinition($entity->getEntityTypeId())->getKey('label')) {
      $form['label'] = array(
        '#type' => 'textfield',
        '#title' => $this->translationManager->translate('New Label'),
        '#maxlength' => 255,
        '#required' => TRUE,
      );
    }

    $form['id'] = array(
      '#type' => 'machine_name',
      '#title' => $this->translationManager->translate('New Id'),
      '#maxlength' => 255,
      '#required' => TRUE,
    );

    // If entity must have a prefix
    // (e.g. entity_form_mode, entity_view_mode, ...).
    if (method_exists($entity, 'getTargetType')) {
      $form['id']['#field_prefix'] = $entity->getTargetType() . '.';
    }

    if (method_exists($entity, 'load')) {
      $form['id']['#machine_name'] = [
        'exists' => get_class($entity) . '::load',
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getNewValues(FormStateInterface $form_state) {
    // If entity must have a prefix
    // (e.g. entity_form_mode, entity_view_mode, ...).
    $field_prefix = '';
    if (isset($form_state->getCompleteForm()['id']['#field_prefix'])) {
      $field_prefix = $form_state->getCompleteForm()['id']['#field_prefix'];
    }

    return [
      'id' => $field_prefix . $form_state->getValue('id'),
      'label' => $form_state->getValue('label'),
    ];
  }

}
