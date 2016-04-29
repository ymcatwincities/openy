<?php
/**
 * @file
 * Contains Drupal\simple_sitemap\LinkGeneratorInterface.
 */

namespace Drupal\simple_sitemap;

use Drupal\Component\Plugin\PluginInspectionInterface;

/**
 * Defines an interface for simple_sitemap plugins.
 */
interface LinkGeneratorInterface extends PluginInspectionInterface {

  /**
   * Get metadata about the entities that the link generator is providing.
   *
   * @return array
   *   An array of information about the link generator.
   */
  public function getInfo();

  /**
   * Get a non-executed query for entities of a specific bundle type.
   *
   * @param string $bundle
   *   The bundle to query for.
   *
   * @return \Drupal\Core\Database\Query\SelectInterface
   *   A query ready for execution.
   */
  public function getQuery($bundle);

}
