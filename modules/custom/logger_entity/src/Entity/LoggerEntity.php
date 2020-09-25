<?php

namespace Drupal\logger_entity\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\user\UserInterface;
use Drupal\node\NodeInterface;

/**
 * Defines the Logger Entity entity.
 *
 * @ingroup logger_entity
 *
 * @ContentEntityType(
 *   id = "logger_entity",
 *   label = @Translation("Logger Entity"),
 *   bundle_label = @Translation("Logger Entity type"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\logger_entity\LoggerEntityListBuilder",
 *     "views_data" = "Drupal\logger_entity\Entity\LoggerEntityViewsData",
 *
 *     "form" = {
 *       "default" = "Drupal\logger_entity\Form\LoggerEntityForm",
 *       "add" = "Drupal\logger_entity\Form\LoggerEntityForm",
 *       "edit" = "Drupal\logger_entity\Form\LoggerEntityForm",
 *       "delete" = "Drupal\logger_entity\Form\LoggerEntityDeleteForm",
 *     },
 *     "access" = "Drupal\logger_entity\LoggerEntityAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\logger_entity\LoggerEntityHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "logger_entity",
 *   admin_permission = "administer logger entity entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "bundle" = "type",
 *     "label" = "name",
 *     "uuid" = "uuid",
 *     "uid" = "user_id",
 *     "langcode" = "langcode",
 *     "status" = "status",
 *     "data" = "data",
 *   },
 *   links = {
 *     "canonical" = "/admin/config/logger_entity/{logger_entity}",
 *     "add-page" = "/admin/config/logger_entity/add",
 *     "add-form" = "/admin/config/logger_entity/add/{logger_entity_type}",
 *     "edit-form" = "/admin/config/logger_entity/{logger_entity}/edit",
 *     "delete-form" = "/admin/config/logger_entity/{logger_entity}/delete",
 *     "collection" = "/admin/config/logger_entity",
 *   },
 *   bundle_entity_type = "logger_entity_type",
 *   field_ui_base_route = "entity.logger_entity_type.edit_form"
 * )
 */
class LoggerEntity extends ContentEntityBase implements LoggerEntityInterface {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageInterface $storage_controller, array &$values) {
    parent::preCreate($storage_controller, $values);
    $values += array(
      'user_id' => \Drupal::currentUser()->id(),
    );
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
  public function getName() {
    return $this->get('name')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setName($name) {
    $this->set('name', $name);
    return $this;
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
  public function setCreatedTime($timestamp) {
    $this->set('created', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwner() {
    return $this->get('user_id')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwnerId() {
    return $this->get('user_id')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwnerId($uid) {
    $this->set('user_id', $uid);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwner(UserInterface $account) {
    $this->set('user_id', $account->id());
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isPublished() {
    return (bool) $this->getEntityKey('status');
  }

  /**
   * {@inheritdoc}
   */
  public function setPublished($published) {
    $this->set('status', $published ? NodeInterface::PUBLISHED : NodeInterface::NOT_PUBLISHED);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getData() {
    return unserialize($this->get('data')->value);
  }

  /**
   * {@inheritdoc}
   */
  public function setData(array $data) {
    $this->set('data', serialize($data));
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['user_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Authored by'))
      ->setDescription(t('The user ID of author of the Logger Entity entity.'))
      ->setRevisionable(TRUE)
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
      ->setTranslatable(TRUE)
      ->setDisplayOptions('view', array(
        'label' => 'hidden',
        'type' => 'author',
        'weight' => 0,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'entity_reference_autocomplete',
        'weight' => 5,
        'settings' => array(
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'autocomplete_type' => 'tags',
          'placeholder' => '',
        ),
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Name'))
      ->setDescription(t('The name of the Logger Entity entity.'))
      ->setSettings(array(
        'max_length' => 250,
        'text_processing' => 0,
      ))
      ->setDefaultValue('')
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'string',
        'weight' => -4,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'string_textfield',
        'weight' => -4,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Publishing status'))
      ->setDescription(t('A boolean indicating whether the Logger Entity is published.'))
      ->setDefaultValue(TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the entity was last edited.'));

    $fields['data'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Data'))
      ->setDescription(t('Serialized data.'))
      ->setSettings(
        [
          'case_sensitive' => TRUE,
        ]
      );

    return $fields;
  }

}
