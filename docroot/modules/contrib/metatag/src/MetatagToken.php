<?php

/**
 * @file
 * Contains the \Drupal\metatag\MetatagToken class.
 */

namespace Drupal\metatag;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Utility\Token;

/**
 * Token handling service. Uses core token service or contributed Token.
 */
class MetatagToken {

  /**
   * Module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Token service.
   *
   * @var \Drupal\Core\Utility\Token
   */
  protected $coreToken;

  /**
   * Constructs a new MetatagToken object.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   Module handler service.
   * @param \Drupal\Core\Utility\Token $token
   *   Token service.
   */
  public function __construct(ModuleHandlerInterface $module_handler, Token $token) {
    $this->coreToken = $token;
    $this->moduleHandler = $module_handler;
  }

  /**
   * Gatekeeper function to direct to either the core or contributed Token.
   *
   * @param $string
   * @param $data
   * @param array $settings
   * @return mixed|string $string
   */
  public function tokenReplace($string, $data, $settings) {
    if ($this->moduleHandler->moduleExists('token')) {
      return $this->contribReplace($string, $data, $settings);
    }
    else {
      return $this->coreReplace($string, $data, $settings);
    }
  }

  /**
   * Gatekeeper function to direct to either the core or contributed Token.
   *
   * @return array
   *   If token module is installed, a popup browser plus a help text. If not
   *   only the help text.
   */
  public function tokenBrowser() {
    $form = array();

    $form['intro_text'] = array(
      '#markup' => '<p>' . t('Configure the meta tags below. Use tokens to avoid redundant meta data and search engine penalization. For example, a \'keyword\' value of "example" will be shown on all content using this configuration, whereas using the [node:field_keywords] automatically inserts the "keywords" values from the current entity (node, term, etc).') . '</p>',
    );

    if ($this->moduleHandler->moduleExists('token')) {
      $form['tokens'] = array(
        '#theme' => 'token_tree_link',
        '#token_types' => 'all',
        '#global_types' => TRUE,
        '#click_insert' => TRUE,
        '#show_restricted' => FALSE,
        '#recursion_limit' => 3,
        '#text' => t('Browse available tokens'),
      );
    }

    return $form;
  }

  /**
   * Replace tokens with their values using the core token service.
   *
   * @param $string
   * @param $data
   * @param array $settings
   * @return mixed|string
   */
  private function coreReplace($string, $data, $settings = array()) {
    $settings += array('clear' => TRUE);

    // Replace tokens with core Token service.
    $replaced = $this->coreToken->replace($string, $data, $settings);

    // Ensure that there are no double-slash sequences due to empty token values.
    $replaced = preg_replace('/(?<!:)\/+\//', '/', $replaced);

    return $replaced;
  }

  /**
   * Replace tokens with their values using the contributed token module.
   *
   * @param $string
   * @param $data
   * @param array $settings
   * @return mixed|string
   */
  private function contribReplace($string, $data, $settings = array()) {
    // @TODO: Add contrib Token integration when it is ready.
    // For now, just redirect to the core replacement to avoid breaking sites
    // where Token is installed.
    return $this->coreReplace($string, $data, $settings);
  }

}
