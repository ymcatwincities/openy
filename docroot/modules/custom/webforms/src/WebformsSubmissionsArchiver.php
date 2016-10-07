<?php

namespace Drupal\webforms;


/**
 * Class WebformsSubmissionsArchiver
 * @package Drupal\webforms
 */
class WebformsSubmissionsArchiver {

  /**
   * Archiving loop, should be run from cron.
   */
  public function archive() {
    // @todo Get list of contact_storage entities.
    // @todo Loop through all of them to find the data, older than a month.
    // @todo Archive a single month data, store to local Archive entity.
    // @todo Check if the file is greater than a zero, remove archived data.
    // @todo finish a loop.
  }
}