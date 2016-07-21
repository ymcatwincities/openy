<?php

namespace Drupal\ymca_mindbody\Controller;

use Drupal\mindbody\MindbodyException;
use Drupal\ymca_mindbody\Form\MindbodyPTForm;
use Drupal\Core\Controller\ControllerBase;

/**
 * Controller for "Mindbody results" page.
 */
class MindbodyResultsController extends ControllerBase {

  /**
   * Set page content.
   */
  public function content() {
    // TODO: use DI.
    $query = \Drupal::request()->query->all();
    $values = array(
      'location' => !empty($query['location']) && is_numeric($query['location']) ? $query['location'] : NULL,
      'program' => !empty($query['program']) && is_numeric($query['program']) ? $query['program'] : NULL,
      'session_type' => !empty($query['session_type']) && is_numeric($query['session_type']) ? $query['session_type'] : NULL,
      'trainer' => !empty($query['trainer']) ? $query['trainer'] : NULL,
      'start_time' => !empty($query['start_time']) ? $query['start_time'] : NULL,
      'end_time' => !empty($query['end_time']) ? $query['end_time'] : NULL,
      'date_range' => !empty($query['date_range']) ? $query['date_range'] : NULL,
    );
    if (isset($query['context'])) {
      $values['context'] = $query['context'];
    }

    /** @var \Drupal\ymca_mindbody\YmcaMindbodyResultsSearcher $searcher */
    $searcher = \Drupal::service('ymca_mindbody.results_searcher');
    $node = \Drupal::request()->get('node');
    try {
      $search_results = $searcher->getSearchResults($values, $node);
    }
    catch (MindbodyException $e) {
      // TODO: use DI.
      $logger = \Drupal::getContainer()->get('logger.factory')->get('ymca_mindbody');
      $logger->error('Failed to get the results: %msg', ['%msg' => $e->getMessage()]);

      return [
        '#prefix' => '<div class="row mindbody-search-results-content">
          <div class="container">
            <div class="day col-sm-12">',
        // TODO: use service instead of form.
        'markup' => $searcher->getDisabledMarkup(),
        '#suffix' => '</div></div></div>',
      ];
    }

    // TODO: refactor to use render arrays.
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
    return t('Personal Trainer Schedules');
  }

}
