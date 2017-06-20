<?php

namespace Drupal\rabbit_hole\Plugin;

use Drupal\Core\Entity\Entity;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Component\Plugin\PluginInspectionInterface;

/**
 * Defines an interface for Rabbit hole behavior plugin plugins.
 */
interface RabbitHoleBehaviorPluginInterface extends PluginInspectionInterface {

  const USES_RESPONSE_NEVER = 0;
  const USES_RESPONSE_SOMETIMES = 1;
  const USES_RESPONSE_ALWAYS = 2;

  /**
   * Perform the rabbit hole action.
   *
   * @param Entity $entity
   *   The entity the action is being performed on.
   */
  public function performAction(Entity $entity);

  /**
   * Return a settings form for the rabbit hole action.
   *
   * @param array &$form
   *   The form array to modify.
   * @param array &$form_state
   *   The form state array to modify.
   * @param string $form_id
   *   The form ID.
   * @param Entity|null $entity
   *   The entity used by the form.
   * @param bool $entity_is_bundle
   *   Whether the entity is a bundle.
   * @param ImmutableConfig|null $bundle_settings
   *   The behavior settings for the bundle of the entity (or the entity itself,
   *   if it is a bundle).
   */
  public function settingsForm(&$form, &$form_state, $form_id, Entity $entity = NULL,
    $entity_is_bundle = FALSE, ImmutableConfig $bundle_settings = NULL);

  /**
   * Handle submission of the settings form for this plugin.
   */
  public function settingsFormHandleSubmit(&$form, &$form_state);

  /**
   * Add to or adjust the fields added by rabbit hole.
   *
   * @param array $fields
   *   The array of fields to be altered.
   */
  public function alterExtraFields(array &$fields);

  /**
   * Get whether this plugin uses a response to perform its action.
   *
   * Override this to return one of USES_RESPONSE_NEVER,
   * USES_RESPONSE_SOMETIMES, or USES_RESPONSE_ALWAYS to indicate whether
   * performAction() should be invoked only when a null response is given,
   * regardless of whether there is a response (it'll figure out what to do with
   * or without on its own), or only when a non-null response is given. Defaults
   * to returning USES_RESPONSE_NEVER.
   */
  public function usesResponse();

}
