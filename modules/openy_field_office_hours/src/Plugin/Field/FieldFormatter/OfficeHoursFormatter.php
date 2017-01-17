<?php

namespace Drupal\ymca_field_office_hours\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'openy_office_hours' formatter.
 *
 * @FieldFormatter(
 *   id = "openy_office_hours",
 *   label = @Translation("OpenY Office Hours"),
 *   field_types = {
 *     "openy_office_hours"
 *   }
 * )
 */
class OfficeHoursFormatter extends FormatterBase implements ContainerFactoryPluginInterface {

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
    $groups = [];
    $rows = [];

    foreach ($items as $delta => $item) {
      // Group days by their values.
      foreach ($item as $i_item) {
        $day = str_replace('hours_', '', $i_item->getName());
        $value = $i_item->getValue();
        if ($groups && end($groups)['value'] == $value) {
          $array_keys = array_keys($groups);
          $group = &$groups[end($array_keys)];
          $group['days'][] = $day;
        }
        else {
          $groups[] = [
            'value' => $i_item->getValue(),
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
        '#theme' => 'table',
        '#header' => [],
        '#rows' => $rows,
      ];
    }

    return $elements;
  }

}
