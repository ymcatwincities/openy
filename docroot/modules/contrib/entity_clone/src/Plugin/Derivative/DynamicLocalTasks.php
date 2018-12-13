<?php

namespace Drupal\entity_clone\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\Core\StringTranslation\TranslationManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines dynamic local tasks.
 */
class DynamicLocalTasks extends DeriverBase implements ContainerDeriverInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * The translation manager.
   *
   * @var \Drupal\Core\StringTranslation\TranslationManager
   */
  protected $translationManager;

  /**
   * Constructs a new DynamicLocalTasks.
   *
   * @param \Drupal\Core\Entity\EntityTypeManager $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\StringTranslation\TranslationManager $string_translation
   *   The translation manager.
   */
  public function __construct(EntityTypeManager $entity_type_manager, TranslationManager $string_translation) {
    $this->entityTypeManager = $entity_type_manager;
    $this->translationManager = $string_translation;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('string_translation')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $this->derivatives = [];

    /** @var \Drupal\Core\Entity\EntityTypeInterface $entity_type */
    foreach ($this->entityTypeManager->getDefinitions() as $entity_type_id => $entity_type) {
      $has_clone_path = $entity_type->hasLinkTemplate('clone-form');
      $has_canonical_path = $entity_type->hasLinkTemplate('canonical');

      if ($has_clone_path) {
        $this->derivatives["$entity_type_id.clone_tab"] = array(
          'route_name' => "entity.$entity_type_id.clone_form",
          'title' => $this->translationManager->translate('Clone'),
          'base_route' => "entity.$entity_type_id." . ($has_canonical_path ? "canonical" : "edit_form"),
          'weight' => 100,
        );
      }
    }

    return $this->derivatives;
  }

}
