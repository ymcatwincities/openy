<?php

/**
 * @file
 * Contains \Drupal\embed\Entity\EmbedButton.
 */

namespace Drupal\embed\Entity;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\editor\EditorInterface;
use Drupal\embed\EmbedButtonInterface;

/**
 * Defines the EmbedButton entity.
 *
 * @ConfigEntityType(
 *   id = "embed_button",
 *   label = @Translation("Embed button"),
 *   handlers = {
 *     "form" = {
 *       "add" = "Drupal\embed\Form\EmbedButtonForm",
 *       "edit" = "Drupal\embed\Form\EmbedButtonForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm"
 *     },
 *     "list_builder" = "Drupal\embed\EmbedButtonListBuilder",
 *   },
 *   admin_permission = "administer embed buttons",
 *   config_prefix = "button",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *   },
 *   links = {
 *     "edit-form" = "/admin/config/content/embed/button/manage/{embed_button}",
 *     "delete-form" = "/admin/config/content/embed/button/manage/{embed_button}/delete",
 *     "collection" = "/admin/config/content/embed",
 *   },
 *   config_export = {
 *     "label",
 *     "id",
 *     "type_id",
 *     "type_settings",
 *     "icon_uuid",
 *   }
 * )
 */
class EmbedButton extends ConfigEntityBase implements EmbedButtonInterface {

  use StringTranslationTrait;

  /**
   * The EmbedButton ID.
   *
   * @var string
   */
  public $id;

  /**
   * Label of EmbedButton.
   *
   * @var string
   */
  public $label;

  /**
   * The embed type plugin ID.
   *
   * @var string
   */
  public $type_id;

  /**
   * Embed type settings.
   *
   * An array of key/value pairs.
   *
   * @var array
   */
  public $type_settings = [];

  /**
   * UUID of the button's icon file.
   *
   * @var string
   */
  public $icon_uuid;

  /**
   * {@inheritdoc}
   */
  public function getTypeId() {
    return $this->type_id;
  }

  /**
   * {@inheritdoc}
   */
  public function getTypeLabel() {
    if ($definition = $this->embedTypeManager()->getDefinition($this->getTypeId(), FALSE)) {
      return $definition['label'];
    }
    else {
      return $this->t('Unknown');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getTypePlugin() {
    if ($plugin_id = $this->getTypeId()) {
      return $this->embedTypeManager()->createInstance($plugin_id, $this->getTypeSettings());
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getIconFile() {
    if ($this->icon_uuid) {
      return $this->entityManager()->loadEntityByUuid('file', $this->icon_uuid);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getIconUrl() {
    if ($image = $this->getIconFile()) {
      return file_create_url($image->getFileUri());
    }
    else {
      return $this->getTypePlugin()->getDefaultIconUrl();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    parent::calculateDependencies();

    // Add the file icon entity as dependency if an UUID was specified.
    if ($this->icon_uuid && $file_icon = $this->entityManager()->loadEntityByUuid('file', $this->icon_uuid)) {
      $this->addDependency($file_icon->getConfigDependencyKey(), $file_icon->getConfigDependencyName());
    }

    // Gather the dependencies of the embed type plugin.
    $plugin = $this->getTypePlugin();
    $this->calculatePluginDependencies($plugin);

    return $this->dependencies;
  }

  /**
   * Gets the embed type plugin manager.
   *
   * @return \Drupal\embed\EmbedType\EmbedTypeManager
   */
  protected function embedTypeManager() {
    return \Drupal::service('plugin.manager.embed.type');
  }

  /**
   * Gets the file usage service.
   *
   * @return \Drupal\file\FileUsage\FileUsageInterface
   */
  protected function fileUsage() {
    return \Drupal::service('file.usage');
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageInterface $storage, $update = TRUE) {
    parent::postSave($storage, $update);

    $icon_file = $this->getIconFile();
    if (isset($this->original) && $old_icon_file = $this->original->getIconFile()) {
      /** @var \Drupal\file\FileInterface $old_icon_file */
      if (!$icon_file || $icon_file->uuid() != $old_icon_file->uuid()) {
        $this->fileUsage()->delete($old_icon_file, 'embed', $this->getEntityTypeId(), $this->id());
      }
    }

    if ($icon_file) {
      $usage = $this->fileUsage()->listUsage($icon_file);
      if (empty($usage['embed'][$this->getEntityTypeId()][$this->id()])) {
        $this->fileUsage()->add($icon_file, 'embed', $this->getEntityTypeId(), $this->id());
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function postDelete(EntityStorageInterface $storage, array $entities) {
    parent::postDelete($storage, $entities);

    // Remove file usage for any button icons.
    foreach ($entities as $entity) {
      /** @var \Drupal\embed\EmbedButtonInterface $entity */
      if ($icon_file = $entity->getIconFile()) {
        \Drupal::service('file.usage')->delete($icon_file, 'embed', $entity->getEntityTypeId(), $entity->id());
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getTypeSetting($key, $default = NULL) {
    if (isset($this->type_settings[$key])) {
      return $this->type_settings[$key];
    }
    else {
      return $default;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getTypeSettings() {
    return $this->type_settings;
  }

}
