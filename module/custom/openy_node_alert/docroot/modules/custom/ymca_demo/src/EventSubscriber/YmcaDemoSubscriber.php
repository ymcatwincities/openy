<?php

namespace Drupal\ymca_demo\EventSubscriber;

use Drupal\migrate\Event\MigrateEvents;
use Drupal\migrate\Event\MigrateImportEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class YmcaDemoSubscriber.
 *
 * @package Drupal\ymca_demo\EventSubscriber
 */
class YmcaDemoSubscriber implements EventSubscriberInterface {

  /**
   * Set frontpage after import.
   *
   * @param \Drupal\migrate\Event\MigrateImportEvent $event
   *   The Event to process.
   */
  public function onPostImport(MigrateImportEvent $event) {
    // Set front page to "node".
    switch ($event->getMigration()->id()) {
      case 'ymca_migrate_taxonomy_term_tags':
        $query = \Drupal::entityQuery('node')
          ->condition('uuid', '46c4ac68-5d53-44ff-b362-98a489ccfb98')
          ->range(0, 1);
        if ($ids = $query->execute()) {
          \Drupal::configFactory()
            ->getEditable('system.site')
            ->set('page.front', '/node/' . reset($ids))
            ->save(TRUE);
        }
        break;
    }

  }

  /**
   * Registers the methods in this class that should be listeners.
   *
   * @return array
   *   An array of event listener definitions.
   */
  public static function getSubscribedEvents() {
    return [MigrateEvents::POST_IMPORT => [['onPostImport', 100]]];
  }

}
