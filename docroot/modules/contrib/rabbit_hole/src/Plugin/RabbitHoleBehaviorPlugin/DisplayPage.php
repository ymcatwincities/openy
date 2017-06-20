<?php

namespace Drupal\rabbit_hole\Plugin\RabbitHoleBehaviorPlugin;

use Drupal\rabbit_hole\Plugin\RabbitHoleBehaviorPluginBase;

/**
 * Does nothing (displays a page).
 *
 * @RabbitHoleBehaviorPlugin(
 *   id = "display_page",
 *   label = @Translation("Display the page")
 * )
 */
class DisplayPage extends RabbitHoleBehaviorPluginBase {

  // Empty class: just does RabbitHoleBehaviorBase's defaults, which is nothing.
}
