<?php

namespace Drupal\ymca_groupex\Form;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Url;

/**
 * Implements Groupex Full Form.
 */
class GroupexFormFull extends GroupexFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'groupex_form_full';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $query = \Drupal::request()->query->all();

    $formatted_results = '';
    if (isset($query['location']) && is_numeric($query['location'])) {
      $values['location'] = $query['location'];
      $formatted_results = self::buildResults($form, $form_state);
    }
    if (isset($query['filter_date'])) {
      $values['date_select'] = $query['filter_date'];
    }

    $form['#prefix'] = '<div id="groupex-full-form-wrapper">';
    $form['#suffix'] = '</div>';
    $classes = 'hidden';
    $location_classes = 'show';
    if (isset($values['location']) && is_numeric($values['location'])) {
      $classes = 'show';
      $location_classes = 'hidden';
    }

    $form['location_select'] = [
      '#type' => 'select',
      '#options' => $this->getOptions($this->request(['query' => ['locations' => TRUE]]), 'id', 'name'),
      '#default_value' => !empty($values['location']) ? $values['location'] : '',
      '#title' => $this->t('Locations'),
      '#prefix' => '<div id="location-select-wrapper" class="' . $classes . '">',
      '#suffix' => '</div>',
      '#ajax' => [
        'callback' => [$this, 'rebuildAjaxCallback'],
        'wrapper' => 'groupex-full-form-wrapper',
        'event' => 'change',
        'method' => 'replace',
        'effect' => 'fade',
        'progress' => [
          'type' => 'throbber',
        ],
      ],
    ];

    $date_options = [];
    for ($i = 0; $i < 7; $i++) {
      $date = date('n/d/y', REQUEST_TIME + $i * 86400);
      $date_options[$date] = $date;
    }
    $form['date_select'] = [
      '#type' => 'select',
      '#options' => $date_options,
      '#title' => $this->t('Date'),
      '#prefix' => '<div id="date-select-wrapper" class="' . $classes . '">',
      '#suffix' => '</div>',
      '#default_value' => !empty($values['date_select']) ? $values['date_select'] : '',
      '#ajax' => [
        'callback' => [$this, 'rebuildAjaxCallback'],
        'wrapper' => 'groupex-full-form-wrapper',
        'event' => 'change',
        'method' => 'replace',
        'effect' => 'fade',
        'progress' => [
          'type' => 'throbber',
        ],
      ],
    ];

    $form['location'] = [
      '#type' => 'radios',
      '#options' => $this->getOptions($this->request(['query' => ['locations' => TRUE]]), 'id', 'name'),
      '#title' => $this->t('Locations'),
      '#default_value' => !empty($values['location']) ? $values['location'] : '',
      '#prefix' => '<div id="location-wrapper" class="' . $location_classes . '">',
      '#suffix' => '</div>',
      '#ajax' => [
        'callback' => [$this, 'rebuildAjaxCallback'],
        'wrapper' => 'groupex-full-form-wrapper',
        'event' => 'change',
        'method' => 'replace',
        'effect' => 'fade',
        'progress' => [
          'type' => 'throbber',
        ],
      ],
    ];

    $filter_date_default = date('n/d/y', REQUEST_TIME);
    $form['date'] = [
      '#type' => 'hidden',
      '#default_value' => $filter_date_default,
    ];

    $form['results'] = [
      '#markup' => '<div class="groupex-results">' . render($formatted_results) . '</div>',
    ];

    $form['#attached']['library'][] = 'ymca_groupex/ymca_groupex';

    return $form;
  }

  /**
   * Custom ajax callback.
   */
  public function rebuildAjaxCallback(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $location = !empty($values['location_select']) ? $values['location_select'] : $values['location'];
    $filter_date = !empty($values['date_select']) ? $values['date_select'] : $values['date'];
    $parameters = [
      'location' => $location,
      'filter_date' => $filter_date,
    ];
    $formatted_results = self::buildResults($form, $form_state);
    $response = new AjaxResponse();
    $response->addCommand(new HtmlCommand('#groupex-full-form-wrapper .groupex-results', $formatted_results));
    $response->addCommand(new InvokeCommand(NULL, 'groupExLocationAjaxAction', array($parameters)));
    return $response;
  }

  /**
   * Custom ajax callback.
   */
  public function buildResults(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $query = \Drupal::request()->query->all();
    if (!isset($values['location']) && is_numeric($query['location'])) {
      $values['location_select'] = $values['location'] = $query['location'];
    }
    if (!isset($values['date']) && !empty($query['filter_date'])) {
      $values['date_select'] = $values['date'] = $query['filter_date'];
    }
    $location = !empty($values['location_select']) ? $values['location_select'] : $values['location'];
    $filter_date = !empty($values['date_select']) ? $values['date_select'] : $values['date'];
    $class = !empty($query['class']) ? $query['class'] : 'any';
    $filter_length = !empty($query['filter_length']) ? $query['filter_length'] : 'day';
    $groupex_class = !empty($query['groupex_class']) ? $query['groupex_class'] : 'groupex_table_class';
    $parameters = [
      'location' => $location,
      'class' => $class,
      'category' => 'any',
      'filter_length' => $filter_length,
      'filter_date' => $filter_date,
      'groupex_class' => $groupex_class,
    ];
    // Add optional parameter.
    if (!empty($query['instructor'])) {
      $parameters['instructor'] = $query['instructor'];
    }
    \Drupal::service('ymca_groupex.schedule_fetcher')->__construct($parameters);
    // Get classes schedules.
    $schedule = \Drupal::service('ymca_groupex.schedule_fetcher')->getSchedule();
    // Are results empty?
    $empty_results = \Drupal::service('ymca_groupex.schedule_fetcher')->isEmpty();
    // Format results as table view.
    $formatted_results = ymca_groupex_schedule_table_layout($schedule);
    return $formatted_results;
  }

}
