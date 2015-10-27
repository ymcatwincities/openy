<?php

/**
 * @file
 * Contains \Drupal\migrate_plus\Event\MigratePrepareRowEvent.
 */

namespace Drupal\migrate_plus\Event;

use Drupal\migrate\Entity\MigrationInterface;
use Drupal\migrate\MigrateSkipRowException;
use Drupal\migrate\Plugin\MigrateSourceInterface;
use Drupal\migrate\Row;
use Symfony\Component\EventDispatcher\Event;

/**
 * Wraps a prepare-row event for event listeners.
 */
class MigratePrepareRowEvent extends Event {

  /**
   * Row object.
   *
   * @var \Drupal\migrate\Row
   */
  protected $row;

  /**
   * Migration entity.
   *
   * @var \Drupal\migrate\Plugin\MigrateSourceInterface
   */
  protected $source;

  /**
   * Constructs a prepare-row event object.
   *
   * @param \Drupal\migrate\Row $row
   *   Row of source data to be analyzed/manipulated.
   *
   * @param \Drupal\migrate\Plugin\MigrateSourceInterface $source
   *   Source plugin that is the source of the event.
   */
  public function __construct(Row $row, MigrateSourceInterface $source) {
    $this->row = $row;
    $this->source = $source;
  }

  /**
   * Gets the row object.
   *
   * @return \Drupal\migrate\Row
   *   The row object about to be imported.
   */
  public function getRow() {
    return $this->row;
  }

  /**
   * Gets the source plugin.
   *
   * @return \Drupal\migrate\Plugin\MigrateSourceInterface $source
   *   The source plugin firing the event.
   */
  public function getSource() {
    return $this->source;
  }

}
