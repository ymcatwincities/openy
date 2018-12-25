<?php

namespace Drupal\panels;

use Drupal\panels\Plugin\DisplayVariant\PanelsDisplayVariant;

/**
 * Interface for a service that manages Panels displays.
 */
interface PanelsDisplayManagerInterface {

  /**
   * Create a new panels display.
   *
   * @param string|\Drupal\Core\Layout\LayoutInterface|NULL $layout
   *   The layout plugin object or plugin id. If omitted, the default Panels
   *   layout will be used.
   * @param string|\Drupal\panels\Plugin\DisplayBuilder\DisplayBuilderInterface|NULL $builder
   *   The builder object or plugin id. If omitted, the default Panels builder
   *   will be used.
   *
   * @return \Drupal\panels\Plugin\DisplayVariant\PanelsDisplayVariant
   *
   * @throws \Exception
   *   If $layout or $builder are of an invalid type.
   */
  public function createDisplay($layout = NULL, $builder = NULL);

  /**
   * Creates a panels display from exported configuration.
   *
   * @param array $config
   *   The configuration exported from display variant.
   * @param bool $validate
   *   Whether or not to validate against the configuration again the schema.
   *
   * @return \Drupal\panels\Plugin\DisplayVariant\PanelsDisplayVariant
   */
  public function importDisplay(array $config, $validate = TRUE);

  /**
   * Export configuration from a panels display.
   *
   * @param \Drupal\panels\Plugin\DisplayVariant\PanelsDisplayVariant $display
   *   The panels display.
   *
   * @return array
   *   Configuration exported from the display.
   */
  public function exportDisplay(PanelsDisplayVariant $display);

}
