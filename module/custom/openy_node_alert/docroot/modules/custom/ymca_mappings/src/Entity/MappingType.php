<?php

namespace Drupal\ymca_mappings\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;
use Drupal\ymca_mappings\MappingTypeInterface;

/**
 * Defines the Mapping type entity.
 *
 * @ConfigEntityType(
 *   id = "mapping_type",
 *   label = @Translation("Mapping type"),
 *   handlers = {
 *     "list_builder" = "Drupal\ymca_mappings\MappingTypeListBuilder",
 *     "form" = {
 *       "add" = "Drupal\ymca_mappings\Form\MappingTypeForm",
 *       "edit" = "Drupal\ymca_mappings\Form\MappingTypeForm",
 *       "delete" = "Drupal\ymca_mappings\Form\MappingTypeDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\ymca_mappings\MappingTypeHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "mapping_type",
 *   admin_permission = "administer site configuration",
 *   bundle_of = "mapping",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/mappings/mapping_type/{mapping_type}",
 *     "add-form" = "/admin/structure/mappings/mapping_type/add",
 *     "edit-form" = "/admin/structure/mappings/mapping_type/{mapping_type}/edit",
 *     "delete-form" = "/admin/structure/mappings/mapping_type/{mapping_type}/delete",
 *     "collection" = "/admin/structure/mappings/mapping_type"
 *   }
 * )
 */
class MappingType extends ConfigEntityBundleBase implements MappingTypeInterface {

  /**
   * The Mapping type ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Mapping type label.
   *
   * @var string
   */
  protected $label;

}
