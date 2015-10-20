<?php

/**
 * @file
 * Contains \Drupal\default_content\LinkManager\TypeLinkManager.
 */

namespace Drupal\default_content\LinkManager;

use Drupal\rest\LinkManager\TypeLinkManager as RestTypeLinkManager;

/**
 * Creates a type link manager that references drupal.org as the domain.
 */
class TypeLinkManager extends RestTypeLinkManager {

  /**
   * {@inheritdoc}
   */
  public function getTypeUri($entity_type, $bundle) {
    // Make the base path refer to drupal.org.
    return "http://drupal.org/rest/type/$entity_type/$bundle";
  }

}
