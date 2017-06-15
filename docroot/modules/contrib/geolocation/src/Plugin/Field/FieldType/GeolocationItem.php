<?php

namespace Drupal\geolocation\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\TypedData\MapDataDefinition;

/**
 * Plugin implementation of the 'geolocation' field type.
 *
 * @FieldType(
 *   id = "geolocation",
 *   label = @Translation("Geolocation"),
 *   description = @Translation("This field stores location data (lat, lng)."),
 *   default_widget = "geolocation_latlng",
 *   default_formatter = "geolocation_latlng"
 * )
 */
class GeolocationItem extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return array(
      'columns' => array(
        'lat' => array(
          'description' => 'Stores the latitude value',
          'type' => 'float',
          'size' => 'big',
          'not null' => TRUE,
        ),
        'lng' => array(
          'description' => 'Stores the longitude value',
          'type' => 'float',
          'size' => 'big',
          'not null' => TRUE,
        ),
        'lat_sin' => array(
          'description' => 'Stores the sine of latitude',
          'type' => 'float',
          'size' => 'big',
          'not null' => TRUE,
        ),
        'lat_cos' => array(
          'description' => 'Stores the cosine of latitude',
          'type' => 'float',
          'size' => 'big',
          'not null' => TRUE,
        ),
        'lng_rad' => array(
          'description' => 'Stores the radian longitude',
          'type' => 'float',
          'size' => 'big',
          'not null' => TRUE,
        ),
        'data' => array(
          'description' => 'Serialized array of geolocation meta information.',
          'type' => 'blob',
          'size' => 'big',
          'not null' => FALSE,
          'serialize' => TRUE,
        ),
      ),
      'indexes' => array(
        'lat' => array('lat'),
        'lng' => array('lng'),
      ),
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['lat'] = DataDefinition::create('float')
      ->setLabel(t('Latitude'));

    $properties['lng'] = DataDefinition::create('float')
      ->setLabel(t('Longitude'));

    $properties['lat_sin'] = DataDefinition::create('float')
      ->setLabel(t('Latitude sine'));

    $properties['lat_cos'] = DataDefinition::create('float')
      ->setLabel(t('Latitude cosine'));

    $properties['lng_rad'] = DataDefinition::create('float')
      ->setLabel(t('Longitude radian'));

    $properties['data'] = MapDataDefinition::create()
      ->setLabel(t('Meta data'));

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function generateSampleValue(FieldDefinitionInterface $field_definition) {
    $values['lat'] = rand(-90, 90) - rand(0, 999999) / 1000000;
    $values['lng'] = rand(-180, 180) - rand(0, 999999) / 1000000;
    return $values;
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $lat = $this->get('lat')->getValue();
    $lng = $this->get('lng')->getValue();
    return $lat === NULL || $lat === '' || $lng === NULL || $lng === '';
  }

  /**
   * {@inheritdoc}
   */
  public function preSave() {
    $this->get('lat')->setValue(trim($this->get('lat')->getValue()));
    $this->get('lng')->setValue(trim($this->get('lng')->getValue()));
    $this->get('lat_sin')->setValue(sin(deg2rad($this->get('lat')->getValue())));
    $this->get('lat_cos')->setValue(cos(deg2rad($this->get('lat')->getValue())));
    $this->get('lng_rad')->setValue(deg2rad($this->get('lng')->getValue()));
  }

}
