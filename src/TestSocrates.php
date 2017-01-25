<?php
/**
 * Created by PhpStorm.
 * User: podarok
 * Date: 25.01.17
 * Time: 16:32
 */

namespace Drupal\openy_socrates;


class TestSocrates implements OpenyDataServiceInterface{

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