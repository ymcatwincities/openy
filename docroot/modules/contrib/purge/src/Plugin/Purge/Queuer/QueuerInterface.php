<?php

namespace Drupal\purge\Plugin\Purge\Queuer;

use Drupal\Component\Plugin\PluginInspectionInterface;

/**
 * Describes a plugin that queues invalidation objects.
 */
interface QueuerInterface extends PluginInspectionInterface {

  /**
   * Retrieve the title of this queuer.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   */
  public function getLabel();

  /**
   * Retrieve the description of this queuer.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   */
  public function getDescription();

}
