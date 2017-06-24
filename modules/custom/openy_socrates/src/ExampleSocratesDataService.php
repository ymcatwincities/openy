<?php

namespace Drupal\openy_socrates;

/**
 * Class ExampleSocratesDataService.
 *
 * Here you can find a super simple example of the Data Service which is added
 * to Socrates service by default for demo purposes.
 *
 * @package Drupal\openy_socrates
 */
class ExampleSocratesDataService implements OpenyDataServiceInterface {

  /**
   * Dummy method call for exporting to Socrates service.
   *
   * @return array
   *   Test data.
   */
  public function testDummyMethodAddToSocrates() {
    return ['dummy data'];
  }

  /**
   * {@inheritdoc}
   */
  public function addDataServices(array $services) {
    return ['testDummyMethodAddToSocrates'];
  }

}
