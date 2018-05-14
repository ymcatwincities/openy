<?php

namespace Drupal\geolocation_demo\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Routing\RouteMatchInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Returns responses for geolocation_demo module routes.
 */
class DemoRenderElementController extends ControllerBase {

  /**
   * Return the non-functional geocoding widget form.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Page request object.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match.
   *
   * @return array
   *   A render array.
   */
  public function renderElementDemo(Request $request, RouteMatchInterface $route_match) {
    $elements = [];

    $elements['single_map'] = [
      '#type' => 'geolocation_google_map',
      '#prefix' => $this->t('This is a prefix'),
      '#suffix' => $this->t('This is a suffix'),
      '#longitude' => 42,
      '#latitude' => 34,
      '#width' => 100,
      '#height' => 100,
      '#zoom' => 4,
    ];

    $elements['single_map_code'] = [
      '#type' => 'details',
      '#title' => $this->t('Single Map Code'),
      'code' => [
        '#type' => 'html_tag',
        '#tag' => 'pre',
        '#value' => '
        $elements[\'single_map\'] = [
          \'#type\' => \'geolocation_google_map\',
          \'#prefix\' => $this->t(\'This is a prefix\'),
          \'#suffix\' => $this->t(\'This is a suffix\'),
          \'#longitude\' => 42,
          \'#latitude\' => 34,
          \'#width\' => 100,
          \'#height\' => 100,
          \'#zoom\' => 4,
        ];
        ',
      ],
    ];

    $elements['common_map'] = [
      '#type' => 'geolocation_google_map',
      '#width' => 200,
      '#height' => 200,
      '#locations' => [
        [
          'latitude' => 13,
          'longitude' => 32,
        ],
        [
          'latitude' => -11,
          'longitude' => -12,
        ],
      ],
    ];

    $elements['common_map_code'] = [
      '#type' => 'details',
      '#title' => $this->t('Common Map Code'),
      'code' => [
        '#type' => 'html_tag',
        '#tag' => 'pre',
        '#value' => '
        $elements[\'common_map\'] = [
          \'#type\' => \'geolocation_google_map\',
          \'#width\' => 200,
          \'#height\' => 200,
          \'#locations\' => [
            [
              \'latitude\' => 13,
              \'longitude\' => 32,
            ],
            [
              \'latitude\' => -11,
              \'longitude\' => -12,
            ],
          ],
        ];
        ',
      ],
    ];

    $elements['map_settings'] = [
      '#type' => 'geolocation_google_map',
      '#latitude' => 40.6700,
      '#longitude' => -73.9400,
      '#controls' => TRUE,
      '#width' => 250,
      '#height' => 250,
      '#settings' => [
        'google_map_settings' => [
          'style' => json_decode('[{"elementType":"labels","stylers":[{"visibility":"off"},{"color":"#f49f53"}]},{"featureType":"landscape","stylers":[{"color":"#f9ddc5"},{"lightness":-7}]},{"featureType":"road","stylers":[{"color":"#813033"},{"lightness":43}]},{"featureType":"poi.business","stylers":[{"color":"#645c20"},{"lightness":38}]},{"featureType":"water","stylers":[{"color":"#1994bf"},{"saturation":-69},{"gamma":0.99},{"lightness":43}]},{"featureType":"road.local","elementType":"geometry.fill","stylers":[{"color":"#f19f53"},{"weight":1.3},{"visibility":"on"},{"lightness":16}]},{"featureType":"poi.business"},{"featureType":"poi.park","stylers":[{"color":"#645c20"},{"lightness":39}]},{"featureType":"poi.school","stylers":[{"color":"#a95521"},{"lightness":35}]},{},{"featureType":"poi.medical","elementType":"geometry.fill","stylers":[{"color":"#813033"},{"lightness":38},{"visibility":"off"}]},{},{},{},{},{},{},{},{},{},{},{},{"elementType":"labels"},{"featureType":"poi.sports_complex","stylers":[{"color":"#9e5916"},{"lightness":32}]},{},{"featureType":"poi.government","stylers":[{"color":"#9e5916"},{"lightness":46}]},{"featureType":"transit.station","stylers":[{"visibility":"off"}]},{"featureType":"transit.line","stylers":[{"color":"#813033"},{"lightness":22}]},{"featureType":"transit","stylers":[{"lightness":38}]},{"featureType":"road.local","elementType":"geometry.stroke","stylers":[{"color":"#f19f53"},{"lightness":-10}]},{},{},{}]'),
        ],
      ],
    ];

    $elements['map_settings_code'] = [
      '#type' => 'details',
      '#title' => $this->t('Map Settings Code'),
      'code' => [
        '#type' => 'html_tag',
        '#tag' => 'pre',
        '#value' => '
        $elements[\'map_settings\'] = [
          \'#type\' => \'geolocation_google_map\',
          \'#latitude\' => 40.6700,
          \'#longitude\' => -73.9400,
          \'#controls\' => TRUE,
          \'#width\' => 250,
          \'#height\' => 250,
          \'#settings\' => [
            \'google_map_settings\' => [
              \'style\' => json_decode(\'[{"elementType":"labels","stylers":[{"visibility":"off"},{"color":"#f49f53"}]},{"featureType":"landscape","stylers":[{"color":"#f9ddc5"},{"lightness":-7}]},{"featureType":"road","stylers":[{"color":"#813033"},{"lightness":43}]},{"featureType":"poi.business","stylers":[{"color":"#645c20"},{"lightness":38}]},{"featureType":"water","stylers":[{"color":"#1994bf"},{"saturation":-69},{"gamma":0.99},{"lightness":43}]},{"featureType":"road.local","elementType":"geometry.fill","stylers":[{"color":"#f19f53"},{"weight":1.3},{"visibility":"on"},{"lightness":16}]},{"featureType":"poi.business"},{"featureType":"poi.park","stylers":[{"color":"#645c20"},{"lightness":39}]},{"featureType":"poi.school","stylers":[{"color":"#a95521"},{"lightness":35}]},{},{"featureType":"poi.medical","elementType":"geometry.fill","stylers":[{"color":"#813033"},{"lightness":38},{"visibility":"off"}]},{},{},{},{},{},{},{},{},{},{},{},{"elementType":"labels"},{"featureType":"poi.sports_complex","stylers":[{"color":"#9e5916"},{"lightness":32}]},{},{"featureType":"poi.government","stylers":[{"color":"#9e5916"},{"lightness":46}]},{"featureType":"transit.station","stylers":[{"visibility":"off"}]},{"featureType":"transit.line","stylers":[{"color":"#813033"},{"lightness":22}]},{"featureType":"transit","stylers":[{"lightness":38}]},{"featureType":"road.local","elementType":"geometry.stroke","stylers":[{"color":"#f19f53"},{"lightness":-10}]},{},{},{}]\'),
            ],
          ],
        ];
        ',
      ],
    ];

    return $elements;
  }

}
