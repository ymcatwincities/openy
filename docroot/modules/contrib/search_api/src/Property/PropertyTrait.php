<?php

/**
 * @file
 * Contains \Drupal\search_api\Property\PropertyTrait.
 */

namespace Drupal\search_api\Property;

/**
 * Contains methods for implementing a simple property object.
 *
 * @see \Drupal\search_api\Property\PropertyInterface
 */
trait PropertyTrait {

  /**
   * The locked state of the property.
   *
   * @var bool
   */
  protected $indexedLocked = FALSE;

  /**
   * The locked state of the property's type.
   *
   * @var bool
   */
  protected $typeLocked = FALSE;

  /**
   * The hidden state of the property.
   *
   * @var bool
   */
  protected $hidden = FALSE;

  /**
   * The fixed field settings of the property.
   *
   * @var array
   */
  protected $fieldSettings = array();

  /**
   * Sets the indexed locked state for the property.
   *
   * @param bool $indexed_locked
   *   (optional) The new indexed locked state for the property.
   *
   * @return $this
   */
  public function setIndexedLocked($indexed_locked = TRUE) {
    $this->indexedLocked = $indexed_locked;
    return $this;
  }

  /**
   * Determines whether the property should always be indexed.
   *
   * @return bool
   *   TRUE if this indexed property should be locked; FALSE otherwise.
   *
   * @see \Drupal\search_api\Property\PropertyInterface::isIndexedLocked()
   */
  public function isIndexedLocked() {
    return $this->indexedLocked;
  }

  /**
   * Sets the type locked state for the property.
   *
   * @param bool $type_locked
   *   (optional) The new type locked state for the property.
   *
   * @return $this
   */
  public function setTypeLocked($type_locked = TRUE) {
    $this->typeLocked = $type_locked;
    return $this;
  }

  /**
   * Determines whether the type of this property should be locked.
   *
   * @return bool
   *   TRUE if the type should be locked; FALSE otherwise.
   *
   * @see \Drupal\search_api\Property\PropertyInterface::isTypeLocked()
   */
  public function isTypeLocked() {
    return $this->typeLocked;
  }

  /**
   * Sets the hidden state.
   *
   * @param bool $hidden
   *   (optional) The new hidden state.
   *
   * @return $this
   */
  public function setHidden($hidden = TRUE) {
    $this->hidden = $hidden;
    return $this;
  }

  /**
   * Determines whether this processor should be hidden from the user.
   *
   * @return bool
   *   TRUE if this processor should be hidden from the user; FALSE otherwise.
   *
   * @see \Drupal\search_api\Property\PropertyInterface::isHidden()
   */
  public function isHidden() {
    return $this->hidden;
  }

  /**
   * Sets the field settings.
   *
   * @param mixed $fieldSettings
   *   The new field settings.
   *
   * @return $this
   */
  public function setFieldSettings($fieldSettings) {
    $this->fieldSettings = $fieldSettings;
    return $this;
  }

  /**
   * Retrieves the settings that the field should have, if it is locked.
   *
   * @return array
   *   An array of field settings, or an empty array to use the defaults.
   *
   * @see \Drupal\search_api\Property\PropertyInterface::getFieldSettings()
   */
  public function getFieldSettings() {
    return $this->fieldSettings;
  }

}
