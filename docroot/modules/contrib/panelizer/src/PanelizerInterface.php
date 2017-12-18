<?php

namespace Drupal\panelizer;

use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\panels\Plugin\DisplayVariant\PanelsDisplayVariant;

/**
 * Interface for the Panelizer service.
 */
interface PanelizerInterface {

  /**
   * Gets the entity view display for the entity type, bundle and view mode.
   *
   * @param $entity_type_id
   *   The entity type id.
   * @param $bundle
   *   The bundle.
   * @param $view_mode
   *   The view mode.
   *
   * @return \Drupal\Core\Entity\Display\EntityViewDisplayInterface|NULL
   *   The entity view display if one exists; NULL otherwise.
   */
  public function getEntityViewDisplay($entity_type_id, $bundle, $view_mode);

  /**
   * Gets the Panels display for a given entity and view mode.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   * @param string $view_mode
   *   The entity view mode.
   * @param \Drupal\Core\Entity\Display\EntityViewDisplayInterface|NULL $display
   *   If the caller already has the correct display, it can optionally be
   *   passed in here so the Panelizer service doesn't have to look it up;
   *   otherwise, this argument can bo omitted.
   *
   * @return \Drupal\panels\Plugin\DisplayVariant\PanelsDisplayVariant|NULL
   *   The Panels display if panelized; NULL otherwise.
   */
  public function getPanelsDisplay(FieldableEntityInterface $entity, $view_mode, EntityViewDisplayInterface $display = NULL);

  /**
   * Sets the Panels display for a given entity and view mode.
   *
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   *   The entity.
   * @param string $view_mode
   *   The entity view mode.
   * @param string|NULL $default
   *   The name of the default if setting to a default; otherwise NULL.
   * @param \Drupal\panels\Plugin\DisplayVariant\PanelsDisplayVariant|NULL $panels_display
   *   The Panels display if this is an override; otherwise NULL.
   *
   * @throws \Drupal\panelizer\Exception\PanelizerException
   *   When custom overrides aren't enabled on this entity, bundle and view
   *   mode.
   */
  public function setPanelsDisplay(FieldableEntityInterface $entity, $view_mode, $default, PanelsDisplayVariant $panels_display = NULL);

  /**
   * Gets the default Panels displays for an entity type, bundle and view mode.
   *
   * @param string $entity_type_id
   *   The entity type id.
   * @param string $bundle
   *   The bundle.
   * @param string $view_mode
   *   The view mode.
   * @param \Drupal\Core\Entity\Display\EntityViewDisplayInterface|NULL $display
   *   If the caller already has the correct display, it can optionally be
   *   passed in here so the Panelizer service doesn't have to look it up;
   *   otherwise, this argument can bo omitted.
   *
   * @return \Drupal\panels\Plugin\DisplayVariant\PanelsDisplayVariant[]
   *   An associative array of Panels displays, keyed by the machine name of
   *   the default if panelized; NULL otherwise. All panelized view modes will
   *   have at least one named 'default'.
   */
  public function getDefaultPanelsDisplays($entity_type_id, $bundle, $view_mode, EntityViewDisplayInterface $display = NULL);

  /**
   * Gets one default Panels display for an entity type, bundle and view mode.
   *
   * @param string $name
   *   The name of the default.
   * @param string $entity_type_id
   *   The entity type id.
   * @param string $bundle
   *   The bundle.
   * @param string $view_mode
   *   The view mode.
   * @param \Drupal\Core\Entity\Display\EntityViewDisplayInterface|NULL $display
   *   If the caller already has the correct display, it can optionally be
   *   passed in here so the Panelizer service doesn't have to look it up;
   *   otherwise, this argument can bo omitted.
   *
   * @return \Drupal\panels\Plugin\DisplayVariant\PanelsDisplayVariant|NULL
   *   The default Panels display with the given name if it exists; otherwise
   *   NULL.
   */
  public function getDefaultPanelsDisplay($name, $entity_type_id, $bundle, $view_mode, EntityViewDisplayInterface $display = NULL);

