<?php

/**
 * @file
 * Contains \Drupal\entity_browser\FieldWidgetDisplayBase.
 */

namespace Drupal\entity_browser;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginBase;

/**
 * Base implementation for field widget display plugins.
 */
abstract class FieldWidgetDisplayBase extends PluginBase implements FieldWidgetDisplayInterface {

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function isApplicable(EntityTypeInterface $entity_type) {
    return TRUE;
  }

}
