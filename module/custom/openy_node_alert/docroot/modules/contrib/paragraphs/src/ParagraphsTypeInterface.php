<?php

namespace Drupal\paragraphs;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface defining a ParagraphsType entity.
 */
interface ParagraphsTypeInterface extends ConfigEntityInterface {

  /**
   * Returns the ordered collection of feature plugin instances.
   *
   * @return \Drupal\paragraphs\ParagraphsBehaviorCollection
   *   The behavior plugins collection.
   */
  public function getBehaviorPlugins();

  /**
   * Returns an individual plugin instance.
   *
   * @param string $instance_id
   *   The ID of a behavior plugin instance to return.
   *
   * @return \Drupal\paragraphs\ParagraphsBehaviorInterface
   *   A specific feature plugin instance.
   */
  public function getBehaviorPlugin($instance_id);

  /**
   * Retrieves all the enabled plugins.
   *
   * @return array
   *   Array of the enabled plugins as instances.
   */
  public function getEnabledBehaviorPlugins();

  /**
   * Returns TRUE if $plugin_id is enabled on this ParagraphType Entity.
   *
   * @param string $plugin_id
   *   The plugin id, as specified in the plugin annotation details.
   *
   * @return bool
   *   True or False dependant on plugin state
   */
  public function hasEnabledBehaviorPlugin($plugin_id);

}
