<?php

/**
 * @file
 * Provides Drupal\webform\ComponentInterface
 */

namespace Drupal\webform;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\Node;

/**
 * Defines an interface for webform component plugins.
 */
interface ComponentInterface extends PluginInspectionInterface {
  /**
   * Return the label of the component.
   *
   * @return string
   */
  public function getLabel();

  /**
   * Return the description of the component.
   *
   * @return string
   */
  public function getDescription();

  public function buildForm(array $form, FormStateInterface $form_state, Node $node = NULL);

  /**
   *
   */
  public function setConfiguration(array $configuration);

  /**
   *
   */
  public function defaultConfiguration();

}
