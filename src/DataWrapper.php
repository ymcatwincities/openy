<?php

namespace Drupal\openy_calc;

/**
 * Class DataWrapper.
 */
class DataWrapper implements DataWrapperInterface {

  /**
   * {@inheritdoc}
   */
  public function getMembershipPriceMatrix() {
    $matrix = [
      [
        'id' => 'youth',
        'title' => 'Youth',
        'description' => 'Here short description',
        'locations' => [
          [
            'title' => 'Loc #1',
            'id' => 12,
            'price' => 100,
          ],
          [
            'title' => 'Loc #2',
            'id' => 13,
            'price' => 200,
          ],
          [
            'title' => 'Loc #3',
            'id' => 14,
            'price' => 300,
          ],
        ],
      ],
    ];

    return $matrix;
  }

  /**
   * {@inheritdoc}
   */
  public function getMembershipTypes() {
    $types = [];

    foreach ($this->getMembershipPriceMatrix() as $membership_type) {
      $types[$membership_type['id']] = [
        'title' => $membership_type['title'],
        'description' => $membership_type['description'],
      ];
    }

    return $types;
  }

  /**
   * {@inheritdoc}
   */
  public function getLocations($membership_type) {
    $locations = [];
    $membership_definition = FALSE;

    foreach ($this->getMembershipPriceMatrix() as $membership_type_item) {
      if ($membership_type_item['id'] == $membership_type) {
        $membership_definition = $membership_type_item;
        break;
      }
    }

    foreach ($membership_definition['locations'] as $location) {
      $locations[$location['id']] = [
        'title' => $location['title'],
      ];
    }

    return $locations;
  }

  /**
   * {@inheritdoc}
   */
  public function getPrice($location_id, $membership_type) {
    foreach ($this->getMembershipPriceMatrix() as $membership_type_item) {
      if ($membership_type_item['id'] == $membership_type) {
        foreach ($membership_type_item['locations'] as $location) {
          if ($location['id'] == $location_id) {
            return $location['price'];
          }
        }
        break;
      }
    }

    return FALSE;
  }

}
