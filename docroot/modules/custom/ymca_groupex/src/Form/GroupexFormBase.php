<?php
/**
 * @file
 * Contains \Drupal\ymca_groupex\Form\GroupexFormBase.
 */

namespace Drupal\ymca_groupex\Form;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\ymca_groupex\GroupexRequestTrait;

/**
 * Implements Groupex form for location.
 */
abstract class GroupexFormBase extends FormBase {

  use GroupexRequestTrait;

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Check if we have additional argument to prepopulate the form.
    $refine = FALSE;
    $params = [];
    $args = func_get_args();
    if (isset($args[2])) {
      $refine = TRUE;
      $params = $args[2];
    }

    // Classes IDs has some garbage withing the IDs.
    $class_name_options = $this->getOptions($this->request(['query' => ['classes' => TRUE]]), 'id', 'title');
    $dirty_keys = array_keys($class_name_options);
    $refined_keys = array_map(function($item) {
      return str_replace(self::$idStrip, '', $item);
    }, $dirty_keys);
    $refined_options = array_combine($refined_keys, array_values($class_name_options));

    $form['class_name'] = [
      '#type' => 'select',
      '#options' => ['any' => $this->t('All')] + $refined_options,
      '#title' => $this->t('Class Name (optional)'),
      '#default_value' => $refine ? $params['class'] : [],
    ];

    $form['category'] = [
      '#type' => 'select',
      '#options' => ['any' => $this->t('All')] + $this->getOptions($this->request(['query' => ['categories' => TRUE]]), 'id', 'name'),
      '#title' => $this->t('Category (optional)'),
      '#default_value' => $refine ? $params['category'] : [],
    ];

    $form['time_of_day'] = [
      '#type' => 'checkboxes',
      '#options' => [
        'morning' => $this->t('Morning (6 a.m. - 12 p.m.)'),
        'afternoon' => $this->t('Afternoon (12 p.m. - 5 p.m.)'),
        'evening' => $this->t('Evening (5 p.m. - 10 p.m.)'),
      ],
      '#title' => $this->t('Time of Day (optional)'),
      '#default_value' => $refine ? $params['time_of_day'] : [],
    ];

    $form['filter_length'] = [
      '#type' => 'checkboxes',
      '#options' => [
        'day' => $this->t('Day'),
        'week' => $this->t('Week'),
      ],
      '#default_value' => $refine ? [$params['filter_length']] : ['day'],
      '#title' => $this->t('View Day or Week'),
      '#description' => $this->t('Selecting more than one location limits your search to one day.'),
    ];

    $filter_date_default = DrupalDateTime::createFromTimestamp(REQUEST_TIME);
    if ($refine) {
      $filter_date_default = DrupalDateTime::createFromFormat(
        self::$dateFilterFormat,
        $params['filter_date']
      );
    }
    $form['filter_date'] = [
      '#type' => 'datetime',
      '#title' => $this->t('Start Date'),
      '#default_value' => $filter_date_default,
      '#date_time_element' => 'none',
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Find Class'),
    ];

    // Attach JS.
    $form['#attached']['library'][] = 'ymca_groupex/ymca_groupex';

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
      'filter_length' => reset($form_state->getValue('filter_length')),
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

    return $params;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $location = $form_state->getValue('location');

    // User may select up to 4 locations.
    if (count($location) > 4) {
      $form_state->setError($form['location'], $this->t('Please, select less than 4 locations.'));
    }

    $filter_length = array_filter($form_state->getValue('filter_length'));
    // User may not search by 2 locations and week period.
    if (count($location) > 1 && reset($filter_length) != 'day') {
      $form_state->setError($form['filter_length'], $this->t('Please, choose day view.'));
    }
  }

}
