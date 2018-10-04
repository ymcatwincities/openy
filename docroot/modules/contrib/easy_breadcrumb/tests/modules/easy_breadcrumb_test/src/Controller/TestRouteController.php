<?php

namespace Drupal\easy_breadcrumb_test\Controller;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Controller\ControllerBase;

/**
 * Provides block routines for search server-specific routes.
 */
class TestRouteController extends ControllerBase {

  /**
   * Displays page for testing purposes.
   *
   * @return array
   *   An array suitable for drupal_render().
   */
  public function page() {
    return [
      '#markup' => 'Test Page',
    ];
  }

  /**
   * Returns the page title as FormattableMarkup.
   *
   * Among other places, used in Drupal\search_api\Controller\IndexController.php
   *
   * @return \Drupal\Component\Render\FormattableMarkup
   *   The page title.
   */
  public function pageTitleFormattableMarkup() {
    return new FormattableMarkup('Type: @type', ['@type' => FormattableMarkup::class]);
  }

  /**
   * Returns the page title as TranslatableMarkup.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   The page title.
   */
  public function pageTitleTranslatableMarkup() {
    return $this->t('TranslatableMarkup');
  }

  /**
   * Returns the page title as FormattableMarkup.
   *
   * @return array
   *   The page title.
   */
  public function pageTitleRender() {
    return [
      '#markup' => 'this is a string',
    ];
  }


}
