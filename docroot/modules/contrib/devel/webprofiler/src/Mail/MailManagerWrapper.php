<?php

namespace Drupal\webprofiler\Mail;

use Drupal\Core\Mail\MailManager;
use Drupal\webprofiler\DataCollector\MailDataCollector;

/**
 * Class MailManagerWrapper
 */
class MailManagerWrapper extends MailManager {

  /**
   * @var
   */
  private $dataCollector;

  /**
   * {@inheritdoc}
   */
  public function createInstance($plugin_id, array $configuration = []) {
    $instance = parent::createInstance($plugin_id, $configuration);

    $wrapper = new MailPluginWrapper($instance, $this->dataCollector, $plugin_id, $configuration);

    return $wrapper;
  }

  /**
   * @param \Drupal\webprofiler\DataCollector\MailDataCollector $dataCollector
   */
  public function setDataCollector(MailDataCollector $dataCollector) {
    $this->dataCollector = $dataCollector;
  }
}
