<?php

namespace Drupal\acquia_connector\Form;

use Drupal\acquia_connector\Helper\Storage;
use Drupal\acquia_connector\Client;
use Drupal\Core\Url;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\PrivateKey;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\acquia_connector\ConnectorException;

/**
 * Class SettingsForm.
 *
 * @package Drupal\acquia_connector\Form
 */
class SettingsForm extends ConfigFormBase {

  /**
   * The config factory interface.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The private key.
   *
   * @var \Drupal\Core\PrivateKey
   */
  protected $privateKey;

  /**
   * The Acquia connector client.
   *
   * @var \Drupal\acquia_connector\Client
   */
  protected $client;

  /**
   * Constructs a \Drupal\aggregator\SettingsForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\PrivateKey $private_key
   *   The private key.
   * @param \Drupal\acquia_connector\Client $client
   *   The Acquia client.
   */
  public function __construct(ConfigFactoryInterface $config_factory, ModuleHandlerInterface $module_handler, PrivateKey $private_key, Client $client) {
    parent::__construct($config_factory);

    $this->moduleHandler = $module_handler;
    $this->privateKey = $private_key;
    $this->client = $client;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('module_handler'),
      $container->get('private_key'),
      $container->get('acquia_connector.client')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['acquia_connector.settings', 'acquia_search.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'acquia_connector_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $config = $this->config('acquia_connector.settings');
    $storage = new Storage();
    $identifier = $storage->getIdentifier();
    $key = $storage->getKey();
    $subscription = $config->get('subscription_name');

    if (empty($identifier) && empty($key)) {
      return new RedirectResponse($this->url('acquia_connector.start'));
    }

    // Check our connection to the Acquia Network and validate credentials.
    try {
      $this->client->getSubscription($identifier, $key);
    }
    catch (ConnectorException $e) {
      $error_message = acquia_connector_connection_error_message($e->getCustomMessage('code', FALSE));
      $ssl_available = in_array('ssl', stream_get_transports(), TRUE) && !defined('ACQUIA_DEVELOPMENT_NOSSL') && $config->get('spi.ssl_verify');
      if (empty($error_message) && $ssl_available) {
        $error_message = $this->t('There was an error in validating your subscription credentials. You may want to try disabling SSL peer verification by setting the variable acquia_connector.settings:spi.ssl_verify to false.');
      }
      drupal_set_message($error_message, 'error', FALSE);
    }

    $form['connected'] = array(
      '#markup' => $this->t('<h3>Connected to the Acquia Network</h3>'),
    );
    if (!empty($subscription)) {
      $form['subscription'] = array(
        '#markup' => $this->t('Subscription: @sub <a href=":url">change</a>', array('@sub' => $subscription, ':url' => $this->url('acquia_connector.setup'))),
      );
    }

    $form['identification'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('Site Identification'),
      '#collapsible' => FALSE,
    );

    $form['identification']['description']['#markup'] = $this->t('Provide a name for this site to uniquely identify it on Acquia Cloud.');
    $form['identification']['description']['#weight'] = -2;

    $form['identification']['site'] = array(
      '#prefix' => '<div class="acquia-identification">',
      '#suffix' => '</div>',
      '#weight' => -1,
    );

    $form['identification']['site']['name'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Name'),
      '#maxlength' => 255,
      '#required' => TRUE,
      '#default_value' => $config->get('spi.site_name') ?: \Drupal::service('acquia_connector.spi')->getAcquiaHostedName(),
    );

    $acquia_hosted = \Drupal::service('acquia_connector.spi')->checkAcquiaHosted();

    if ($acquia_hosted) {
      $form['identification']['#description'] = $this->t('Acquia hosted sites are automatically provided with a machine name.');
    }

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
      $form['identification']['site']['machine_name']['#default_value'] = $this->config('acquia_connector.settings')->get('spi.site_machine_name') ?: \Drupal::service('acquia_connector.spi')->getAcquiaHostedMachineName();
      $form['identification']['site']['machine_name']['#disabled'] = TRUE;
    }

    $form['connection'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('Acquia Subscription Settings'),
      '#collapsible' => FALSE,
    );

    // Help documentation is local unless the Help module is disabled.
    if ($this->moduleHandler->moduleExists('help')) {
      $help_url = \Drupal::url('help.page', array('name' => 'acquia_connector'));
    }
    else {
      $help_url = Url::fromUri('https://docs.acquia.com/network/install')->getUri();
    }

    if (!empty($identifier) && !empty($key)) {
      $ssl_available = (in_array('ssl', stream_get_transports(), TRUE) && !defined('ACQUIA_DEVELOPMENT_NOSSL'));

      $form['connection']['spi'] = array(
        '#prefix' => '<div class="acquia-spi">',
        '#suffix' => '</div>',
        '#weight' => 0,
      );

      $form['connection']['description']['#markup'] = $this->t('Allow collection and examination of the following items. <a href=":url">Learn more</a>.', array(':url' => $help_url));
      $form['connection']['description']['#weight'] = '-1';

      $form['connection']['spi']['admin_priv'] = array(
        '#type' => 'checkbox',
        '#title' => $this->t('Admin privileges'),
        '#default_value' => $config->get('spi.admin_priv'),
      );
      $form['connection']['spi']['send_node_user'] = array(
        '#type' => 'checkbox',
        '#title' => $this->t('Nodes and users'),
        '#default_value' => $config->get('spi.send_node_user'),
      );
      $form['connection']['spi']['send_watchdog'] = array(
        '#type' => 'checkbox',
        '#title' => $this->t('Watchdog logs'),
        '#default_value' => $config->get('spi.send_watchdog'),
      );
      $form['connection']['spi']['module_diff_data'] = array(
        '#type' => 'checkbox',
        '#title' => $this->t('Source code'),
        '#default_value' => (int) $config->get('spi.module_diff_data') && $ssl_available,
        '#description' => $this->t('Source code analysis requires a SSL connection and for your site to be publicly accessible. <a href=":url">Learn more</a>.', array(':url' => $help_url)),
        '#disabled' => !$ssl_available,
      );
      $form['connection']['acquia_dynamic_banner'] = array(
        '#type' => 'checkbox',
        '#title' => $this->t('Receive updates from Acquia Subscription'),
        '#default_value' => $config->get('spi.dynamic_banner'),
      );
      $form['connection']['alter_variables'] = array(
        '#type' => 'checkbox',
        '#title' => $this->t('Allow Insight to update list of approved variables.'),
        '#default_value' => (int) $config->get('spi.set_variables_override'),
        '#description' => $this->t('Insight can set variables on your site to recommended values at your approval, but only from a specific list of variables. Check this box to allow Insight to update the list of approved variables. <a href=":url">Learn more</a>.', array(':url' => $help_url)),
      );

      $use_cron = $config->get('spi.use_cron');

      $form['connection']['use_cron'] = array(
        '#type' => 'checkbox',
        '#title' => $this->t('Send via Drupal cron'),
        '#default_value' => $use_cron,
      );

      $form['#attached']['library'][] = 'acquia_connector/acquia_connector.form';
      $key = sha1($this->privateKey->get());
      $url = Url::fromRoute('acquia_connector.send', [], ['query' => ['key' => $key], 'absolute' => TRUE])->toString();

      $form['connection']['use_cron_url'] = array(
        '#type' => 'container',
        '#children' => $this->t("Enter the following URL in your server's crontab to send SPI data:<br /><em>:url</em>", array(':url' => $url)),
        '#states' => array(
          'visible' => array(
            ':input[name="use_cron"]' => array('checked' => FALSE),
          ),
        ),
      );
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
    $config = \Drupal::configFactory()->getEditable('acquia_connector.settings');
    $values = $form_state->getValues();
    $config->set('spi.module_diff_data', $values['module_diff_data'])
      ->set('spi.site_name', $values['name'])
      ->set('spi.dynamic_banner', $values['acquia_dynamic_banner'])
      ->set('spi.admin_priv', $values['admin_priv'])
      ->set('spi.send_node_user', $values['send_node_user'])
      ->set('spi.send_watchdog', $values['send_watchdog'])
      ->set('spi.use_cron', $values['use_cron'])
      ->set('spi.set_variables_override', $values['alter_variables'])
      ->save();

    // If the machine name changed, send information so we know if it is a dupe.
    if ($values['machine_name'] != $this->config('acquia_connector.settings')->get('spi.site_machine_name')) {
      $config->set('spi.site_machine_name', $values['machine_name'])->save();

      $response = \Drupal::service('acquia_connector.spi')->sendFullSpi(ACQUIA_SPI_METHOD_CREDS);
      \Drupal::service('acquia_connector.spi')->spiProcessMessages($response);
    }

    parent::submitForm($form, $form_state);
  }
}
