<?php

/**
 * @file
 * Contains \Drupal\webform\Form\WebformSettingsForm.
 */

namespace Drupal\webform\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Datetime\Entity\DateFormat;

/**
 * Configure Webform admin settings.
 */
class WebformSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'webform_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['webform.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('webform.settings');

    $form['#tree'] = TRUE;

    $form['components'] = array(
      '#type' => 'details',
      '#title' => t('Available components'),
      '#description' => t('These are the available field types for your installation of Webform. You may disable any of these components by unchecking its corresponding box. Only checked components will be available in existing or new webforms.'),
      '#open' => TRUE,
      '#theme' => 'webform_admin_settings_components_table',
    );

    // Add each component to the form.
    $manager = \Drupal::service('plugin.manager.webform.component');
    $component_types = $manager->getDefinitions();
    foreach ($component_types as $key => $component_type) {
      $component = $manager->createInstance($component_type['id']);
      $form['components'][$key] = array(
        '#type' => 'checkbox',
        '#title' => $component->getLabel(),
        '#description' => $component->getDescription(),
        '#return_value' => 1,
        '#default_value' => 1,//$component['enabled'],
      );
    }

    $form['email'] = array(
      '#type' => 'details',
      '#title' => t('Default e-mail values'),
      '#open' => TRUE,
    );

    $form['email']['default_from_address']  = array(
      '#type' => 'textfield',
      '#title' => t('From address'),
      '#default_value' => $config->get('email.default_from_address'),
      '#description' => t('The default sender address for emailed webform results; often the e-mail address of the maintainer of your forms.'),
    );

    $form['email']['default_from_name']  = array(
      '#type' => 'textfield',
      '#title' => t('From name'),
      '#default_value' => $config->get('email.default_from_name'),
      '#description' => t('The default sender name which is used along with the default from address.'),
    );

    $form['email']['default_subject']  = array(
      '#type' => 'textfield',
      '#title' => t('Default subject'),
      '#default_value' => $config->get('email.default_subject'),
      '#description' => t('The default subject line of any e-mailed results.'),
    );

    $form['email']['replyto']  = array(
      '#type' => 'checkbox',
      '#title' => t('Use Reply-To header'),
      '#default_value' => $config->get('email.replyto'),
      '#description' => t('Sends all e-mail from the domain of the default address above and sets the "Reply-To" header to the actual sender. Helps prevent e-mail from being flagged as spam.'),
    );

    $form['email']['html_capable']  = array(
      '#type' => 'checkbox',
      '#title' => t('HTML mail system'),
      '#default_value' => $config->get('email.html_capable'),
      '#description' => t('Whether the mail system configured for webform is capable of sending mail in HTML format.'),
    );

    $form['email']['default_format']  = array(
      '#type' => 'radios',
      '#title' => t('Format'),
      '#options' => array(
        0 => t('Plain text'),
        1 => t('HTML'),
      ),
      '#default_value' => $config->get('email.default_format'),
      '#description' => t('The default format for new e-mail settings. Webform e-mail options take precedence over the settings for system-wide e-mails configured in MIME mail.'),
      '#states' => array(
        'visible' => array(
          ':input[name="email[html_capable]"]' => array('checked' => TRUE),
        ),
      ),
    );

    $form['email']['format_override']  = array(
      '#type' => 'radios',
      '#title' => t('Format override'),
      '#options' => array(
        0 => t('Per-webform configuration of e-mail format'),
        1 => t('Send all e-mails in the default format'),
      ),
      '#default_value' => $config->get('email.format_override'),
      '#description' => t('Force all webform e-mails to be sent in the default format.'),
      '#states' => array(
        'visible' => array(
          ':input[name="email[html_capable]"]' => array('checked' => TRUE),
        ),
      ),
    );

    $form['progressbar'] = array(
      '#type' => 'details',
      '#title' => t('Progress bar'),
      '#open' => TRUE,
    );

    $form['progressbar']['style']  = array(
      '#type' => 'checkboxes',
      '#title' => t('Progress bar style'),
      '#options' => array(
        'progressbar_bar' => t('Show progress bar'),
        'progressbar_page_number' => t('Show page number as number of completed (i.e. Page 1 of 10)'),
        'progressbar_percent' => t('Show percentage completed (i.e. 10%)'),
        'progressbar_pagebreak_labels' => t('Show page labels from page break components'),
        'progressbar_include_confirmation' => t('Include confirmation page in progress bar'),
      ),
      '#default_value' => $config->get('progressbar.style'),
      '#description' => t('Choose how the progress bar should be displayed for multi-page forms.'),
    );

    $form['progressbar']['label_first'] = array(
      '#type' => 'textfield',
      '#title' => t('First page label'),
      '#default_value' => $config->get('progressbar.label_first'),
      '#maxlength' => 255,
    );

    $form['progressbar']['label_confirmation'] = array(
      '#type' => 'textfield',
      '#title' => t('Confirmation page label'),
      '#default_value' => $config->get('progressbar.label_confirmation'),
      '#maxlength' => 255,
    );

    $form['advanced'] = array(
      '#type' => 'details',
      '#title' => t('Advanced options'),
      '#open' => FALSE,
    );

    $form['advanced']['tracking_mode']  = array(
      '#type' => 'radios',
      '#title' => t('Track anonymous users by:'),
      '#options' => array(
        'cookie' => t('Cookie only (least strict)'),
        'ip_address' => t('IP address only'),
        'strict' => t('Both cookie and IP address (most strict)'),
      ),
      '#default_value' => $config->get('advanced.tracking_mode'),
      '#description' => t('<a href="http://www.wikipedia.org/wiki/HTTP_cookie">Cookies</a> can be used to help prevent the same user from repeatedly submitting a webform. Limiting by IP address is more effective against repeated submissions, but may result in unintentional blocking of users sharing the same address. Confidential submissions are tracked by cookie only. Logged-in users are always tracked by their user ID and are not affected by this option.'),
    );

    $form['advanced']['email_address_format'] = array(
      '#type' => 'radios',
      '#title' => t('E-mail address format'),
      '#options' => array(
        'long' => t('Long format: "Example Name" &lt;name@example.com&gt;'),
        'short' => t('Short format: name@example.com'),
      ),
      '#default_value' => $config->get('advanced.email_address_format'),
      '#description' => t('Most servers support the "long" format which will allow for more friendly From addresses in e-mails sent. However many Windows-based servers are unable to send in the long format. Change this option if experiencing problems sending e-mails with Webform.'),
    );

    $form['advanced']['email_address_individual'] = array(
      '#type' => 'radios',
      '#title' => t('E-mailing multiple recipients'),
      '#options' => array(
        0 => t('Send a single e-mail to all recipients'),
        1 => t('Send individual e-mails to each recipient'),
      ),
      '#default_value' => $config->get('advanced.email_address_individual'),
      '#description' => t('Individual e-mails increases privacy by not revealing the addresses of other recipients. A single e-mail to all recipients lets them use "Reply All" to communicate.'),
    );

    $date_types = DateFormat::loadMultiple();
    $date_formatter = \Drupal::service('date.formatter');
    $date_format_options = array();
    foreach ($date_types as $machine_name => $format) {
      $date_format_options[$machine_name] = t('@name - @sample', array('@name' => $format->get('label'), '@sample' => $date_formatter->format(REQUEST_TIME, $machine_name)));
    }
    $form['advanced']['date_type'] = array(
      '#type' => 'select',
      '#title' => t('Date format'),
      '#options' => $date_format_options,
      '#default_value' => $config->get('advanced.date_type'),
      '#description' => t('Choose the format for the display of date components. Only the date portion of the format is used. Reporting and export use the short format.'),
    );

    module_load_include('inc', 'webform', 'includes/webform.export');
    $form['advanced']['export_format'] = array(
      '#type' => 'radios',
      '#title' => t('Default export format'),
      '#options' => webform_export_list(),
      '#default_value' => $config->get('advanced.export_format'),
    );

    $form['advanced']['csv_delimiter']  = array(
      '#type' => 'select',
      '#title' => t('Default export delimiter'),
      '#description' => t('This is the delimiter used in the CSV/TSV file when downloading Webform results. Using tabs in the export is the most reliable method for preserving non-latin characters. You may want to change this to another character depending on the program with which you anticipate importing results.'),
      '#default_value' => $config->get('advanced.csv_delimiter'),
      '#options' => array(
        ','  => t('Comma (,)'),
        '\t' => t('Tab (\t)'),
        ';'  => t('Semicolon (;)'),
        ':'  => t('Colon (:)'),
        '|'  => t('Pipe (|)'),
        '.'  => t('Period (.)'),
        ' '  => t('Space ( )'),
      ),
    );

  $form['advanced']['export_wordwrap'] = array(
    '#type' => 'radios',
    '#title' => t('Export word-wrap'),
    '#options' => array(
      '0' => t('Only text containing return characters'),
      '1' => t('All text'),
    ),
    '#default_value' => $config->get('advanced.export_wordwrap'),
    '#description' => t('Some export formats, such as Microsoft Excel, support word-wrapped text cells.'),
  );

    $form['advanced']['submission_access_control']  = array(
      '#type' => 'radios',
      '#title' => t('Submission access control'),
      '#options' => array(
        '1' => t('Select the user roles that may submit each individual webform'),
        '0' => t('Disable Webform submission access control'),
      ),
      '#default_value' => $config->get('advanced.submission_access_control'),
      '#description' => t('By default, the configuration form for each webform allows the administrator to choose which roles may submit the form. You may want to allow users to always submit the form if you are using a separate node access module to control access to webform nodes themselves.'),
    );

    $form['advanced']['email_select_max'] = array(
      '#type' => 'textfield',
      '#title' => t("Select email mapping limit"),
      '#default_value' => $config->get('advanced.email_select_max'),
      '#description' => t('When mapping emails addresses to a select component, limit the choice to components with less than the amount of options indicated. This is to avoid flooding the email settings form.'),
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();

    $disabled_components = array();
    foreach ($values['components'] as $name => $enabled) {
      if (!$enabled) {
        $disabled_components[] = $name;
      }
    }
    $values['disabled_components'] = $disabled_components;

    // Trim out empty options in the progress bar options.
    $values['progressbar']['style'] = array_keys(array_filter($values['progressbar']['style']));

    $this->config('webform.settings')
      ->set('disabled_components', $values['disabled_components'])
      ->set('email.default_from_address', $values['email']['default_from_address'])
      ->set('email.default_from_name', $values['email']['default_from_name'])
      ->set('email.default_subject', $values['email']['default_subject'])
      ->set('email.replyto', $values['email']['replyto'])
      ->set('email.html_capable', $values['email']['html_capable'])
      ->set('email.default_format', $values['email']['default_format'])
      ->set('email.format_override', $values['email']['format_override'])
      ->set('progressbar.style', $values['progressbar']['style'])
      ->set('progressbar.label_first', $values['progressbar']['label_first'])
      ->set('progressbar.label_confirmation', $values['progressbar']['label_confirmation'])
      ->set('advanced.tracking_mode', $values['advanced']['tracking_mode'])
      ->set('advanced.email_address_format', $values['advanced']['email_address_format'])
      ->set('advanced.email_address_individual', $values['advanced']['email_address_individual'])
      ->set('advanced.date_type', $values['advanced']['date_type'])
      ->set('advanced.export_format', $values['advanced']['export_format'])
      ->set('advanced.csv_delimiter', $values['advanced']['csv_delimiter'])
      ->set('advanced.export_wordwrap', $values['advanced']['export_wordwrap'])
      ->set('advanced.submission_access_control', $values['advanced']['submission_access_control'])
      ->set('advanced.email_select_max', $values['advanced']['email_select_max'])
      ->save();

    parent::submitForm($form, $form_state);
  }
}
