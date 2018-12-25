<?php

/**
 * @file
 * Contains csv generation logic.
 */

use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\views\Views;

/**
 * Batch operations: process items to export.csv from view.
 */
function ymca_export_process_pages(&$context) {
  $uri = 'public://export.csv';
  if (empty($context['sandbox'])) {
    $context['sandbox']['progress'] = 0;
    $context['sandbox']['current_page'] = 0;
    $context['sandbox']['max'] = 3;
    if (file_exists($uri)) {
      file_unmanaged_delete($uri);
    }
  }

  $limit = 1000;
  $offset = $context['sandbox']['current_page'] * $limit;

  $view = Views::getView('ymca_members');
  $view->setDisplay('ymca_retention_members_export');
  $view->display_handler->setContentType('csv');
  $view->get_total_rows = TRUE;
  $view->setItemsPerPage($limit);
  $view->setOffset($offset);
  $view->usePager();
  $view->execute();
  $total_rows = $view->total_rows;
  $render = $view->render('ymca_retention_members_export');
  $text = '';
  if (is_object($render['#markup'])) {
    $text = $render['#markup']->__toString();
  }
  if (file_exists($uri)) {
    $text = preg_replace('/^.+\n/', '', $text);
    file_put_contents($uri, $text . "\r\n", FILE_APPEND | LOCK_EX);
  }
  else {
    file_unmanaged_save_data($text . "\r\n", $uri, FILE_EXISTS_REPLACE);
  }
  if ($offset == 0) {
    $context['sandbox']['max'] = ($total_rows / $limit) + 1;
    $context['results']['total'] = $total_rows;
    $context['results']['uri'] = $uri;
  }
  $context['sandbox']['progress']++;
  $context['sandbox']['current_page']++;

  if ($context['sandbox']['progress'] != $context['sandbox']['max']) {
    $context['finished'] = $context['sandbox']['progress'] / $context['sandbox']['max'];
  }
}

/**
 * Finish callback for Batch.
 */
function ymca_export_finished_callback($success, $results, $operations) {
  if ($success) {
    $file_link = Link::fromTextAndUrl(
      t('Download export.csv'),
      Url::fromUri(file_create_url($results['uri']), array()))
      ->toString();
    $message = \Drupal::translation()->formatPlural(
      $results['total'],
      'One row processed. @link',
      '@count posts processed. @link',
      array('@link' => $file_link)
    );
  }
  else {
    $message = t('Finished with an error.');
  }
  drupal_set_message($message);
}
