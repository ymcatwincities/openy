<?php

/**
 * @file
 * Preprocess functions for FullCalendar.
 */

/**
 * Builds the FullCalendar structure as a render array.
 */
function template_preprocess_fullcalendar(&$variables) {
  // If we're using ajax, we're done.
  if (!empty($variables['view']->fullcalendar_ajax)) {
    $variables['element'] = $variables['rows'];
    return;
  }

  $variables['element'] = array(
    '#attached' => $variables['options']['#attached'],
    'status' => array(
      '#type' => 'container',
      '#attributes' => array(
        'class' => array(
          'fullcalendar-status',
        ),
      ),
    ),
    'fullcalendar' => array(
      '#type' => 'container',
      '#attributes' => array(
        'class' => array(
          'fullcalendar',
        ),
      ),
    ),
    'content' => array(
      '#type' => 'container',
      '#attributes' => array(
        'class' => array(
          'fullcalendar-content',
        ),
      ),
      'events' => $variables['rows'],
    ),
  );
}

/**
 * Render the FullCalendar.
 */
function theme_fullcalendar($variables) {
  return \Drupal::service('renderer')->render($variables['element']);
}

/**
 * Build the render array for an individual event.
 */
function template_preprocess_fullcalendar_event(&$variables) {
  $variables['element'] = array(
    '#type' => 'container',
    '#attributes' => array(
      'class' => array(
        'fullcalendar-event',
      ),
    ),
    'title' => array(
      '#prefix' => '<h3 class="title">',
      '#suffix' => '</h3>',
      '#markup' => $variables['entity']->label(),
    ),
  );
  foreach ($variables['event'] as $instance) {
    $variables['element'][] = array(
      '#type' => 'container',
      '#attributes' => array(
        'class' => array(
          'fullcalendar-instance',
        ),
      ),
      array($instance),
    );
  }
}

/**
 * Render the event.
 */
function theme_fullcalendar_event($variables) {
  return \Drupal::service('renderer')->render($variables['element']);
}
