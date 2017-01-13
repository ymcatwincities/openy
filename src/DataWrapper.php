<?php

namespace Drupal\openy_calc;

/**
 * Class DataWrapper.
 */
class DataWrapper implements DataWrapperInterface {

  /**
   * {@inheritdoc}
   */
  public function getMembershipTypes() {
    return [
      'youth' => [
        'title' => 'Youth',
        'description' => 'Short description of membership type',
      ],
      'adult' => [
        'title' => 'Adult',
        'description' => 'Short description of membership type',
      ],
      'family' => [
        'title' => 'Family',
        'description' => 'Short description of membership type',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getLocations() {
    return [
      1 => [
        'title' => 'Location #1',
      ],
      2 => [
        'title' => 'Location #2',
      ],
      3 => [
        'title' => 'Location #3',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getPrice($location_id, $membership_type) {
    return '100$/Month';
  }

}
