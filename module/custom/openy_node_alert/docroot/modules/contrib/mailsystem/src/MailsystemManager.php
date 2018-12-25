<?php

/**
 * @file
 * Contains \Drupal\mailsystem\MailsystemManager.
 */

namespace Drupal\mailsystem;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Utility\SafeMarkup;
use Drupal\Core\Mail\MailInterface;
use Drupal\Core\Mail\MailManager;
use Drupal\Core\Theme\ThemeInitializationInterface;
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
   * Sets the theme manager for mailsystem.
   *
   * @param \Drupal\Core\Theme\ThemeManagerInterface $theme_manager
   *   The theme manager.
   */
  public function setThemeManager(ThemeManagerInterface $theme_manager) {
    $this->themeManager = $theme_manager;
  }

  /**
   * Sets the theme initialization for mailsystem.
   *
   * @param \Drupal\Core\Theme\ThemeInitializationInterface $theme_initialization
   *   The theme initialization.
   */
  public function setThemeInitialization(ThemeInitializationInterface $theme_initialization) {
    $this->themeInitialization = $theme_initialization;
  }

  /**
   * {@inheritdoc}
   */
  public function mail($module, $key, $to, $langcode, $params = array(), $reply = NULL, $send = TRUE) {
    // Switch the theme to the configured mail theme.
    $mail_theme = $this->getMailTheme();
    $current_active_theme = $this->themeManager->getActiveTheme();
    if ($mail_theme && $mail_theme != $current_active_theme->getName()) {
      $this->themeManager->setActiveTheme($this->themeInitialization->initTheme($mail_theme));
    }

    try {
      $message = parent::mail($module, $key, $to, $langcode, $params, $reply, $send);
    }
    finally {
      // Revert the active theme, this is done inside a finally block so it is
      // executed even if an exception is thrown during sending a mail.
      if ($mail_theme != $current_active_theme->getName()) {
        $this->themeManager->setActiveTheme($current_active_theme);
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

    $config = $this->configFactory->get('mailsystem.settings');

    foreach($message_id_list as $message_id) {
      $plugin_id = $config->get($message_id);
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
    $theme = $this->configFactory->get('mailsystem.settings')->get('theme');
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
