<?php

namespace Drupal\Tests\search_api_solr\Traits;


/**
 * Provides a function to invoke protected/private methods of a class.
 */
trait InvokeMethodTrait {

  /**
   * Calls protected/private method of a class.
   *
   * @param object &$object
   *   Instantiated object that we will run method on.
   * @param string
   *   Method name to call.
   * @param array $parameters
   *   Array of parameters to pass into method.
   *
   * @return mixed
   *   Method return.
   */
  protected function invokeMethod(&$object, $methodName, array $parameters = []) {
    $reflection = new \ReflectionClass(get_class($object));
    $method = $reflection->getMethod($methodName);
    $method->setAccessible(TRUE);

    return $method->invokeArgs($object, $parameters);
  }

}
