<?php
/**
 * @file
 * Contains \Drupal\simple_sitemap\Plugin\LinkGenerator\NodeType.
 *
 * Plugin for node entity link generation.
 */

namespace Drupal\simple_sitemap\Plugin\LinkGenerator;

use Drupal\simple_sitemap\Annotation\LinkGenerator;
use Drupal\simple_sitemap\LinkGeneratorBase;

/**
 * NodeType class.
 *
 * @LinkGenerator(
 *   id = "node_type"
 * )
 */
class NodeType extends LinkGeneratorBase {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    return array(
      'field_info' => array(
        'entity_id' => 'nid',
        'lastmod' => 'changed',
      ),
      'path_info' => array(
        'route_name' => 'entity.node.canonical',
        'entity_type' => 'node',
      )
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getQuery($bundle) {
    return $this->database->select('node_field_data', 'n')
      ->fields('n', array('nid', 'changed'))
      ->condition('type', $bundle)
      ->condition('status', 1);
  }
}
