<?php

namespace Drupal\acquia_connector\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class SpiChangeForm.
 *
 * @package Drupal\acquia_connector\Form
 */
class SpiChangeForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['acquia_connector.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'acquia_connector_spi_change_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('acquia_connector.settings');
    $blocked = $config->get('spi.blocked');
    $acquia_hosted = \Drupal::service('acquia_connector.spi')->checkAcquiaHosted();
    $environment_change = \Drupal::service('acquia_connector.spi')->checkEnvironmentChange();

    if (!$environment_change && !$blocked) {
      $form['#markup'] = $this->t("<h2>No changes detected</h2><p>This form is used to address changes in your site's environment. No changes are currently detected.</p>");
      return $form;
    }
    elseif ($blocked) {
      $form['env_change_action'] = array(
        '#type' => 'checkboxes',
        '#title' => $this->t('The Acquia Connector is disabled and is not sending site profile data to Acquia Cloud for evaluation.'),
        '#options' => array(
          'unblock' => $this->t('Enable this site and send data to Acquia Cloud.'),
        ),
        '#required' => TRUE,
      );
    }
    else {
      $env_changes = $config->get('spi.environment_changes');
      $off_acquia_hosting = array_key_exists('acquia_hosted', $env_changes) && !$acquia_hosted;

      $form['env'] = array(
        '#type' => 'fieldset',
        '#title' => $this->t('<strong>The following changes have been detected in your site environment:</strong>'),
        '#description' => array(
          '#theme' => 'item_list',
          '#items' => $env_changes,
        ),
      );

      $form['env_change_action'] = array(
        '#type' => 'radios',
        '#title' => $this->t('How would you like to proceed?'),
        '#options' => array(
          'block' => $this->t('Disable this site from sending profile data to Acquia Cloud.'),
          'update' => $this->t('Update existing site with these changes.'),
          'create' => $this->t('Track this as a new site on Acquia Cloud.'),
        ),
        '#required' => TRUE,
        '#default_value' => $config->get('spi.environment_changed_action'),
      );

      $form['identification'] = array(
        '#type' => 'fieldset',
        '#title' => $this->t('Site Identification'),
        '#collapsible' => FALSE,
        '#states' => array(
          'visible' => array(
            ':input[name="env_change_action"]' => array('value' => 'create'),
          ),
        ),
      );

      $form['identification']['site'] = array(
        '#prefix' => '<div class="acquia-identification">',
        '#suffix' => '</div>',
        '#weight' => -2,
      );

      $form['identification']['site']['name'] = array(
        '#type' => 'textfield',
        '#title' => $this->t('Name'),
        '#maxlength' => 255,
        '#required' => TRUE,
        '#default_value' => $config->get('spi.site_name'),
      );

      $form['identification']['site']['machine_name'] = array(
        '#type' => 'machine_name',
        '#title' => $this->t('Machine name'),
        '#maxlength' => 255,
        '#required' => TRUE,
        '#machine_name' => array(
          'exists' => array($this, 'exists'),
          'source' => array('identification', 'site', 'name'),
        ),
        '#default_value' => $config->get('spi.site_machine_name'),
      );

      if ($acquia_hosted) {
        $form['identification']['site']['machine_name']['#disabled'] = TRUE;
        $form['identification']['site']['machine_name']['#default_value'] = \Drupal::service('acquia_connector.spi')->getAcquiaHostedMachineName();
      }
      elseif ($off_acquia_hosting) {
        unset($form['env_change_action']['#options']['block']);
        unset($form['env_change_action']['#options']['update']);
        unset($form['env_change_action']['#states']);
        unset($form['identification']['site']['name']['#default_value']);
        unset($form['identification']['site']['machine_name']['#default_value']);
        $form['env_change_action']['#default_value'] = 'create';
        $form['env_change_action']['#access'] = FALSE;
      }

    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * Determines if the machine name already exists.
   *
   * @return bool
   *   FALSE.
   */
  public function exists() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $values = $form_state->getValues();
    $config = \Drupal::configFactory()->getEditable('acquia_connector.settings');

    if (isset($values['env_change_action']['unblock']) && $values['env_change_action']['unblock'] == 'unblock') {
      $config->set('spi.environment_changed_action', $values['env_change_action']['unblock'])->save();
    }
    else {
      $config->set('spi.environment_changed_action', $values['env_change_action'])->save();
    }

    if ($values['env_change_action'] == 'create') {
      $config->set('spi.site_name', $values['name'])
        ->set('spi.site_machine_name', $values['machine_name'])
        ->save();
    }
    parent::submitForm($form, $form_state);

    // Send information as soon as the key/identifier pair is submitted.
    $response = \Drupal::service('acquia_connector.spi')->sendFullSpi(ACQUIA_SPI_METHOD_CREDS);
    \Drupal::service('acquia_connector.spi')->spiProcessMessages($response);
    $form_state->setRedirect('system.status');
  }

}
