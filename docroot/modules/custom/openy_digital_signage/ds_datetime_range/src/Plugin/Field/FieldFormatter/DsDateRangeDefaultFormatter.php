<?php

namespace Drupal\ds_datetime_range\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\datetime\Plugin\Field\FieldFormatter\DateTimeDefaultFormatter;
use Drupal\datetime_range\DateTimeRangeTrait;

/**
 * Plugin implementation of the 'Default' formatter for 'daterange' fields with
 * extended settings.
 *
 * This formatter renders the data range using <time> elements, with
 * configurable date formats (from the list of configured formats) and a
 * separator.
 *
 * @FieldFormatter(
 *   id = "ds_daterange_default",
 *   label = @Translation("Digital Signage Date Range Default"),
 *   field_types = {
 *     "daterange"
 *   }
 * )
 */
class DsDateRangeDefaultFormatter extends DateTimeDefaultFormatter {

  use DateTimeRangeTrait;

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
        'separator' => '-',
        'only_start_date' => '1',
      ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    $separator = $this->getSetting('separator');
    $only_start_date = $this->getSetting('only_start_date');

    foreach ($items as $delta => $item) {
      if (empty($item->start_date) && $only_start_date) {
        continue;
      }
      if (empty($item->start_date) || empty($item->end_date)) {
        continue;
      }
      /** @var \Drupal\Core\Datetime\DrupalDateTime $start_date */
      $start_date = $item->start_date;
      /** @var \Drupal\Core\Datetime\DrupalDateTime $end_date */
      $end_date = $item->end_date;

      if ($only_start_date) {
        $elements[$delta] = [
          'start_date' => $this->buildDateWithIsoAttribute($start_date),
        ];
      }
      else {
        if ($start_date->getTimestamp() !== $end_date->getTimestamp()) {
          $elements[$delta] = [
            'start_date' => $this->buildDateWithIsoAttribute($start_date),
            'separator' => ['#plain_text' => ' ' . $separator . ' '],
            'end_date' => $this->buildDateWithIsoAttribute($end_date),
          ];
        }
        else {
          $elements[$delta] = $this->buildDateWithIsoAttribute($start_date);
          if (!empty($item->_attributes)) {
            $elements[$delta]['#attributes'] += $item->_attributes;
            // Unset field item attributes since they have been included in the
            // formatter output and should not be rendered in the field template.
            unset($item->_attributes);
          }
        }
      }
    }

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form = parent::settingsForm($form, $form_state);

    $form['separator'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Date separator'),
      '#description' => $this->t('The string to separate the start and end dates'),
      '#default_value' => $this->getSetting('separator'),
    ];
    $form['only_start_date'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show only start date'),
      '#description' => $this->t('Allow to use only start date.'),
      '#default_value' => $this->getSetting('only_start_date'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();

    if ($separator = $this->getSetting('separator')) {
      $summary[] = $this->t('Separator: %separator', [
        '%separator' => $separator,
      ]);
    }
    if ($only_start_date = $this->getSetting('only_start_date')) {
      $summary[] = $this->t('Show only start date: %$only_start_date', [
        '%$only_start_date' => $only_start_date ? $this->t('Yes') : $this->t('No'),
      ]);
    }

    return $summary;
  }

}
