<?php

namespace Drupal\openy_mappings\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;
use Drupal\openy_mappings\MappingTypeInterface;

/**
 * Defines the Mapping type entity.
 *
 * @ConfigEntityType(
 *   id = "mapping_type",
 *   label = @Translation("Mapping type"),
 *   handlers = {
 *     "list_builder" = "Drupal\openy_mappings\MappingTypeListBuilder",
 *     "form" = {
 *       "add" = "Drupal\openy_mappings\Form\MappingTypeForm",
 *       "edit" = "Drupal\openy_mappings\Form\MappingTypeForm",
 *       "delete" = "Drupal\openy_mappings\Form\MappingTypeDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\openy_mappings\MappingTypeHtmlRouteProvider",
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
 *   config_export = {},
 *   links = {
 *     "canonical" = "/admin/openy/settings/mappings/mapping_type/{mapping_type}",
 *     "add-form" = "/admin/openy/settings/mappings/mapping_type/add",
 *     "edit-form" = "/admin/openy/settings/mappings/mapping_type/{mapping_type}/edit",
 *     "delete-form" = "/admin/openy/settings/mappings/mapping_type/{mapping_type}/delete",
 *     "collection" = "/admin/openy/settings/mappings/mapping_type"
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
