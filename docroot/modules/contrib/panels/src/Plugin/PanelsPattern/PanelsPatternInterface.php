<?php

namespace Drupal\panels\Plugin\PanelsPattern;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\user\SharedTempStoreFactory;

/**
 * Provides an interface for defining PanelsPatterns.
 */
interface PanelsPatternInterface extends PluginInspectionInterface {

  /**
   * Gets the tempstore key identifier.
   *
   * @param array $cached_values
   *
   * @return string
   */
  public function getMachineName($cached_values);

  /**
   * Gets the array of default contexts for this panels pattern.
   *
   * @param \Drupal\user\SharedTempStoreFactory $tempstore
   *   The tempstore factory object.
   * @param string $tempstore_id
   *   The tempstore identifier.
   * @param string $machine_name
   *   The tempstore key.
   *
   * @return \Drupal\Core\Plugin\Context\ContextInterface[]
   */
  public function getDefaultContexts(SharedTempStoreFactory $tempstore, $tempstore_id, $machine_name);

  /**
   * Gets the block list url.
   *
   * @param string $tempstore_id
   *   The tempstore identifier.
   * @param string $machine_name
   *   The tempstore key.
   * @param string $region
   *   The region in which to place the block after it is created.
   * @param string $destination
   *   The destination to which to redirect after submission.
   *
   * @return \Drupal\Core\Url
   */
  public function getBlockListUrl($tempstore_id, $machine_name, $region = NULL, $destination = NULL);

  /**
   * Gets the block add url.
   *
   * @param string $tempstore_id
   *   The tempstore identifier.
   * @param string $machine_name
   *   The tempstore key.
   * @param string $block_id
   *   The id of the block plugin to create.
   * @param string $region
   *   The region in which to place the block after it is created.
   * @param string $destination
   *   The destination to which to redirect after submission.
   *
   * @return \Drupal\Core\Url
   */
  public function getBlockAddUrl($tempstore_id, $machine_name, $block_id, $region = NULL, $destination = NULL);

  /**
   * Gets the block edit url.
   *
   * @param string $tempstore_id
   *   The tempstore identifier.
   * @param string $machine_name
   *   The tempstore key.
   * @param string $block_id
   *   The unique id of the block in this panel.
   * @param string $destination
   *   The destination to which to redirect after submission.
   *
   * @return \Drupal\Core\Url
   */
  public function getBlockEditUrl($tempstore_id, $machine_name, $block_id, $destination = NULL);

  /**
   * Gets the block delete url.
   *
   * @param string $tempstore_id
   *   The tempstore identifier.
   * @param string $machine_name
   *   The tempstore key.
   * @param string $block_id
   *   The unique id of the block in this panel.
   * @param string $destination
   *   The destination to which to redirect after submission.
   *
   * @return \Drupal\Core\Url
   */
  public function getBlockDeleteUrl($tempstore_id, $machine_name, $block_id, $destination = NULL);

}
