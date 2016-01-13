<?php

/**
 * @file
 * Contains YMCA holiday hours formatter.
 */

namespace Drupal\ymca_field_holiday_hours\Plugin\Field\FieldFormatter;

use Drupal\Core\Render\Markup;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Component\Utility\Html;

/**
 * Plugin implementation of the 'ymca_holiday_hours' formatter.
 *
 * @FieldFormatter(
 *   id = "ymca_holiday_hours",
 *   label = @Translation("Ymca holiday hours"),
 *   field_types = {
 *     "ymca_holiday_hours"
 *   }
 * )
 */
class YmcaHolidayHoursFormatter extends FormatterBase implements ContainerFactoryPluginInterface {

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings']
    );
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $rows = [];

    // Calculate timezone offset.
    $tz = new \DateTimeZone(\Drupal::config('ymca_migrate.settings')->get('timezone'));
    $dt = new \DateTime(NULL, $tz);
    $offset = $dt->getOffset();

    foreach ($items as $item) {
      $values = $item->getValue();

      // Skip holidays with empty date.
      if (empty($values['date'])) {
        continue;
      }

      // Check date.
      $date = \DateTime::createFromFormat('U', $values['date']);
      if (!$date) {
        \Drupal::logger('ymca_field_holiday_hours')->error("Can't obtain the time.");
        continue;
      }
      $holiday_timestamp = $date->getTimestamp();

      // We have to show the block withing 14 days before the holiday.
      $period = 60 * 60 * 24 * 14;

      $request = REQUEST_TIME + $offset;

      // Show the block before the defined period and withing the current day.
      if ($request < ($holiday_timestamp + (60 * 60 * 24)) && ($holiday_timestamp - $request) <= $period) {
        $title = Html::escape($values['holiday']);
        $rows[] = [
          Markup::create('<span>' . $title . '</span>:'),
          $values['hours'],
        ];
      }
    }

    $elements[0] = [
      '#theme' => 'table',
      '#header' => [],
      '#rows' => $rows,
    ];

    return $elements;
  }

}
