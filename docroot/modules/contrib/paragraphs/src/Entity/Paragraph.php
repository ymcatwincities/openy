<?php

namespace Drupal\paragraphs\Entity;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\TypedData\TranslatableInterface;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\entity_reference_revisions\EntityNeedsSaveInterface;
use Drupal\entity_reference_revisions\EntityNeedsSaveTrait;
use Drupal\paragraphs\ParagraphInterface;
use Drupal\user\UserInterface;

/**
 * Defines the Paragraph entity.
 *
 * @ingroup paragraphs
 *
 * @ContentEntityType(
 *   id = "paragraph",
 *   label = @Translation("Paragraph"),
 *   bundle_label = @Translation("Paragraph type"),
 *   handlers = {
 *     "view_builder" = "Drupal\paragraphs\ParagraphViewBuilder",
 *     "access" = "Drupal\paragraphs\ParagraphAccessControlHandler",
 *     "storage_schema" = "Drupal\paragraphs\ParagraphStorageSchema",
 *     "form" = {
 *       "default" = "Drupal\Core\Entity\ContentEntityForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *       "edit" = "Drupal\Core\Entity\ContentEntityForm"
 *     },
 *     "views_data" = "Drupal\views\EntityViewsData",
 *   },
 *   base_table = "paragraphs_item",
 *   data_table = "paragraphs_item_field_data",
 *   revision_table = "paragraphs_item_revision",
 *   revision_data_table = "paragraphs_item_revision_field_data",
 *   translatable = TRUE,
 *   entity_revision_parent_type_field = "parent_type",
 *   entity_revision_parent_id_field = "parent_id",
 *   entity_revision_parent_field_name_field = "parent_field_name",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "bundle" = "type",
 *     "langcode" = "langcode",
 *     "revision" = "revision_id"
 *   },
 *   bundle_entity_type = "paragraphs_type",
 *   field_ui_base_route = "entity.paragraphs_type.edit_form",
 *   common_reference_revisions_target = TRUE,
 *   content_translation_ui_skip = TRUE,
 *   render_cache = FALSE,
 *   default_reference_revision_settings = {
 *     "field_storage_config" = {
 *       "cardinality" = -1,
 *       "settings" = {
 *         "target_type" = "paragraph"
 *       }
 *     },
 *     "field_config" = {
 *       "settings" = {
 *         "handler" = "default:paragraph"
 *       }
 *     },
 *     "entity_form_display" = {
 *       "type" = "entity_reference_paragraphs"
 *     },
 *     "entity_view_display" = {
 *       "type" = "entity_reference_revisions_entity_view"
 *     }
 *   }
 * )
 */
class Paragraph extends ContentEntityBase implements ParagraphInterface, EntityNeedsSaveInterface {

  use EntityNeedsSaveTrait;

  /**
   * The behavior plugin data for the paragraph entity.
   */
  protected $unserializedBehaviorSettings;

  /**
   * {@inheritdoc}
   */
  public function getParentEntity() {
    if (!isset($this->get('parent_type')->value) || !isset($this->get('parent_id')->value)) {
      return NULL;
    }

    $parent = \Drupal::entityTypeManager()->getStorage($this->get('parent_type')->value)->load($this->get('parent_id')->value);

    // Return current translation of parent entity, if it exists.
    if ($parent != NULL && ($parent instanceof TranslatableInterface) && $parent->hasTranslation($this->language()->getId())) {
      return $parent->getTranslation($this->language()->getId());
    }

    return $parent;
  }

  /**
   * {@inheritdoc}
   */
  public function label() {
    $label = '';
    if ($parent = $this->getParentEntity()) {
      $parent_field = $this->get('parent_field_name')->value;
      $values = $parent->{$parent_field};
      foreach ($values as $key => $value) {
        if ($value->entity->id() == $this->id()) {
          $label = $parent->label() . ' > ' . $value->getFieldDefinition()->getLabel();
        }
      }
    }
    return $label;
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    parent::preSave($storage);

    // If no owner has been set explicitly, make the current user the owner.
    if (!$this->getOwner()) {
      $this->setOwnerId(\Drupal::currentUser()->id());
    }
    // If no revision author has been set explicitly, make the node owner the
    // revision author.
    if (!$this->getRevisionAuthor()) {
      $this->setRevisionAuthorId($this->getOwnerId());
    }

    // If behavior settings are not set then get them from the entity.
    if ($this->unserializedBehaviorSettings !== NULL) {
      $this->set('behavior_settings', serialize($this->unserializedBehaviorSettings));
    }
  }

  /**
   * Gets all the behavior settings.
   *
   * @return array
   *   The array of behavior settings.
   */
  public function getAllBehaviorSettings() {
    if ($this->unserializedBehaviorSettings === NULL) {
      $this->unserializedBehaviorSettings = unserialize($this->get('behavior_settings')->value);
    }
    if (!is_array($this->unserializedBehaviorSettings)) {
      $this->unserializedBehaviorSettings = [];
    }
    return $this->unserializedBehaviorSettings;
  }

  /**
   * Gets the behavior setting of an specific plugin.
   *
   * @param string $plugin_id
   *   The plugin ID for which to get the settings.
   * @param string|array $key
   *   Values are stored as a multi-dimensional associative array. If $key is a
   *   string, it will return $values[$key]. If $key is an array, each element
   *   of the array will be used as a nested key. If $key = array('foo', 'bar')
   *   it will return $values['foo']['bar'].
   * @param mixed $default
   *   (optional) The default value if the specified key does not exist.
   *
   * @return mixed
   *   The value for the given key.
   */
  public function &getBehaviorSetting($plugin_id, $key, $default = NULL) {
    $settings = $this->getAllBehaviorSettings();
    $exists = NULL;
    $value = &NestedArray::getValue($settings, array_merge((array) $plugin_id, (array) $key), $exists);
    if (!$exists) {
      $value = $default;
    }
    return $value;
  }

