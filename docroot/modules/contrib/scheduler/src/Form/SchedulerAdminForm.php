<?php

/**
 * @file
 * Contains \Drupal\scheduler\Form\SchedulerAdminForm.
 */

namespace Drupal\scheduler\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Url;

/**
 * Main administration form for the Scheduler module.
 */
class SchedulerAdminForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'scheduler_admin_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['scheduler.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('scheduler.settings');
    $now = t('Example: %date', ['%date' => \Drupal::service('date.formatter')->format(REQUEST_TIME, 'custom', $config->get('date_format'))]);
    $url = Url::fromUri('http://php.net/manual/en/function.date.php');
    $form['date_format'] = [
      '#type' => 'textfield',
      '#title' => t('Date format'),
      '#default_value' => $config->get('date_format'),
      '#size' => 20,
      '#maxlength' => 20,
      '#required' => TRUE,
      '#field_suffix' => ' <small>' . $now . '</small>',
      '#description' => t('The format for entering scheduled dates and times. For the date use the letters %date_letters and for the time use %time_letters. See @url for more details.', [
        '%date_letters' => SCHEDULER_DATE_LETTERS,
        '%time_letters' => SCHEDULER_TIME_LETTERS,
        '@url' => \Drupal::l(t('the PHP date() function'), $url),
      ]),
    ];

    // Options for setting date-only with default time.
    $form['date_only_fieldset'] = [
      '#type' => 'fieldset',
      '#title' => t('Date only'),
      '#collapsible' => FALSE,
    ];
    $form['date_only_fieldset']['allow_date_only'] = [
      '#type' => 'checkbox',
      '#title' => t('Allow users to enter only a date and provide a default time.'),
      '#default_value' => $config->get('allow_date_only'),
      '#description' => t('When only a date is entered the time will default to a specified value, but the user can change this if required.'),
    ];
    $form['date_only_fieldset']['default_time'] = [
      '#type' => 'textfield',
      '#title' => t('Default time'),
      '#default_value' => $config->get('default_time'),
      '#size' => 20,
      '#maxlength' => 20,
      '#description' => t('This is the time that will be used if the user does not enter a value. Format: HH:MM:SS.'),
      '#states' => [
        'visible' => [
          ':input[name="scheduler_allow_date_only"]' => ['checked' => TRUE],
        ],
      ],
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Replace all contiguous whitespaces (including tabs and newlines) with a
    // single plain space.
    $form_state->setValue(['date_format'], trim(preg_replace('/\s+/', ' ', $form_state->getValue(['date_format']))));

    // Validate the letters used in the scheduler date format. All punctuation
    // is accepted, so remove everything except word characters then check that
    // there is nothing else which is not in the list of acceptable date/time
    // letters.
    $no_punctuation = preg_replace('/[^\w+]/', '', $form_state->getValue(['date_format']));
    if (preg_match_all('/[^' . SCHEDULER_DATE_LETTERS . SCHEDULER_TIME_LETTERS . ']/', $no_punctuation, $extra)) {
      $form_state->setErrorByName('date_format', t('You may only use the letters $date_letters for the date and $time_letters for the time. Remove the extra characters $extra', [
        '$date_letters' => SCHEDULER_DATE_LETTERS,
        '$time_letters' => SCHEDULER_TIME_LETTERS,
        '$extra' => implode(' ', $extra[0]),
      ]));
    };

    // If date-only is enabled then check if a valid default time was entered.
    // Leading zeros and seconds can be omitted, eg. 6:30 is considered valid.
    if ($form_state->getValue(['allow_date_only'])) {
      $default_time = date_parse($form_state->getValue(['default_time']));
      if ($default_time['error_count']) {
        $form_state->setErrorByName('default_time', t('The default time should be in the format HH:MM:SS'));
      }
      else {
        // Insert any possibly omitted leading zeroes.
        $unix_time = mktime($default_time['hour'], $default_time['minute'], $default_time['second']);
        $form_state->setValue(['default_time'], \Drupal::service('date.formatter')->format($unix_time, 'custom', 'H:i:s'));
      }
    }

    // Check that either the date format has a time part or the date-only option
    // is turned on.
    $time_format = $this->getTimeOnlyFormat($form_state->getValue(['date_format']));
    if ($time_format == '' && !$form_state->getValue(['allow_date_only'])) {
      $form_state->setErrorByName('date_format', t('You must either include a time within the date format or enable the date-only option.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Extract the date part and time part of the full format, for use with the
    // default time functionality. Assume the date and time time parts begin and
    // end with a letter, but any punctuation between these will be retained.
    $format = $form_state->getValue(['date_format']);
    $time_only_format = $this->getTimeOnlyFormat($format);
    $date_only_format = $this->getDateOnlyFormat($format);

    $this->config('scheduler.settings')
      ->set('time_only_format', $time_only_format)
      ->set('date_only_format', $date_only_format)
      ->set('date_format', $format)
      ->set('allow_date_only', $form_state->getValue(['allow_date_only']))
      ->set('default_time', $form_state->getValue('default_time'))
      ->save();

    if (empty($time_only_format)) {
      drupal_set_message(t('The date part of the Scheduler format is %date_part. There is no time part', ['%date_part' => $date_only_format]));
    }
    else {
      drupal_set_message(t('The date part of the Scheduler format is %date_part and the time part is %time_part.', ['%date_part' => $date_only_format, '%time_part' => $time_only_format]));
    }

    parent::submitForm($form, $form_state);
  }

  /**
   * Returns the time part of a date format.
   *
   * For example, when given the string 'Y-m-d H:s:i' it will return 'H:s:i'.
   *
   * @param string $format
   *   A date format compatible with the PHP date() function.
   *
   * @return string
   *   The time part of the date format, or an empty string if it does not
   *   contain a time part.
   */
  protected function getTimeOnlyFormat($format) {
    $time_start = strcspn($format, SCHEDULER_TIME_LETTERS);
    $time_length = strlen($format) - strcspn(strrev($format), SCHEDULER_TIME_LETTERS) - $time_start;
    return substr($format, $time_start, $time_length);
  }

  /**
   * Returns the date part of a date format.
   *
   * For example, when given the string 'Y-m-d H:s:i' it will return 'Y-m-d'.
   *
   * @param string $format
   *   A date format compatible with the PHP date() function.
   *
   * @return string
   *   The date part of the date format, or an empty string if it does not
   *   contain a date part.
   */
  protected function getDateOnlyFormat($format) {
    $date_start = strcspn($format, SCHEDULER_DATE_LETTERS);
    $date_length = strlen($format) - strcspn(strrev($format), SCHEDULER_DATE_LETTERS) - $date_start;
    return substr($format, $date_start, $date_length);
  }

}
