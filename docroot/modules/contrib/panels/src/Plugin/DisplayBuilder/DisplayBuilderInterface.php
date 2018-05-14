<?php

namespace Drupal\panels\Plugin\DisplayBuilder;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\panels\Plugin\DisplayVariant\PanelsDisplayVariant;

/**
 * Defines the DisplayBuilder plugin type.
 */
interface DisplayBuilderInterface extends PluginInspectionInterface {

  /**
   * Renders a Panels display.
   *
   * This is the outermost method in the Panels render pipeline. It calls the
   * inner methods, which return a content array, which is in turn passed to the
   * theme function specified in the layout plugin.
   *
   * @param Drupal\panels\Plugin\DisplayVariant\PanelsDisplayVariant
   *   The Panels display variant to render.
   *
   * @return array
   *   Render array modified by the display builder.
   */
  public function build(PanelsDisplayVariant $panels_display);

}
