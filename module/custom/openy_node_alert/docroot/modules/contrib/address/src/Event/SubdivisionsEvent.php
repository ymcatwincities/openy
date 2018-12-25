<?php

namespace Drupal\address\Event;

use Symfony\Component\EventDispatcher\Event;

/**
 * Defines the subdivisions event.
 *
 * @see \Drupal\address\Event\AddressEvents
 * @see \Drupal\address_test\EventSubscriber\GreatBritainEventSubscriber
 * @see \CommerceGuys\Addressing\Subdivision\Subdivision
 */
class SubdivisionsEvent extends Event {

  /**
   * The subdivision parents (country code, subdivision codes).
   *
   * @var array
   */
  protected $parents;

  /**
   * The subdivision definitions.
   *
   * @var array
   */
  protected $definitions = [];

  /**
   * Constructs a new SubdivisionsEvent object.
   *
   * @param array $parents
   *   The subdivision parents.
   */
  public function __construct(array $parents) {
    $this->parents = $parents;
  }

  /**
   * Gets the subdivision parents.
   *
   * @return array
   *   The subdivision parents.
   */
  public function getParents() {
    return $this->parents;
  }

  /**
   * Gets the subdivision definitions.
   *
   * @return array
   *   The subdivision definitions.
   */
  public function getDefinitions() {
    return $this->definitions;
  }

  /**
   * Sets the subdivision definitions.
   *
   * @param array $definitions
   *   The subdivision definitions.
   *
   * @return $this
   */
  public function setDefinitions(array $definitions) {
    $this->definitions = $definitions;
    return $this;
  }

}
