<?php

namespace Drupal\openy_stats\Plugin\rest\resource;

use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;

/**
 * Provides a Enabled Modules Resource.
 *
 * @RestResource(
 *   id = "enabled_modules_resource",
 *   label = @Translation("Enabled Modules"),
 *   uri_paths = {
 *     "canonical" = "/openy_stats/enabled_modules"
 *   }
 * )
 */
class OpenyStatsResource extends ResourceBase {

  /**
   * Responds to entity GET requests.
   * @return \Drupal\rest\ResourceResponse
   */
  public function get() {
    $call_config = [
      'module_list' => [
        'type' => 'class',
        'name' => 'OpenyStatsResource',
        'method' => 'getModuleList',
        'arguments' => ''
      ]
    ];
    $list = \Drupal::service('openy_stats.modulestats')->getModuleList();
    return new ResourceResponse($list);
  }

}
