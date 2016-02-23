<?php

/**
 * @file
 * Contains \Drupal\mailsystem\MailsystemManager.
 */

namespace Drupal\mailsystem;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Utility\SafeMarkup;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Mail\MailInterface;
use Drupal\Core\Mail\MailManager;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\Theme\Registry;
use Drupal\Core\Theme\ThemeInitializationInterface;
use Drupal\Core\Theme\ThemeManager;
use Drupal\Core\Theme\ThemeManagerInterface;

/**
 * Factory for creating mail system objects based on BasePlugin's.
 */
class MailsystemManager extends MailManager {

  /**
   * Constants used for the configuration.
   */
  const MAILSYSTEM_TYPE_SENDING = 'sender';
  const MAILSYSTEM_TYPE_FORMATTING = 'formatter';
  const MAILSYSTEM_MODULES_CONFIG = 'modules';

  /**
   * Config object for mailsystem configuration.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $mailsystemConfig;

  /**
   * @var \Drupal\Core\Theme\ThemeManagerInterface
   */
  protected $themeManager;

  /**
   * The theme initialization.
   *
   * @var \Drupal\Core\Theme\ThemeInitializationInterface
   */
  protected $themeInitialization;

  /**
   * @var \Drupal\Core\Theme\Registry
   */
  protected $defaultThemeRegistry;

  /**
   * @var \Drupal\Core\Theme\Registry
   */
  protected $mailThemeRegistry;

  /**
   * {@inheritdoc}
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler, ConfigFactoryInterface $config_factory, LoggerChannelFactoryInterface $logger_factory, TranslationInterface $string_translation, ThemeManagerInterface $theme_manager, ThemeInitializationInterface $theme_initialization, Registry $default_theme_registry, Registry $mail_theme_registry) {
    parent::__construct($namespaces, $cache_backend, $module_handler, $config_factory, $logger_factory, $string_translation);
    $this->mailsystemConfig = $config_factory->get('mailsystem.settings');
    $this->themeManager = $theme_manager;
    $this->themeInitialization = $theme_initialization;
    $this->defaultThemeRegistry = $default_theme_registry;
    $this->mailThemeRegistry = $mail_theme_registry;
  }

  /**
   * {@inheritdoc}
   */
  public function mail($module, $key, $to, $langcode, $params = array(), $reply = NULL, $send = TRUE) {
    // Switch the theme to the configured mail theme.
    $mail_theme = $this->getMailTheme();
    $current_active_theme = $this->themeManager->getActiveTheme();
    if ($mail_theme != $current_active_theme->getName()) {
      $this->themeManager->setActiveTheme($this->themeInitialization->initTheme($mail_theme));

      // The theme registry returns the same registry object no matter which
      // theme is currently active. This works around that by having a duplicate
      // service, that is only called when the mail theme is acive.
      // @todo: This will not work if this can not be called. Remove this once
      //   https://www.drupal.org/node/2640962 is committed.
      if ($this->themeManager instanceof ThemeManager) {
        $this->themeManager->setThemeRegistry($this->mailThemeRegistry);
      }
    }

    try {
      $message = parent::mail($module, $key, $to, $langcode, $params, $reply, $send);
    }
    finally {
      // Revert the active theme, this is done inside a finally block so it is
      // executed even if an exception is thrown during sending a mail.
      if ($mail_theme != $current_active_theme->getName()) {
        $this->themeManager->setActiveTheme($current_active_theme);
        if ($this->themeManager instanceof ThemeManager) {
          $this->themeManager->setThemeRegistry($this->defaultThemeRegistry);
        }
      }
    }
    return $message;
  }

  /**
   * {@inheritdoc}
   */
  public function getInstance(array $options) {
    $module = isset($options['module']) ? $options['module'] : 'default';
    $key = isset($options['key']) ? $options['key'] : '';

    return new Adapter(
      $this->getPluginInstance($module, $key, static::MAILSYSTEM_TYPE_FORMATTING),
      $this->getPluginInstance($module, $key, static::MAILSYSTEM_TYPE_SENDING)
    );
  }

  /**
   * Get a Mail-Plugin instance and return it.
   *
   * @param string $module
   *   Module name which is going to send and email.
   * @param string $key
   *   (optional) The ID if the email which is being sent.
   * @param string $type
   *   (optional) A subtype, like 'sending' or 'formatting'.
   *   Use \Drupal\mailsystem\MailsystemManager\MAILSYSTEM_TYPE_SENDING and
   *   \Drupal\mailsystem\MailsystemManager\MAILSYSTEM_TYPE_FORMATTING.
   *
   * @return \Drupal\Core\Mail\MailInterface
   *   A mail plugin instance.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  protected function getPluginInstance($module, $key = '', $type = '') {
    $plugin_id = NULL;

    // List of message ids which can be configured.
    $message_id_list = array(
      self::MAILSYSTEM_MODULES_CONFIG . '.' . $module . '.' . $key . '.' . $type,
      self::MAILSYSTEM_MODULES_CONFIG . '.' . $module . '.none.' . $type,
      self::MAILSYSTEM_MODULES_CONFIG . '.' . $module . '.' . $type,
      'defaults.' . $type,
      'defaults'
    );

    foreach($message_id_list as $message_id) {
      $plugin_id = $this->mailsystemConfig->get($message_id);
      if (!is_null($plugin_id)) {
        break;
      }
    }

    // If there is no instance cached, try to create one.
    if (empty($this->instances[$plugin_id])) {
      $plugin = $this->createInstance($plugin_id);
      if ($plugin instanceof MailInterface) {
        $this->instances[$plugin_id] = $plugin;
      }
      else {
        throw new InvalidPluginDefinitionException($plugin_id,
          SafeMarkup::format('Class %class does not implement interface %interface',
            array('%class' => get_class($plugin), '%interface' => 'Drupal\Core\Mail\MailInterface')
          )
        );
      }
    }
    return $this->instances[$plugin_id];
  }

  /**
   * Retrieves the key of the theme used to render the emails.
   */
  public function getMailTheme() {
    $theme = $this->mailsystemConfig->get('theme');
    switch ($theme) {
      case 'default':
        $theme = $this->configFactory->get('system.theme')->get('default');
        break;
      case 'current':
        $theme = $this->themeManager->getActiveTheme()->getName();
        break;
      case 'domain':
        // Fetch the theme for the current domain.
        // @todo: Reimplement this as soon as module port or similar module is around.
        if (FALSE && \Drupal::moduleHandler()->moduleExists('domain_theme')) {
          // Assign the selected theme, based on the active domain.
          global $_domain;
          $domain_theme = domain_theme_lookup($_domain['domain_id']);
          // The above returns -1 on failure.
          $theme = ($domain_theme != -1) ? $domain_theme['theme'] : $this->themeManager->getActiveTheme()->getName();
        }
        break;
    }
    return $theme;
  }

}
