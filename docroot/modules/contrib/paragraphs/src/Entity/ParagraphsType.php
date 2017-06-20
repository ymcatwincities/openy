<?php

namespace Drupal\paragraphs\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;
use Drupal\paragraphs\ParagraphsTypeInterface;

/**
 * Defines the ParagraphsType entity.
 *
 * @ConfigEntityType(
 *   id = "paragraphs_type",
 *   label = @Translation("Paragraphs type"),
 *   handlers = {
 *     "list_builder" = "Drupal\paragraphs\Controller\ParagraphsTypeListBuilder",
 *     "form" = {
 *       "add" = "Drupal\paragraphs\Form\ParagraphsTypeForm",
 *       "edit" = "Drupal\paragraphs\Form\ParagraphsTypeForm",
 *       "delete" = "Drupal\paragraphs\Form\ParagraphsTypeDeleteConfirm"
 *     }
 *   },
 *   config_prefix = "paragraphs_type",
 *   admin_permission = "administer paragraphs types",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *   },
 *   bundle_of = "paragraph",
 *   links = {
 *     "edit-form" = "/admin/structure/paragraphs_type/{paragraphs_type}",
 *     "delete-form" = "/admin/structure/paragraphs_type/{paragraphs_type}/delete",
 *     "collection" = "/admin/structure/paragraphs_type",
 *   }
 * )
 */
class ParagraphsType extends ConfigEntityBundleBase implements ParagraphsTypeInterface
{

  /**
   * The ParagraphsType ID.
   *
   * @var string
   */
  public $id;

  /**
   * The ParagraphsType label.
   *
   * @var string
   */
  public $label;

}
