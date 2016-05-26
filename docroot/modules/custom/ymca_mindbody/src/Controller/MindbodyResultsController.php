<?php

namespace Drupal\ymca_mindbody\Controller;

use Drupal\ymca_mindbody\Form\MindbodyPOCForm;

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
      'trainer' => is_numeric($query['trainer']) ? $query['trainer'] : '',
    );

    $form = new MindbodyPOCForm();
    $search_results = $form->getSearchResults($values);

    return [
      '#markup' => '<div class="content"><div class="container">' . $search_results . '</div></div>',
    ];
  }

  /**
   * Set Title.
   */
  public function setTitle() {
    return t('Personal Training Schedules');
  }

}
