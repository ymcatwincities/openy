<?php

namespace Drupal\openy_block_date;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Entity\EntityInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Datetime\DrupalDateTime;

/**
 * Defines a class for reacting to entity events.
 */
class EntityOperations implements ContainerInjectionInterface {

  /**
   * Date block tag.
   */
  const TAG = 'block_date';

  /**
   * Time.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

  /**
   * Config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * EntityOperations constructor.
   *
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   Time.
   * @param \Drupal\Core\Config\ConfigFactoryInterface
   *   Config factory.
   */
  public function __construct(TimeInterface $time, ConfigFactoryInterface $configFactory) {
    $this->time = $time;
    $this->configFactory = $configFactory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('datetime.time'),
      $container->get('config.factory')
    );
  }

  /**
   * Acts on hook_ENTITY_TYPE_view_alter().
   *
   * @param array $build
   *   Build.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   Entity.
   * @param \Drupal\Core\Entity\Display\EntityViewDisplayInterface $display
   *   Display.
   *
   * @return array
   *   Build.
   */
  public function viewAlter(array $build, EntityInterface $entity, EntityViewDisplayInterface $display) {
    $requestTime = $this->time->getRequestTime();

    $dates = [];
    $fields_date = ['field_start_date', 'field_end_date'];
    foreach ($fields_date as $field) {
      // Set tags with timestamps of future block changes.
      $dateTime = DrupalDateTime::createFromFormat(\Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface::DATETIME_STORAGE_FORMAT, $entity->$field->value, 'UTC');
      $timestamp = $dateTime->getTimestamp();
      if ($timestamp > $requestTime) {
        $build['#cache']['tags'][] = self::TAG . ":$timestamp";
      }

      $dates[$field]['timestamp'] = $timestamp;
    }

    // Show content depending on time.
    $elements = [
      'field_start_date',
      'field_end_date',
      'field_content_date_before',
      'field_content_date_between',
      'field_content_date_end',
    ];

    foreach ($elements as $element) {
      hide($build[$element]);
    }

    if ($requestTime < $dates['field_start_date']['timestamp']) {
      show($build['field_content_date_before']);
    }
    elseif ($requestTime >= $dates['field_start_date']['timestamp'] && $requestTime < $dates['field_end_date']['timestamp']) {
      show($build['field_content_date_between']);
    }
    elseif ($requestTime >= $dates['field_end_date']['timestamp']) {
      show($build['field_content_date_end']);
    }

    return $build;
  }

}
