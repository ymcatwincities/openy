<?php

namespace Drupal\ymca_groupex\Form;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\ymca_groupex\GroupexRequestTrait;
use Drupal\ymca_groupex\GroupexScheduleFetcher;

/**
 * Implements Groupex form for location.
 */
abstract class GroupexFormBase extends FormBase {

  use GroupexRequestTrait;

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $timezone = new \DateTimeZone($this->config('system.date')->get('timezone')['default']);

    // Check if we have additional argument to prepopulate the form.
    $refine = FALSE;
    $params = [];
    $args = func_get_args();
    if (isset($args[2])) {
      $refine = TRUE;
      $params = GroupexScheduleFetcher::normalizeParameters($args[2]);
    }

    $form['#attributes'] = ['class' => ['groupex-search-form']];

    // Classes IDs has some garbage withing the IDs.
    $class_name_options = $this->getOptions($this->request(['query' => ['classes' => TRUE]]), 'id', 'title');
    $dirty_keys = array_keys($class_name_options);
    $refined_keys = array_map(function ($item) {
      return str_replace(GroupexRequestTrait::$idStrip, '', $item);
    }, $dirty_keys);
    $refined_options = array_combine($refined_keys, array_values($class_name_options));
    $form['class_name'] = [
      '#type' => 'select',
      '#options' => ['any' => $this->t('(all)')] + $refined_options,
      '#title' => $this->t('Class Name'),
      '#title_extra' => $this->t('(optional)'),
      '#default_value' => $refine && !empty($params['class']) ? $params['class'] : [],
    ];

    $form['category'] = [
      '#type' => 'select',
      '#options' => ['any' => $this->t('(all)')] + $this->getOptions($this->request(['query' => ['categories' => TRUE]]), 'id', 'name'),
      '#title' => $this->t('Category'),
      '#title_extra' => $this->t('(optional)'),
      '#default_value' => $refine && !empty($params['category']) ? $params['category'] : [],
    ];

    $form['time_of_day'] = [
      '#type' => 'checkboxes',
      '#options' => [
        'morning' => $this->t('Morning <small>(6 a.m. - 12 p.m.)</small>'),
        'afternoon' => $this->t('Afternoon <small>(12 p.m. - 5 p.m.)</small>'),
        'evening' => $this->t('Evening <small>(5 p.m. - 10 p.m.)</small>'),
      ],
      '#title' => $this->t('Time of Day'),
      '#title_extra' => $this->t('(optional)'),
      '#default_value' => ($refine && !empty($params['time_of_day'])) ? $params['time_of_day'] : [],
    ];

    $form['filter_length'] = [
      '#type' => 'radios',
      '#options' => [
        'day' => $this->t('Day'),
        'week' => $this->t('Week'),
      ],
      '#default_value' => $refine && !empty($params['filter_length']) ? $params['filter_length'] : 'day',
      '#title' => $this->t('View Day or Week'),
    ];

    $filter_date_default = DrupalDateTime::createFromTimestamp(REQUEST_TIME, $timezone);
    if ($refine && !empty($params['filter_date'])) {
      $date = DrupalDateTime::createFromFormat(
        self::$dateFilterFormat,
        $params['filter_date'],
        $timezone
      );
      $date->setTime(0, 0, 0);
      $filter_date_default = $date;
    }
    $form['filter_date'] = [
      '#type' => 'datetime',
      '#date_date_format' => 'n/d/y',
      '#title' => $this->t('Start Date'),
      '#default_value' => $filter_date_default,
      '#date_time_element' => 'none',
      '#date_date_element' => 'text',
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Find Classes'),
    ];

    // Attach JS.
    $form['#attached']['library'][] = 'ymca_groupex/ymca_groupex';
    $form['#cache'] = [
      'max-age' => 3600,
      'contexts' => ['url.query_args'],
    ];

    return $form;
  }

  /**
   * Get form item options.
   *
   * @param array $data
   *   Data to iterate.
   * @param string $key
   *   Key name.
   * @param string $value
   *   Value name.
   *
   * @return array
   *   Array of options.
   */
  protected function getOptions($data, $key, $value) {
    $options = [];
    foreach ($data as $item) {
      $options[$item->$key] = $item->$value;
    }

    return $options;
  }

  /**
   * Get redirect parameters.
   *
   * @param array $form
   *   Drupal form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   *
   * @return array
   *   Redirect parameters.
   */
  protected function getRedirectParams(array $form, FormStateInterface $form_state) {
    $params = [
      'location' => array_filter($form_state->getValue('location')),
      'class' => $form_state->getValue('class_name'),
      'category' => $form_state->getValue('category'),
      'filter_length' => $form_state->getValue('filter_length'),
    ];

    // Time of day is optional.
    $time_of_day = array_filter($form_state->getValue('time_of_day'));
    if (!empty($time_of_day)) {
      $params['time_of_day'] = array_values($time_of_day);
    }

    // Get date.
    /** @var DrupalDateTime $date */
    $date = $form_state->getValue('filter_date');
    $params['filter_date'] = $date->format(self::$dateFilterFormat);

    // Toggle filter_length if user has selected more than 1 location and week period.
    if ($params['filter_length'] == 'week' && count($params['location']) > 1) {
      $params['filter_length'] = 'day';
      drupal_set_message(t('Search results across multiple locations are limited to a single day.'), 'warning');
    }

    return $params;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $timezone = new \DateTimeZone($this->config('system.date')->get('timezone')['default']);

    $location = array_filter($form_state->getValue('location'));

    // User may select up to 4 locations.
    if (count($location) > 4) {
      $form_state->setError($form['location'], $this->t('Please, select less than 5 locations.'));
    }

  }

}
