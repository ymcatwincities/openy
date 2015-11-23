<?php

/**
 * @file
 * Contains of \Drupal\crop\CropStorage.
 */

namespace Drupal\crop;

use Drupal\Core\Entity\Sql\SqlContentEntityStorage;

/**
 * Image crop storage class.
 */
class CropStorage extends SqlContentEntityStorage implements CropStorageInterface {

}
