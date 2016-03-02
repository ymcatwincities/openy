<?php

/**
 * @file
 * Contains Drupal\search_api\Property\BasicProperty.
 */

namespace Drupal\search_api\Property;

use Drupal\Core\TypedData\DataDefinition;

/**
 * Represents a basic Search API property.
 */
class BasicProperty extends DataDefinition implements PropertyInterface {

  use PropertyTrait;

  /**
   * Create a new property from the given definition.
   *
   * @param array $definition
   *   The property's definition.
   *
   * @return static
   */
  public static function createFromDefinition(array $definition) {
    return new static($definition);
  }

  /**
   * {@inheritdoc}
   */
  public function getWrappedProperty() {
    return $this;
  }

}
