<?php

/**
 * @file
 * Contains \Drupal\search_api\Item\FieldTrait.
 */

namespace Drupal\search_api\Item;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\search_api\Entity\Index;
use Drupal\search_api\IndexInterface;
use Drupal\search_api\SearchApiException;
use Drupal\search_api\Utility;

/**
 * Provides a trait for classes wrapping a specific field on an index.
 *
 * @see \Drupal\search_api\Item\GenericFieldInterface
 */
trait FieldTrait {

  /**
   * The index this field is attached to.
   *
   * @var \Drupal\search_api\IndexInterface
   */
  protected $index;

  /**
   * The ID of the index this field is attached to.
   *
   * This is only used to avoid serialization of the index in __sleep() and
   * __wakeup().
   *
   * @var string
   */
  protected $indexId;

  /**
   * The field's identifier.
   *
   * @var string
   */
  protected $fieldIdentifier;

  /**
   * The field's datasource's ID.
   *
   * @var string|null
   */
  protected $datasourceId;

  /**
   * The field's datasource.
   *
   * @var \Drupal\search_api\Datasource\DatasourceInterface|null
   */
  protected $datasource;

  /**
   * The property path on the search object.
   *
   * @var string
   */
  protected $propertyPath;

  /**
   * This field's data definition.
   *
   * @var \Drupal\Core\TypedData\DataDefinitionInterface
   */
  protected $dataDefinition;

  /**
   * The human-readable label for this field.
   *
   * @var string
   */
  protected $label;

  /**
   * The human-readable description for this field.
   *
   * FALSE if the field has no description.
   *
   * @var string|false
   */
  protected $description;

  /**
   * The human-readable label for this field's datasource.
   *
   * @var string
   */
  protected $labelPrefix;

  /**
   * Whether this field should be hidden from the user.
   *
   * @var bool
   */
  protected $hidden;

  /**
   * Constructs a FieldTrait object.
   *
   * @param \Drupal\search_api\IndexInterface $index
   *   The field's index.
   * @param string $field_identifier
   *   The field's combined identifier, with datasource prefix if applicable.
   */
  public function __construct(IndexInterface $index, $field_identifier) {
    $this->index = $index;
    $this->fieldIdentifier = $field_identifier;
    list($this->datasourceId, $this->propertyPath) = Utility::splitCombinedId($field_identifier);
  }

  /**
   * Returns the index of this field.
   *
   * @return \Drupal\search_api\IndexInterface
   *   The index to which this field belongs.
   *
   * @see \Drupal\search_api\Item\GenericFieldInterface::getIndex()
   */
  public function getIndex() {
    return $this->index;
  }

  /**
   * Returns the index of this field.
   *
   * This is useful when retrieving fields from cache, to have the index always
   * set to the same object that is returning them. The method shouldn't be used
   * in any other case.
   *
   * @param \Drupal\search_api\IndexInterface $index
   *   The index to which this field belongs.
   *
   * @return $this
   *
   * @throws \InvalidArgumentException
   *   Thrown if the ID of the given index is not the same as the ID of the
   *   index that was set up to now.
   *
   * @see \Drupal\search_api\Item\GenericFieldInterface::setIndex()
   */
  public function setIndex(IndexInterface $index) {
    if ($this->index->id() != $index->id()) {
      throw new \InvalidArgumentException('Attempted to change the index of a field object.');
    }
    $this->index = $index;
    return $this;
  }

  /**
   * Returns the field identifier of this field.
   *
   * @return string
   *   The identifier of this field.
   *
   * @see \Drupal\search_api\Item\GenericFieldInterface::getFieldIdentifier()
   */
  public function getFieldIdentifier() {
    return $this->fieldIdentifier;
  }

  /**
   * Retrieves the ID of this field's datasource.
   *
   * @return string|null
   *   The plugin ID of this field's datasource, or NULL if the field is
   *   datasource-independent.
   *
   * @see \Drupal\search_api\Item\GenericFieldInterface::getDatasourceId()
   */
  public function getDatasourceId() {
    return $this->datasourceId;
  }

  /**
   * Returns the datasource of this field.
   *
   * @return \Drupal\search_api\Datasource\DatasourceInterface|null
   *   The datasource to which this field belongs. NULL if the field is
   *   datasource-independent.
   *
   * @throws \Drupal\search_api\SearchApiException
   *   Thrown if the field's datasource couldn't be loaded.
   *
   * @see \Drupal\search_api\Item\GenericFieldInterface::getDatasource()
   */
  public function getDatasource() {
    if (!isset($this->datasource) && isset($this->datasourceId)) {
      $this->datasource = $this->index->getDatasource($this->datasourceId);
    }
    return $this->datasource;
  }

  /**
   * Retrieves this field's property path.
   *
   * @return string
   *   The property path.
   *
   * @see \Drupal\search_api\Item\GenericFieldInterface::getPropertyPath()
   */
  public function getPropertyPath() {
    return $this->propertyPath;
  }

  /**
   * Retrieves this field's label.
   *
   * The field's label, contrary to the label returned by the field's data
   * definition, contains a human-readable representation of the full property
   * path. The datasource label is not included, though – use getPrefixedLabel()
   * for that.
   *
   * @return string
   *   A human-readable label representing this field's property path.
   *
   * @see \Drupal\search_api\Item\GenericFieldInterface::getLabel()
   */
  public function getLabel() {
    if (!isset($this->label)) {
      $label = '';
      try {
        $label = $this->getDataDefinition()->getLabel();
      }
      catch (SearchApiException $e) {
        watchdog_exception('search_api', $e);
      }
      $pos = strrpos($this->propertyPath, ':');
      if ($pos) {
        $parent_id = substr($this->propertyPath, 0, $pos);
        if ($this->datasourceId) {
          $parent_id = Utility::createCombinedId($this->datasourceId, $parent_id);
        }
        $label = Utility::createField($this->index, $parent_id)->getLabel() . ' » ' . $label;
      }
      $this->label = $label;
    }
    return $this->label;
  }

