<?php

/**
 * @file
 * Contains Drupal\search_api\Property\ProxyProperty.
 */

namespace Drupal\search_api\Property;

use Doctrine\Common\Proxy\Exception\InvalidArgumentException;
use Drupal\Core\TypedData\DataDefinitionInterface;

/**
 * Provides a property that works largely as a wrapper of an existing property.
 */
class ProxyProperty implements PropertyInterface {

  use PropertyTrait;

  /**
   * The wrapped property.
   *
   * @var \Drupal\Core\TypedData\DataDefinitionInterface
   */
  protected $wrappedProperty;

  /**
   * Constructs a ProxyProperty object.
   *
   * @param \Drupal\Core\TypedData\DataDefinitionInterface $wrappedProperty
   *   The wrapped property.
   */
  public function __construct(DataDefinitionInterface $wrappedProperty) {
    $this->wrappedProperty = $wrappedProperty;
  }

  /**
   * Creates a new wrapped property.
   *
   * @param \Drupal\Core\TypedData\DataDefinitionInterface $wrappedProperty
   *   The wrapped property.
   *
   * @return static
   */
  public static function create(DataDefinitionInterface $wrappedProperty) {
    return new static($wrappedProperty);
  }

  /**
   * {@inheritdoc}
   */
  public function getWrappedProperty() {
    return $this->wrappedProperty;
  }

  /**
   * {@inheritdoc}
   */
  public static function createFromDataType($data_type) {
    throw new InvalidArgumentException('\Drupal\search_api\Property\ProxyProperty::createFromDataType is not implemented');
  }

  /**
   * {@inheritdoc}
   */
  public function getDataType() {
    if (!$this->wrappedProperty) {
      return NULL;
    }
    return $this->wrappedProperty->getDataType();
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel() {
    if (!$this->wrappedProperty) {
      return NULL;
    }
    return $this->wrappedProperty->getLabel();
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->wrappedProperty->getDescription();
  }

  /**
   * {@inheritdoc}
   */
  public function isList() {
    return $this->wrappedProperty->isList();
  }

  /**
   * {@inheritdoc}
   */
  public function isReadOnly() {
    return $this->wrappedProperty->isReadOnly();
  }

  /**
   * {@inheritdoc}
   */
  public function isComputed() {
    return $this->wrappedProperty->isComputed();
  }

  /**
   * {@inheritdoc}
   */
  public function isRequired() {
    return $this->wrappedProperty->isRequired();
  }

  /**
   * {@inheritdoc}
   */
  public function getClass() {
    return $this->wrappedProperty->getClass();
  }

  /**
   * {@inheritdoc}
   */
  public function getSettings() {
    return $this->wrappedProperty->getSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function getSetting($setting_name) {
    return $this->wrappedProperty->getSetting($setting_name);
  }

  /**
   * {@inheritdoc}
   */
  public function getConstraints() {
    return $this->wrappedProperty->getConstraints();
  }

  /**
   * {@inheritdoc}
   */
  public function getConstraint($constraint_name) {
    return $this->wrappedProperty->getConstraint($constraint_name);
  }

  /**
   * {@inheritdoc}
   */
  public function addConstraint($constraint_name, $options = NULL) {
    return $this->wrappedProperty->addConstraint($constraint_name, $options);
  }

}
