<?php

namespace Drupal\openy_calc;

/**
 * Class DataWrapperBase.
 */
abstract class DataWrapperBase implements DataWrapperInterface {

  /**
   * {@inheritdoc}
   */
  abstract public function getMembershipPriceMatrix();

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
