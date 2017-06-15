<?php

namespace Drupal\rabbit_hole;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Session\AccountProxy;
use Drupal\rabbit_hole\Plugin\RabbitHoleBehaviorPluginManager;
use Drupal\rabbit_hole\Plugin\RabbitHoleBehaviorPluginInterface;
use Drupal\rabbit_hole\Plugin\RabbitHoleEntityPluginManager;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class BehaviorInvoker.
 *
 * @package Drupal\rabbit_hole
 */
class BehaviorInvoker implements BehaviorInvokerInterface {

  /**
   * Drupal\rabbit_hole\BehaviorSettingsManager definition.
   *
   * @var Drupal\rabbit_hole\BehaviorSettingsManager
   */
  protected $rhBehaviorSettingsManager;

  /**
   * Drupal\rabbit_hole\Plugin\RabbitHoleBehaviorPluginManager definition.
   *
   * @var Drupal\rabbit_hole\Plugin\RabbitHoleBehaviorPluginManager
   */
  protected $rhBehaviorPluginManager;

  /**
   * Drupal\rabbit_hole\Plugin\RabbitHoleBehaviorPluginManager definition.
   *
   * @var Drupal\rabbit_hole\Plugin\RabbitHoleEntityPluginManager
   */
  protected $rhEntityPluginManager;

  /**
   * Drupal\rabbit_hole\EntityExtender definition.
   */
  protected $rhEntityExtender;

  /**
   * The current user.
   *
   * @var Drupal\Core\Session\AccountProxy
   */
  protected $currentUser;

  /**
   * Constructor.
   */
  public function __construct(
    BehaviorSettingsManager $rabbit_hole_behavior_settings_manager,
    RabbitHoleBehaviorPluginManager $plugin_manager_rabbit_hole_behavior_plugin,
    RabbitHoleEntityPluginManager $plugin_manager_rabbit_hole_entity_plugin,
    EntityExtender $entity_extender,
    AccountProxy $current_user
  ) {
    $this->rhBehaviorSettingsManager = $rabbit_hole_behavior_settings_manager;
    $this->rhBehaviorPluginManager = $plugin_manager_rabbit_hole_behavior_plugin;
    $this->rhEntityPluginManager = $plugin_manager_rabbit_hole_entity_plugin;
    $this->rhEntityExtender = $entity_extender;
    $this->currentUser = $current_user;
  }

  /**
   * Invoke a rabbit hole behavior based on an entity's configuration.
   *
   * This assumes the entity is configured for use with Rabbit Hole - if you
   * pass an entity to this method and it does not have a rabbit hole plugin it
   * will use the defaults!
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *    The entity to apply rabbit hole behavior on.
   * @param Symfony\Component\HttpFoundation\Response $current_response
   *    The current response, to be passed along to and potentially altered by
   *    any called rabbit hole plugin.
   *
   * @return Symfony\Component\HttpFoundation\Response|null
   *    A response or null if the response is unchanged.
   *
   * @throws Symfony\Component\HttpKernel\Exception\NotFoundHttpException
   *   The PageNotFound plugin may throw a NotFoundHttpException which is not
   *   handled by this method. This usually shouldn't be caught as it is
   *   intended behavior.
   * @throws Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
   *   The PageNotFound plugin may throw a NotFoundHttpException which is not
   *   handled by this method. This usually shouldn't be caught as it is
   *   intended behavior.
   */
  public function processEntity(ContentEntityInterface $entity, Response $current_response = NULL) {
    $permission = 'rabbit hole bypass ' . $entity->getEntityTypeId();
    if ($this->currentUser->hasPermission($permission)) {
      return NULL;
    }

    $values = $this->getRabbitHoleValuesForEntity($entity);
    $plugin = $this->rhBehaviorPluginManager
      ->createInstance($values['rh_action'], $values);

    $resp_use = $plugin->usesResponse();
    $response_required = $resp_use == RabbitHoleBehaviorPluginInterface::USES_RESPONSE_ALWAYS;
    $response_allowed = $resp_use == $response_required
      || $resp_use == RabbitHoleBehaviorPluginInterface::USES_RESPONSE_SOMETIMES;

    // Most plugins never make use of the response and only run when it's not
    // provided (i.e. on a request event).
    if ((!$response_allowed && $current_response == NULL)
      // Some plugins may or may not make use of the response so they'll run in
      // both cases and work out the logic of when to return NULL internally.
      || $response_allowed
      // Though none exist at the time of this writing, some plugins could
      // require a response so that case is handled.
      || $response_required && $current_response != NULL) {

      return $plugin->performAction($entity, $current_response);
    }
    // All other cases return NULL, meaning the response is unchanged.
    else {
      return NULL;
    }
  }

  /**
   * Load a list of entity IDs supported by rabbit hole given available plugins.
   *
   * @return array
   *   An array of string entity ids.
   */
  public function getPossibleEntityTypeKeys() {
    $entity_type_keys = array();
    foreach ($this->rhEntityPluginManager->getDefinitions() as $def) {
      $entity_type_keys[] = $def['entityType'];
    }
    return $entity_type_keys;
  }

  /**
   * An entity's rabbit hole configuration, or the default if it does not exist.
   *
   * Return an entity's rabbit hole configuration or, failing that, the default
   * configuration for the bundle (which itself will call the base default
   * configuration if necessary).
   *
   * @return array
   *   An array of values from the entity's fields matching the base properties
   *   added by rabbit hole.
   */
  private function getRabbitHoleValuesForEntity(ContentEntityBase $entity) {
    $field_keys = array_keys($this->rhEntityExtender->getGeneralExtraFields());
    $values = array();

    $config = $this->rhBehaviorSettingsManager->loadBehaviorSettingsAsConfig(
      $entity->getEntityType()->getBundleEntityType()
        ?: $entity->getEntityType()->id(),
      $entity->getEntityType()->getBundleEntityType()
        ? $entity->bundle()
        : NULL
    );

    // We trigger the default bundle action under the following circumstances:
    $trigger_default_bundle_action =
    // Bundle settings do not allow override
      !$config->get('allow_override')
    // Entity does not have rh_action field.
      || !$entity->hasField('rh_action')
    // Entity has rh_action field but it's null (hasn't been set).
      || $entity->get('rh_action')->value == NULL
    // Entity has been explicitly set to use the default bundle action.
      || $entity->get('rh_action')->value == 'bundle_default';

    if ($trigger_default_bundle_action) {
      foreach ($field_keys as $field_key) {
        $config_field_key = substr($field_key, 3);
        $values[$field_key] = $config->get($config_field_key);
      }
    }
    else {
      foreach ($field_keys as $field_key) {
        if ($entity->hasField($field_key)) {
          $values[$field_key] = $entity->{$field_key}->value;
        }
      }
    }
    return $values;
  }

}
