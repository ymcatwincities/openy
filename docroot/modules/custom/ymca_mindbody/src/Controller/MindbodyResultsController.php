<?php

namespace Drupal\ymca_mindbody\Controller;

use Drupal\ymca_mindbody\Form\MindbodyPTForm;

/**
 * Controller for "Mindbody results" page.
 */
class MindbodyResultsController {

  /**
   * Set page content.
   */
  public function content() {
    $query = \Drupal::request()->query->all();
    $values = array(
      'location' => is_numeric($query['location']) ? $query['location'] : '',
      'program' => is_numeric($query['program']) ? $query['program'] : '',
      'session_type' => is_numeric($query['session_type']) ? $query['session_type'] : '',
      'trainer' => isset($query['trainer']) ? $query['trainer'] : '',
      'start_time' => isset($query['start_time']) ? $query['start_time'] : '',
      'end_time' => isset($query['end_time']) ? $query['end_time'] : '',
      'start_date' => isset($query['start_date']) ? $query['start_date'] : '',
      'end_date' => isset($query['end_date']) ? $query['end_date'] : '',
    );

    $form = new MindbodyPTForm();
    $search_results = $form->getSearchResults($values);

    return [
      '#markup' => render($search_results),
      '#cache' => [
        'max-age' => 0,
      ],
    ];
  }

  /**
   * Set Title.
   */
  public function setTitle() {
    return t('Personal Training Schedules');
  }

}
