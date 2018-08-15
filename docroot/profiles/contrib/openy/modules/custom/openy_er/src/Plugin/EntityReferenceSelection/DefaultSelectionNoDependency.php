<?php

namespace Drupal\openy_er\Plugin\EntityReferenceSelection;

use Drupal\Core\Entity\Plugin\EntityReferenceSelection\DefaultSelection;

/**
 * No dependency plugin implementation of the Entity Reference Selection plugin.
 *
 * Also serves as a base class for specific types of Entity Reference
 * Selection plugins.
 *
 * @see \Drupal\Core\Entity\EntityReferenceSelection\SelectionPluginManager
 * @see \Drupal\Core\Entity\Annotation\EntityReferenceSelection
 * @see \Drupal\Core\Entity\EntityReferenceSelection\SelectionInterface
 * @see \Drupal\Core\Entity\Plugin\Derivative\DefaultSelectionDeriver
 * @see plugin_api
 *
 * @EntityReferenceSelection(
 *   id = "default_no_dep",
 *   label = @Translation("Default (openy)"),
 *   group = "default (openy)",
 *   weight = 0,
 *   deriver = "Drupal\Core\Entity\Plugin\Derivative\DefaultSelectionDeriver"
 * )
 */
class DefaultSelectionNoDependency extends DefaultSelection {

  // Intentionally empty.

}
