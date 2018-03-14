<?php

namespace Drupal\search_api_solr\Plugin\SolrConnector;

use Drupal\Core\Form\FormStateInterface;
use Solarium\Core\Client\Endpoint;
use Solarium\Core\Client\Request;
use Solarium\QueryType\Select\Query\Query;

/**
 * Standard Solr connector.
 *
 * @SolrConnector(
 *   id = "basic_auth",
 *   label = @Translation("Basic Auth"),
 *   description = @Translation("A connector usable for Solr installations protected by basic authentication.")
 * )
 */
class BasicAuthSolrConnector extends StandardSolrConnector {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return parent::defaultConfiguration() + [
      'username' => '',
      'password' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $form['auth'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('HTTP Basic Authentication'),
      '#description' => $this->t('If your Solr server is protected by basic HTTP authentication, enter the login data here.'),
      '#collapsible' => TRUE,
      '#collapsed' => empty($this->configuration['username']),
    );

    $form['auth']['username'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Username'),
      '#default_value' => $this->configuration['username'],
      '#required' => TRUE,
    );

    $form['auth']['password'] = array(
      '#type' => 'password',
      '#title' => $this->t('Password'),
      '#description' => $this->t('If this field is left blank and the HTTP username is filled out, the current password will not be changed.'),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    // Since the form is nested into another, we can't simply use #parents for
    // doing this array restructuring magic. (At least not without creating an
    // unnecessary dependency on internal implementation.)
    $values += $values['auth'];

    // For password fields, there is no default value, they're empty by default.
    // Therefore we ignore empty submissions if the user didn't change either.
    if ($values['password'] === ''
      && isset($this->configuration['username'])
      && $values['username'] === $this->configuration['username']) {
      $values['password'] = $this->configuration['password'];
    }

    foreach ($values['auth'] as $key => $value) {
      $form_state->setValue($key, $value);
    }

    // Clean-up the form to avoid redundant entries in the stored configuration.
    $form_state->unsetValue('auth');

    parent::submitConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function search(Query $query, Endpoint $endpoint = NULL) {
    $this->connect();

    if (!$endpoint) {
      $endpoint = $this->solr->getEndpoint('core');
    }

    // Use the 'postbigrequest' plugin if no specific http method is
    // configured. The plugin needs to be loaded before the request is
    // created.
    if ($this->configuration['http_method'] == 'AUTO') {
      $this->solr->getPlugin('postbigrequest');
    }

    // Use the manual method of creating a Solarium request so we can control
    // the HTTP method.
    $request = $this->solr->createRequest($query);

    // Set the configured HTTP method.
    if ($this->configuration['http_method'] == 'POST') {
      $request->setMethod(Request::METHOD_POST);
    }
    elseif ($this->configuration['http_method'] == 'GET') {
      $request->setMethod(Request::METHOD_GET);
    }

    // Set HTTP Basic Authentication parameter, if login data was set.
    if (strlen($this->configuration['username']) && strlen($this->configuration['password'])) {
      $request->setAuthentication($this->configuration['username'], $this->configuration['password']);
    }

    return $this->solr->executeRequest($request, $endpoint);
  }

  /**
   * {@inheritdoc}
   */
  public function viewSettings() {
    $vars = array(
      '%user' => $this->configuration['username'],
      '%pass' => str_repeat('*', strlen($this->configuration['password'])),
    );

    $info[] = array(
      'label' => $this->t('Basic HTTP authentication'),
      'info' => $this->t('Username: %user | Password: %pass', $vars),
    );

    return $info;
  }

}
