<?php

namespace Drupal\rabbit_hole\Plugin;

use Drupal\Component\Plugin\PluginInspectionInterface;

/**
 * Defines an interface for Rabbit hole entity plugin plugins.
 */
interface RabbitHoleEntityPluginInterface extends PluginInspectionInterface {

  /**
   * Return locations to attach submit handlers to entities.
   *
   * This should return an array of arrays, e.g.:
   * array(
   *   array('actions', 'submit', '#publish'),
   *   array('actions', 'publish', '#submit'),
   * ).
   */
  public function getFormSubmitHandlerAttachLocations();

  /**
   * Return locations to attach submit handlers to entity bundles.
   *
   * This should return an array of arrays, e.g.:
   * array(
   *   array('actions', 'submit', '#publish'),
   *   array('actions', 'publish', '#submit'),
   * ).
   *
   * @return array
   *   A multidimensional array.
   */
  public function getBundleFormSubmitHandlerAttachLocations();

  /**
   * Return the form ID of the config form for this plugin's entity.
   *
   * Return the form ID of the global config form for the entity targeted by
   * this plugin.
   *
   * @return string
   *   The form ID of the global config form.
   */
  public function getGlobalConfigFormId();

  /**
   * Return locations to attach submit handlers to the global config form.
   *
   * This should return an array of arrays, e.g.:
   * array(
   *   array('actions', 'submit', '#publish'),
   *   array('actions', 'publish', '#submit'),
   * ).
   */
  public function getGlobalFormSubmitHandlerAttachLocations();

  /**
   * Return a map of entity IDs used by this plugin to token IDs.
   *
   * @return array
   *   A map of token IDs to entity IDs in the form
   *   array('entity ID' => 'token ID')
   */
  public function getEntityTokenMap();
}