  /**
   * Sets this field's label.
   *
   * @param string $label
   *   A human-readable label representing this field's property path.
   *
   * @return $this
   *
   * @see \Drupal\search_api\Item\GenericFieldInterface::setLabel()
   */
  public function setLabel($label) {
    $this->label = $label;
    return $this;
  }

  /**
   * Retrieves this field's description.
   *
   * @return string|null
   *   A human-readable description for this field, or NULL if the field has no
   *   description.
   *
   * @see \Drupal\search_api\Item\GenericFieldInterface::getDescription()
   */
  public function getDescription() {
    if (!isset($this->description)) {
      try {
        $this->description = $this->getDataDefinition()->getDescription();
        $this->description = $this->description ?: FALSE;
      }
      catch (SearchApiException $e) {
        watchdog_exception('search_api', $e);
      }
    }
    return $this->description ?: NULL;
  }

  /**
   * Sets this field's description.
   *
   * @param string|null $description
   *   A human-readable description for this field, or NULL if the field has no
   *   description.
   *
   * @return $this
   *
   * @see \Drupal\search_api\Item\GenericFieldInterface::setDescription()
   */
  public function setDescription($description) {
    // Set FALSE instead of NULL so caching will work properly.
    $this->description = $description ?: FALSE;
    return $this;
  }

  /**
   * Retrieves this field's label along with datasource prefix.
   *
   * Returns a value similar to getLabel(), but also contains the datasource
   * label, if applicable.
   *
   * @return string
   *   A human-readable label representing this field's property path and
   *   datasource.
   *
   * @see \Drupal\search_api\Item\GenericFieldInterface::getPrefixedLabel()
   */
  public function getPrefixedLabel() {
    if (!isset($this->labelPrefix)) {
      $this->labelPrefix = '';
      if (isset($this->datasourceId)) {
        $this->labelPrefix = $this->datasourceId;
        try {
          $this->labelPrefix = $this->getDatasource()->label();
        }
        catch (SearchApiException $e) {
          watchdog_exception('search_api', $e);
        }
        $this->labelPrefix .= ' » ';
      }
    }
    return $this->labelPrefix . $this->getLabel();
  }

  /**
   * Sets this field's label prefix.
   *
   * @param string $label_prefix
   *   A human-readable label representing this field's datasource and ending in
   *   some kind of visual separator.
   *
   * @return $this
   *
   * @see \Drupal\search_api\Item\GenericFieldInterface::setLabelPrefix()
   */
  public function setLabelPrefix($label_prefix) {
    $this->labelPrefix = $label_prefix;
    return $this;
  }

  /**
   * Determines whether this field should be hidden from the user.
   *
   * @return bool
   *   TRUE if this field should be hidden from the user.
   */
  public function isHidden() {
    return (bool) $this->hidden;
  }

  /**
   * Sets whether this field should be hidden from the user.
   *
   * @param bool $hidden
   *   (optional) TRUE if the field should be hidden, FALSE otherwise.
   *
   * @return $this
   *
   * @see \Drupal\search_api\Item\GenericFieldInterface::setHidden()
   */
  public function setHidden($hidden = TRUE) {
    $this->hidden = $hidden;
    return $this;
  }

  /**
   * Retrieves this field's data definition.
   *
   * @return \Drupal\Core\TypedData\DataDefinitionInterface
   *   The data definition object for this field.
   *
   * @throws \Drupal\search_api\SearchApiException
   *   Thrown if the field's data definition is unknown.
   *
   * @see \Drupal\search_api\Item\GenericFieldInterface::getDataDefinition()
   */
  // @todo This currently only works for unnested fields, since
  //   Index::getPropertyDefinitions() won't return any nested ones.
  public function getDataDefinition() {
    if (!isset($this->dataDefinition)) {
      $definitions = $this->index->getPropertyDefinitions($this->datasourceId);
      if (!isset($definitions[$this->propertyPath])) {
        $args['@field'] = $this->fieldIdentifier;
        $args['%index'] = $this->index->label();
        throw new SearchApiException(new FormattableMarkup('Could not retrieve data definition for field "@field" on index %index.', $args));
      }
      $this->dataDefinition = $definitions[$this->propertyPath];
    }
    return $this->dataDefinition;
  }

  /**
   * Implements the magic __sleep() method to control object serialization.
   */
  public function __sleep() {
    $properties = $this->getSerializationProperties();
    return array_keys($properties);
  }

  /**
   * Retrieves the properties that should be serialized.
   *
   * Used in __sleep(), but extracted to be more easily usable for subclasses.
   *
   * @return array
   *   An array mapping property names of this object to their values.
   */
  protected function getSerializationProperties() {
    $this->indexId = $this->index->id();
    $properties = get_object_vars($this);
    // Don't serialize objects in properties.
    unset($properties['index'], $properties['datasource'], $properties['dataDefinition']);
    return $properties;
  }

  /**
   * Implements the magic __wakeup() method to control object unserialization.
   */
  public function __wakeup() {
    if ($this->indexId) {
      $this->index = Index::load($this->indexId);
      unset($this->indexId);
    }
  }

}
