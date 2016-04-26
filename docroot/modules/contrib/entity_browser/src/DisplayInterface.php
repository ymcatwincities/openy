<?php

/**
 * @file
 * Contains \Drupal\entity_browser\DisplayInterface.
 */

namespace Drupal\entity_browser;

use Drupal\Component\Plugin\ConfigurablePluginInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Plugin\PluginFormInterface;

/**
 * Defines the interface for entity browser displays.
 */
interface DisplayInterface extends PluginInspectionInterface, ConfigurablePluginInterface, PluginFormInterface {

  /**
   * Returns the display label.
   *
   * @return string
   *   The display label.
   */
  public function label();

  /**
   * Displays entity browser.
   *
   * This is the "entry point" for every non-entity browser code to interact
   * with it. It will take care about displaying entity browser in one way or
   * another.
   *
   * @return array
   *   An array suitable for drupal_render().
   */
  public function displayEntityBrowser();

  /**
   * Indicates completed selection.
   *
   * Entity browser will call this function when selection is done. Display
   * plugin is responsible for fetching selected entities and sending them to
   * the initiating code.
   *
   * @param \Drupal\Core\Entity\EntityInterface[] $entities
   *
   */
  public function selectionCompleted(array $entities);

  /**
   * Gets the uuid for this display.
   *
   * @return string
   *   The uuid string.
   */
  public function getUuid();

  /**
   * Sets the uuid for this display.
   *
   * @param string $uuid
   *   The uuid string.
   */
  public function setUuid($uuid);

}
