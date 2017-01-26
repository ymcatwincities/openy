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
      ->condition('type', 'branch')
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
      $coordinates = $location->get('field_ct_coordinates')->getValue();
      $pins[] = [
        'lat' => round($coordinates[0]['lat'], 5),
        'lng' => round($coordinates[0]['lng'], 5),
        'title' => $location->label(),
        'markup' => $this->renderer->renderRoot($view),
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
