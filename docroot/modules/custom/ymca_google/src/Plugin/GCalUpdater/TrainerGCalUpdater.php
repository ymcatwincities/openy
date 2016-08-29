<?php

namespace Drupal\ymca_google\Plugin\GCalUpdater;

use Drupal\Core\Plugin\PluginBase;
use Drupal\ymca_google\GCalUpdaterInterface;
use Drupal\ymca_groupex_google_cache\GroupexGoogleCacheInterface;

/**
 * Provides Trainer update.
 *
 * @Plugin(
 *   id = "trainer",
 * )
 */
class TrainerGCalUpdater extends PluginBase implements GCalUpdaterInterface {

  /**
   * {@inheritdoc}
   */
  public function check(GroupexGoogleCacheInterface $cache, \stdClass $item) {
    // If there is save Google event let's mark item for update.
    $field_event = $cache->get('field_gg_google_event');
    if ($field_event->isEmpty()) {
      return TRUE;
    }

    // Check for old HTML in the cache.
    $event = unserialize($field_event->get(0)->value);
    $regex = '/<span class=\"subbed\".*><br>(.*)<\/span>/';
    preg_match($regex, $event->getDescription(), $match);
    if (!empty($match)) {
      return TRUE;
    }

    return FALSE;
  }

}
