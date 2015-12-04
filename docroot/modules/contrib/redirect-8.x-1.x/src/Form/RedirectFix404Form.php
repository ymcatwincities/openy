<?php

/**
 * @file
 * Contains \Drupal\redirect\Form\RedirectFix404Form
 */

namespace Drupal\redirect\Form;

use Drupal\Component\Utility\SafeMarkup;
use Drupal\Core\Database\Query\SelectInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\Request;

class RedirectFix404Form extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'redirect_fix_404_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $destination = drupal_get_destination();

    $search = $this->getRequest()->get('search');
    $form['#attributes'] = array('class' => array('search-form'));

    // The following requires the dblog module. If it's not available, provide a message.
    if (! \Drupal::moduleHandler()->moduleExists('dblog')) {
      $form['message'] = [
        '#type' => 'markup',
        '#markup' => t('This page requires the Database Logging (dblog) module, which is currently not installed.'),
      ];
      return $form;
    }

    $form['basic'] = array(
      '#type' => 'fieldset',
      '#title' => t('Filter 404s'),
      '#attributes' => array('class' => array('container-inline')),
    );
    $form['basic']['filter'] = array(
      '#type' => 'textfield',
      '#title' => '',
      '#default_value' => $search,
      '#maxlength' => 128,
      '#size' => 25,
    );
    $form['basic']['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Filter'),
      '#action' => 'filter',
    );
    if ($search) {
      $form['basic']['reset'] = array(
        '#type' => 'submit',
        '#value' => t('Reset'),
        '#action' => 'reset',
      );
    }

    $header = array(
      array('data' => t('Page'), 'field' => 'message'),
      array('data' => t('Count'), 'field' => 'count', 'sort' => 'desc'),
      array('data' => t('Last accessed'), 'field' => 'timestamp'),
      array('data' => t('Operations')),
    );

    $count_query = db_select('watchdog', 'w');
    $count_query->addExpression('COUNT(DISTINCT(w.message))');
    $count_query->leftJoin('redirect', 'r', 'w.message = r.redirect_source__path');
    $count_query->condition('w.type', 'page not found');
    $count_query->isNull('r.rid');
    $this->filterQuery($count_query, array('w.message'), $search);

    $query = db_select('watchdog', 'w')
      ->extend('Drupal\Core\Database\Query\TableSortExtender')->orderByHeader($header)
      ->extend('Drupal\Core\Database\Query\PagerSelectExtender')->limit(25);
    $query->fields('w', array('message', 'variables'));
    $query->addExpression('COUNT(wid)', 'count');
    $query->addExpression('MAX(timestamp)', 'timestamp');
    $query->leftJoin('redirect', 'r', 'w.message = r.redirect_source__path');
    $query->isNull('r.rid');
    $query->condition('w.type', 'page not found');
    $query->groupBy('w.message');
    $query->groupBy('w.variables');
    $this->filterQuery($query, array('w.message'), $search);
    $query->setCountQuery($count_query);
    $results = $query->execute();

    $rows = array();
    foreach ($results as $result) {

      // @todo Detect the language from the url.
      $url = SafeMarkup::format($result->message, unserialize($result->variables));

      $request = Request::create($url, 'GET', [], [], [], \Drupal::request()->server->all());
      $path = ltrim($request->getPathInfo(), '/');

      $row = array();
      $row['source'] = \Drupal::l($url, Url::fromUri('base:' . $path, array('query' => $destination)));
      $row['count'] = $result->count;
      $row['timestamp'] = format_date($result->timestamp, 'short');

      $operations = array();
      if (\Drupal::entityManager()->getAccessControlHandler('redirect')->createAccess()) {
        $operations['add'] = array(
          'title' => t('Add redirect'),
          'url' => Url::fromRoute('redirect.add', [], ['query' => array('source' => $path) + $destination]),
        );
      }
      $row['operations'] = array(
        'data' => array(
          '#theme' => 'links',
          '#links' => $operations,
          '#attributes' => array('class' => array('links', 'inline', 'nowrap')),
        ),
      );

      $rows[] = $row;
    }

    $form['redirect_404_table']  = array(
      '#theme' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#empty' => t('No 404 pages without redirects found.'),
    );
    $form['redirect_404_pager'] = array('#type' => 'pager');
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    if ($form_state->getTriggeringElement()['#action'] == 'filter') {
      $form_state->setRedirect('redirect.fix_404', array(), array('query' => array('search' => trim($form_state->getValue('filter')))));
    }
    else {
      $form_state->setRedirect('redirect.fix_404');
    }
  }

  /**
   * Extends a query object for URL redirect filters.
   *
   * @param $query
   *   Query object that should be filtered.
   * @param $keys
   *   The filter string to use.
   */
  protected function filterQuery(SelectInterface $query, array $fields, $keys = '') {
    if ($keys && $fields) {
      // Replace wildcards with PDO wildcards.
      $conditions = db_or();
      $wildcard = '%' . trim(preg_replace('!\*+!', '%', db_like($keys)), '%') . '%';
      foreach ($fields as $field) {
        $conditions->condition($field, $wildcard, 'LIKE');
      }
      $query->condition($conditions);
    }
  }

}
