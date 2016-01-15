<?php

/**
 * @file
 * Contains \Drupal\acquia_connector\EventSubscriber\MaintenanceModeSubscriber.
 */

namespace Drupal\acquia_connector\EventSubscriber;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\State\StateInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Path;
use Drupal\Core\Url;
use Drupal\acquia_connector\Subscription;
use Drupal\acquia_connector\Controller;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;

/**
 * Init (i.e., hook_init()) subscriber that displays a message asking you to join
 * the Acquia network if you haven't already.
 */
class InitSubscriber implements EventSubscriberInterface {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The state factory.
   *
   * @var \Drupal\Core\KeyValueStore\StateInterface
   */
  protected $state;

  /**
   * The cache backend.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  public function __construct(ConfigFactoryInterface $config_factory, StateInterface $state, CacheBackendInterface $cache) {
    $this->configFactory = $config_factory;
    $this->state = $state;
    $this->cache = $cache;
  }

  /**
   * @param GetResponseEvent $event
   */
  public function onKernelRequest(GetResponseEvent $event) {
    // Store server information for SPI in case data is being sent from PHP CLI.
    if (PHP_SAPI == 'cli') {
      return;
    }
    // Check that there's no form submission in progress.
    if (\Drupal::request()->server->get('REQUEST_METHOD') == 'POST') {
      return;
    }
    // Check that we're not on an AJAX overlay page.
    if(\Drupal::request()->isXmlHttpRequest()) {
      return;
    }

    // Check that we're not serving a private file or image
    $controller_name = \Drupal::request()->attributes->get('_controller');
    if (strpos($controller_name, 'FileDownloadController') !== FALSE || strpos($controller_name, 'ImageStyleDownloadController') !== FALSE) {
      return;
    }

    $config = $this->configFactory->get('acquia_connector.settings');
    // Get the last time we processed data.
    $last = $this->state->get('acquia_connector.boot_last', 0);
    // 60 minute interval for storing the global variable.
    $interval = $config->get('cron_interval');
    if ($config->get('cron_interval_override')) {
      $interval = $config->get('cron_interval_override');
    }
    // Determine if the required interval has passed.
    $now = REQUEST_TIME;
    if (($now - $last) > ($interval * 60)) {
      $platform = Controller\SpiController::getPlatform();

      // acquia_spi_data_store_set() replacement.
      $expire = REQUEST_TIME + (60*60*24);
      $this->cache->set('acquia.spi.platform', $platform, $expire);
      $this->state->set('acquia_connector.boot_last', $now);
    }

    if ($config->get('hide_signup_messages')) {
      return;
    }

    // Check that we're not on one of our own config pages, all of which are prefixed
    // with admin/config/system/acquia-connector.
    $current_path = \Drupal::Request()->attributes->get('_system_path');
    if (\Drupal::service('path.matcher')->matchPath($current_path,'admin/config/system/acquia-connector/*')) {
      return;
    }

    // Check that the user has 'administer site configuration' permission.
    if (!\Drupal::currentUser()->hasPermission('administer site configuration')) {
      return;
    }

    // Check that there are no Acquia credentials currently set up.
    if (Subscription::hasCredentials()) {
      return;
    }

    // Display a message asking to connect to the Acquia Network.
    $text = 'Sign up for Acquia Cloud Free, a free Drupal sandbox to experiment with new features, test your code quality, and apply continuous integration best practices. Check out the <a href="@acquia-free">epic set of dev features and tools</a> that come with your free subscription.<br/>If you have an Acquia Subscription, <a href="@settings">connect now</a>. Otherwise, you can turn this message off by disabling the Acquia Connector modules.';
    if (\Drupal::request()->server->has('AH_SITE_GROUP')) {
      $text = '<a href="@settings">Connect your site to the Acquia Subscription now</a>. <a href="@more">Learn more</a>.';
    }
    $message = t(
      $text,
      [
        '@more' => Url::fromUri('https://docs.acquia.com/network/install')->getUri(),
        '@acquia-free' => Url::fromUri('https://www.acquia.com/acquia-cloud-free')->getUri(),
        '@settings' => Url::fromRoute('acquia_connector.setup')->toString(),
      ]);
    drupal_set_message($message, 'warning', FALSE);
  }

  /**
   * Refresh subscription information.
   * @param \Symfony\Component\HttpKernel\Event\FilterControllerEvent $event
   */
  public function onKernelController(FilterControllerEvent $event) {
    if ($event->getRequest()->attributes->get('_route') != 'update.manual_status') {
      return;
    }

    $controller = $event->getController();
    /*
     * $controller passed can be either a class or a Closure.
     * This is not usual in Symfony but it may happen.
     * If it is a class, it comes in array format
     */
    if (!is_array($controller)) {
      return;
    }

    if ($controller[0] instanceof \Drupal\update\Controller\UpdateController) {
      // Refresh subscription information, so we are sure about our update status.
      // We send a heartbeat here so that all of our status information gets
      // updated locally via the return data.
      Subscription::update();
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::REQUEST][] = ['onKernelRequest'];
    $events[KernelEvents::CONTROLLER][] = ['onKernelController'];
    return $events;
  }

}
