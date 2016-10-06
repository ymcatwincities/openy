<?php

namespace Drupal\address\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Maps D7 addressfield values to address values.
 *
 * @MigrateProcessPlugin(
 *   id = "addressfield"
 * )
 */
class AddressField extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $parsed = [
      'country_code' => $value['country'],
      'administrative_area' => $value['administrative_area'],
      'locality' => $value['locality'],
      'dependent_locality' => $value['dependent_locality'],
      'postal_code' => $value['postal_code'],
      'sorting_code' => '',
      'address_line1' => $value['thoroughfare'],
      'address_line2' => $value['premise'],
      'organization' => $value['organisation_name'],
    ];
    if (!empty($value['first_name']) || !empty($value['last_name'])) {
      $parsed['given_name'] = $value['first_name'];
      $parsed['family_name'] = $value['last_name'];
    }
    elseif (!empty($value['name_line'])) {
      $split = explode(" ", $value['name_line']);
      $parsed['given_name'] = array_shift($split);
      $parsed['family_name']  = implode(" ", $split);
    }
    return $parsed;
  }

}
