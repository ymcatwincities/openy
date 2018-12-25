<?php

namespace Drupal\openy_stats\Plugin\rest\resource;

use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Provides a Factory pattern for stats framework.
 *
 * @RestResource(
 *   id = "openy_stats_factory",
 *   label = @Translation("OpenY Stats"),
 *   uri_paths = {
 *     "canonical" = "/openy_stats/{endpoint}"
 *   }
 * )
 */
class OpenyStatsResource extends ResourceBase {

  /**
   * Responds to entity GET requests.
   *
   * @return \Drupal\rest\ResourceResponse
   *   Response.
   */
  public function get($endpoint) {
    $client_ip = \Drupal::request()->getClientIp();
    $allowed_ips = \Drupal::configFactory()->getEditable('openy_stats.settings')->get('allowed_ips');

    if (!in_array($client_ip, $allowed_ips)) {
      throw new AccessDeniedHttpException('Access denied');
    }

    $call_config = [
      'module_list' => [
        'type' => 'service',
        'name' => 'openy_stats.modulestats',
        'method' => 'getModuleList',
        'arguments' => ''
      ],
      'node_stats' => [
        'type' => 'service',
        'name' => 'openy_stats.nodestats',
        'method' => 'getNodeStats',
        'arguments' => ''
      ],
      'prgf_stats' => [
        'type' => 'service',
        'name' => 'openy_stats.prgfstats',
        'method' => 'getPrgfStats',
        'arguments' => ''
      ],
      'blks_stats' => [
        'type' => 'service',
        'name' => 'openy_stats.blcsstats',
        'method' => 'getBlocksStats',
        'arguments' => ''
      ]
    ];
    $result = [];
    if (array_key_exists($endpoint, $call_config)) {
      $current_call = $call_config[$endpoint];
      switch ($current_call['type']) {
        case 'service':
          $service_name = $current_call['name'];
          $service_method = $current_call['method'];
          // @todo Create ability to pass arguments from current_call array.
          $result = \Drupal::service($service_name)->$service_method();
          break;

        default:
          break;
      }
    }

    return new ResourceResponse($result);
  }

}
