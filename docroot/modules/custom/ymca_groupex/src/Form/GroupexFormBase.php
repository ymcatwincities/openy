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
  private function getOptions($data, $key, $value) {
    $options = [];
    foreach ($data as $item) {
      $options[$item->$key] = $item->$value;
    }

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['note'] = [
      '#markup' => $this->t('Search dates and times for drop-in classes (no registration required). Choose a specific category or time of day, or simply click through to view all.'),
    ];

    // Get current node.
    $node = \Drupal::routeMatch()->getParameter('node');

    // Get location ID.
    $locations = \Drupal::config('ymca_groupex.mapping')->get('locations');
    $location_id = array_search(
      $node->label(),
      array_combine(
        array_column($locations, 'id'),
        array_column($locations, 'name')
      ),
      TRUE
    );

    // Form should not be shown if there is no Location.
    if (!$location_id) {
      \Drupal::logger('ymca_groupex')->error("Location ID could not be found.");
      return [
        '#markup' => $this->t('Sorry, search form is currently unavailable.'),
      ];
    }

    $form['location'] = [
      '#type' => 'hidden',
      '#value' => $location_id,
    ];

    $form['nid'] = [
      '#type' => 'hidden',
      '#value' => $node->id(),
    ];

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

    // @todo Add JS which will toggle checkbox if one is selected.
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
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Get params.
    $params = [
      'location' => $form_state->getValue('location'),
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

    // Perform redirect.
    $form_state->setRedirect(
      'ymca_groupex.schedules_search_results',
      ['node' => $form_state->getValue('nid')],
      ['query' => $params]
    );
  }

}
