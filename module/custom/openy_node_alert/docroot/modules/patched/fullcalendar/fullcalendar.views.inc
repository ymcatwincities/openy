<?php

/**
 * @file
 * Contains Views module hooks.
 */

/**
 * Implements hook_views_data().
 */
function fullcalendar_views_data() {
  $data = array();

  $data['fullcalendar'] = array(
    'table' => array(
      'group' => t('FullCalendar'),
      'join' => array('#global' => array()),
    ),
    'gcal' => array(
      'title' => t('Google Calendar'),
      'help' => t('Display events from a Google Calendar.'),
      'field' => array(
        'id' => 'fullcalendar_gcal',
      ),
    ),
  );

  return $data;
}
