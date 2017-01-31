<?php

namespace Drupal\openy_socrates;

/**
 * Class OpenySocratesFacade.
 *
 * @package Drupal\openy_socrates
 *
 * @method mixed getLocationLongtitude(array $args)
 * @method mixed getLocationLatitude(array $args)
 *
 */
class OpenySocratesFacade {

  /**
   * @var array
   */
  private $services;

  /**
   * Magic method call
   * @param $name
   * @param $arguments
   * @return string
   * @throws \Drupal\openy_socrates\OpenySocratesException
   */
  public function __call($name, $arguments) {
    switch ($name) {
      // These are for simple calls which could be kept within Socrates.
      case 'getLocationLongtitude':
        return '50';

      case 'getLocationLatitude':
        return '30';
      default:
        if (isset($this->services[$name])) {
          $data = $this->services[$name];
          $service = array_shift(array_values($data));
          return call_user_func_array([$service, $name], $arguments);
        }
        else {
          throw new OpenySocratesException(
            sprintf('Method %s not implemented yet.', $name)
          );
        }
    }
  }

  /**
   * Setter for services tagged with 'openy_data_service' tag.
   * @param array $services
   */
  public function collectDataServices($services) {
    $todo_services = [];
    foreach ($services as $priority => $allservices) {
      /**
       * @var integer $key
       * @var OpenyDataServiceInterface $service
       */
      foreach ($allservices as $key => $service) {
        foreach ($service->addDataServices($todo_services) as $method) {
          $this->services[$method][$priority] = $service;
          krsort($this->services[$method]);
        }
      }
    }
  }
}
