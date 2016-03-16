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
  function get_entities_of_bundle($bundle) {

    $query = \Drupal::database()->select('node_field_data', 'n')
      ->fields('n', array('nid', 'changed'))
      ->condition('type', $bundle)
      ->condition('status', 1);

    $info = array(
      'field_info' => array(
        'entity_id' => 'nid',
        'lastmod' => 'changed',
      ),
      'path_info' => array(
        'route_name' => 'entity.node.canonical',
        'entity_type' => 'node',
      )
    );
    return array('query' => $query, 'info' => $info);
  }
}
