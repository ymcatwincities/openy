<?php

/**
 * @file
 * Contains Drupal\search_api\Property\SearchPropertyInterface.
 */

namespace Drupal\search_api\Property;

use Drupal\Core\TypedData\DataDefinitionInterface;

/**
 * Represents a special kind of data definition used in the Search API.
 */
interface PropertyInterface extends DataDefinitionInterface {

  /**
   * Determines whether this property should always be indexed.
   *
   * @return bool
   *   TRUE if this property should always be indexed; FALSE otherwise.
   */
  public function isIndexedLocked();

  /**
   * Determines whether the type of this property should be locked.
   *
   * @return bool
   *   TRUE if the type should be locked; FALSE otherwise.
   */
  public function isTypeLocked();

  /**
   * Determines whether this processor should be hidden from the user.
   *
   * @return bool
   *   TRUE if this processor should be hidden from the user; FALSE otherwise.
   */
  public function isHidden();

  /**
   * Retrieves the settings that the field should have, if it is locked.
   *
   * @return array
   *   An array of field settings, or an empty array to use the defaults.
   */
  public function getFieldSettings();

  /**
   * Returns the wrapped property, if any.
   *
   * @return \Drupal\Core\TypedData\DataDefinitionInterface
   *   The wrapper data definition, or $this if this property wasn't created as
   *   a wrapper to an existing data definition.
   */
  public function getWrappedProperty();

}
