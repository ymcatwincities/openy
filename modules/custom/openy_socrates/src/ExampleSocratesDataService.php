<?php
/**
 * Here you can find a super simple example of the Data Service which is added
 * to Socrates service by default for demo purposes.
 */

namespace Drupal\openy_socrates;

/**
 * Class ExampleSocratesDataService.
 *
 * @package Drupal\openy_socrates
 */
class ExampleSocratesDataService implements OpenyDataServiceInterface {

  /**
   * Dummy method call for exporting to Socrates service.
   * @return array
   */
  public function TestDummyMethodAddToSocrates() {
    return ['dummy data'];
  }

  /**
   * {@inheritdoc}
   */
  public function addDataServices($services) {
    return array('TestDummyMethodAddToSocrates');
  }
}
