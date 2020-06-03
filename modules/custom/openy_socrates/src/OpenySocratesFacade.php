<?php

namespace Drupal\openy_socrates;

use Drupal\Component\Utility\Timer;
use Drupal\Core\State\StateInterface;

/**
 * Class OpenySocratesFacade.
 *
 * @package Drupal\openy_socrates
 *
 * @method mixed getLocationLongtitude(array $args)
 * @method mixed getLocationLatitude(array $args)
 */
class OpenySocratesFacade {

  /**
   * State interface.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * Data Services.
   *
   * @var array
   */
  private $services;

  /**
   * Cron Services.
   *
   * @var array
   */
  private $cronServices;

  /**
   * OpenySocratesFacade constructor.
   *
   * @param \Drupal\Core\State\StateInterface $state
   *   State.
   */
  public function __construct(StateInterface $state) {
    $this->state = $state;
  }

  /**
   * Magic method call.
   *
   * @param string $name
   *   Service name.
   * @param array $arguments
   *   Service arguments.
   *
   * @return mixed
   *   Service result.
   *
   * @throws \Drupal\openy_socrates\OpenySocratesException
   */
  public function __call($name, array $arguments) {
    if (isset($this->services[$name])) {
      // Get array of possible variants for the call.
      $calls_data = $this->services[$name];
      // Reset key values for easier access.
      $reset_keys_data = array_values($calls_data);
      // Proceed with first, highest priority item.
      $service = array_shift($reset_keys_data);
      return call_user_func_array([$service, $name], $arguments);
    }
    else {
      throw new OpenySocratesException(
        sprintf('Method %s not implemented yet.', $name)
      );
    }
  }

  /**
   * Setter for services tagged with 'openy_data_service' tag.
   *
   * @param array $services
   *   Services.
   */
  public function collectDataServices(array $services) {
    $todo_services = [];
    foreach ($services as $priority => $allservices) {
      /*
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

  /**
   * Setter for services tagged with 'openy_cron_service' tag.
   *
   * @param array $services
   *   Services.
   */
  public function collectCronServices(array $services) {
    /** @var OpenyCronServiceInterface $service */
    foreach ($services as $periodicity => $service) {
      $this->cronServices[$periodicity] = $service;
    }
  }

  /**
   * Runner for all openy cron services.
   */
  public function cron() {
    $request_time = \Drupal::time()->getRequestTime();

    // @todo Use config to stop/resume the runner.
    $prefix = 'openy_cron_';
    /** @var OpenyCronServiceInterface $service */
    foreach ($this->cronServices as $periodicity => $services) {
      foreach ($services as $service) {
        $name = $prefix . $service->_serviceId;
        $last_run = $this->state->get($name);
        if (($request_time - $last_run) > $periodicity) {
          // @todo Fix DI on OpenY sprint.
          \Drupal::logger("openy_cron")->info('Service %service has been started.', ['%service' => $service->_serviceId]);
          Timer::start($name);
          $service->runCronServices();
          $execution_time = Timer::read($name) / 1000;
          $this->state->set($name, $request_time);
          \Drupal::logger("openy_cron")->info('Service %service has been finished. Execution time: %time sec.', ['%service' => $service->_serviceId, '%time' => $execution_time]);
          Timer::stop($name);
        }
      }
    }
  }

}
