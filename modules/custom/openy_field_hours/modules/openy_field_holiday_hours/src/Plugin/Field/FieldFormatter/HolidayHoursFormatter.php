<?php

namespace Drupal\openy_field_holiday_hours\Plugin\Field\FieldFormatter;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Template\Attribute;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Component\Utility\Html;
use Drupal\Component\Render\FormattableMarkup;

/**
 * Plugin implementation of the 'openy_holiday_hours' formatter.
 *
 * @FieldFormatter(
 *   id = "openy_holiday_hours",
 *   label = @Translation("OpenY Holiday Hours"),
 *   field_types = {
 *     "openy_holiday_hours"
 *   }
 * )
 */
class HolidayHoursFormatter extends FormatterBase implements ContainerFactoryPluginInterface {

  /**
   * Offset to show field before a holiday.
   */
  const SHOW_BEFORE_OFFSET = 1209600;

  /**
   * Offset to show field after a holiday.
   */
  const SHOW_AFTER_OFFSET = 86400;

  /**
   * Config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * HolidayHoursFormatter constructor.
   *
   * @param string $plugin_id
   *   Plugin ID.
   * @param mixed $plugin_definition
   *   Plugin definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   Field definition.
   * @param array $settings
   *   Settings.
   * @param string $label
   *   Label.
   * @param string $view_mode
   *   View mode.
   * @param array $third_party_settings
   *   Third party settings.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   Config factory.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, ConfigFactoryInterface $configFactory) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);

    $this->configFactory = $configFactory;
  }

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
      $configuration['third_party_settings'],
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $rows = [];
    $config = $this->configFactory->get('openy_field_holiday_hours.settings');

    // Calculate timezone offset.
    $tz = new \DateTimeZone($this->configFactory->get('system.date')->get('timezone')['default']);
    $dt = new \DateTime(NULL, $tz);
    $tz_offset = $dt->getOffset();

    // The Holiday Hours should be shown before N days.
    $show_before_offset = $config->get('show_before_offset') ? $config->get('show_before_offset') : self::SHOW_BEFORE_OFFSET;
    $show_before_offset = $tz_offset + $show_before_offset;

    // Also the Holiday Hours should be shown during N offset after a holiday.
    $show_after_offset = $config->get('show_after_offset') ? $config->get('show_after_offset') : self::SHOW_AFTER_OFFSET;

    foreach ($items as $item) {
      $values = $item->getValue();

      // Skip holidays with empty date.
      if (empty($values['date'])) {
        continue;
      }

      $holiday_timestamp = $values['date'];
      $request_time = \Drupal::time()->getRequestTime();
      if ($request_time < ($holiday_timestamp + $show_after_offset) && ($holiday_timestamp - $request_time) <= $show_before_offset) {
        $title = Html::escape($values['holiday']);
        $rows[] = [
          'data' => [
            new FormattableMarkup('<span>' . $title . '</span>: ', []),
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
        'tags' => ['ymca_cron'],
      ],
    ];

    return $elements;
  }

}
