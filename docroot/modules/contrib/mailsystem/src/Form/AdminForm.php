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
   * @var MailsystemManager
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
      '#type' => 'fieldset',
      '#title' => $this->t('Default Mail System'),
      '#collapsible' => FALSE,
      '#tree' => TRUE,
    );

    // Default formatter plugin.
    $form['mailsystem']['default_formatter'] = array(
      '#type' => 'select',
      '#title' => $this->t('Select the default formatter plugin:'),
      '#description' => $this->t('Select the standard Plugin for formatting an email before sending it. This Plugin implements <a href=":interface">@interface</a> and in special the <a href=":format">@format</a> function.', $arguments),
      '#options' => $this->getFormatterPlugins(),
      '#default_value' => $config->get('defaults.formatter'),
    );

    // Default sender plugin.
    $form['mailsystem']['default_sender'] = array(
      '#type' => 'select',
      '#title' => $this->t('Select the default sender plugin:'),
      '#description' => $this->t('Select the standard Plugin for sending an email after formatting it. This Plugin implements <a href=":interface">@interface</a> and in special the <a href=":mail">@mail</a> function.', $arguments),
      '#options' => $this->getSenderPlugins(),
      '#default_value' => $config->get('defaults.sender'),
    );

    // Default theme for formatting emails.
    $form['mailsystem']['default_theme'] = array(
      '#type' => 'select',
      '#title' => $this->t('Theme to render the emails:'),
      '#description' => $this->t('Select the theme that will be used to render emails which are configured for this. This can be either the current theme, the default theme, the domain theme or any active theme.'),
      '#options' => $this->getThemesList(),
      '#default_value' => $config->get('theme'),
    );

    // Fieldset for custom module configuration.
    $form['custom'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('Custom module configurations'),
      '#collapsible' => FALSE,
      '#tree' => TRUE,
    );

    // Configuration for a new module.
    $form['custom']['custom_module'] = array(
      '#type' => 'select',
      '#title' => $this->t('Module:'),
      '#options' => $this->getModulesList(),
      '#default_value' => '',
    );
    $form['custom']['custom_module_key'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Key:'),
      '#description' => $this->t('This is a special value which is used to distinguish between different types of emails sent out by a module.<br/>Currently there is no way to extract them automatically, so you have to check the code and the hook_mail() function calls.'),
      '#default_value' => '',
    );
    $form['custom']['custom_formatter'] = array(
      '#type' => 'select',
      '#title' => $this->t('Formatter plugin:'),
      '#options' => $this->getFormatterPlugins(TRUE),
      '#default_value' => 'none',
    );
    $form['custom']['custom_sender'] = array(
      '#type' => 'select',
      '#title' => $this->t('Sender plugin:'),
      '#options' => $this->getSenderPlugins(TRUE),
      '#default_value' => 'none',
    );

    // Get all configured modules and show them in a list.
    $modules = $config->get(MailsystemManager::MAILSYSTEM_MODULES_CONFIG) ?: [];
    $options = array();
    foreach ($modules as $module => $conf) {
      if (is_array($conf)) {
        // Main table structure.
        $mod = array(
          'module' => ucfirst($module),
          'formatter' => '',
          'sender' => '',
          'key' => '',
        );

        foreach ($conf as $key => $val) {
          $module_key = $module . '.' . $key;

          // Even we have now a key which defines the type or we have an array
          // with the types as keys - in both cases, the values are the Plugins.
          switch ($key) {
            case MailsystemManager::MAILSYSTEM_TYPE_FORMATTING:
              $mod['formatter'] = $this->getPluginLabel($val);
              break;

            case MailsystemManager::MAILSYSTEM_TYPE_SENDING:
              $mod['sender'] = $this->getPluginLabel($val);
              break;

            default:
              if (is_array($val)) {
                $mod['key'] = ($key === 'none') ? '' : $key;
                if (isset($val[MailsystemManager::MAILSYSTEM_TYPE_FORMATTING])) {
                  $mod['formatter'] = $this->getPluginLabel($val[MailsystemManager::MAILSYSTEM_TYPE_FORMATTING]);
                }
                if (isset($val[MailsystemManager::MAILSYSTEM_TYPE_SENDING])) {
                  $mod['sender'] = $this->getPluginLabel($val[MailsystemManager::MAILSYSTEM_TYPE_SENDING]);
                }
              }
              break;
          }
          $options[$module_key] = $mod;
        }
      }
    }

    // Show and change all custom configurations.
    $form['custom']['modules'] = array(
      '#type' => 'tableselect',
      '#header' => array(
        'module' => $this->t('Module'),
        'key' => $this->t('Key'),
        'formatter' => $this->t('Formatter'),
        'sender' => $this->t('Sender'),
      ),
      '#options' => $options,
      '#empty' => $this->t('No special configuration yet...'),
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('mailsystem.settings');

    // Set the default mail formatter.
    if ($form_state->hasValue(['mailsystem', 'default_formatter'])) {
      $class = $form_state->getValue(['mailsystem', 'default_formatter']);
      $plugin = $this->mailManager->getDefinition($class);
      if (isset($plugin)) {
        $config->set('defaults.formatter', $class);
      }
    }

    // Set the default mail sender.
    if ($form_state->hasValue(['mailsystem', 'default_sender'])) {
      $class = $form_state->getValue(['mailsystem', 'default_sender']);
      $plugin = $this->mailManager->getDefinition($class);
      if (isset($plugin)) {
        $config->set('defaults.sender', $class);
      }
    }

    // Set the default theme.
    if ($form_state->hasValue(['mailsystem', 'default_theme'])) {
      $config->set('theme', $form_state->getValue(['mailsystem', 'default_theme']));
    }

    // Create a new module configuration if a module is selected.
    if ($form_state->hasValue(['custom', 'custom_module']) && ($form_state->getValue(['custom', 'custom_module']) != 'none')) {
      $module = $form_state->getValue(['custom', 'custom_module']);
      $key = $form_state->getValue(['custom', 'custom_module_key']);
      $formatter = $form_state->getValue(['custom', 'custom_formatter']);
      $sender = $form_state->getValue(['custom', 'custom_sender']);

      // Create at least two configuration entries:
      // One for the sending and one for the formatting.
      // The configuration entries for the modules are inside the "modules"
      // containment, use MailsystemManager::MAILSYSTEM_MODULES_CONFIG for this.
      //
      // The configuration entries can be:
      // modules.module.key.type -> Plugin for a special mail and send/format function
      // modules.module.type     -> Global plugin for the send/format function
      $config_key = MailsystemManager::MAILSYSTEM_MODULES_CONFIG . '.' . $module;
      $config_key .= !empty($key) ? '.' . $key : '.none';

      if ($formatter != 'none') {
        $config->set($config_key . '.' . MailsystemManager::MAILSYSTEM_TYPE_FORMATTING, $formatter);
      }
      if ($sender != 'none') {
        $config->set($config_key . '.' . MailsystemManager::MAILSYSTEM_TYPE_SENDING, $sender);
      }
    }

    // If there are some selections in the tableselect, remove them.
    if ($form_state->hasValue(['custom', 'modules']) && is_array($form_state->getValue(['custom', 'modules']))) {
      foreach ($form_state->getValue(['custom', 'modules']) as $key => $val) {
        if ($key === $val) {
          $config->clear(MailsystemManager::MAILSYSTEM_MODULES_CONFIG . '.' . $key);
        }
      }
    }

    // Finally save the configuration.
    $config->save();
    drupal_set_message($this->t('Configuration saved.'));
  }

  /**
   * Returns a list with all formatter plugins.
   *
   * The plugin even must implement \Drupal\Core\Mail\MailInterface or the
   * interface we provide for this: \Drupal\mailsystem\FormatterInterface
   *
   * @param bool $showSelect
   *   If TRUE, a "-- Select --" entry is added as the first entry.
   *
   * @return array
   *   Associative array with all formatter plugins:
   *   - name: label
   */
  protected function getFormatterPlugins($showSelect = FALSE) {
    $list = array();

    // Add the "select" as first entry with the default mailsystem id as key.
    if (filter_var($showSelect, FILTER_VALIDATE_BOOLEAN)) {
      $list['none'] = $this->t('-- Select --');
    }

    // Append all MailPlugins.
    foreach ($this->mailManager->getDefinitions() as $v) {
      $list[$v['id']] = $v['label'];
    }
    return $list;
  }

  /**
   * Returns a list with all mail sender plugins.
   *
   * The plugin even must implement \Drupal\Core\Mail\MailInterface or the
   * interface we provide for this: \Drupal\mailsystem\SenderInterface
   *
   * @param bool $showSelect
   *   If TRUE, a "-- Select --" entry is added as the first entry.
   *
   * @return array
   *   Associative array with all mail sender plugins:
   *   - name: label
   */
  protected function getSenderPlugins($showSelect = FALSE) {
    $list = array();

    // Add the "select" as first entry with the default mailsystem id as key.
    if (filter_var($showSelect, FILTER_VALIDATE_BOOLEAN)) {
      $list['none'] = $this->t('-- Select --');
    }

    // Append all MailPlugins.
    foreach ($this->mailManager->getDefinitions() as $v) {
      $list[$v['id']] = $v['label'];
    }
    return $list;
  }

  /**
   * Returns a list with all themes.
   *
   * @return array
   *   Associative array with all enabled themes:
   *   - name: label
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
   * Returns a list with all modules which sends emails.
   *
   * Currently this is evaluated by the hook_mail implementation.
   *
   * @return array
   *   Associative array with all modules which sends emails:
   *   - module: label
   */
  protected function getModulesList() {
    $list = array(
      'none' => $this->t('-- Select --'),
    );
    foreach ($this->moduleHandler->getImplementations('mail') as $module) {
      $list[$module] = ucfirst($module);
    }
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

}
