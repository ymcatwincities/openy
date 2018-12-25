<?php

/**
 * @file
 * Drush integration for the acquia search module.
 */

// TODO: This complete file is deprecated? Contains many D7isms and there is no
// use to set a derived key per environment? (Preferred method is auto-switch or
// acquia_search.settings.connection_override).
use Drupal\search_api\Entity\Server;
use Drupal\acquia_connector\CryptConnector;

define('ACQUIA_SEARCH_ENVIRONMENT_ID', 'acquia_search_server_1');
/**
 * @file
 * Drush integration for acquia_search.
 */

/**
 * Implements hook_drush_command().
 */
function acquia_search_drush_command() {
  $items = array();

  $items['solr-set-derived-key'] = array(
    'callback' => 'acquia_search_set_derived_key_for_env',
    'description' => dt('Sets an environment to be active for Acquia search using a specific derived key.
      You should use your Acquia Identifier and Network Key to set this value.'),
    'options' => array(
      'env-id' => array(
        'description' => 'Apache Solr environment id.',
      ),
      'acquia-id' => array(
        'description' => 'Acquia Subscription ID.',
      ),
      'acquia-key' => array(
        'description' => 'Acquia Subscription Key.',
      ),
    ),
    'examples' => array(
      'drush solr-set-derived-key --env-id=acquia_search_server_1_1 --acquia-id=AAAA-12345 --acquia-key=abcdefgijklmnopqrstuvw12345' => 'Set this environment to use the following keys.',
    ),
  );
  return $items;
}

/**
 * Implements hook_drush_help().
 */
function acquia_search_drush_help($section) {
  switch ($section) {
    case 'drush:solr-set-derived-key':
      return dt("Sets an environment to be active for Acquia search using a specific derived key.
      You should use your Acquia Identifier and Network Key to set this value.");
  }
}

/**
 * Drush callback.
 *
 * Set the derived key for a specific environment, using an id and key.
 * Also set the url to point to the acquia service to make it easier for
 * customers to set any environment as an acquia environment.
 */
function acquia_search_set_derived_key_for_env() {
  // Environment id.
  $env_id = drush_get_option('env-id');
  // Check on bool, because drush sets the var as true if no value is given.
  if (empty($env_id) || is_bool($env_id)) {
    return drush_set_error('DRUSH_VARIABLE_ERROR', dt('No apachesolr environment id specified'));
  }
  if ($env_id == ACQUIA_SEARCH_ENVIRONMENT_ID) {
    return drush_set_error('DRUSH_VARIABLE_ERROR', dt('Modifying the original Acquia Search subscription is not allowed. Please use another environment'));
  }
  $environment = Server::load($env_id);
  // Check if the environment is valid.
  if (empty($environment)) {
    return drush_set_error('DRUSH_ACQUIA_ERROR', dt('The specified environment does not exist'));
  }
  // Acquia network subscription id.
  $acquia_id = drush_get_option('acquia-id');
  // Check on bool, because drush sets the var as true if no value is given.
  if (empty($acquia_id) || is_bool($acquia_id)) {
    return drush_set_error('DRUSH_VARIABLE_ERROR', dt('No Acquia subscription id specified'));
  }
  // Acquia network subscription key.
  $acquia_key = drush_get_option('acquia-key');
  // Check on bool, because drush sets the var as true if no value is given.
  if (empty($acquia_key) || !is_string($acquia_key)) {
    return drush_set_error('DRUSH_VARIABLE_ERROR', dt('No Acquia subscription key specified'));
  }

  // We do not want to send a heartbeat to the server, we only need subscription
  // data.
  $params = array(
    'active' => FALSE,
    'no_heartbeat' => 1,
  );
  $subscription = \Drupal::service('acquia_connector.client')->getSubscription($acquia_id, $acquia_key, $params);
  // Check if the subscription was valid.
  if (!is_array($subscription)) {
    return drush_set_error('DRUSH_ACQUIA_ERROR', dt('Could not fetch data for specified subscription.'));
  }

  // We use a salt from acquia.com in key derivation since this is a shared
  // value that we could change on the AN side if needed to force any
  // or all clients to use a new derived key.
  $salt = isset($subscription['derived_key_salt']) ? $subscription['derived_key_salt'] : '';
  if (empty($salt)) {
    return drush_set_error('DRUSH_ACQUIA_ERROR', dt('Could not get a salt.'));
  }

  $derived_key = CryptConnector::createDerivedKey($salt, $acquia_id, $acquia_key);
  if (!empty($derived_key)) {
    $environment->set('host', acquia_search_get_search_host());
    $environment->set('path', '/solr/' . $acquia_id);
    $environment->save();
    \Drupal::configFactory()->getEditable('acquia_search.settings')->set('derived_key', $derived_key)->save();
  }
  else {
    return drush_set_error('DRUSH_ACQUIA_ERROR', dt('Could not generate a derived key.'));
  }
}
