<?php

namespace Drupal\embedded_groupexpro_schedule\Element;

use Drupal\Core\Render\Element\RenderElement;

/**
 * Class EmbeddedGroupExProSchedule.
 *
 * @RenderElement("embedded_groupexpro_schedule")
 */
class EmbeddedGroupExProSchedule extends RenderElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    return [
      '#theme' => 'embedded_groupexpro_schedule',
    ];
  }

}