  /**
   * Sets all the behavior settings of a plugin.
   *
   * @param array $settings
   *   The behavior settings from the form.
   */
  public function setAllBehaviorSettings(array $settings) {
    // Set behavior settings fields.
    $this->unserializedBehaviorSettings = $settings;
  }

  /**
   * Sets the behavior settings of a plugin.
   *
   * @param string $plugin_id
   *   The plugin ID for which to set the settings.
   * @param array $settings
   *   The behavior settings from the form.
   */
  public function setBehaviorSettings($plugin_id, array $settings) {
    // Set behavior settings fields.
    $this->unserializedBehaviorSettings[$plugin_id] = $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageInterface $storage, $update = TRUE) {
    $this->setNeedsSave(FALSE);
    parent::postSave($storage, $update);
  }

  /**
   * {@inheritdoc}
   */
  public function preSaveRevision(EntityStorageInterface $storage, \stdClass $record) {
    parent::preSaveRevision($storage, $record);
  }

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime() {
    return $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwner() {
    return $this->get('uid')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwnerId() {
    return $this->get('uid')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwnerId($uid) {
    $this->set('uid', $uid);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwner(UserInterface $account) {
    $this->set('uid', $account->id());
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getType() {
    return $this->bundle();
  }

  /**
   * {@inheritdoc}
   */
  public function getParagraphType() {
    return $this->type->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getRevisionAuthor() {
    return $this->get('revision_uid')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function setRevisionAuthorId($uid) {
    $this->set('revision_uid', $uid);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getRevisionLog() {
    return '';
  }

  /**
   * {@inheritdoc}
   */
  public function setRevisionLog($revision_log) {
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields['id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('ID'))
      ->setDescription(t('The ID of the Paragraphs entity.'))
      ->setReadOnly(TRUE)
      ->setSetting('unsigned', TRUE);

    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The UUID of the paragraphs entity.'))
      ->setReadOnly(TRUE);

    $fields['revision_id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Revision ID'))
      ->setDescription(t('The paragraphs entity revision ID.'))
      ->setReadOnly(TRUE)
      ->setSetting('unsigned', TRUE);

    $fields['type'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Type'))
      ->setDescription(t('The paragraphs type.'))
      ->setSetting('target_type', 'paragraphs_type')
      ->setReadOnly(TRUE);

    $fields['langcode'] = BaseFieldDefinition::create('language')
      ->setLabel(t('Language code'))
      ->setDescription(t('The paragraphs entity language code.'))
      ->setRevisionable(TRUE);

    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Authored by'))
      ->setDescription(t('The user ID of the paragraphs author.'))
      ->setRevisionable(TRUE)
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
      ->setDefaultValueCallback('Drupal\paragraphs\Entity\Paragraph::getCurrentUserId')
      ->setTranslatable(TRUE)
      ->setDisplayOptions('form', array(
        'type' => 'hidden',
        'weight' => 0,
      ))
      ->setDisplayConfigurable('form', TRUE);

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Published'))
      ->setRevisionable(TRUE)
      ->setTranslatable(TRUE)
      ->setDefaultValue(TRUE)
      ->setDisplayConfigurable('form', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Authored on'))
      ->setDescription(t('The time that the Paragraph was created.'))
      ->setRevisionable(TRUE)
      ->setTranslatable(TRUE)
      ->setDisplayOptions('form', array(
        'type' => 'hidden',
        'weight' => 0,
      ))
      ->setDisplayConfigurable('form', TRUE);

    $fields['revision_uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Revision user ID'))
      ->setDescription(t('The user ID of the author of the current revision.'))
      ->setSetting('target_type', 'user')
      ->setQueryable(FALSE)
      ->setRevisionable(TRUE);

    $fields['parent_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Parent ID'))
      ->setDescription(t('The ID of the parent entity of which this entity is referenced.'))
      ->setSetting('is_ascii', TRUE);

    $fields['parent_type'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Parent type'))
      ->setDescription(t('The entity parent type to which this entity is referenced.'))
      ->setSetting('is_ascii', TRUE)
      ->setSetting('max_length', EntityTypeInterface::ID_MAX_LENGTH);

    $fields['parent_field_name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Parent field name'))
      ->setDescription(t('The entity parent field name to which this entity is referenced.'))
      ->setSetting('is_ascii', TRUE)
      ->setSetting('max_length', FieldStorageConfig::NAME_MAX_LENGTH);

    $fields['behavior_settings'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Behavior settings'))
      ->setDescription(t('The behavior plugin settings'))
      ->setRevisionable(TRUE)
      ->setDefaultValue(serialize([]));

    return $fields;
  }

  /**
   * Default value callback for 'uid' base field definition.
   *
   * @see ::baseFieldDefinitions()
   *
   * @return array
   *   An array of default values.
   */
  public static function getCurrentUserId() {
    return array(\Drupal::currentUser()->id());
  }

  /**
  * {@inheritdoc}
  */
 public function createDuplicate() {
   $duplicate = parent::createDuplicate();
   // Loop over entity fields and duplicate nested paragraphs.
   foreach ($duplicate->getFields() as $field) {
     if ($field->getFieldDefinition()->getType() == 'entity_reference_revisions') {
       if ($field->getFieldDefinition()->getTargetEntityTypeId() == "paragraph") {
         foreach ($field as $item) {
           $item->entity = $item->entity->createDuplicate();
         }
       }
     }
   }
   return $duplicate;
 }

}
