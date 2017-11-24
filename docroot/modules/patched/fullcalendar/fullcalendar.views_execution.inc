<?php

/**
 * @file
 * Contains Views module runtime hooks.
 */

use Drupal\Component\Utility\Html;
use Drupal\Views\ViewExecutable;

/**
 * Implements hook_views_plugins_alter().
 */
function fullcalendar_views_plugins_alter(&$plugins) {
  $plugins['display']['fullcalendar'] = array(
    'title' => t('FullCalendar - Deprecated, use page display instead!'),
    'help' => t('Stop using this display, use page instead'),
    'no ui' => TRUE,
  ) + $plugins['display']['page'];
}

/**
 * Implements hook_views_pre_view().
 *
 * Add an argument that provides the current date for each date field present.
 */
function fullcalendar_views_pre_view($view, $display_id, $args) {
  $style = $view->display_handler->getOption('style');
  if ($style['type'] != 'fullcalendar') {
    return;
  }

  // Get the current view settings.
  $view->initStyle();
  $settings = $view->style_plugin->options;

  foreach ($view->style_plugin->getPlugins() as $plugin) {
    $plugin->preView($settings);
  }

  // If we're not using ajax, we're done.
  if (!$view->display_handler->getOption('use_ajax')) {
    // Set the new view settings.
    $view->style_plugin->options = $settings;
    return;
  }

  $settings['fullcalendar_fields_count'] = 0;
  $exposed_input = $view->getExposedInput();
  // Loop through each date field and provide an argument for it.
  foreach ($view->display_handler->getHandlers('field') as $field_id => $field) {
    if (!fullcalendar_field_is_date($field)) {
      continue;
    }

    // Add an exposed filter for the date field.
    $field_value_id = $field->realField . str_replace($field->field, '', $field_id);
    if (isset($exposed_input[$field_value_id])) {
      $timestamp = (strtotime($exposed_input[$field_value_id]['min']['date']) + strtotime($exposed_input[$field_value_id]['max']['date'])) / 2;
      $min = date('Y-m', $timestamp);
      $max = date('Y-m', strtotime($min . ' +1 month'));
    }
    else {
      $min = date('Y-m', mktime(0, 0, 0, $settings['date']['month'] + 1, 1, $settings['date']['year']));
      $max = date('Y-m', mktime(0, 0, 0, $settings['date']['month'] + 2, 1, $settings['date']['year']));
    }
    $min = date('Y-m-d', strtotime($min . ' -2 weeks'));
    $max = date('Y-m-d', strtotime($max . ' +2 weeks'));
    $options = array(
      'exposed' => TRUE,
      'form_type' => 'date_select',
      'operator' => 'between',
      'default_date' => $min,
      'default_to_date' => $max,
      'group' => 0,
    );
    if (!empty($field->options['relationship'])) {
      $options['relationship'] = $field->options['relationship'];
    }
    /* @var \Drupal\Views\ViewExecutable $view */
    $option_id = $view->addHandler($display_id, 'filter', 'field_data_' . $field->field, $field->field . '_value', $options);
    $settings['fullcalendar_fields'][$option_id] = Html::getClass($option_id);
    $settings['fullcalendar_fields_count']++;
    $view->setHandlerOption($display_id, 'filter', $option_id, 'expose', array('identifier' => $option_id, 'operator' => $option_id . '_op'));

  }

  if (isset($timestamp)) {
    $settings['date']['date'] = date('d', $timestamp);
    $settings['date']['month'] = date('n', $timestamp) - 1;
    $settings['date']['year'] = date('Y', $timestamp);
  }

  $view->style_plugin->options = $settings;
}

/**
 * Implements hook_views_query_alter().
 *
 * Force the query to be distinct.
 */
function fullcalendar_views_query_alter($view, $query) {
  $style = $view->display_handler->getOption('style');
  if ($style['type'] != 'fullcalendar') {
    return;
  }

  $query->distinct = TRUE;
}

/**
 * Implements hook_views_ajax_data_alter().
 */
function fullcalendar_views_ajax_data_alter(&$commands, &$view) {
  $style = $view->display_handler->getOption('style');
  if ($style['type'] != 'fullcalendar') {
    $commands = array();
  }
}
