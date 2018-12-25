<?php

namespace Drupal\file_entity\Plugin\QueueWorker;

use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\file\Entity\File;

/**
 * @QueueWorker(
 *   id = "file_entity_type_determine",
 *   title = @Translation("Determine file type"),
 *   cron = {"time" = 60}
 * )
 */
class FileDetermineType extends QueueWorkerBase {

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    if ($file = File::load($data)) {
      // The file type will be automatically determined when saving the file.
      $file->save();
    }
  }

}
