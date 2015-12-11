<?php
/**
 * @file
 * Contains \Drupal\scheduler\Plugin\Field\FieldWidget\TimestampDatetimeNoDefaultWidget.
 */

namespace Drupal\scheduler\Plugin\Field\FieldWidget;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Datetime\Element\Datetime;
use Drupal\Core\Datetime\Entity\DateFormat;
use Drupal\Core\Datetime\Plugin\Field\FieldWidget\TimestampDatetimeWidget;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'datetime timestamp' widget.
 *
 * @FieldWidget(
 *   id = "datetime_timestamp_no_default",
 *   label = @Translation("Datetime Timestamp no default"),
 *   description = @Translation("An optional datetime field. Does not provide a default time if left blank."),
 *   field_types = {
 *     "timestamp",
 *   }
 * )
 */
class TimestampDatetimeNoDefaultWidget extends TimestampDatetimeWidget {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);
    $date_format = DateFormat::load('html_date')->getPattern();
    $time_format = DateFormat::load('html_time')->getPattern();
    $element['value']['#description'] = $this->t('Format: %format. Leave blank to disable.', array('%format' => Datetime::formatExample($date_format . ' ' . $time_format)));

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    foreach ($values as &$item) {
      // @todo The structure is different whether access is denied or not, to
      //   be fixed in https://www.drupal.org/node/2326533.
      $date = NULL;
      if (isset($item['value']) && $item['value'] instanceof DrupalDateTime) {
        $date = $item['value'];
      }
      elseif (isset($item['value']['object']) && $item['value']['object'] instanceof DrupalDateTime) {
        $date = $item['value']['object'];
      }

      $item['value'] = $date ? $date->getTimestamp() : NULL;
    }
    return $values;
  }

}
