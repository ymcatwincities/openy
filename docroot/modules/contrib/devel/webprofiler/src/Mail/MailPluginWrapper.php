<?php

namespace Drupal\webprofiler\Mail;

use Drupal\Core\Mail\MailInterface;
use Drupal\webprofiler\DataCollector\MailDataCollector;

/**
 * Class MailPluginWrapper
 */
class MailPluginWrapper implements MailInterface {

  /**
   * @var \Drupal\Core\Mail\MailInterface
   */
  private $mail;

  /**
   * @var \Drupal\webprofiler\DataCollector\MailDataCollector
   */
  private $dataCollector;

  /**
   * @var
   */
  private $plugin_id;

  /**
   * @var
   */
  private $configuration;

  /**
   * @param \Drupal\Core\Mail\MailInterface $mail
   * @param \Drupal\webprofiler\DataCollector\MailDataCollector $dataCollector
   * @param $plugin_id
   * @param $configuration
   */
  public function __construct(MailInterface $mail, MailDataCollector $dataCollector, $plugin_id, $configuration) {
    $this->mail = $mail;
    $this->dataCollector = $dataCollector;
    $this->plugin_id = $plugin_id;
    $this->configuration = $configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function format(array $message) {
    return $this->mail->format($message);
  }

  /**
   * {@inheritdoc}
   */
  public function mail(array $message) {
    $this->dataCollector->addMessage($message, $this->plugin_id, $this->configuration, $this->mail);

    return $this->mail->mail($message);
  }
}
