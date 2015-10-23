<?php

/**
 * @file
 * Contains \Drupal\file_entity\Entity\FileEntity.
 */

namespace Drupal\file_entity\Entity;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\file\Entity\File;

/**
 * Replace for the core file entity class.
 */
class FileEntity extends File {

  /**
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageInterface $storage, array &$values) {
    parent::preCreate($storage, $values);
    $values += array(
      'type' => FILE_TYPE_NONE,
    );
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(array $values, $entity_type, $bundle = FALSE, $translations = array()) {
    if (!$bundle) {
      $values['type'] = FILE_TYPE_NONE;
      $bundle = FILE_TYPE_NONE;
    }

    parent::__construct($values, $entity_type, $bundle, $translations);
  }


  /**
   * {@inheritdoc}
   */
  public function url($rel = 'canonical', $options = array()) {
    // While self::urlInfo() will throw an exception if the entity is new,
    // the expected result for a URL is always a string.
    if ($this->isNew() || !$this->hasLinkTemplate($rel)) {
      return '';
    }

    $uri = $this->urlInfo($rel);
    $options += $uri->getOptions();
    $uri->setOptions($options);
    return $uri->toString();
  }

  /**
   * {@inheritdoc}
   */
  public function postCreate(EntityStorageInterface $storage) {
    parent::postCreate($storage);

    // Update the bundle.
    if ($this->bundle() === FILE_TYPE_NONE) {
      $this->updateBundle();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    if (file_exists($this->getFileUri())) {
      $this->setSize(filesize($this->getFileUri()));
    }

    $this->setMimeType(\Drupal::service('file.mime_type.guesser')->guess($this->getFileUri()));

    // Update the bundle.
    if ($this->bundle() === FILE_TYPE_NONE) {
      $this->updateBundle();
    }
    // \Drupal\Core\Entity\ContentEntityStorageBase::hasFieldChanged() expects
    // that the original entity has the same fields. Update the bundle if it was
    // changed.
    if (!empty($this->original) && $this->bundle() != $this->original->bundle()) {
      $this->original->get('type')->target_id = $this->bundle();
      $this->original->fieldDefinitions = NULL;
      $this->original->typedData = NULL;
      $this->original->entityKeys['bundle'] = $this->bundle();
    }

    // Fetch image dimensions.
    module_load_include('inc', 'file_entity', 'file_entity.file');
    file_entity_metadata_fetch_image_dimensions($this);
  }

  /**
   * Updates the file bundle.
   */
  public function updateBundle($type = NULL) {
    if (!$type) {
      $type = file_get_type($this);

      if (!$type) {
        return;
      }
    }

    // Update the type field.
    $this->get('type')->target_id = $type;
    // Clear the field definitions, so that they will be fetched for the new bundle.
    $this->fieldDefinitions = NULL;
    $this->typedData = NULL;
    // Update the entity keys cache.
    $this->entityKeys['bundle'] = $type;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);
    $fields['type'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('File type'))
      ->setDescription(t('The type of the file.'))
      ->setSetting('target_type', 'file_type');

    $fields['filename']
      ->setDisplayOptions('view', array(
        'type' => 'string',
        'label' => 'hidden',
        'weight' => -5,
      ))
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('form', array(
        'type' => 'string_textfield',
        'weight' => -5,
      ))
      ->setDisplayConfigurable('form', TRUE);

    $fields['uri']
      ->setDisplayOptions('view', array(
        'type' => 'file_image',
        'label' => 'hidden',
        'weight' => -5,
      ))
      ->setDisplayConfigurable('view', TRUE);

    $fields['uid']->setDisplayOptions('view', array(
      'type' => 'uri_link',
      'weight' => 1,
    ))
      ->setDisplayOptions('form', array(
        'type' => 'entity_reference_autocomplete',
        'weight' => -1,
        'settings' => array(
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'autocomplete_type' => 'tags',
          'placeholder' => '',
        )
      ));

    $fields['filemime']->setDisplayOptions('view', array(
      'type' => 'string',
      'weight' => 2,
    ));
    $fields['filesize']->setDisplayOptions('view', array(
      'type' => 'file_size',
      'weight' => 3,
    ));

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    // Assume that files are only embedded in other entities and don't have
    // their own cache tags.
    // @todo Make this configurable.
    return [];
  }

  /**
   * Invalidates an entity's cache tags upon save.
   *
   * @param bool $update
   *   TRUE if the entity has been updated, or FALSE if it has been inserted.
   */
  protected function invalidateTagsOnSave($update) {
    // An entity was created or updated: invalidate its list cache tags. (An
    // updated entity may start to appear in a listing because it now meets that
    // listing's filtering requirements. A newly created entity may start to
    // appear in listings because it did not exist before.)
    $tags = $this->getEntityType()->getListCacheTags();
    if ($update) {
      // Files don't have their own cache tags, instead, we invalidate cache
      // tags of entities that use that file.
      foreach (\Drupal::service('file.usage')->listUsage($this) as $module => $module_references) {
        foreach ($module_references as $type => $ids) {
          if ($this->entityManager()->hasDefinition($type)) {
            $tags = Cache::mergeTags($tags, Cache::buildTags($type, array_keys($ids)));
          }
        }
      }
    }
    Cache::invalidateTags($tags);
  }

  /**
   * Invalidates an entity's cache tags upon delete.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Entity\EntityInterface[] $entities
   *   An array of entities.
   */
  protected static function invalidateTagsOnDelete(EntityTypeInterface $entity_type, array $entities) {
    $tags = $entity_type->getListCacheTags();
    // We only invalidate cache tags of entities using the file. If a file is
    // deleted, we assume that it is no longer used.
    Cache::invalidateTags($tags);
  }

}
