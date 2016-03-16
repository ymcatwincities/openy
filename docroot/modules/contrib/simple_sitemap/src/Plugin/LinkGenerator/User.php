<?php
/**
 * @file
 * Contains \Drupal\simple_sitemap\Plugin\LinkGenerator\User.
 *
 * Plugin for user link generation.
 */

namespace Drupal\simple_sitemap\Plugin\LinkGenerator;

use Drupal\simple_sitemap\Annotation\LinkGenerator;
use Drupal\simple_sitemap\LinkGeneratorBase;

/**
 * User class.
 *
 * @LinkGenerator(
 *   id = "user",
 *   form_id = "user_admin_settings"
 * )
 */
class User extends LinkGeneratorBase {

  /**
   * {@inheritdoc}
   */
  function get_entities_of_bundle($bundle) {

    $query = \Drupal::database()->select('users_field_data', 'u')
      ->fields('u', array('uid', 'changed'))
      ->condition('status', 1);

    $info = array(
      'field_info' => array(
        'entity_id' => 'uid',
        'lastmod' => 'changed',
      ),
      'path_info' => array(
        'route_name' => 'entity.user.canonical',
        'entity_type' => 'user',
      ));
    return array('query' => $query, 'info' => $info);
  }
}
