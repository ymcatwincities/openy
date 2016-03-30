<?php

namespace Drupal\ymca_field_holiday_hours\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Template\Attribute;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Component\Utility\Html;
use Drupal\Component\Render\FormattableMarkup;

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
    $tz = new \DateTimeZone(\Drupal::config('system.date')->get('timezone')['default']);
    $dt = new \DateTime(NULL, $tz);
    $tz_offset = $dt->getOffset();

    // The Holiday Hours should be shown before 14 days.
    $holidays_offset = \Drupal::config('ymca_hours.settings')->get('holidays_offset');
    $before_offset = $tz_offset + $holidays_offset;

    // Also the Holiday Hours should be shown during the day.
    $after_offset = 60 * 60 * 24;

    foreach ($items as $item) {
      $values = $item->getValue();

      // Skip holidays with empty date.
      if (empty($values['date'])) {
        continue;
      }

      $holiday_timestamp = $values['date'];

      if (REQUEST_TIME < ($holiday_timestamp + $after_offset) && ($holiday_timestamp - REQUEST_TIME) <= $before_offset) {
        $title = Html::escape($values['holiday']);
        $rows[] = [
          'data' => [
            new FormattableMarkup('<span>' . $title . '</span>:', []),
            $values['hours'],
          ],
          'data-timestamp' => $holiday_timestamp,
        ];
      }
    }

    $elements[0] = [
      '#attributes' => new Attribute(['class' => 'holiday-hours']),
      '#theme' => 'table',
      '#rows' => $rows,
      '#cache' => [
        'tags' => ['ymca_cron']
      ],
    ];

    return $elements;
  }

}
