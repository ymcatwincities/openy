<?php

/**
 * @file
 * Definition of Drupal\stage_file_proxy\EventSubscriber\ProxySubscriber.
 */

namespace Drupal\stage_file_proxy\EventSubscriber;

use Drupal\Component\Utility\Unicode;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Url;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Drupal\stage_file_proxy\FetchManagerInterface;

/**
 * Stage file proxy subscriber for controller requests.
 */
class ProxySubscriber implements EventSubscriberInterface {

  /**
   * The manager used to fetch the file against.
   *
   * @var \Drupal\stage_file_proxy\FetchManagerInterface
   */
  protected $manager;

  /**
   * The logger.
   *
   * @var LoggerInterface
   */
  protected $logger;

  /**
   * Construct the FetchManager.
   *
   * @param \Drupal\stage_file_proxy\FetchManagerInterface $manager
   *   The manager used to fetch the file against.
   *
   * @param \Psr\Log\LoggerInterface $logger
   */
  public function __construct(FetchManagerInterface $manager, LoggerInterface $logger) {
    $this->manager = $manager;
    $this->logger = $logger;
  }

  /**
   * Fetch the file according the its origin.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
   *   The Event to process.
   */
  public function checkFileOrigin(GetResponseEvent $event) {
    $file_dir = $this->manager->filePublicPath();
    $uri = $event->getRequest()->getPathInfo();

    $uri = Unicode::substr($uri, 1);

    if (strpos($uri, '' . $file_dir) !== 0) {
      return;
    }

    // Note if the origin server files location is different. This
    // must be the exact path for the remote site's public file
    // system path, and defaults to the local public file system path.
    $remote_file_dir = trim(\Drupal::config('stage_file_proxy.settings')->get('origin_dir'));
    if (!$remote_file_dir) {
      $remote_file_dir = $file_dir;
    }

    $uri = rawurldecode($uri);
    $relative_path = Unicode::substr($uri, Unicode::strlen($file_dir) + 1);

    // Get the origin server.
    $server = \Drupal::config('stage_file_proxy.settings')->get('origin');

    if ($server) {
      // Is this imagecache? Request the root file and let imagecache resize.
      if (\Drupal::config('stage_file_proxy.settings')->get('origin') && $original_path = $this->manager->styleOriginalPath($relative_path, TRUE)) {
        $relative_path = file_uri_target($original_path);
        if (file_exists($original_path)) {
          // Imagecache can generate it without our help.
          return;
        }
      }

      $query = \Drupal::request()->query->all();
      $query_parameters = UrlHelper::filterQueryParameters($query);

      if (\Drupal::config('stage_file_proxy.settings')->get('hotlink')) {

        $location = Url::fromUri("$server/$remote_file_dir/$relative_path", array(
          'query' => $query_parameters,
          'absolute' => TRUE,
        ))->toString();

      }
      elseif ($this->manager->fetch($server, $remote_file_dir, $relative_path)) {
        // Refresh this request & let the web server work out mime type, etc.
        $location = Url::fromUri('base://' . $uri, array(
          'query' => $query_parameters,
          'absolute' => TRUE,
        ))->toString();
      }
      else {
        $this->logger->error('Stage File Proxy encountered an unknown error by retrieving file @file', array('@file' => $server . '/' . UrlHelper::encodePath($remote_file_dir . '/' . $relative_path)));
        throw new NotFoundHttpException();
      }

      header("Location: $location");
      exit;
    }
  }

  /**
   * Registers the methods in this class that should be listeners.
   *
   * @return array
   *   An array of event listener definitions.
   */
  static function getSubscribedEvents() {
    // Priority 240 is after ban middleware but before page cache.
    $events[KernelEvents::REQUEST][] = array('checkFileOrigin', 240);
    return $events;
  }

}
