<?php

namespace Drupal\address\Repository;

use CommerceGuys\Addressing\AddressFormat\AddressFormatRepository as ExternalAddressFormatRepository;
use Drupal\address\Event\AddressEvents;
use Drupal\address\Event\AddressFormatEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Provides address formats.
 *
 * Address formats are stored inside the base class, which is extended here to
 * allow the definitions to be altered via events.
 */
class AddressFormatRepository extends ExternalAddressFormatRepository {

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * Creates an AddressFormatRepository instance.
   *
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher.
   */
  public function __construct(EventDispatcherInterface $event_dispatcher) {
    $this->eventDispatcher = $event_dispatcher;
  }

  /**
   * {@inheritdoc}
   */
  protected function processDefinition($countryCode, array $definition) {
    $definition = parent::processDefinition($countryCode, $definition);
    // Allow other modules to alter the address format.
    $event = new AddressFormatEvent($definition);
    $this->eventDispatcher->dispatch(AddressEvents::ADDRESS_FORMAT, $event);
    $definition = $event->getDefinition();

    return $definition;
  }

}
