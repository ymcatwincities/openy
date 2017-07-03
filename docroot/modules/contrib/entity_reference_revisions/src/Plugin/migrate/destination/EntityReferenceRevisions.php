<?php

namespace Drupal\entity_reference_revisions\Plugin\migrate\destination;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\TypedData\TranslatableInterface;
use Drupal\migrate\MigrateException;
use Drupal\migrate\Plugin\migrate\destination\EntityRevision;
use Drupal\migrate\Plugin\MigrateIdMapInterface;
use Drupal\migrate\Row;

/**
 * Provides entity_reference_revisions destination plugin.
 *
 * @MigrateDestination(
 *   id = "entity_reference_revisions",
 *   deriver = "Drupal\entity_reference_revisions\Plugin\Derivative\MigrateEntityReferenceRevisions"
 * )
 */
class EntityReferenceRevisions extends EntityRevision {

  /**
   * {@inheritdoc}
   */
  protected static function getEntityTypeId($pluginId) {
    // Remove "entity_reference_revisions:".
    // Ideally, we would call getDerivativeId(), but since this is static
    // that is not possible so we follow the same pattern as core.
    return substr($pluginId, 27);
  }

  /**
   * {@inheritdoc}
   */
  protected function save(ContentEntityInterface $entity, array $oldDestinationIdValues = array()) {
    $entity->save();

    return [
      $this->getKey('id') => $entity->id(),
      $this->getKey('revision') => $entity->getRevisionId(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    if ($revision_key = $this->getKey('revision')) {
      $id_key = $this->getKey('id');
      $ids[$id_key]['type'] = 'integer';

      // TODO: Improve after https://www.drupal.org/node/2783715 is finished.
      $ids[$revision_key]['type'] = 'integer';

      if ($this->isTranslationDestination()) {
        if ($revision_key = $this->getKey('langcode')) {
          $ids[$revision_key]['type'] = 'string';
        }
        else {
          throw new MigrateException('This entity type does not support translation.');
        }
      }

      return $ids;
    }
    throw new MigrateException('This entity type does not support revisions.');
  }

  /**
   * {@inheritdoc}
   */
  protected function getEntity(Row $row, array $oldDestinationIdValues) {
    $revision_id = $oldDestinationIdValues ?
      array_pop($oldDestinationIdValues) :
      $row->getDestinationProperty($this->getKey('revision'));
    if (!empty($revision_id) && ($entity = $this->storage->loadRevision($revision_id))) {
      $entity->setNewRevision(FALSE);
    }
    else {
      // Attempt to ensure we always have a bundle.
      if ($bundle = $this->getBundle($row)) {
        $row->setDestinationProperty($this->getKey('bundle'), $bundle);
      }

      // Stubs might need some required fields filled in.
      if ($row->isStub()) {
        $this->processStubRow($row);
      }
      $entity = $this->storage->create($row->getDestination())
        ->enforceIsNew(TRUE);
      $entity->setNewRevision(TRUE);
    }
    $entity = $this->updateEntity($entity, $row) ?: $entity;
    $this->rollbackAction = MigrateIdMapInterface::ROLLBACK_DELETE;
    return $entity;
  }

  /**
   * {@inheritdoc}
   */
  public function rollback(array $destination_identifiers) {
    if ($this->isTranslationDestination()) {
      $this->rollbackTranslation($destination_identifiers);
    }
    else {
      $this->rollbackNonTranslation($destination_identifiers);
    }
  }

  /**
   * Rollback translation destinations.
   *
   * @param array $destination_identifiers
   *   The IDs of the destination object to delete.
   */
  protected function rollbackTranslation(array $destination_identifiers) {
    $entity = $this->storage->loadRevision(array_pop($destination_identifiers));
    if ($entity && $entity instanceof TranslatableInterface) {
      if ($key = $this->getKey('langcode')) {
        if (isset($destination_identifier[$key])) {
          $langcode = $destination_identifier[$key];
          if ($entity->hasTranslation($langcode)) {
            // Make sure we don't remove the default translation.
            $translation = $entity->getTranslation($langcode);
            if (!$translation->isDefaultTranslation()) {
              $entity->removeTranslation($langcode);
              $entity->save();
            }
          }
        }
      }
    }
  }

  /**
   * Rollback non-translation destinations.
   *
   * @param array $destination_identifiers
   *   The IDs of the destination object to delete.
   */
  protected function rollbackNonTranslation(array $destination_identifiers) {
    $revision_id = array_pop($destination_identifiers);
    $entity = $this->storage->loadRevision($revision_id);
    if ($entity) {
      if ($entity->isDefaultRevision()) {
        $entity->delete();
      }
      else {
        $this->storage->deleteRevision($revision_id);
      }
    }
  }
}
