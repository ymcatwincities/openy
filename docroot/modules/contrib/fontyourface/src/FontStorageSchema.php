<?php

namespace Drupal\fontyourface;

use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Entity\Sql\SqlContentEntityStorageSchema;

/**
 * Defines the font schema handler.
 */
class FontStorageSchema extends SqlContentEntityStorageSchema {

  /**
   * {@inheritdoc}
   */
  protected function getEntitySchema(ContentEntityTypeInterface $entity_type, $reset = FALSE) {
    $schema = parent::getEntitySchema($entity_type, $reset);

    $schema['fontyourface_font']['indexes'] += [
      'name' => ['name'],
      'pid' => ['pid'],
    ];
    $schema['fontyourface_font']['unique keys'] += [
      'url' => ['url'],
    ];

    return $schema;
  }

}
