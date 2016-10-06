<?php

namespace Drupal\address_test\EventSubscriber;

use Drupal\address\Event\AddressEvents;
use Drupal\address\Event\AvailableCountriesEvent;
use Drupal\address\Event\InitialValuesEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class AddressTestEventSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[AddressEvents::AVAILABLE_COUNTRIES][] = ['onAvailableCountries'];
    $events[AddressEvents::INITIAL_VALUES][] = ['onInitialValues'];
    return $events;
  }

  /**
   * Generates a set of available countries.
   *
   * @return array The countries.
   */
  public function getAvailableCountries() {
    return ['AU' => 'AU', 'BR' => 'BR', 'CA' => 'CA', 'GB' => 'GB', 'JP' => 'JP'];
  }

  /**
   * Generate a set of initial values.
   *
   * @return array Array of initial values.
   */
  public function getInitialValues() {
    return [
      'country_code' => 'AU',
      'administrative_area' => 'NSW',
      'locality' => 'Sydney',
      'dependent_locality' => '',
      'postal_code' => '2000',
      'sorting_code' => '',
      'address_line1' => 'Some address',
      'address_line2' => 'Some street',
      'organization' => 'Some Organization',
      'given_name' => 'John',
      'family_name' => 'Smith',
    ];
  }

  /**
   * Alters the available countries.
   *
   * @param \Drupal\address\Event\AvailableCountriesEvent $event
   *   The available countries event.
   */
  public function onAvailableCountries(AvailableCountriesEvent $event) {
    $event->setAvailableCountries($this->getAvailableCountries());
  }

  /**
   * Alters the initial values.
   *
   * @param \Drupal\address\Event\InitialValuesEvent $event
   *   The initial values event.
   */
  public function onInitialValues(InitialValuesEvent $event) {
    $event->setInitialValues($this->getInitialValues());
  }

}
