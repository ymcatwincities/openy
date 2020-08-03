<?php

namespace Drupal\logger_entity\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;

/**
 * Defines the Logger Entity type entity.
 *
 * @ConfigEntityType(
 *   id = "logger_entity_type",
 *   label = @Translation("Logger Entity type"),
 *   handlers = {
 *     "list_builder" = "Drupal\logger_entity\LoggerEntityTypeListBuilder",
 *     "form" = {
 *       "add" = "Drupal\logger_entity\Form\LoggerEntityTypeForm",
 *       "edit" = "Drupal\logger_entity\Form\LoggerEntityTypeForm",
 *       "delete" = "Drupal\logger_entity\Form\LoggerEntityTypeDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\logger_entity\LoggerEntityTypeHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "logger_entity_type",
 *   admin_permission = "administer site configuration",
 *   bundle_of = "logger_entity",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   config_export = {},
 *   links = {
 *     "canonical" = "/admin/config/logger_entity_type/{logger_entity_type}",
 *     "add-form" = "/admin/config/logger_entity_type/add",
 *     "edit-form" = "/admin/config/logger_entity_type/{logger_entity_type}/edit",
 *     "delete-form" = "/admin/config/logger_entity_type/{logger_entity_type}/delete",
 *     "collection" = "/admin/config/logger_entity_type"
 *   }
 * )
 */
class LoggerEntityType extends ConfigEntityBundleBase implements LoggerEntityTypeInterface {

  /**
   * The Logger Entity type ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Logger Entity type label.
   *
   * @var string
   */
  protected $label;

}
