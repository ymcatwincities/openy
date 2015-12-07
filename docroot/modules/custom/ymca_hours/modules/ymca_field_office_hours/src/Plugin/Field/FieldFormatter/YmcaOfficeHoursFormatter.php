<?php

/**
 * @file
 * Contains Ymca office hours formatter.
 */

namespace Drupal\ymca_field_office_hours\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\TypedData\Plugin\DataType\StringData;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'ymca_office_hours' formatter.
 *
 * @FieldFormatter(
 *   id = "ymca_office_hours",
 *   label = @Translation("Ymca office hours"),
 *   field_types = {
 *     "ymca_office_hours"
 *   }
 * )
 */
class YmcaOfficeHoursFormatter extends FormatterBase implements ContainerFactoryPluginInterface {

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
      $lines = [];

      foreach ($item as $i_item) {
        /* @var StringData $i_item $a */
        $groups[$i_item->getValue()]['days'][] = substr_replace($i_item->getName(), '', 0, 6);
      }

      foreach ($groups as $g_item_key => $g_item_value) {
        $title = sprintf('%s - %s', reset($g_item_value['days']), array_pop($g_item_value['days']));
        $lines[] = sprintf('%s: %s', $title, $g_item_key);
      }

      $elements[$delta] = [
        '#theme' => 'item_list',
        '#items' => $lines,
      ];
    }

    return $elements;
  }

}
