<?php

namespace Drupal\openy_mindbody_tools;

use Drupal\ymca_mappings\Entity\Mapping;


/**
 * Class PriceUpdater.
 *
 * @package Drupal\openy_mindbody_tools
 */
class PriceUpdater  {

  /**
   * Update prices.
   */
  public function update() {
    // Set new price matrix.
    $prices = [
      30 => [
        1 => ['member' => 50, 'nonmember' => 70],
        4 => ['member' => 180, 'nonmember' => 260],
        8 => ['member' => 310, 'nonmember' => 470],
        12 => ['member' => 450, 'nonmember' => 690],
        20 => ['member' => 610, 'nonmember' => 1010],
      ],
      60 => [
        1 => ['member' => 75, 'nonmember' => 95],
        4 => ['member' => 280, 'nonmember' => 360],
        8 => ['member' => 480, 'nonmember' => 640],
        12 => ['member' => 700, 'nonmember' => 960],
        20 => ['member' => 1039, 'nonmember' => 1439],
      ],
    ];

    $data = [];

    foreach (array_keys($prices) as $sessionLength) {
      foreach (array_keys($prices[$sessionLength]) as $package) {
        $data[] = [
          'field_package' => $package,
          'field_session_length' => $sessionLength,
          'field_member_price' => $prices[$sessionLength][$package]['member'],
          'field_nonmember_price' => $prices[$sessionLength][$package]['nonmember'],
        ];
      }
    }

    $query = \Drupal::service('entity.query')->get('mapping');
    $query->condition('type', 'personify_product');
    $mapping_ids = $query->execute();

    // Let's save some memory.
    $chunk_size = 50;
    $chunks = array_chunk($mapping_ids, $chunk_size);
    foreach ($chunks as $chunk) {
      $entities = Mapping::loadMultiple($chunk);
      foreach ($entities as $entity) {
        foreach ($data as $index => $row) {
          if (
            $entity->field_session_length->value == $row['field_session_length'] &&
            $entity->field_package->value == $row['field_package']
          ) {
            $entity->set('field_member_price', $row['field_member_price']);
            $entity->set('field_nonmember_price', $row['field_nonmember_price']);
            $entity->save();

            $msg = "Updated price for product ID: %d. Location ID: %d, Session Length: %d, Package: %d";
            $msgOutput = sprintf(
              $msg,
              $entity->id(),
              $entity->field_location_ref->target_id,
              $entity->field_session_length->value,
              $entity->field_package->value
            );
            \Drupal::logger('openy_mindbody_tools')->info($msgOutput);
          }
        }
      }
    }

  }

}
