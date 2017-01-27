<?php

namespace Drupal\openy_calc;

use Drupal\openy_socrates\OpenyDataServiceInterface;

/**
 * Class CalcDataWrapper.
 *
 * Provides example of membership matrix.
 */
class CalcDataWrapper extends DataWrapperBase implements OpenyDataServiceInterface {

  /**
   * {@inheritdoc}
   */
  public function getMembershipPriceMatrix() {
    $matrix = [
      [
        'id' => 'youth',
        'title' => 'Youth',
        'description' => 'Youth membership',
        'locations' => [
          [
            'title' => 'Location #1',
            'id' => 1,
            'price' => 10,
          ],
          [
            'title' => 'Location #2',
            'id' => 2,
            'price' => 20,
          ],
          [
            'title' => 'Location #3',
            'id' => 3,
            'price' => 30,
          ],
        ],
      ],
      [
        'id' => 'adult',
        'title' => 'Adult',
        'description' => 'Adult membership',
        'locations' => [
          [
            'title' => 'Location #1',
            'id' => 1,
            'price' => 100,
          ],
          [
            'title' => 'Location #2',
            'id' => 2,
            'price' => 200,
          ],
          [
            'title' => 'Location #3',
            'id' => 3,
            'price' => 300,
          ],
        ],
      ],
      [
        'id' => 'family',
        'title' => 'Family',
        'description' => 'Family membership',
        'locations' => [
          [
            'title' => 'Location #1',
            'id' => 1,
            'price' => 1000,
          ],
          [
            'title' => 'Location #2',
            'id' => 2,
            'price' => 2000,
          ],
          [
            'title' => 'Location #3',
            'id' => 3,
            'price' => 3000,
          ],
        ],
      ],
    ];

    return $matrix;
  }

  /**
   * {@inheritdoc}
   */
  public function getLocationPins() {
    $location_ids = $this->queryFactory->get('node')
      ->condition('type', ['branch', 'camp'], 'IN')
      ->execute();

    if (!$location_ids) {
      return [];
    }

    $storage = $this->entityTypeManager->getStorage('node');
    $builder = $this->entityTypeManager->getViewBuilder('node');
    $locations = $storage->loadMultiple($location_ids);

    $pins = [];
    foreach ($locations as $location) {
      $view = $builder->view($location, 'membership_teaser');
      $coordinates = $location->get('field_location_coordinates')->getValue();
      $tags = [];
      switch ($location->getType()) {
        case 'branch':
          $tags[] = t('YMCA');
          $icon = file_create_url(drupal_get_path('module', 'location_finder') . '/img/map_icon_blue.png');
          break;

        case 'camp':
          $tags[] = t('Camps');
          $icon = file_create_url(drupal_get_path('module', 'location_finder') . '/img/map_icon_green.png');
          break;
      }
      $pins[] = [
        'icon' => $icon,
        'tags' => $tags,
        'lat' => round($coordinates[0]['lat'], 5),
        'lng' => round($coordinates[0]['lng'], 5),
        'name' => $location->label(),
        'markup' => $this->renderer->renderRoot($view),
        'element' => '',
      ];
    }

    return $pins;
  }

  /**
   * {@inheritdoc}
   */
  public function addDataServices($services) {
    return [
      'getLocationPins',
      'getMembershipPriceMatrix',
      'getMembershipTypes',
      'getLocations',
      // @todo consider to extend Socrates with service_name:method instead of just method or to make methods more longer in names.
      'getPrice',
    ];
  }
}
