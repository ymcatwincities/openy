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
    $groups = [];
    $rows = [];

    foreach ($items as $delta => $item) {
      // Group days by their values.
      foreach ($item as $i_item) {
        /* @var StringData $i_item $a */
        $groups[$i_item->getValue()]['days'][] = str_replace('hours_', '', $i_item->getName());
      }

      // TODO: fix incorrect logic.
      foreach ($groups as $hours => $group) {
        $title = sprintf('%s - %s', ucfirst(reset($group['days'])), ucfirst(end($group['days'])));
        if (count($group['days']) == 1) {
          $title = ucfirst(reset($group['days']));
        }
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
