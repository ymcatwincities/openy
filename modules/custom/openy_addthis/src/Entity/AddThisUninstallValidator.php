<?php

namespace Drupal\openy_addthis\Entity;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\FieldableEntityStorageInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Extension\ModuleUninstallValidatorInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\Url;

/**
 * Validates module uninstall readiness based on existing content entities.
 */
class AddThisUninstallValidator implements ModuleUninstallValidatorInterface {

  use StringTranslationTrait;

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new ContentUninstallValidator.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_manager
   *   The entity manager.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translation service.
   */
  public function __construct(EntityTypeManagerInterface $entity_manager, TranslationInterface $string_translation) {
    $this->entityTypeManager = $entity_manager;
    $this->stringTranslation = $string_translation;
  }

  /**
   * {@inheritdoc}
   */
  public function validate($module) {
    $reasons = [];
    if ($module !== 'openy_addthis') {
      return $reasons;
    }
    $entity_types = $this->entityTypeManager->getDefinitions();
    foreach ($entity_types as $entity_type_id => $entity_type) {
      $storage = $this->entityTypeManager->getStorage($entity_type_id);

      if ($storage instanceof EntityFieldManagerInterface) {
        foreach ($storage->getFieldStorageDefinitions() as $storage_definition) {
          if (
            $storage_definition->getProvider() === 'openy_addthis'
            && $storage instanceof FieldableEntityStorageInterface
            && $storage->countFieldData($storage_definition, TRUE)
          ) {

            $reasons[] = $this->t('<a href="@url">Remove field values</a>: @field-name on entity type @entity_type.', [
              '@field-name' => $storage_definition->getName(),
              '@entity_type' => $entity_type->getLabel(),
              '@url' => Url::fromRoute(
                'openy_addthis.prepare_module_uninstall'
              )->toString(),
            ]);
          }
        }
      }
    }
    return $reasons;
  }

}
