<?php

/**
 * @file
 * Scheduler node edit functions.
 *
 * This file is included only when a node is being edited by a user who has
 * permission to schedule nodes, and the node-type is enabled for scheduling.
 */

use Drupal\Component\Utility\Xss;
use Drupal\Core\Form\FormStateInterface;

/**
 * Helper function that does all the work for the real hook_form_alter().
 *
 * @see scheduler_form_alter()
 */
function _scheduler_form_alter(array &$form, FormStateInterface $form_state) {
  /** @var \Drupal\node\NodeTypeInterface $type */
  $type = $form_state->getFormObject()->getEntity()->type->entity;
  $publishing_enabled = $type->getThirdPartySetting('scheduler', 'publish_enable', FALSE);
  $unpublishing_enabled = $type->getThirdPartySetting('scheduler', 'unpublish_enable', FALSE);
  $use_vertical_tabs = $type->getThirdPartySetting('scheduler', 'use_vertical_tabs', TRUE);

  $date_format = \Drupal::config('scheduler.settings')->get('date_format');
  $date_only_format = \Drupal::config('scheduler.settings')->get('date_only_format');
  $time_only_format = \Drupal::config('scheduler.settings')->get('time_only_format');
  $date_only_allowed = \Drupal::config('scheduler.settings')->get('allow_date_only');

  /* @var $node \Drupal\node\NodeInterface */
  $node = $form_state->getFormObject()->getEntity();

  // A publish_on date is required if the content type option is set and the
  // node is being created or it currently has a scheduled publishing date.
  $publishing_required = $type->getThirdPartySetting('scheduler', 'publish_required', FALSE)
    && ($node->isNew() || (!$node->isPublished() && !empty($node->publish_on->value)));

  // An unpublish_on date is required if the content type option is set and
  // the node is being created or the current status is published or the
  // node is scheduled to be published.
  $unpublishing_required = $type->getThirdPartySetting('scheduler', 'unpublish_required', FALSE)
    && ($node->isNew() || $node->isPublished() || !empty($node->publish_on->value));

  // Expand the fieldset if either publishing or unpublishing is required, or if
  // the fieldset was configured to be expanded.
  $expand_details = $publishing_required || $unpublishing_required || $type->getThirdPartySetting('scheduler', 'expand_fieldset', FALSE);

  $form['scheduler_settings'] = array(
    '#type' => 'details',
    '#title' => t('Scheduling options'),
    '#open' => $expand_details,
    '#weight' => 35,
    '#group' => $use_vertical_tabs ? 'advanced' : FALSE,
    '#attributes' => array(
      'class' => array('scheduler-form'),
    ),
    '#optional' => FALSE,
  );
  // Attach the javascript for the vertical tabs.
  if ($use_vertical_tabs) {
    $form['scheduler_settings']['#attached']['library'][] = 'scheduler/vertical-tabs';
  }

  // Define the descriptions depending on whether the time can be skipped.
  $descriptions = array();
  if ($date_only_allowed && ($date_only_format != $date_format)) {
    $descriptions['format'] = t('Format: %date_only_format or %standard_format.', array(
      '%date_only_format' => \Drupal::service('date.formatter')->format(time(), 'custom', $date_only_format),
      '%standard_format' => \Drupal::service('date.formatter')->format(time(), 'custom', $date_format),
    ));
  }
  else {
    $descriptions['format'] = t('Format: %standard_format.', array(
      '%standard_format' => \Drupal::service('date.formatter')->format(time(), 'custom', $date_format),
    ));
  }
  // Show the default time so users know what they will get if they do not
  // enter a time.
  if ($date_only_allowed) {
    $default_time = strtotime(\Drupal::config('scheduler.settings')->get('default_time'));
    $descriptions['default'] = t('The default time is @default_time.', array(
      '@default_time' => \Drupal::service('date.formatter')->format($default_time, 'custom', $time_only_format ? $time_only_format : SCHEDULER_TIME_ONLY_FORMAT),
    ));
  }

  if (!$publishing_required) {
    $descriptions['blank'] = t('Leave the date blank for no scheduled publishing.');
  }

  $form['publish_on']['#access'] = $publishing_enabled;
  $form['publish_on']['#group'] = 'scheduler_settings';
  $form['publish_on']['widget'][0]['value']['#required'] = $publishing_required;
  $form['publish_on']['widget'][0]['value']['#description'] = Xss::filter(implode(' ', $descriptions));

  if (!$unpublishing_required) {
    $descriptions['blank'] = t('Leave the date blank for no scheduled unpublishing.');
  }
  else {
    unset($descriptions['blank']);
  }

  $form['unpublish_on']['#access'] = $unpublishing_enabled;
  $form['unpublish_on']['#group'] = 'scheduler_settings';
  $form['unpublish_on']['widget'][0]['value']['#required'] = $unpublishing_required;
  $form['unpublish_on']['widget'][0]['value']['#description'] = Xss::filter(implode(' ', $descriptions));
}
