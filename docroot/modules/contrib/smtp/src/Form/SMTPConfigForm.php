<?php

/**
 * @file
 * Contains \Drupal\smtp\Form\SMTPConfigForm.
 */

namespace Drupal\smtp\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\smtp\Plugin\Mail\SMTPMailSystem;

/**
 * Implements the SMTP admin settings form.
 */
class SMTPConfigForm extends ConfigFormBase {

  /**
   * {@inheritdoc}.
   */
  public function getFormID() {
    return 'smtp_admin_settings';
  }

  /**
   * {@inheritdoc}.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->configFactory->getEditable('smtp.settings');

    if ($config->get('smtp_on')) {
      drupal_set_message(t('SMTP module is active.'));
    }
    else {
      drupal_set_message(t('SMTP module is INACTIVE.'));
    }

    $form['onoff'] = array(
      '#type'  => 'fieldset',
      '#title' => t('Install options'),
    );
    $form['onoff']['smtp_on'] = array(
      '#type' => 'radios',
      '#title' => t('Turn this module on or off'),
      '#default_value' => $config->get('smtp_on'),
      '#options' => array(1 => t('On'), 0 => t('Off')),
      '#description' => t('To uninstall this module you must turn it off here first.'),
    );

    $form['server'] = array(
      '#type'  => 'fieldset',
      '#title' => t('SMTP server settings'),
    );
    $form['server']['smtp_host'] = array(
      '#type' => 'textfield',
      '#title' => t('SMTP server'),
      '#default_value' => $config->get('smtp_host'),
      '#description' => t('The address of your outgoing SMTP server.'),
    );
    $form['server']['smtp_hostbackup'] = array(
      '#type' => 'textfield',
      '#title' => t('SMTP backup server'),
      '#default_value' => $config->get('smtp_hostbackup'),
      '#description' => t('The address of your outgoing SMTP backup server. If the primary server can\'t be found this one will be tried. This is optional.'),
    );
    $form['server']['smtp_port'] = array(
      '#type' => 'textfield',
      '#title' => t('SMTP port'),
      '#size' => 6,
      '#maxlength' => 6,
      '#default_value' => $config->get('smtp_port'),
      '#description' => t('The default SMTP port is 25, if that is being blocked try 80. Gmail uses 465. See :url for more information on configuring for use with Gmail.', array(':url' => 'http://gmail.google.com/support/bin/answer.py?answer=13287')),
    );
    // Only display the option if openssl is installed.
    if (function_exists('openssl_open')) {
      $encryption_options = array(
        'standard' => t('No'),
        'ssl' => t('Use SSL'),
        'tls' => t('Use TLS'),
      );
      $encryption_description = t('This allows connection to an SMTP server that requires SSL encryption such as Gmail.');
    }
    // If openssl is not installed, use normal protocol.
    else {
      $config->set('smtp_protocol', 'standard');
      $encryption_options = array('standard' => t('No'));
      $encryption_description = t('Your PHP installation does not have SSL enabled. See the :url page on php.net for more information. Gmail requires SSL.', array(':url' => 'http://php.net/openssl'));
    }
    $form['server']['smtp_protocol'] = array(
      '#type' => 'select',
      '#title' => t('Use encrypted protocol'),
      '#default_value' => $config->get('smtp_protocol'),
      '#options' => $encryption_options,
      '#description' => $encryption_description,
    );

    $form['auth'] = array(
      '#type' => 'fieldset',
      '#title' => t('SMTP Authentication'),
      '#description' => t('Leave blank if your SMTP server does not require authentication.'),
    );
    $form['auth']['smtp_username'] = array(
      '#type' => 'textfield',
      '#title' => t('Username'),
      '#default_value' => $config->get('smtp_username'),
      '#description' => t('SMTP Username.'),
    );
    $form['auth']['smtp_password'] = array(
      '#type' => 'password',
      '#title' => t('Password'),
      '#default_value' => $config->get('smtp_password'),
      '#description' => t('SMTP password. If you have already entered your password before, you should leave this field blank, unless you want to change the stored password.'),
    );

    $form['email_options'] = array(
      '#type'  => 'fieldset',
      '#title' => t('E-mail options'),
    );
    $form['email_options']['smtp_from'] = array(
      '#type' => 'textfield',
      '#title' => t('E-mail from address'),
      '#default_value' => $config->get('smtp_from'),
      '#description' => t('The e-mail address that all e-mails will be from.'),
    );
    $form['email_options']['smtp_fromname'] = array(
      '#type' => 'textfield',
      '#title' => t('E-mail from name'),
      '#default_value' => $config->get('smtp_fromname'),
      '#description' => t('The name that all e-mails will be from. If left blank will use a default of: @name',
          ['@name' => $this->configFactory->get('system.site')->get('name')]),
    );
    $form['email_options']['smtp_allowhtml'] = array(
      '#type' => 'checkbox',
      '#title' => t('Allow to send e-mails formatted as HTML'),
      '#default_value' => $config->get('smtp_allowhtml'),
      '#description' => t('Checking this box will allow HTML formatted e-mails to be sent with the SMTP protocol.'),
    );

    $form['email_test'] = array(
      '#type' => 'fieldset',
      '#title' => t('Send test e-mail'),
    );
    $form['email_test']['smtp_test_address'] = array(
      '#type' => 'textfield',
      '#title' => t('E-mail address to send a test e-mail to'),
      '#default_value' => '',
      '#description' => t('Type in an address to have a test e-mail sent there.'),
    );

    $form['smtp_debugging'] = array(
      '#type' => 'checkbox',
      '#title' => t('Enable debugging'),
      '#default_value' => $config->get('smtp_debugging'),
      '#description' => t('Checking this box will print SMTP messages from the server for every e-mail that is sent.'),
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();

    if ($values['smtp_on'] == 1 && $values['smtp_host'] == '') {
      $form_state->setErrorByName('smtp_host', $this->t('You must enter an SMTP server address.'));
    }

    if ($values['smtp_on'] == 1 && $values['smtp_port'] == '') {
      $form_state->setErrorByName('smtp_port', $this->t('You must enter an SMTP port number.'));
    }

    if ($values['smtp_from'] && !valid_email_address($values['smtp_from'])) {
      $form_state->setErrorByName('smtp_from', $this->t('The provided from e-mail address is not valid.'));
    }

    if ($values['smtp_test_address'] && !valid_email_address($values['smtp_test_address'])) {
      $form_state->setErrorByName('smtp_test_address', $this->t('The provided test e-mail address is not valid.'));
    }

    // If username is set empty, we must set both username/password empty as well.
    if (empty($values['smtp_username'])) {
      $values['smtp_password'] = '';
    }
    // A little hack. When form is presented, the password is not shown (Drupal way of doing).
    // So, if user submits the form without changing the password, we must prevent it from being reset.
    elseif (empty($values['smtp_password'])) {
      $form_state->unsetValue('smtp_password');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $config = $this->configFactory->getEditable('smtp.settings');
    $mail_config = $this->configFactory->getEditable('system.mail');
    $mail_system = $mail_config->get('interface');

    // Updating config vars.
    if (isset($values['smtp_password'])) {
      $config->set('smtp_password', $values['smtp_password']);
    }
    $config->set('smtp_on', $values['smtp_on'])
      ->set('smtp_host', $values['smtp_host'])
      ->set('smtp_hostbackup', $values['smtp_hostbackup'])
      ->set('smtp_port', $values['smtp_port'])
      ->set('smtp_protocol', $values['smtp_protocol'])
      ->set('smtp_username', $values['smtp_username'])
      ->set('smtp_from', $values['smtp_from'])
      ->set('smtp_fromname', $values['smtp_fromname'])
      ->set('smtp_allowhtml', $values['smtp_allowhtml'])
      ->set('smtp_debugging', $values['smtp_debugging'])
      ->save();

    // If an address was given, send a test e-mail message.
    if ($test_address = $values['smtp_test_address']) {
      $params['subject'] = t('Drupal SMTP test e-mail');
      $params['body'] = array(t('If you receive this message it means your site is capable of using SMTP to send e-mail.'));
      $account = \Drupal::currentUser();
      // If module is off, send the test message with SMTP by temporarily overriding.
      if (!$config->get('smtp_on')) {
        $original = $mail_config->get('interface');
        $mail_system['default'] = 'SMTPMailSystem';
        $mail_config->set('interface', $mail_system)->save();
      }
      \Drupal::service('plugin.manager.mail')->mail('smtp', 'smtp-test', $test_address, $account->getPreferredLangcode(), $params);
      if (!$config->get('smtp_on')) {
        $mail_config->set('interface', $original)->save();
      }
      drupal_set_message(t('A test e-mail has been sent to @email via SMTP. You may want to check the log for any error messages.', ['@email' => $test_address]));
    }

    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   *
   * @todo - Flesh this out.
   */
  public function getEditableConfigNames() {}

}
