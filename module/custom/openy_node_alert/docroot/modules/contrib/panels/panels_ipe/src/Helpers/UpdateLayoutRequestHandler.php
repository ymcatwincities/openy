<?php

namespace Drupal\panels_ipe\Helpers;

use Drupal\panels\Plugin\DisplayVariant\PanelsDisplayVariant;

class UpdateLayoutRequestHandler extends RequestHandlerBase {

  /**
   * @inheritdoc
   */
  protected function handle(PanelsDisplayVariant $panels_display, $decodedRequest, $save_to_temp_store = FALSE) {
    $this->updateLayout($panels_display, $decodedRequest, $save_to_temp_store);
  }

  /**
   * Changes the layout for the given Panels Display.
   *
   * @param \Drupal\panels\Plugin\DisplayVariant\PanelsDisplayVariant $panels_display
   * @param $layout_model
   * @param bool $save_to_temp_store
   */
  private function updateLayout(PanelsDisplayVariant $panels_display, $layout_model, $save_to_temp_store = FALSE) {
    $panels_display = self::updatePanelsDisplay($panels_display, $layout_model);

    $this->invokeHook('panels_ipe_panels_display_presave', [
      $panels_display,
      $layout_model,
    ]);

    if ($save_to_temp_store) {
      $this->savePanelsDisplayToTempStore($panels_display);
    }
    else {
      $this->savePanelsDisplay($panels_display);
    }
  }

  /**
   * Updates the current Panels display based on the changes done in our app.
   *
   * @param \Drupal\panels\Plugin\DisplayVariant\PanelsDisplayVariant $panels_display
   *   The current Panels display.
   * @param array $layout_model
   *   The decoded LayoutModel from our App.
   *
   * @return \Drupal\panels\Plugin\DisplayVariant\PanelsDisplayVariant
   */
  private static function updatePanelsDisplay(PanelsDisplayVariant $panels_display, array $layout_model) {
    // Set our weight and region based on the metadata in our Backbone app.
    foreach ($layout_model['regionCollection'] as $region) {
      $weight = 0;
      foreach ($region['blockCollection'] as $block) {
        /** @var \Drupal\Core\Block\BlockBase $block_instance */
        $block_instance = $panels_display->getBlock($block['uuid']);

        $block_instance->setConfigurationValue('region', $region['name']);
        $block_instance->setConfigurationValue('weight', ++$weight);

        $panels_display->updateBlock($block['uuid'], $block_instance->getConfiguration());
      }
    }

    return $panels_display;
  }

}
