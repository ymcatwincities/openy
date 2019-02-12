<?php

namespace Drupal\openy_activity_finder\Entity;


use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Defines the Program Search Check Log entity type.
 *
 * @ContentEntityType(
 *   id = "program_search_log_check",
 *   label = @Translation("Program Search Check Log"),
 *   label_collection = @Translation("Checks"),
 *   label_singular = @Translation("check"),
 *   label_plural = @Translation("checks"),
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
 *     "views_data" = "Drupal\views\EntityViewsData",
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
 *   base_table = "program_search_log_check",
 *   data_table = "program_search_log_check",
 *   translatable = FALSE,
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "details",
 *   },
 *   links = {
 *     "collection" = "/admin/reports/program-search"
 *   },
 *   admin_permission = "administer program search logs"
 * )
 */
class ProgramSearchCheckLog extends ContentEntityBase {

  const TYPE_DETAILS = 'details';
  const TYPE_REGISTER = 'register';

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created on'))
      ->setDescription(new TranslatableMarkup('The time that the @entity was created.', ['@entity' => $entity_type->getSingularLabel()]));

    $fields['details'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Details'))
      ->setSetting('max_length', 255);

    $fields['log_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Search Request'))
      ->setSetting('target_type', 'program_search_log');

    $fields['type'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Type'))
      ->setDescription(t('Either More Details or click on Register button.'))
      ->setRequired(TRUE)
      ->setDefaultValue(static::TYPE_DETAILS)
      ->setSetting('allowed_values', static::getTypeOptions());

    return $fields;
  }

  static public function getTypeOptions() {
    return [
      self::TYPE_DETAILS => t('Details'),
      self::TYPE_REGISTER => t('Register'),
    ];
  }

}
