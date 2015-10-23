<?php

/**
 * @file
 * Contains \Drupal\devel\StackMiddleware\DevelMiddleware.
 */

namespace Drupal\devel\StackMiddleware;

use Drupal\Component\Utility\Timer;
use Drupal\Core\Config\Config;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Database;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * Provides a HTTP middleware to collect performance related data for devel.
 */
class DevelMiddleware implements HttpKernelInterface {

  /**
   * The wrapped HTTP kernel.
   *
   * @var \Symfony\Component\HttpKernel\HttpKernelInterface
   */
  protected $httpKernel;

  /**
   * Constructs a new DevelMiddleware object.
   *
   * @param \Symfony\Component\HttpKernel\HttpKernelInterface $http_kernel
   *   The wrapped HTTP kernel.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   A config factory for retrieving required config objects.
   */
  public function __construct(HttpKernelInterface $http_kernel, ConfigFactoryInterface $config_factory) {
    $this->httpKernel = $http_kernel;
    $this->config = $config_factory->get('devel.settings');
  }

  /**
   * {@inheritdoc}
   */
  public function handle(Request $request, $type = self::MASTER_REQUEST, $catch = TRUE) {
    Timer::start('devel_page');

    if ($this->config->get('memory')) {
      // TODO: Avoid global.
      global $memory_init;
      $memory_init = memory_get_usage();
    }

    if ($this->config->get('query_display')) {
      Database::startLog('devel');
    }


    return $this->httpKernel->handle($request, $type, $catch);
  }

}
