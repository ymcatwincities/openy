<?php

namespace Drupal\social_feed_fetcher;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Datetime\DrupalDateTime;

abstract class PluginNodeProcessorPluginBase extends PluginBase implements PluginNodeProcessorPluginInterface {


  /**
   * @var \Drupal\Core\Config\Config
   */
  protected $config;

  /**
   * @var \Drupal\Core\Entity\EntityStorageInterface|mixed|object
   */
  protected $entityStorage;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public function getId() {
    return $this->pluginDefinition['id'];
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel() {
    return $this->pluginDefinition['label'];
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($source, $data_item) {
    return TRUE;
  }

  /**
   * Helper function
   * Check if post with ID doesn't exist
   *
   * @param int $data_item_id
   *
   * @return array|int
   */
  public function isPostIdExist($data_item_id){
    if($data_item_id) {
      $query = $this->entityStorage->getQuery()
        ->condition('status', 1)
        ->condition('type', 'social_post')
        ->condition('field_id', $data_item_id);
      return $query->execute();
    }
  }

  /**
   * Helper function for getting Drupal based time entry.
   *
   * @param $time_entry
   *    Time format from social network.
   *
   * @return string
   *   Formatted time string.
   */
  public function setPostTime($time_entry){
    /** @var \Drupal\Core\Datetime\DrupalDateTime $time */
    $time = new DrupalDateTime($time_entry);
    $time->setTimezone(new \DateTimezone(DATETIME_STORAGE_TIMEZONE));
    return $time->format(DATETIME_DATETIME_STORAGE_FORMAT);
  }

  /**
   * Setter for entityStorage
   *
   * @param $enitytStorage
   *
   * @return $this
   */
  public function setStorage($enitytStorage){
    $this->entityStorage = $enitytStorage;
    return $this;
  }

  /**
   * Setter for Config.
   *
   * @param $config
   *
   * @return $this
   */
  public function setConfig($config){
    $this->config = $config;
    return $this;
  }
}