<?php

/**
 * @file
 * Contains \Drupal\entity_embed\EntityEmbedDisplay\EntityEmbedDisplayInterface.
 */

namespace Drupal\entity_embed\EntityEmbedDisplay;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Component\Plugin\ConfigurablePluginInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the required interface for all entity embed display plugins.
 *
 * @ingroup entity_embed_api
 */
interface EntityEmbedDisplayInterface extends ConfigurablePluginInterface, PluginFormInterface, PluginInspectionInterface {

  /**
   * Indicates whether the entity embed display can be used.
   *
   * This method allows base implementations to add general access restrictions
   * that should apply to all extending entity embed display plugins.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user session for which to check access.
   *
   * @return bool
   *   TRUE if this entity embed display plugin can be used, or FALSE otherwise.
   */
  public function access(AccountInterface $account = NULL);

  /**
   * Builds and returns the renderable array for this display plugin.
   *
   * @return array
   *   A renderable array representing the content of the embedded entity.
   */
  public function build();

}
