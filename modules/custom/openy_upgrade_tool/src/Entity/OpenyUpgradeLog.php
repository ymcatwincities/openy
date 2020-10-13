<?php

namespace Drupal\openy_upgrade_tool\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\RevisionableContentEntityBase;
use Drupal\Core\Entity\RevisionableInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Serialization\Yaml;
use Drupal\user\UserInterface;

/**
 * Defines the Openy upgrade log entity.
 *
 * @ingroup openy_upgrade_tool
 *
 * @ContentEntityType(
 *   id = "openy_upgrade_log",
 *   label = @Translation("Openy upgrade log"),
 *   handlers = {
 *     "storage" = "Drupal\openy_upgrade_tool\OpenyUpgradeLogStorage",
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\openy_upgrade_tool\OpenyUpgradeLogListBuilder",
 *     "views_data" = "Drupal\openy_upgrade_tool\Entity\OpenyUpgradeLogViewsData",
 *
 *     "form" = {
 *       "default" = "Drupal\openy_upgrade_tool\Form\OpenyUpgradeLogForm",
 *       "add" = "Drupal\openy_upgrade_tool\Form\OpenyUpgradeLogForm",
 *       "edit" = "Drupal\openy_upgrade_tool\Form\OpenyUpgradeLogForm",
 *       "delete" = "Drupal\openy_upgrade_tool\Form\OpenyUpgradeLogDeleteForm",
 *     },
 *     "access" = "Drupal\openy_upgrade_tool\OpenyUpgradeLogAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\openy_upgrade_tool\OpenyUpgradeLogHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "openy_upgrade_log",
 *   revision_table = "openy_upgrade_log_revision",
 *   revision_data_table = "openy_upgrade_log_field_revision",
 *   admin_permission = "administer openy upgrade log entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "revision" = "vid",
 *     "label" = "name",
 *     "uuid" = "uuid",
 *     "uid" = "user_id",
 *     "status" = "status",
 *   },
 *   links = {
 *     "canonical" = "/admin/openy/development/upgrade-log/dashboard/{openy_upgrade_log}",
 *     "add-form" = "/admin/openy/development/upgrade-log/add",
 *     "edit-form" = "/admin/openy/development/upgrade-log/dashboard/{openy_upgrade_log}/edit",
 *     "delete-form" = "/admin/openy/development/upgrade-log/dashboard/{openy_upgrade_log}/delete",
 *     "version-history" = "/admin/openy/development/upgrade-log/dashboard/{openy_upgrade_log}/revisions",
 *     "revision" = "/admin/openy/development/upgrade-log/dashboard/{openy_upgrade_log}/revisions/{openy_upgrade_log_revision}/view",
 *     "revision_revert" = "/admin/openy/development/upgrade-log/dashboard/{openy_upgrade_log}/revisions/{openy_upgrade_log_revision}/revert",
 *     "revision_delete" = "/admin/openy/development/upgrade-log/dashboard/{openy_upgrade_log}/revisions/{openy_upgrade_log_revision}/delete",
 *     "collection" = "/admin/openy/development/upgrade-log/dashboard",
 *   },
 *      revision_metadata_keys = {
 *     "revision_user" = "revision_user",
 *     "revision_created" = "revision_created",
 *     "revision_log_message" = "revision_log_message"
 *   },
 * )
 */
class OpenyUpgradeLog extends RevisionableContentEntityBase implements OpenyUpgradeLogInterface {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageInterface $storage_controller, array &$values) {
    parent::preCreate($storage_controller, $values);
    $values += [
      'user_id' => \Drupal::currentUser()->id(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function urlRouteParameters($rel) {
    $uri_route_parameters = parent::urlRouteParameters($rel);

    if ($rel === 'revision_revert' && $this instanceof RevisionableInterface) {
      $uri_route_parameters[$this->getEntityTypeId() . '_revision'] = $this->getRevisionId();
    }
    elseif ($rel === 'revision_delete' && $this instanceof RevisionableInterface) {
      $uri_route_parameters[$this->getEntityTypeId() . '_revision'] = $this->getRevisionId();
    }

    return $uri_route_parameters;
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    parent::preSave($storage);

    foreach (array_keys($this->getTranslationLanguages()) as $langcode) {
      $translation = $this->getTranslation($langcode);

      // If no owner has been set explicitly, make the anonymous user the owner.
      if (!$translation->getOwner()) {
        $translation->setOwnerId(0);
      }
    }

    // If no revision author has been set explicitly, make the openy_upgrade_log
    // owner the revision author.
    if (!$this->getRevisionUser()) {
      $this->setRevisionUserId($this->getOwnerId());
    }
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
  public function getChangedTime() {
    return $this->get('changed')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setChangedTime($timestamp) {
    $this->set('changed', $timestamp);
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
  public function getData() {
    return unserialize($this->get('data')->value);
  }

  /**
   * {@inheritdoc}
   */
  public function getYmlData() {
    return Yaml::encode($this->getData());
  }

  /**
   * {@inheritdoc}
   */
  public function setData(array $data) {
    unset($data['uuid'], $data['_core']);
    $this->set('data', serialize($data));
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getStatus() {
    return (bool) $this->get('status')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setStatus($status) {
    $this->set('status', $status);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function applyCurrentActiveVersion() {
    // User confirmed current config version from active storage and
    // left everything unchanged.
    $this->set('status', TRUE);
    $this->save();
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function applyOpenyVersion() {
    \Drupal::service('openy_upgrade_log.manager')
      ->applyOpenyVersion($this->getName());
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function applyConfigVersionFromData() {
    \Drupal::service('openy_upgrade_log.manager')
      ->updateExistingConfig($this->getName(), $this->getData());
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['user_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Authored by'))
      ->setDescription(t('The user ID that related to config update.'))
      ->setRevisionable(TRUE)
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
      ->setTranslatable(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'author',
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 5,
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'autocomplete_type' => 'tags',
          'placeholder' => '',
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Name'))
      ->setDescription(t('The config name that related to Openy upgrade log.'))
      ->setRevisionable(TRUE)
      ->setSettings([
        'max_length' => 256,
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => -4,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -4,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setRequired(TRUE);

    $fields['data'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Config Data'))
      ->setDescription(t('Serialized config data.'))
      ->setDefaultValue('')
      ->setRequired(TRUE)
      ->setRevisionable(TRUE)
      ->setSettings(['case_sensitive' => TRUE])
      ->setDisplayOptions('view', [
        'label' => 'visible',
        'type' => 'basic_string',
        'weight' => 5,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textarea',
        'weight' => 5,
        'settings' => ['rows' => 5],
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setRevisionable(TRUE)
      ->setLabel(t('Conflict resolving status'))
      ->setDescription(t('A boolean indicating whether the conflict with Open Y config version was resolved or not.'))
      ->setDefaultValue(0);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the entity was last edited.'));

    return $fields;
  }

}
