<?php

namespace Drupal\openy_block_expander\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Theme\ThemeNegotiatorInterface;

/**
 * An example controller.
 */
class DemoController extends ControllerBase implements ThemeNegotiatorInterface {

  /**
   * {@inheritdoc}
   */
  public function content() {
    // -> form
    // @todo Create class with the form in Form namespace.
    // @todo implement logic for setting different theme depending on selection.

    // -> here
    // @todo use form_builder service to render the form here.
    $block = \Drupal\block_content\Entity\BlockContent::load(61);
    $render = \Drupal::entityTypeManager()->getViewBuilder('block_content')->view($block);

    return $render;
  }

  public function applies(RouteMatchInterface $route_match) {
    // config.
    return TRUE;
  }

  public function determineActiveTheme(RouteMatchInterface $route_match) {
    // select from config
    return 'openy_rose';
  }

}
