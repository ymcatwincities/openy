<?php

namespace Drupal\migrate_plus\Plugin\migrate_plus\authentication;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\migrate_plus\AuthenticationPluginBase;

/**
 * Provides digest authentication for the HTTP resource.
 *
 * @Authentication(
 *   id = "digest",
 *   title = @Translation("Digest")
 * )
 */
class Digest extends AuthenticationPluginBase implements ContainerFactoryPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function getAuthenticationOptions() {
    return [
      'auth' => [
        $this->configuration['username'],
        $this->configuration['password'],
        'digest',
      ],
    ];
  }

}
