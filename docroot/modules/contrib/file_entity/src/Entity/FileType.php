<?php

namespace Drupal\file_entity\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;
use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\file_entity\FileTypeInterface;

/**
 * Defines the File type configuration entity.
 *
 * @ConfigEntityType(
 *   id = "file_type",
 *   label = @Translation("File type"),
 *   handlers = {
 *     "list_builder" = "Drupal\file_entity\FileTypeListBuilder",
 *     "form" = {
 *       "default" = "Drupal\file_entity\Form\FileTypeForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm",
 *       "enable" = "Drupal\file_entity\Form\FileTypeEnableForm",
 *       "disable" = "Drupal\file_entity\Form\FileTypeDisableForm",
 *     },
 *   },
 *   admin_permission = "administer file types",
 *   config_prefix = "type",
 *   bundle_of = "file",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "status" = "status",
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "description",
 *     "mimetypes",
 *   },
 *   links = {
 *     "collection" = "/admin/structure/file-types",
 *     "edit-form" = "/admin/structure/file-types/manage/{file_type}/edit",
 *     "delete-form" = "/admin/structure/file-types/manage/{file_type}/delete",
 *     "enable" = "/admin/structure/file-types/manage/{file_type}/enable",
 *     "disable" = "/admin/structure/file-types/manage/{file_type}/disable",
 *   },
 * )
 */
class FileType extends ConfigEntityBundleBase implements FileTypeInterface {

  /**
   * The machine name of this file type.
   *
   * @var string
   */
  protected $id;

  /**
   * The human-readable name of the file type.
   *
   * @var string
   */
  protected $label;

  /**
   * A brief description of this file type.
   *
   * @var string
   */
  protected $description;

  protected $type;

  /**
   * MIME types associated with this file type.
   *
   * @var array
   */
  protected $mimetypes = array();

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->description;
  }

  /**
   * {@inheritdoc}
   */
  public function getMimeTypes() {
    return $this->mimetypes ?: array();
  }

  /**
   * {@inheritdoc}
   */
  public function setLabel($label) {
    $this->label = $label;
  }

  /**
   * {@inheritdoc}
   */
  public function setDescription($description) {
    $this->description = $description;
  }

  /**
   * {@inheritdoc}
   */
  public function setMimeTypes($mimetypes) {
    $this->mimetypes = array_values($mimetypes);
  }

  /**
   * {@inheritdoc}
   */
  public static function loadEnabled($status = TRUE) {
    $types = array();
    foreach (self::loadMultiple() as $id => $type) {
      if ($type->status == $status) {
        $types[$id] = $type;
      }
    }
    return $types;
  }

  /**
   * {@inheritdoc}
   */
  public static function sort(ConfigEntityInterface $a, ConfigEntityInterface $b) {
    // Sort primarily by status, secondarily by label.
    if ($a->status() == $b->status()) {
      return strnatcasecmp($a->label(), $b->label());
    }
    return ($b->status() - $a->status());
  }
}
