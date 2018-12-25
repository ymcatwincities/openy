<?php

namespace Drupal\rabbit_hole\Plugin;

use Drupal\Core\Entity\Entity;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Component\Plugin\PluginBase;

/**
 * Base class for Rabbit hole behavior plugin plugins.
 */
abstract class RabbitHoleBehaviorPluginBase extends PluginBase implements RabbitHoleBehaviorPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function performAction(Entity $entity) {
    // Perform no action.
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(&$form, &$form_state, $form_id, Entity $entity = NULL,
    $entity_is_bundle = FALSE, ImmutableConfig $bundle_settings = NULL) {
    // Present no settings form.
  }

  /**
   * {@inheritdoc}
   */
  public function settingsFormHandleSubmit(&$form, &$form_state) {
    // No extra action to handle submission by default.
  }

  /**
   * {@inheritdoc}
   */
  public function alterExtraFields(array &$fields) {
    // Don't change the fields by default.
  }

  /**
   * {@inheritdoc}
   */
  public function usesResponse() {
    return RabbitHoleBehaviorPluginInterface::USES_RESPONSE_NEVER;
  }

}
