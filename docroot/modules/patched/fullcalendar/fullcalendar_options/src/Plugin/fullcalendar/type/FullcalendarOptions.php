<?php

namespace Drupal\fullcalendar_options\Plugin\fullcalendar\type;

use Drupal\Core\Form\FormStateInterface;
use Drupal\fullcalendar\Plugin\FullcalendarBase;

/**
 * @todo.
 *
 * @FullcalendarOption(
 *   id = "fullcalendar_options",
 *   module = "fullcalendar_options",
 *   js = TRUE
 * )
 */
class FullcalendarOptions extends FullcalendarBase {

  /**
   * {@inheritdoc}
   */
  public function defineOptions() {
    $options = array();
    foreach ($this->optionsListParsed() as $key => $info) {
      $options[$key]['default'] = $info['#default_value'];
      // If this is a Boolean value, set the 'bool' flag for export.
      if (isset($info['#data_type']) && $info['#data_type'] == 'bool') {
        $options[$key]['bool'] = TRUE;
      }
    }

    return array(
      'fullcalendar_options' => array(
        'contains' => $options,
      ),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    $options = $this->optionsListParsed();
    // There were no options added, remove the parent fieldset.
    if (!empty($options)) {
      $form['fullcalendar_options'] = array(
        '#type' => 'details',
        '#title' => $this->t('Extra options'),
        '#open' => TRUE,
      );
      // Add the default value to each option.
      foreach ($options as $key => $info) {
        $form['fullcalendar_options'][$key] = $info;
        if (isset($this->style->options['fullcalendar_options'][$key])) {
          $form['fullcalendar_options'][$key]['#default_value'] = $this->style->options['fullcalendar_options'][$key];
        }
      }
    }
  }

  /**
   * @todo.
   */
  public function optionsList() {
    $form = array();

    $form['firstHour'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('First hour'),
      '#description' => $this->t('Determines the first hour that will be visible in the scroll pane.'),
      '#size' => 2,
      '#maxlength' => 2,
      '#default_value' => 6,
      '#data_type' => 'int',
    );
    $form['minTime'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Minimum time'),
      '#description' => $this->t('Determines the first hour/time that will be displayed, even when the scrollbars have been scrolled all the way up.'),
      '#size' => 2,
      '#maxlength' => 2,
      '#default_value' => 0,
      '#data_type' => 'int',
    );
    $form['maxTime'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Maximum time'),
      '#description' => $this->t('Determines the last hour/time (exclusively) that will be displayed, even when the scrollbars have been scrolled all the way down.'),
      '#size' => 2,
      '#maxlength' => 2,
      '#default_value' => 24,
      '#data_type' => 'int',
    );
    $form['slotMinutes'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Slot minutes'),
      '#description' => $this->t('The frequency for displaying time slots, in minutes.'),
      '#size' => 2,
      '#maxlength' => 2,
      '#default_value' => 30,
      '#data_type' => 'int',
    );
    $form['defaultEventMinutes'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Default event minutes'),
      '#description' => $this->t('Determines the length (in minutes) an event appears to be when it has an unspecified end date.'),
      '#size' => 4,
      '#maxlength' => 4,
      '#default_value' => 120,
      '#data_type' => 'int',
    );
    $form['allDaySlot'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('All day slot'),
      '#description' => $this->t('Determines if the "all-day" slot is displayed at the top of the calendar.'),
      '#default_value' => TRUE,
      '#data_type' => 'bool',
    );
    $form['weekends'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Weekends'),
      '#description' => $this->t('Whether to include Saturday/Sunday columns in any of the calendar views.'),
      '#default_value' => TRUE,
      '#data_type' => 'bool',
    );
    $form['lazyFetching'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Lazy fetching'),
      '#description' => $this->t('Determines when event fetching should occur.'),
      '#default_value' => TRUE,
      '#data_type' => 'bool',
    );
    $form['disableDragging'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Disable dragging'),
      '#description' => $this->t('Disables all event dragging, even when events are editable.'),
      '#default_value' => FALSE,
      '#data_type' => 'bool',
    );
    $form['disableResizing'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Disable resizing'),
      '#description' => $this->t('Disables all event resizing, even when events are editable.'),
      '#default_value' => FALSE,
      '#data_type' => 'bool',
    );
    $form['dragRevertDuration'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Drag revert duration'),
      '#description' => $this->t('Time (in ms) it takes for an event to revert to its original position after an unsuccessful drag.'),
      '#size' => 6,
      '#maxlength' => 6,
      '#default_value' => 500,
      '#data_type' => 'int',
    );
    $form['dayClick'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Day click'),
      '#description' => $this->t('Switch the display when a day is clicked'),
      '#default_value' => FALSE,
      '#data_type' => 'bool',
    );
    return $form;
  }

  /**
   * @todo.
   */
  protected function optionsListParsed() {
    $form = $this->optionsList();
    // By default, restrict the form to options allowed by the admin settings.
    $form = array_intersect_key($form, array_filter(\Drupal::config('fullcalendar_options.settings')->get()));

    if (isset($form['dayClick'])) {
      // Add in dependency form elements.
      $form['dayClickView'] = array(
        '#type' => 'select',
        '#title' => $this->t('Display'),
        '#description' => $this->t('The display to switch to when a day is clicked.'),
        '#default_value' => 'agendaWeek',
        '#options' => array(
          'month' => $this->t('Month'),
          'agendaWeek' => $this->t('Week (Agenda)'),
          'basicWeek' => $this->t('Week (Basic)'),
          'agendaDay' => $this->t('Day (Agenda)'),
          'basicDay' => $this->t('Day (Basic)'),
        ),
        '#states' => array(
          'visible' => array(
            ':input[name="style_options[fullcalendar_options][dayClick]"]' => array('checked' => TRUE),
          ),
        ),
      );
    }

    return $form;
  }

}
