<?php
/**
 * @file
 * Contains \Drupal\simple_sitemap\LinkGenerator\Menu.
 *
 * Plugin for menu entity link generation.
 */

namespace Drupal\simple_sitemap\Plugin\LinkGenerator;

use Drupal\simple_sitemap\Annotation\LinkGenerator;
use Drupal\simple_sitemap\LinkGeneratorBase;

/**
 * Menu class.
 *
 * @LinkGenerator(
 *   id = "menu"
 * )
 */
class Menu extends LinkGeneratorBase {

  /**
   * {@inheritdoc}
   */
  function get_entities_of_bundle($bundle) {

    $query = \Drupal::database()->select('menu_tree', 'm')
      ->fields('m', array('mlid', 'route_name', 'route_parameters', 'options'))
      ->condition('menu_name', $bundle)
      ->condition('enabled', 1)
      ->condition('route_name', '', '!=');

    $info = array(
      'field_info' => array(
        'entity_id' => 'mlid',
        'route_name' => 'route_name',
        'route_parameters' => 'route_parameters',
        'options' => 'options',
      ),
      'path_info' => array()
    );
    return array('query' => $query, 'info' => $info);
  }
}
