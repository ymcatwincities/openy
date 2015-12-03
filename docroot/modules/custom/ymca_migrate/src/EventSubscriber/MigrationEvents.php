<?php
/**
 * @file
 * Events for running tools after import.
 */

namespace Drupal\ymca_migrate\EventSubscriber;


use Drupal\Component\Serialization\Yaml;
use Drupal\migrate\Event\MigrateEvents;
use Drupal\migrate\Event\MigrateImportEvent;
use Drupal\migrate\MigrateExecutable;
use Drupal\views\Entity\View;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\PostResponseEvent;

class MigrationEvents implements EventSubscriberInterface {

  /**
   * Run configs after import.
   *
   * @param \Drupal\migrate\Event\MigrateImportEvent $event
   *   The Event to process.
   */
  public function onPostImport(MigrateImportEvent $event) {
    switch ($event->getMigration()->id()) {
      case 'ymca_migrate_taxonomy_term_tags':
        /* \Drupal\Core\Config\ConfigManager $cm */
        $cm = \Drupal::service('config.manager');
        $data = $cm->getConfigFactory()->get('views.views.ymca_news.yml')->getRawData();
        // View::create(Yaml::encode($data));
        break;
    }
    $message = t('Event done: @echo', array('@echo' => var_export($data, TRUE)));
    \Drupal::logger('ymca_migrate')->error($message);
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
