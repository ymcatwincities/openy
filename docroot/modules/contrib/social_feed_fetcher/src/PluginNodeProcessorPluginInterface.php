<?php

namespace Drupal\social_feed_fetcher;

use Drupal\Component\Plugin\PluginInspectionInterface;

interface PluginNodeProcessorPluginInterface extends PluginInspectionInterface {

  /**
   * Getting ID.
   */
  public function getId();

  /**
   * Getting Label.
   */
  public function getLabel();

  /**
   * Creating node process.
   *
   * @param string $source
   *   Source of feed procedure.
   * @param mixed $data_item
   *    Data from the feed procedure.
   *
   * @return bool
   *   Return TRUE if node was created.
   */
  public function processItem($source, $data_item);

}