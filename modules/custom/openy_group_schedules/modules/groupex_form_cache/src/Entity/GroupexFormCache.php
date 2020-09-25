<?php

namespace Drupal\groupex_form_cache\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\groupex_form_cache\GroupexFormCacheInterface;
use Drupal\user\UserInterface;
use Drupal\node\NodeInterface;

/**
 * Defines the GroupEx Pro Form Cache entity.
 *
 * @ingroup groupex_form_cache
 *
 * @ContentEntityType(
 *   id = "groupex_form_cache",
 *   label = @Translation("GroupEx Pro Form Cache"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\groupex_form_cache\GroupexFormCacheListBuilder",
 *     "views_data" = "Drupal\groupex_form_cache\Entity\GroupexFormCacheViewsData",
 *
 *     "form" = {
 *       "default" = "Drupal\groupex_form_cache\Form\GroupexFormCacheForm",
 *       "add" = "Drupal\groupex_form_cache\Form\GroupexFormCacheForm",
 *       "edit" = "Drupal\groupex_form_cache\Form\GroupexFormCacheForm",
 *       "delete" = "Drupal\groupex_form_cache\Form\GroupexFormCacheDeleteForm",
 *     },
 *     "access" = "Drupal\groupex_form_cache\GroupexFormCacheAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\groupex_form_cache\GroupexFormCacheHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "groupex_form_cache",
 *   admin_permission = "administer groupex form cache entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "name",
 *     "uuid" = "uuid",
 *     "uid" = "user_id",
 *     "langcode" = "langcode",
 *     "status" = "status",
 *   },
 *   links = {
 *     "canonical" = "/admin/openy/integrations/groupex-pro/groupex_form_cache/{groupex_form_cache}",
 *     "add-form" = "/admin/openy/integrations/groupex-pro/groupex_form_cache/add",
 *     "edit-form" = "/admin/openy/integrations/groupex-pro/groupex_form_cache/{groupex_form_cache}/edit",
 *     "delete-form" = "/admin/openy/integrations/groupex-pro/groupex_form_cache/{groupex_form_cache}/delete",
 *     "collection" = "/admin/openy/integrations/groupex-pro/groupex_form_cache",
 *   },
 *   field_ui_base_route = "groupex_form_cache.settings"
 * )
 */
class GroupexFormCache extends ContentEntityBase implements GroupexFormCacheInterface {

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
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields['id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('ID'))
      ->setDescription(t('The ID of the GroupEx Pro Form Cache entity.'))
      ->setReadOnly(TRUE);
    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The UUID of the GroupEx Pro Form Cache entity.'))
      ->setReadOnly(TRUE);

    $fields['user_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Authored by'))
      ->setDescription(t('The user ID of author of the GroupEx Pro Form Cache entity.'))
      ->setRevisionable(TRUE)
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
      ->setDefaultValueCallback('Drupal\node\Entity\Node::getCurrentUserId')
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
      ->setDescription(t('The name of the GroupEx Pro Form Cache entity.'))
      ->setSettings([
        'max_length' => 50,
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
      ->setDisplayConfigurable('view', TRUE);

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Publishing status'))
      ->setDescription(t('A boolean indicating whether the GroupEx Pro Form Cache is published.'))
      ->setDefaultValue(TRUE);

    $fields['langcode'] = BaseFieldDefinition::create('language')
      ->setLabel(t('Language code'))
      ->setDescription(t('The language code for the GroupEx Pro Form Cache entity.'))
      ->setDisplayOptions('form', [
        'type' => 'language_select',
        'weight' => 10,
      ])
      ->setRevisionable(TRUE)
      ->setTranslatable(TRUE)
      ->setDefaultValue(LanguageInterface::LANGCODE_NOT_SPECIFIED)
      ->setInitialValue(LanguageInterface::LANGCODE_NOT_SPECIFIED)
      // @todo: Define this via an options provider once.
      // https://www.drupal.org/node/2329937 is completed.
      ->addPropertyConstraints('value', [
        'AllowedValues' => ['callback' => __CLASS__ . '::getAllowedConfigurableLanguageCodes'],
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the entity was last edited.'));

    return $fields;
  }

  /**
   * Defines allowed configurable language codes for AllowedValues constraints.
   *
   * @return string[]
   *   The allowed values.
   */
  public static function getAllowedConfigurableLanguageCodes() {
    return array_keys(\Drupal::languageManager()->getLanguages(LanguageInterface::STATE_CONFIGURABLE));
  }

}
