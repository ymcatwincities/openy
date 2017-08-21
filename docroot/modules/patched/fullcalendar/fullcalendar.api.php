<?php

/**
 * @file
 * Hooks provided by the FullCalendar module.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Constructs CSS classes for an entity.
 *
 * @param object $entity
 *   Object representing the entity.
 *
 * @return array
 *   Array of CSS classes.
 */
function hook_fullcalendar_classes($entity) {
  // Add the entity type as a class.
  return array(
    $entity->entity_type,
  );
}

/**
 * Alter the CSS classes for an entity.
 *
 * @param array $classes
 *   Array of CSS classes.
 * @param object $entity
 *   Object representing the entity.
 */
function hook_fullcalendar_classes_alter(&$classes, $entity) {
  // Remove all classes set by modules.
  $classes = array();
}

/**
 * Declare that you provide a droppable callback.
 *
 * Implementing this hook will cause a checkbox to appear on the view settings,
 * when checked FullCalendar will search for JS callbacks in the form
 * Drupal.fullcalendar.droppableCallbacks.MODULENAME.callback.
 *
 * @see http://arshaw.com/fullcalendar/docs/dropping/droppable
 */
function hook_fullcalendar_droppable() {
  // This hook will never be executed.
  return TRUE;
}

/**
 * Allows your module to affect the editability of the calendar.
 *
 * If any module implementing this hook returns FALSE, the value will be set to
 * FALSE. Use hook_fullcalendar_editable_alter() to override this if necessary.
 *
 * @param object $entity
 *   Object representing the entity.
 * @param object $view
 *   Object representing the view.
 *
 * @return bool
 *   A Boolean value dictating whether of not the calendar is editable.
 */
function hook_fullcalendar_editable($entity, $view) {
  return _fullcalendar_update_access($entity);
}

/**
 * Allows your module to forcibly override the editability of the calendar.
 *
 * @param bool $editable
 *   A Boolean value dictating whether of not the calendar is editable.
 * @param object $entity
 *   Object representing the entity.
 * @param object $view
 *   Object representing the view.
 */
function hook_fullcalendar_editable_alter(&$editable, $entity, $view) {
  $editable = FALSE;
}

/**
 * Alter the dates after they're loaded, before they're added for rendering.
 *
 * @param object $date1
 *   The start date object.
 * @param object $date2
 *   The end date object.
 * @param array $context
 *   An associative array containing the following key-value pairs:
 *   - instance: The field instance.
 *   - entity: The entity object for this date.
 *   - field: The field info.
 */
function hook_fullcalendar_process_dates_alter(&$date1, &$date2, $context) {
  // Always display dates only on one day.
  if ($date1->format(DATE_FORMAT_DATE) != $date2->format(DATE_FORMAT_DATE)) {
    $date2 = $date1;
  }
}

/**
 * @} End of "addtogroup hooks".
 */
