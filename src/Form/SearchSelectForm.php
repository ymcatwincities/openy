<?php

namespace Drupal\openy\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines a form for Open Y Search services selection during install.
 */
class SearchSelectForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'openy_select_search';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, array &$install_state = NULL) {
    $form['#title'] = $this->t('Select Open Y Search');

    $form['service'] = [
      '#type' => 'radios',
      '#title' => $this->t('Search service'),
      '#description' => $this->t('Select Search service provided by Open Y you are going to use.'),
      '#options' => [
        'none' => $this->t('None'),
        'openy_google_search' => $this->t('Open Y Google Custom Search'),
        'openy_search_api' => $this->t('Open Y Search API'),
      ],
      '#default_value' => 'none',
    ];
    // Get Google Search Engine ID settings container.
    $form['google_search_engine_id'] = [
      '#title' => $this->t('Google Search Engine ID'),
      '#description' => $this->t('The ID assigned to this website by Google Custom Search engine. To get a engine ID, <a href="https://cse.google.com//">sign up for Google Custom Search</a> and create search engine for your website.'),
      '#maxlength' => 40,
      '#type' => 'textfield',
      '#states' => [
        'visible' => [
          ':input[name="service"]' => ['value' => 'openy_google_search'],
        ],
      ],
    ];

    $form['search_api_server'] = [
      '#type' => 'select',
      '#title' => $this->t('Select your preferred Search API Server:'),
      '#options' => [
        'database' => $this->t('Database'),
        'solr' => $this->t('Solr'),
      ],
      '#default_value' => 'database',
      '#states' => [
        'visible' => [
          ':input[name="service"]' => ['value' => 'openy_search_api'],
        ],
      ],
    ];

    $form['actions'] = [
      'continue' => [
        '#type' => 'submit',
        '#value' => $this->t('Continue'),
      ],
      '#type' => 'actions',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $GLOBALS['install_state']['openy']['search']['service'] = $form_state->getValue('service');
    // Set Google Custom Search Engine ID.
    if (!empty($form_state->getValue('google_search_engine_id'))) {
      $GLOBALS['install_state']['openy']['search']['google_search_engine_id'] = $form_state->getValue('google_search_engine_id');
    }
    // Set search api server type.
    if ($form_state->getValue('service') == 'openy_search_api' && !empty($form_state->getValue('search_api_server'))) {
      $GLOBALS['install_state']['openy']['search']['search_api_server'] = $form_state->getValue('search_api_server');
    }
  }

}
