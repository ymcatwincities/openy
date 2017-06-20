<?php

namespace Drupal\migrate_plus\Event;

/**
 * Defines the row preparation event for the migration system.
 *
 * @see \Drupal\migrate\Event\MigratePrepareRowEvent
 */
final class MigrateEvents {

  /**
   * Name of the event fired when preparing a source data row.
   *
   * This event allows modules to perform an action whenever the source plugin
   * has read the inital source data into a Row object. Typically, this would be
   * used to add data to the row, manipulate the data into a canonical form, or
   * signal by exception that the row should be skipped. The event listener
   * method receives a \Drupal\migrate_plus\Event\MigratePrepareRowEvent instance.
   *
   * @Event
   *
   * @see \Drupal\migrate_plus\Event\MigratePrepareRowEvent
   *
   * @var string
   */
  const PREPARE_ROW = 'migrate_plus.prepare_row';

}
