<?php

namespace Drupal\purge\EventSubscriber;

use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\Core\Cache\CacheableResponseInterface;
use Drupal\purge\Plugin\Purge\TagsHeader\TagsHeadersServiceInterface;

/**
 * Add cache tags headers on cacheable responses, for external caching systems.
 */
class CacheableResponseSubscriber implements EventSubscriberInterface {

  /**
   * The tagsheaders service for iterating the available header plugins.
   *
   * @var \Drupal\purge\Plugin\Purge\TagsHeader\TagsHeadersServiceInterface
   */
  protected $purgeTagsHeaders;

  /**
   * Constructs a CacheableResponseSubscriber object.
   *
   * @param \Drupal\purge\Plugin\Purge\TagsHeader\TagsHeadersServiceInterface $purge_tagsheaders
   *   The tagsheaders service for iterating the available header plugins.
   */
  public function __construct(TagsHeadersServiceInterface $purge_tagsheaders) {
    $this->purgeTagsHeaders = $purge_tagsheaders;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::RESPONSE][] = ['onRespond'];
    return $events;
  }

  /**
   * Add cache tags headers on cacheable responses.
   *
   * @param \Symfony\Component\HttpKernel\Event\FilterResponseEvent $event
   *   The event to process.
   */
  public function onRespond(FilterResponseEvent $event) {
    if (!$event->isMasterRequest()) {
      return;
    }

    // Only set any headers when this is a cacheable response.
    $response = $event->getResponse();
    if ($response instanceof CacheableResponseInterface) {

      // Iterate all tagsheader plugins and add a header for each plugin.
      $tags = $response->getCacheableMetadata()->getCacheTags();
      foreach ($this->purgeTagsHeaders as $header) {

        // Retrieve the header name perform a few simple sanity checks.
        $name = $header->getHeaderName();
        if ((!is_string($name)) || empty(trim($name))) {
          $plugin_id = $header->getPluginId();
          throw new \LogicException("Header plugin '$plugin_id' should return a non-empty string on ::getHeaderName()!");
        }

        $response->headers->set($name, $header->getValue($tags));
      }
    }
  }

}
