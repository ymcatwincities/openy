<?php

/**
 * @file
 * Contains \Drupal\search_api_solr\AutoloaderSubscriber.
 */

namespace Drupal\search_api_solr\EventSubscriber;

use Drupal\Component\Utility\SafeMarkup;
use Drupal\Core\Logger\RfcLogLevel;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class AutoloaderSubscriber implements EventSubscriberInterface {

  /**
   * @var bool
   */
  protected $autoloaderRegistered = false;

  /**
   * Implements \Symfony\Component\EventDispatcher\EventSubscriberInterface::getSubscribedEvents().
   */
  public static function getSubscribedEvents() {
    return array(
      // Run very early but after composer_manager which has a priority of 999.
      KernelEvents::REQUEST => array('onRequest', 990),
    );
  }

  /**
   * Registers the autoloader.
   */
  public function onRequest(GetResponseEvent $event) {
    try {
      $this->registerAutoloader();
    }
    catch (\RuntimeException $e) {
      if (PHP_SAPI !== 'cli') {
        watchdog_exception('search_api_solr', $e, NULL, array(), RfcLogLevel::WARNING);
      }
    }
  }

  /**
   * Registers the autoloader.
   *
   * @throws \RuntimeException
   */
  public function registerAutoloader() {
    if (!$this->autoloaderRegistered) {

      // If the class can already be loaded, do nothing.
      if (class_exists('Solarium\\Client')) {
        $this->autoloaderRegistered = TRUE;
        return;
      }

      $filepath = $this->getAutoloadFilepath();
      if (!is_file($filepath)) {
        throw new \RuntimeException(SafeMarkup::format('Autoloader not found: @filepath', array('@filepath' => $filepath)));
      }
      if (($filepath != DRUPAL_ROOT . '/core/vendor/autoload.php')) {
        $this->autoloaderRegistered = TRUE;
        require $filepath;
      }
    }
  }

  /**
   * Returns the absolute path to the autoload.php file.
   *
   * @return string
   */
  public function getAutoloadFilepath() {
    return drupal_get_path('module', 'search_api_solr') . '/vendor/autoload.php';
  }

}
