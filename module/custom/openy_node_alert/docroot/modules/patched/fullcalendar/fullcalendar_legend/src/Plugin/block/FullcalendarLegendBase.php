<?php

/**
 * @file
 * Contains \Drupal\fullcalendar_legend\Plugin\Block\FullcalendarLegendBase.
 */

namespace Drupal\fullcalendar_legend\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a generic FullCalendar Legend block.
 */
abstract class FullcalendarLegendBase extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    if (!$view = views_get_page_view()) {
      return;
    }
    $style = $view->display_handler->getOption('style');
    if ($style['type'] != 'fullcalendar') {
      return;
    }

    $fields = array();
    foreach ($view->field as $field) {
      if (fullcalendar_field_is_date($field)) {
        $fields[$field->field_info['field_name']] = $field->field_info;
      }
    }
    return array(
      '#theme' => 'fullcalendar_legend',
      '#types' => $this->buildLegend($fields),
    );
  }

  /**
   * @param \Drupal\Core\Field\FieldDefinitionInterface[] $fields
   *
   * @return array
   */
  abstract protected function buildLegend(array $fields);

}
