<?php

/**
 * @file
 * Contains Drupal\cors\EventSubscriber\CorsResponseEventSubscriber
 */

namespace Drupal\Lndr\EventSubscriber;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Path\AliasManagerInterface;
use Drupal\Core\Path\PathMatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Response Event Subscriber for adding CORS headers.
 */
class LndrResponseEventSubscriber implements EventSubscriberInterface {

  /**
   * The path alias manager.
   *
   * @var \Drupal\Core\Path\AliasManagerInterface
   */
  protected $aliasManager;

  /**
   * The path matcher.
   *
   * @var \Drupal\Core\Path\PathMatcherInterface
   */
  protected $pathMatcher;

  /**
   * Constructs a new CORS response event subscriber.
   *
   * @param ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Path\AliasManagerInterface $alias_manager
   *   The alias manager.
   * @param \Drupal\Core\Path\PathMatcherInterface $path_matcher
   *   The path matcher.
   */
  public function __construct(ConfigFactoryInterface $config_factory, AliasManagerInterface $alias_manager, PathMatcherInterface $path_matcher) {
    $this->aliasManager = $alias_manager;
    $this->pathMatcher = $path_matcher;
  }

  /**
   * Adds CORS headers to the response.
   *
   * @param \Symfony\Component\HttpKernel\Event\FilterResponseEvent $event
   *   The GET response event.
   */
  public function addCorsHeaders(FilterResponseEvent $event) {
    $request = $event->getRequest();
    $path_info = $request->getPathInfo();
    $current_path = $this->aliasManager->getPathByAlias($path_info);

    $page_match = $this->pathMatcher->matchPath($current_path, '/service/lndr/*');
    if ($current_path != $path_info) {
      $page_match = $page_match || $this->pathMatcher->matchPath($path_info, '/service/lndr/*');
    }
    if ($page_match) {
      // Let's add our CORS headers
      $response = $event->getResponse();
      $headers = array(
        'Access-Control-Allow-Origin' => '*',
        'Access-Control-Allow-Credentials' => true,
        'Access-Control-Allow-Methods' => 'GET, POST',
        'Access-Control-Allow-Headers' => 'authorization',
      );
      foreach ($headers as $header => $value) {
        $response->headers->set($header, $value, TRUE);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::RESPONSE][] = array('addCorsHeaders');
    return $events;
  }
}
