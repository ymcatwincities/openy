<?php

namespace Drupal\openy_field_custom_hours\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation for openy_custom_hours formatter.
 *
 * @FieldFormatter(
 *   id = "openy_custom_hours_default",
 *   label = @Translation("OpenY Custom Hours"),
 *   field_types = {
 *     "openy_custom_hours"
 *   }
 * )
 */
class CustomHoursFormatterDefault extends FormatterBase implements ContainerFactoryPluginInterface {

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
    $elements = [];

    foreach ($items as $delta => $item) {
      $groups = [];
      $rows = [];
      $label = '';

      // Group days by their values.
      foreach ($item as $i_item) {
        // Do not process label. Store it name for later usage.
        $name = $i_item->getName();
        if ($name == 'hours_label') {
          $label = $i_item->getValue();
          continue;
        }

        $day = str_replace('hours_', '', $name);
        $value = $i_item->getValue() ? $i_item->getValue() : 'Closed';
        if ($groups && end($groups)['value'] == $value) {
          $array_keys = array_keys($groups);
          $group = &$groups[end($array_keys)];
          $group['days'][] = $day;
        }
        else {
          $groups[] = [
            'value' => $value,
            'days' => [$day],
          ];
        }
      }

      foreach ($groups as $group_item) {
        $title = sprintf('%s - %s', ucfirst(reset($group_item['days'])), ucfirst(end($group_item['days'])));
        if (count($group_item['days']) == 1) {
          $title = ucfirst(reset($group_item['days']));
        }
        $hours = $group_item['value'];
        $rows[] = [$title . ':', $hours];
      }

      $elements[$delta] = [
        'title' => [
          '#type' => 'html_tag',
          '#tag' => 'h4',
          '#value' => $label,
        ],
        'table' => [
          '#theme' => 'table',
          '#header' => [],
          '#rows' => $rows,
        ],
      ];
    }

    return $elements;
  }

}
