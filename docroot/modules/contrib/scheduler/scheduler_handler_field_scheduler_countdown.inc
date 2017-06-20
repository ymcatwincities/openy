<?php

namespace Drupal\scheduler;

use Drupal\Core\Form\FormStateInterface;

/**
 * Display scheduled dates in 'countdown' format for use in Views.
 *
 * Field handler to display a countdown until a scheduled action.
 *
 * @TODO Is this file actually used and/or needed in Drupal 8?
 *
 * @see scheduler.views.inc
 * @see http://www.ericschaefer.org/blog/2011/01/09/custom-field-handlers-for-views-2-drupal
 */
class SchedulerHandlerFieldSchedulerCountdown extends views_handler_field {
  const SECOND_SCALE = 1;
  const MINUTE_SCALE = 60;
  const HOUR_SCALE = 3600;
  const DAY_SCALE = 86400;
  const WEEK_SCALE = 604800;

  /**
   * Add the timestamp_field into the SQL query.
   *
   * It is calculated as publish_on - REQUEST_TIME so the result is the number
   * of seconds from now until publishing. If publish_on is in the past then
   * NULL is returned.
   */
  public function query() {
    $this->ensure_my_table();
    $this->node_table = $this->query->ensure_table('node', $this->relationship);
    $time_field = $this->definition['timestamp_field'];
    $this->field_alias = $this->query->add_field(NULL, 'CASE WHEN (' . $time_field . ' > ' . REQUEST_TIME . ') THEN (' . $time_field . ' - ' . REQUEST_TIME . ') ELSE NULL END', $this->table_alias . '_' . $this->field);
  }

  /**
   * Define our display options and provide defaults.
   *
   * @return array
   *   An associative array containing the options.
   */
  public function optionDefinition() {
    $options = parent::option_definition();
    $options['countdown_display'] = ['default' => 'smart'];
    $options['units_display'] = ['default' => 'long'];
    return $options;
  }

  /**
   * Defines the form for the user to select the display options.
   */
  public function optionsForm(array &$form, FormStateInterface $form_state) {
    parent::options_form($form, $form_state);
    $form['countdown_display'] = [
      '#title' => t('Display countdown as'),
      '#type' => 'radios',
      '#options' => [
        'smart' => t('Smart mode'),
        'seconds' => t('Seconds'),
        'minutes' => t('Minutes'),
        'hours' => t('Hours'),
        'days' => t('Days'),
        'weeks' => t('Weeks'),
      ],
      '#default_value' => $this->options['countdown_display'],
    ];
    $form['units_display'] = [
      '#title' => t('Display time units'),
      '#type' => 'radios',
      '#options' => [
        'long' => t('Long (for example 3 days)'),
        'short' => t('Short (for example 3d)'),
        'none' => t('No units at all'),
      ],
      '#default_value' => $this->options['units_display'],
    ];
  }

  /**
   * Callback function to filter out unwanted values.
   *
   * Keep only the array scale values which are smaller than the countdown value
   * being displayed.
   */
  public function scaleFilterCallback($array_value) {
    return ($this->raw_value >= $array_value);
  }

  /**
   * Renders the countdown value in the units required.
   */
  public function render($values) {
    $countdown_display = $this->options['countdown_display'];
    $this->raw_value = $values->{$this->field_alias};

    $scales = [
      'weeks' => self::WEEK_SCALE,
      'days' => self::DAY_SCALE,
      'hours' => self::HOUR_SCALE,
      'minutes' => self::MINUTE_SCALE,
      'seconds' => self::SECOND_SCALE,
    ];
    // If the field has been set to 'Smart', determine the right timescale.
    if ($countdown_display == 'smart') {
      $scales = array_filter($scales, [$this, 'scale_filter_callback']);
      $scale = empty($scales) ? self::SECOND_SCALE : reset($scales);
    }
    // Otherwise use the fixed display requested.
    else {
      $scale = $scales[$countdown_display];
    }

    // Get the display value by dividing the original value by the scale.
    $scaled_value = round($this->raw_value / $scale);

    switch ($scale) {
      case self::SECOND_SCALE:
        $long = \Drupal::translation()->formatPlural($scaled_value, '1 second', '@count seconds', ['@count' => $scaled_value]);
        $short = t('@counts', ['@count' => $scaled_value]);
        break;

      case self::MINUTE_SCALE:
        $long = \Drupal::translation()->formatPlural($scaled_value, '1 minute', '@count minutes', ['@count' => $scaled_value]);
        $short = t('@countm', ['@count' => $scaled_value]);
        break;

      case self::HOUR_SCALE:
        $long = \Drupal::translation()->formatPlural($scaled_value, '1 hour', '@count hours', ['@count' => $scaled_value]);
        $short = t('@counth', ['@count' => $scaled_value]);
        break;

      case self::DAY_SCALE:
        $long = \Drupal::translation()->formatPlural($scaled_value, '1 day', '@count days', ['@count' => $scaled_value]);
        $short = t('@countd', ['@count' => $scaled_value]);
        break;

      case self::WEEK_SCALE:
        $long = \Drupal::translation()->formatPlural($scaled_value, '1 week', '@count weeks', ['@count' => $scaled_value]);
        $short = t('@countw', ['@count' => $scaled_value]);
        break;
    }

    switch ($this->options['units_display']) {
      case 'long':
        return $long;

      case 'short':
        return $short;

      default:
        return $scaled_value;
    }
  }

}
