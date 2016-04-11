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
  public function getInfo() {
    return array(
      'field_info' => array(
        'entity_id' => 'uid',
        'lastmod' => 'changed',
      ),
      'path_info' => array(
        'route_name' => 'entity.user.canonical',
        'entity_type' => 'user',
      )
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getQuery($bundle) {
    return $this->database->select('users_field_data', 'u')
      ->fields('u', array('uid', 'changed'))
      ->condition('status', 1);
  }

}
