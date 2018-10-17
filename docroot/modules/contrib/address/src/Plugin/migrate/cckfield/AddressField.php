<?php

namespace Drupal\address\Plugin\migrate\cckfield;

@trigger_error('AddressField is deprecated in Address 1.3 and will be be removed before Address 2.x. Use \Drupal\address\Plugin\migrate\field\AddressField instead.', E_USER_DEPRECATED);

use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\address\Plugin\migrate\field\AddressField as BaseAddressField;
use Drupal\migrate_drupal\Plugin\MigrateCckFieldInterface;

/**
 * @MigrateCckField(
 *   id = "addressfield",
 *   core = {7},
 *   type_map = {
 *    "addressfield" = "address"
 *   },
 *   source_module = "addressfield",
 *   destination_module = "address"
 * )
 *
 * @deprecated in 1.3, to be removed before 2.x. Use
 * \Drupal\address\Plugin\migrate\field\AddressField instead.
 */
class AddressField extends BaseAddressField implements MigrateCckFieldInterface {

  /**
   * {@inheritdoc}
   */
  public function processCckFieldValues(MigrationInterface $migration, $field_name, $data) {
    return $this->processFieldValues($migration, $field_name, $data);
  }

}
