<?php
/**
 * @file
 * Contains \Drupal\ymca_groupex\Form\GroupexFormBase
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
    $form['class_name'] = [
      '#type' => 'select',
      '#options' => ['any' => $this->t('All')] + $this->getOptions($this->request(['query' => ['classes' => TRUE]]), 'id', 'title'),
      '#title' => $this->t('Class Name (optional)'),
    ];

    $form['category'] = [
      '#type' => 'select',
      '#options' => ['any' => $this->t('All')] + $this->getOptions($this->request(['query' => ['categories' => TRUE]]), 'id', 'name'),
      '#title' => $this->t('Category (optional)'),
    ];

    $form['time_of_day'] = [
      '#type' => 'checkboxes',
      '#options' => [
        'morning' => $this->t('Morning (6 a.m. - 12 p.m.)'),
        'afternoon' => $this->t('Afternoon (12 p.m. - 5 p.m.)'),
        'evening' => $this->t('Evening (5 p.m. - 10 p.m.)'),
      ],
      '#title' => $this->t('Time of Day (optional)'),
    ];

    $form['filter_length'] = [
      '#type' => 'checkboxes',
      '#options' => [
        'day' => $this->t('Day'),
        'week' => $this->t('Week'),
      ],
      '#default_value' => ['day'],
      '#title' => $this->t('View Day or Week'),
      '#description' => $this->t('Selecting more than one location limits your search to one day.'),
    ];

    $form['filter_date'] = [
      '#type' => 'datetime',
      '#title' => $this->t('Start Date'),
      '#default_value' => DrupalDateTime::createFromTimestamp(REQUEST_TIME),
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
   * @param $data
   *   Data to iterate.
   * @param $key
   *   Key name.
   * @param $value
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
      'location' => array_values($form_state->getValue('location')),
      'class' => str_replace('DESC--[', '', $form_state->getValue('class_name')),
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
    $params['filter_date'] = $date->format(self::$date_filter_format);

    return $params;
  }

}
