<?php
/**
 * @file
 * Contains \Drupal\mailsystem\Form\AdminForm.
 */

namespace Drupal\mailsystem\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Extension\ThemeHandlerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\Url;
use Drupal\mailsystem\MailsystemManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Mail System settings form.
 */
class AdminForm extends ConfigFormBase {

  /**
   * @var \Drupal\Core\Mail\MailManagerInterface
   */
  protected $mailManager;

  /**
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * @var \Drupal\Core\Extension\ThemeHandlerInterface
   */
  protected $themeHandler;

  /**
   * Constructs a \Drupal\system\ConfigFormBase object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Mail\MailManagerInterface $mail_manager
   *   The mail manager.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Extension\ThemeHandlerInterface $theme_handler
   *   The theme handler.
   */
  public function __construct(ConfigFactoryInterface $config_factory, MailManagerInterface $mail_manager, ModuleHandlerInterface $module_handler, ThemeHandlerInterface $theme_handler) {
    parent::__construct($config_factory);
    $this->mailManager = $mail_manager;
    $this->moduleHandler = $module_handler;
    $this->themeHandler = $theme_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('plugin.manager.mail'),
      $container->get('module_handler'),
      $container->get('theme_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'mailsystem_admin_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return array('mailsystem.settings');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('mailsystem.settings');

    $arguments = array(
      ':interface' => Url::fromUri('https://api.drupal.org/api/drupal/core!lib!Drupal!Core!Mail!MailInterface.php/interface/MailInterface/8')->toString(),
      '@interface' => '\Drupal\Core\Mail\MailInterface',
      ':format' => Url::fromUri('https://api.drupal.org/api/drupal/core!lib!Drupal!Core!Mail!MailInterface.php/function/MailInterface%3A%3Aformat/8')->toString(),
      '@format' => 'format()',
      ':mail' => Url::fromUri('https://api.drupal.org/api/drupal/core!lib!Drupal!Core!Mail!MailInterface.php/function/MailInterface%3A%3Amail/8')->toString(),
      '@mail' => 'mail()',
    );

    // Default mail system.
    $form['mailsystem'] = array(
      '#type' => 'details',
      '#title' => $this->t('Default Mail System'),
      '#open' => TRUE,
      '#tree' => TRUE,
    );

    // Default formatter plugin.
    $form['mailsystem']['default_formatter'] = array(
      '#type' => 'select',
      '#title' => $this->t('Formatter'),
      '#description' => $this->t('Select the standard plugin for formatting an email before sending it. This plugin implements <a href=":interface">@interface</a> and in special the <a href=":format">@format</a> function.', $arguments),
      '#options' => $this->getOptions(),
      '#default_value' => $config->get('defaults.formatter'),
    );

    // Default sender plugin.
    $form['mailsystem']['default_sender'] = array(
      '#type' => 'select',
      '#title' => $this->t('Sender'),
      '#description' => $this->t('Select the standard plugin for sending an email after formatting it. This plugin implements <a href=":interface">@interface</a> and in special the <a href=":mail">@mail</a> function.', $arguments),
      '#options' => $this->getOptions(),
      '#default_value' => $config->get('defaults.sender'),
    );

    // Default theme for formatting emails.
    $form['mailsystem']['default_theme'] = array(
      '#type' => 'select',
      '#title' => $this->t('Theme to render the emails'),
      '#description' => $this->t('Select the theme that will be used to render emails which are configured for this. This can be either the current theme, the default theme, the domain theme or any active theme.'),
      '#options' => $this->getThemesList(),
      '#default_value' => $config->get('theme'),
    );

    // Fieldset for custom module configuration.
    $form['custom'] = array(
      '#type' => 'details',
      '#title' => $this->t('Module-specific configuration'),
      '#open' => TRUE,
      '#tree' => TRUE,
    );

    // Configuration for a new module.
    $form['custom']['custom_module'] = array(
      '#type' => 'select',
      '#title' => $this->t('Module'),
      '#options' => $this->getModulesList(),
      '#empty_option' => $this->t('- Select -'),
    );
    $form['custom']['custom_module_key'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Key'),
      '#description' => $this->t('The key is used to identify specific mails if the module sends more than one. Leave empty to use the configuration for all mails sent by the selected module.'),
      '#default_value' => '',
    );
    $form['custom']['custom_formatter'] = array(
      '#type' => 'select',
      '#title' => $this->t('Formatter plugin'),
      '#options' => $this->getOptions(),
      '#empty_option' => $this->t('- Default -'),
    );
    $form['custom']['custom_sender'] = array(
      '#type' => 'select',
      '#title' => $this->t('Sender plugin'),
      '#options' => $this->getOptions(),
      '#empty_option' => $this->t('- Default -'),
    );

    $form['custom']['add'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Add'),
      '#validate' => ['::validateAdd'],
      '#submit' => ['::submitAdd'],
      '#button_type' => 'primary',
    );

    // Show and change all custom configurations.
    $form['custom']['modules'] = array(
      '#type' => 'table',
      '#header' => array(
        'module' => $this->t('Module'),
        'key' => $this->t('Key'),
        'formatter' => $this->t('Formatter'),
        'sender' => $this->t('Sender'),
        'remove' => $this->t('Remove'),
      ),
      '#empty' => $this->t('No specific configuration yet.'),
    );

    // Get all configured modules and show them in a list.
    $modules = $config->get(MailsystemManager::MAILSYSTEM_MODULES_CONFIG) ?: [];
    foreach ($modules as $module => $module_settings) {
      if (is_array($module_settings) && $this->moduleHandler->moduleExists($module)) {
        // Main table structure.
        foreach ($module_settings as $key => $settings) {

          $module_key = $module . '.' . $key;

          $row = array(
            'module' => ['#markup' => $this->moduleHandler->getName($module)],
            'key' => ['#markup' => $key == 'none' ? t('All') : $key],
          );

          $row['formatter'] = array(
            '#type' => 'select',
            '#title' => $this->t('Formatter'),
            '#title_display' => 'hidden',
            '#options' => $this->getOptions(),
            '#empty_option' => $this->t('- Default -'),
            '#default_value' => isset($settings['formatter']) ? $settings['formatter'] : '',
          );
          $row['sender'] = array(
            '#type' => 'select',
            '#title' => $this->t('Sender'),
            '#title_display' => 'hidden',
            '#options' => $this->getOptions(),
            '#empty_option' => $this->t('- Default -'),
            '#default_value' => isset($settings['sender']) ? $settings['sender'] : '',
          );
          $row['remove'] = array(
            '#type' => 'checkbox',
            '#default_value' => $module_key,
          );
          $form['custom']['modules'][$module_key] = $row;
        }
      }
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateAdd(array &$form, FormStateInterface $form_state) {
    if ($form_state->getValue(['custom', 'custom_module']) == '') {
      $form_state->setErrorByName('custom][custom_module', $this->t('The module is required.'));
    }

    $config = $this->config('mailsystem.settings');
    $config_key = $this->getModuleKeyConfigPrefix($form_state->getValue(['custom', 'custom_module']), $form_state->getValue(['custom', 'custom_module_key']));
    if ($config->get($config_key)) {
      $form_state->setErrorByName('custom][custom_module', $this->t('An entry for this combination exists already. Use the form below to update or remove it.'));
      return;
    }

    if (($form_state->getValue(['custom', 'custom_formatter']) == '') && ($form_state->getValue(['custom', 'custom_sender']) == '')) {
      $form_state->setErrorByName('custom][custom_formatter', $this->t('At least a formatter or sender is required.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitAdd(array &$form, FormStateInterface $form_state) {
    // Create a new module configuration or update an existing one if a module
    // is selected.
    $module = $form_state->getValue(['custom', 'custom_module']);
    $key = $form_state->getValue(['custom', 'custom_module_key']);
    $formatter = $form_state->getValue(['custom', 'custom_formatter']);
    $sender = $form_state->getValue(['custom', 'custom_sender']);

    // Create two configuration entries, one for the sending and one for the
    // formatting.
    //
    // The configuration entries can be:
    // modules.module.key.type -> Plugin for a special mail and send/format plugin
    // modules.module.none.type     -> Global plugin for the send/format plugin
    $prefix = $this->getModuleKeyConfigPrefix($module, $key);

    $config = $this->config('mailsystem.settings');
    // Create the new custom module configuration.
    if ($formatter) {
      $config->set($prefix . '.' . MailsystemManager::MAILSYSTEM_TYPE_FORMATTING, $formatter);
    }
    if ($sender) {
      $config->set($prefix . '.' . MailsystemManager::MAILSYSTEM_TYPE_SENDING, $sender);
    }
    $config->save();

    drupal_set_message($this->t('The configuration has been added.'));
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('mailsystem.settings');

    // Set the defaults.
    $config->set('defaults.formatter', $form_state->getValue(['mailsystem', 'default_formatter']));
    $config->set('defaults.sender', $form_state->getValue(['mailsystem', 'default_sender']));
    $config->set('theme', $form_state->getValue(['mailsystem', 'default_theme']));

    // Update or remove the custom modules.
    if ($form_state->hasValue(['custom', 'modules']) && is_array($form_state->getValue(['custom', 'modules']))) {
      foreach ($form_state->getValue(['custom', 'modules'], []) as $module_key => $settings) {
        $prefix = MailsystemManager::MAILSYSTEM_MODULES_CONFIG . '.' . $module_key;
        if (!empty($settings['remove'])) {
          // If the checkbox is checked, remove this row.
          $config->clear($prefix);
        }
        else {
          foreach ([MailsystemManager::MAILSYSTEM_TYPE_FORMATTING, MailsystemManager::MAILSYSTEM_TYPE_SENDING] as $type) {
            if (!empty($settings[$type])) {
              $config->set($prefix . '.' . $type, $settings[$type]);
            }
            else {
              $config->clear($prefix . '.' . $type);
            }
          }
        }
      }
    }

    // Finally save the configuration.
    $config->save();
    parent::submitForm($form, $form_state);
  }

  /**
   * Returns a list with all mail plugins.
   *
   * @return string[]
   *   List of mail plugin labels, keyed by ID.
   */
  protected function getOptions() {
    $list = array();

    // Append all MailPlugins.
    foreach ($this->mailManager->getDefinitions() as $definition) {
      $list[$definition['id']] = $definition['label'];
    }
    return $list;
  }

  /**
   * Returns a list with all themes.
   *
   * @return string[]
   *   List of theme options.
   */
  protected function getThemesList() {
    $theme_options = array(
      'current' => $this->t('Current'),
      'default' => $this->t('Default')
    );
    if ($this->moduleHandler->moduleExists('domain_theme')) {
      $theme_options['domain'] = $this->t('Domain Theme');
    }
    foreach ($this->themeHandler->listInfo() as $name => $theme) {
      if ($theme->status === 1) {
        $theme_options[$name] = $theme->info['name'];
      }
    }
    return $theme_options;
  }

  /**
   * Returns a list with all modules that send e-mails.
   *
   * Currently this is evaluated by the hook_mail implementation.
   *
   * @return string[]
   *   List of modules, keyed by the machine name.
   *
   */
  protected function getModulesList() {
    $list = [];
    foreach ($this->moduleHandler->getImplementations('mail') as $module) {
      $list[$module] = $this->moduleHandler->getName($module);
    }
    asort($list);

    return $list;
  }

  /**
   * Returns a label from a mail plugin.
   *
   * @param string $plugin_id
   *   The plugin ID.
   *
   * @return string
   *   The label from a mail plugin.
   */
  protected function getPluginLabel($plugin_id) {
    $definition = $this->mailManager->getDefinition($plugin_id);
    return isset($definition['label']) ? $definition['label'] : $this->t('Unknown Plugin');
  }

  /**
   * Builds the module prefix for a given module and key pair.
   *
   * @param string $module
   *   The module name.
   * @param string $key
   *   The mail key.
   *
   * @return string
   *   The config prefix for the settings array.
   */
  protected function getModuleKeyConfigPrefix($module, $key) {
    $module_key = $module . '.' . ($key ?: 'none');
    $config_key = MailsystemManager::MAILSYSTEM_MODULES_CONFIG . '.' . $module_key;
    return $config_key;
  }

}
