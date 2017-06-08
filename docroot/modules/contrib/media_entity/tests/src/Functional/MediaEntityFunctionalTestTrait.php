<?php

namespace Drupal\Tests\media_entity\Functional;

use Drupal\media_entity\Entity\MediaBundle;

/**
 * Trait with helpers for Media Entity functional tests.
 *
 * @package Drupal\Tests\media_entity\Functional
 */
trait MediaEntityFunctionalTestTrait {

  /**
   * Creates a media bundle.
   *
   * @param array $values
   *   The media bundle values.
   * @param string $type_name
   *   (optional) The media type provider plugin that is responsible for
   *   additional logic related to this media).
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   Returns newly created media bundle.
   */
  protected function drupalCreateMediaBundle(array $values = [], $type_name = 'generic') {
    if (!isset($values['bundle'])) {
      $id = strtolower($this->randomMachineName());
    }
    else {
      $id = $values['bundle'];
    }
    $values += [
      'id' => $id,
      'label' => $id,
      'type' => $type_name,
      'type_configuration' => [],
      'field_map' => [],
      'new_revision' => FALSE,
    ];

    $bundle = MediaBundle::create($values);
    $status = $bundle->save();

    $this->assertEquals($status, SAVED_NEW, 'Could not create a media bundle of type ' . $type_name . '.');

    return $bundle;
  }

}
