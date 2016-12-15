<?php

namespace Drupal\panels\Plugin\DisplayBuilder;

use Drupal\Core\Plugin\PluginBase;
use Drupal\panels\Plugin\DisplayVariant\PanelsDisplayVariant;

/**
 * Provides base class for Display Builder plugins.
 */
abstract class DisplayBuilderBase extends PluginBase implements DisplayBuilderInterface {

  /**
   * {@inheritdoc}
   */
  public function build(PanelsDisplayVariant $panels_display) {
    $regions = $panels_display->getRegionAssignments();
    return $regions;
  }

}
