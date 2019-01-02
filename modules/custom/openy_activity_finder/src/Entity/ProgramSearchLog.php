<?php

namespace Drupal\openy_activity_finder\Entity;


use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Defines the Program Search Log entity type.
 *
 * @ContentEntityType(
 *   id = "program_search_log",
 *   label = @Translation("Program Search Log"),
 *   label_collection = @Translation("Logs"),
 *   label_singular = @Translation("log"),
 *   label_plural = @Translation("logs"),
 *   label_count = @PluralTranslation(
 *     singular = "@count log",
 *     plural = "@count logs"
 *   ),
 *   handlers = {
 *     "storage" = "Drupal\Core\Entity\Sql\SqlContentEntityStorage",
 *     "storage_schema" = "Drupal\Core\Entity\Sql\SqlContentEntityStorageSchema",
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\Core\Entity\EntityListBuilder",
 *     "access" = "Drupal\Core\Entity\EntityAccessControlHandler",
 *     "views_data" = "Drupal\openy_activity_finder\ProgramSearchLogViewsData",
 *     "form" = {
 *       "default" = "Drupal\Core\Entity\ContentEntityForm",
 *       "edit" = "Drupal\Core\Entity\ContentEntityForm",
 *       "create" = "Drupal\Core\Entity\ContentEntityForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider",
 *     }
 *   },
 *   base_table = "program_search_log",
 *   data_table = "program_search_log",
 *   translatable = FALSE,
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "hash",
 *   },
 *   links = {
 *     "collection" = "/admin/reports/program-search"
 *   },
 *   admin_permission = "administer program search logs"
 * )
 */
class ProgramSearchLog extends ContentEntityBase {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created on'))
      ->setDescription(new TranslatableMarkup('The time that the @entity was created.', ['@entity' => $entity_type->getSingularLabel()]));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(new TranslatableMarkup('The time that the @entity was last changed.', ['@entity' => $entity_type->getSingularLabel()]));

    $fields['hash'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Hash'))
      ->setSetting('max_length', 255);

    $fields['hash_ip_agent'] = BaseFieldDefinition::create('string')
      ->setLabel(t('IP + Agent hash'))
      ->setSetting('max_length', 255);

    $fields['keyword'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Keyword'))
      ->setSetting('max_length', 255);

    $fields['location'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Location'))
      ->setSetting('max_length', 255);

    $fields['age'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Age'))
      ->setSetting('max_length', 255);

    $fields['day'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Day of week'))
      ->setSetting('max_length', 255);

    $fields['page'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Page of results'))
      ->setSetting('max_length', 255);

    return $fields;
  }

}