  /**
   * @param $name
   *   The name of the default.
   * @param $entity_type_id
   *   The entity type id.
   * @param $bundle
   *   The bundle.
   * @param $view_mode
   *   The view mode.
   * @param \Drupal\panels\Plugin\DisplayVariant\PanelsDisplayVariant $panels_display
   *   The Panels display to use as the default.
   *
   * @throws \Drupal\panelizer\Exception\PanelizerException
   *   When a display can't be found for the given entity type, bundle and view
   *   mode.
   */
  public function setDefaultPanelsDisplay($name, $entity_type_id, $bundle, $view_mode, PanelsDisplayVariant $panels_display);

  public function getDisplayStaticContexts($name, $entity_type_id, $bundle, $view_mode, EntityViewDisplayInterface $display = NULL);

  public function setDisplayStaticContexts($name, $entity_type_id, $bundle, $view_mode, $contexts);



  /**
   * Checks if the given entity type, bundle and view mode are panelized.
   *
   * @param string $entity_type_id
   *   The entity type id.
   * @param string $bundle
   *   The bundle.
   * @param string $view_mode
   *   The view mode.
   * @param \Drupal\Core\Entity\Display\EntityViewDisplayInterface|NULL $display
   *   If the caller already has the correct display, it can optionally be
   *   passed in here so the Panelizer service doesn't have to look it up;
   *   otherwise, this argument can bo omitted.
   *
   * @return bool
   *   TRUE if panelized; otherwise FALSE.
   */
  public function isPanelized($entity_type_id, $bundle, $view_mode, EntityViewDisplayInterface $display = NULL);

  /**
   * Get the Panelizer settings for an entity type, bundle and view mode.
   *
   * @param string $entity_type_id
   *   The entity type id.
   * @param string $bundle
   *   The bundle.
   * @param string $view_mode
   *   The view mode.
   * @param \Drupal\Core\Entity\Display\EntityViewDisplayInterface|NULL $display
   *   If the caller already has the correct display, it can optionally be
   *   passed in here so the Panelizer service doesn't have to look it up;
   *   otherwise, this argument can bo omitted.
   *
   * @return array
   *   An associative array with the following keys:
   *   - enable (bool): Whether or not this view mode is panelized.
   *   - field (bool): Whether or not the field is present.
   */
  public function getPanelizerSettings($entity_type_id, $bundle, $view_mode, EntityViewDisplayInterface $display = NULL);

  /**
   * @param string $entity_type_id
   *   The entity type id.
   * @param string $bundle
   *   The bundle.
   * @param string $view_mode
   *   The view mode.
   * @param array $settings
   *   An associative array with the same keys as the associative array
   *   returned by getPanelizerSettings().
   * @param \Drupal\Core\Entity\Display\EntityViewDisplayInterface|NULL $display
   *   If the caller already has the correct display, it can optionally be
   *   passed in here so the Panelizer service doesn't have to look it up;
   *   otherwise, this argument can bo omitted.
   *
   * @see PanelizerInterface::getPanelizerSettings()
   */
  public function setPanelizerSettings($entity_type_id, $bundle, $view_mode, array $settings, EntityViewDisplayInterface $display = NULL);

  /**
   * Get permissions for all panelized entity types and bundles.
   *
   * @return array
   *   Associative array intended to be returned by hook_permission().
   *
   * @see hook_permission()
   */
  public function getPermissions();

  /**
   * Checks if a user has permission to perform an operation on an entity.
   *
   * @param string $op
   *   The operation. Possible values include:
   *   - "revert to default"
   *   - "change content"
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   * @param string $view_mode
   *   The view mode.
   * @param \Drupal\Core\Session\AccountInterface|NULL $account
   *   (optional) The user account to check; or the current user if omitted.
   *
   * @return bool
   *   TRUE if the user has permission; FALSE otherwise.
   */
  public function hasEntityPermission($op, EntityInterface $entity, $view_mode, AccountInterface $account = NULL);

  /**
   * Checks if a user has permission to perform an operation on a default.
   *
   * @param string $op
   *   The operation. Possible values include:
   *   - "change content"
   * @param string $entity_type_id
   *   The entity type id.
   * @param string $bundle
   *   The bundle.
   * @param string $view_mode
   *   The view mode.
   * @param string $default
   *   The name of the default.
   * @param \Drupal\Core\Session\AccountInterface|NULL $account
   *   (optional) The user account to check; or the current user if omitted.
   *
   * @return bool
   *   TRUE if the user has permission; FALSE otherwise.
   */
  public function hasDefaultPermission($op, $entity_type_id, $bundle, $view_mode, $default, AccountInterface $account = NULL);

}
