<?php
/**
 * @file
 * Contains \Drupal\globalredirect\EventSubscriber\GlobalredirectSettingsCacheTag.
 */
namespace Drupal\globalredirect\EventSubscriber;
use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Drupal\Core\Config\ConfigCrudEvent;
use Drupal\Core\Config\ConfigEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
/**
 * A subscriber invalidating the 'rendered' cache tag when saving globalredirect settings.
 */
class GlobalredirectSettingsCacheTag implements EventSubscriberInterface {
  /**
   * The cache tags invalidator.
   *
   * @var \Drupal\Core\Cache\CacheTagsInvalidatorInterface
   */
  protected $cacheTagsInvalidator;
  /**
   * Constructs a GlobalredirectSettingsCacheTag object.
   *
   * @param \Drupal\Core\Cache\CacheTagsInvalidatorInterface $cache_tags_invalidator
   *   The cache tags invalidator.
   */
  public function __construct(CacheTagsInvalidatorInterface $cache_tags_invalidator) {
    $this->cacheTagsInvalidator = $cache_tags_invalidator;
  }
  /**
   * Invalidate the 'rendered' cache tag whenever the settings are modified.
   *
   * @param \Drupal\Core\Config\ConfigCrudEvent $event
   *   The Event to process.
   */
  public function onSave(ConfigCrudEvent $event) {
    // Changing the Global Redirect settings means that any cached page might
    // result in a different response, so we need to invalidate them all.
    if ($event->getConfig()->getName() === 'globalredirect.settings') {
      $this->cacheTagsInvalidator->invalidateTags(['rendered']);
    }
  }
  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[ConfigEvents::SAVE][] = ['onSave'];
    return $events;
  }
}
