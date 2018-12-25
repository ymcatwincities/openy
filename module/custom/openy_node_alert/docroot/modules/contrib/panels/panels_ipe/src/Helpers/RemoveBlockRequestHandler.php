<?php

namespace Drupal\panels_ipe\Helpers;

use Drupal\panels\Plugin\DisplayVariant\PanelsDisplayVariant;

class RemoveBlockRequestHandler extends RequestHandlerBase {

  /**
   * @inheritdoc
   */
  protected function handle(PanelsDisplayVariant $panels_display, $decoded_request, $save_to_temp_store = FALSE) {
    $panels_display->removeBlock($decoded_request);

    if ($save_to_temp_store) {
      $this->savePanelsDisplayToTempStore($panels_display);
    }
    else {
      $this->savePanelsDisplay($panels_display);
    }
  }

}
