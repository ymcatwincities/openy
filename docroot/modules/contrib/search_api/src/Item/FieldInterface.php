<?php

/**
 * @file
 * Contains \Drupal\search_api\Item\FieldInterface.
 */

namespace Drupal\search_api\Item;

/**
 * Represents a field on a search item that can be indexed.
 *
 * Traversing the object retrieves all its values.
 */
interface FieldInterface extends GenericFieldInterface, \Traversable {

  /**
   * Retrieves the Search API data type of this field.
   *
   * @return string
   *   The data type of the field.
   */
  public function getType();

  /**
   * Sets the Search API data type of this field.
   *
   * @param string $type
   *   The data type of the field.
   * @param bool $notify
   *   (optional) Whether to notify the index of the change, i.e., set the field
   *   type in the index accordingly.
   *
   * @return $this
   */
  public function setType($type, $notify = FALSE);

  /**
   * Retrieves the value of this field.
   *
   * @return array
   *   A numeric array of zero or more values for this field, with indices
   *   starting with 0.
   */
  public function getValues();

  /**
   * Sets the values of this field.
   *
   * @param array $values
   *   The values of the field.
   *
   * @return $this
   */
  public function setValues(array $values);

  /**
   * Adds a value to this field.
   *
   * @param mixed $value
   *   A value to add to this field.
   *
   * @return $this
   */
  public function addValue($value);

  /**
   * Retrieves the original data type of this field.
   *
   * This is the Drupal data type of the original property definition, which
   * might not be a valid Search API data type. Instead it has to be a type that
   * is recognized by
   * \Drupal\Core\TypedData\TypedDataManager::createDataDefinition().
   *
   * @return string
   *   The original data type.
   */
  public function getOriginalType();

  /**
   * Sets the original data type of this field.
   *
   * @param string $original_type
   *   The field's original data type.
   *
   * @return $this
   */
  public function setOriginalType($original_type);

  /**
   * Determines whether this field is indexed in the index.
   *
   * @return bool
   *   TRUE if this field is indexed in its index, FALSE otherwise.
   */
  public function isIndexed();

  /**
   * Sets whether this field is indexed in the index.
   *
   * @param bool $indexed
   *   The new indexed state of this field.
   * @param bool $notify
   *   (optional) Whether to notify the index of the change, i.e., set the field
   *   to indexed in its options, too.
   *
   * @return $this
   */
  public function setIndexed($indexed, $notify = FALSE);

  /**
   * Retrieves the field's boost value.
   *
   * @return float
   *   The boost set for this field. Defaults to 1.0 and is mostly only relevant
   *   for fulltext fields.
   */
  public function getBoost();

  /**
   * Sets the field's boost value.
   *
   * @param float $boost
   *   The new boost value.
   * @param bool $notify
   *   (optional) Whether to notify the index of the change, i.e., set the
   *   field's boost in its options, too.
   *
   * @return $this
   */
  public function setBoost($boost, $notify = FALSE);

  /**
   * Determines whether this field should always be enabled/indexed.
   *
   * @return bool
   *   TRUE if this field should be locked as enabled/indexed.
   */
  public function isIndexedLocked();

  /**
   * Sets whether this field should be locked.
   *
   * @param bool $indexed_locked
   *   (optional) TRUE if the field should be locked, FALSE otherwise.
   *
   * @return $this
   */
  public function setIndexedLocked($indexed_locked = TRUE);

  /**
   * Determines whether the type of this field should be locked.
   *
   * @return bool
   *   TRUE if the type of this field should be locked.
   */
  public function isTypeLocked();

  /**
   * Sets whether the type of this field should be locked.
   *
   * @param bool $type_locked
   *   (optional) TRUE if the type of the field should be locked, FALSE
   *   otherwise.
   *
   * @return $this
   */
  public function setTypeLocked($type_locked = TRUE);

}
