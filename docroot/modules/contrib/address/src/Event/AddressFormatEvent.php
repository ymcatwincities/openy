<?php

namespace Drupal\address\Event;

use Symfony\Component\EventDispatcher\Event;

/**
 * Defines the address format event.
 *
 * @see \Drupal\address\Event\AddressEvents
 * @see \CommerceGuys\Addressing\AddressFormat\AddressFormat
 */
class AddressFormatEvent extends Event {

  /**
   * The address format definition.
   *
   * @var array
   */
  protected $definition;

  /**
   * Constructs a new AddressFormatEvent object.
   *
   * @param array $definition
   *   The address format definition.
   */
  public function __construct(array $definition) {
    $this->definition = $definition;
  }

  /**
   * Gets the address format definition.
   *
   * @return array
   *   The address format definition.
   */
  public function getDefinition() {
    return $this->definition;
  }

  /**
   * Sets the address format definition.
   *
   * @param array $definition
   *   The address format definition.
   *
   * @return $this
   */
  public function setDefinition(array $definition) {
    $this->definition = $definition;
    return $this;
  }

}
