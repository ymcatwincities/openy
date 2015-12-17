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

    foreach ($items as $item) {
      $values = $item->getValue();
      $title = \Drupal\Component\Utility\Html::escape($values['holiday']);
      $rows[] = [
        Markup::create('<span>' . $title . '</span>:'),
        $values['hours'],
      ];
    }

    $elements[0] = [
      '#theme' => 'table',
      '#header' => [],
      '#rows' => $rows,
    ];

    return $elements;
  }

}
