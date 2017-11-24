<?php

namespace Drupal\openy_block_date;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
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
   * Acts on hook_ENTITY_TYPE_build_defaults_alter().
   *
   * @param array $build
   *   Build.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   Entity.
   * @param $view_mode
   *   View mode.
   *
   * @return array
   *   Build.
   */
  public function buildDefaultsAlter(array $build, EntityInterface $entity, $view_mode) {
    if ($view_mode != 'default') {
      return $build;
    }

    // Remove default rendered data.
    unset($build['#block_content']);

    $requestTime = $this->time->getRequestTime();
    $timezone = $this->configFactory->get('timezone.default');

    $dates = [];
    $fields_date = ['field_start_date', 'field_end_date'];
    foreach ($fields_date as $field) {
      // Set tags with future block changes timestamps.
      $dateTime = DrupalDateTime::createFromFormat(DATETIME_DATETIME_STORAGE_FORMAT, $entity->$field->value, $timezone);
      $timestamp = $dateTime->getTimestamp();
      if ($timestamp > $requestTime) {
        $build['#cache']['tags'][] = self::TAG . ":$timestamp";
      }
    }

    // @todo Show contents depending on the date.
    $build['#markup'] = 'Add markup here';

    return $build;
  }

}
