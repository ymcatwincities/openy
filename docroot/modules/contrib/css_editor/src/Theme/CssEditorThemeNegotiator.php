<?php

namespace Drupal\css_editor\Theme;

use Drupal\Core\Theme\ThemeNegotiatorInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Url;

class CssEditorThemeNegotiator implements ThemeNegotiatorInterface {

  /**
   * {@inheritdoc}
   */
  public function applies(RouteMatchInterface $route_match) {
    return isset($_REQUEST['theme']) && isset($_SERVER['HTTP_REFERER']) &&
      $_SERVER['HTTP_REFERER'] == Url::fromUri('internal:/admin/appearance/settings/' . $_REQUEST['theme'], array('absolute' => TRUE))->toString();
  }

  /**
   * {@inheritdoc}
   */
  public function determineActiveTheme(RouteMatchInterface $route_match) {
    return $_REQUEST['theme'];
  }

}
