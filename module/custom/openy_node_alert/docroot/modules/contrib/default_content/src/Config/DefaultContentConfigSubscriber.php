<?php

namespace Drupal\default_content\Config;

use Drupal\Core\Config\ConfigEvents;
use Drupal\Core\Config\ConfigImporterEvent;
use Drupal\default_content\ImporterInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Reacts to configuration events for the Default Content module.
 */
class DefaultContentConfigSubscriber implements EventSubscriberInterface {

  /**
   * The default content importer.
   *
   * @var \Drupal\default_content\ImporterInterface
   */
  protected $defaultContentImporter;

  /**
   * Constructs a DefaultContentConfigSubscriber object.
   *
   * @param \Drupal\default_content\ImporterInterface $default_content_importer
   *   The default content importer.
   */
  public function __construct(ImporterInterface $default_content_importer) {
    $this->defaultContentImporter = $default_content_importer;
  }

  /**
   * Creates default content after config synchronization.
   *
   * @param \Drupal\Core\Config\ConfigImporterEvent $event
   *   The config importer event.
   */
  public function onConfigImport(ConfigImporterEvent $event) {
    $modules = $event->getConfigImporter()->getExtensionChangelist('module', 'install');
    foreach ($modules as $module) {
      $this->defaultContentImporter->importContent($module);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [ConfigEvents::IMPORT => 'onConfigImport'];
  }

}
