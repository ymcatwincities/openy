<?php

namespace Drupal\openy_campaign\Entity;

use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;

/**
 * Defines the Mapping Branch entity.
 *
 * @ingroup openy_campaign
 *
 * @ContentEntityType(
 *   id = "openy_campaign_mapping_branch",
 *   label = @Translation("Mapping Branch entity"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\openy_campaign\Entity\Controller\MappingListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\openy_campaign\Form\MappingForm",
 *       "add" = "Drupal\openy_campaign\Form\MappingForm",
 *       "edit" = "Drupal\openy_campaign\Form\MappingForm",
 *       "delete" = "Drupal\openy_campaign\Form\MappingDeleteForm",
 *     },
 *     "access" = "Drupal\openy_campaign\EntityAccess\MemberAccessControlHandler",
 *   },
 *   base_table = "openy_campaign_mapping_branch",
 *   admin_permission = "administer content types",
 *   fieldable = TRUE,
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "branch",
 *   },
 *   links = {
 *     "canonical" = "/admin/openy/retention-campaign/openy-campaign-mapping-branch/{openy_campaign_mapping_branch}",
 *     "edit-form" = "/admin/openy/retention-campaign/openy-campaign-mapping-branch/{openy_campaign_mapping_branch}/edit",
 *     "delete-form" = "/admin/openy/retention-campaign/openy-campaign-mapping-branch/{openy_campaign_mapping_branch}/delete",
 *     "collection" = "/admin/openy/retention-campaign/openy-campaign-mapping-branch/list"
 *   },
 * )
 */
class Mapping extends ContentEntityBase {

  /**
   * {@inheritdoc}
   *
   * Define the field properties here.
   *
   * Field name, type and size determine the table structure.
   *
   * In addition, we can define how the field and its content can be manipulated
   * in the GUI. The behaviour of the widgets used can be determined here.
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {

    // Standard field, used as unique if primary index.
    $fields['id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('ID'))
      ->setDescription(t('The ID of the Mapping Branch entity.'))
      ->setReadOnly(TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the record was created.'));

    $fields['personify_branch'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Personify branch ID'))
      ->setDescription(t('The personify branch id.'))
      ->setSettings([
        'default_value' => '',
        'max_length' => 255,
        'text_processing' => 0,
      ])
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'string',
        'weight' => -6,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string',
        'weight' => -6,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['branch'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Branch'))
      ->setDescription(t('Member branch name from the site.'))
      ->setSetting('target_type', 'node')
      ->setSetting('handler', 'default')
      ->setSetting('handler_settings', ['target_bundles' => ['branch' => 'branch']])
      ->setDisplayOptions('view', [
        'label'  => 'inline',
        'type'   => 'branch',
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'type'     => 'entity_reference_autocomplete',
        'weight'   => -5,
        'settings' => [
          'match_operator'    => 'CONTAINS',
          'size'              => '60',
          'autocomplete_type' => 'tags',
          'placeholder'       => '',
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getId() {
    return $this->get('id')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getPersonifyBranch() {
    return $this->get('personify_branch')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setPersonifyBranch($email) {
    $this->set('personify_branch', $email);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getBranchId() {
    return $this->get('branch')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setBranchId($target_id) {
    return $this->set('branch', $target_id);
  }

  /**
   * Get Branch node ID by the Personify ID.
   *
   * @param $personify_branch
   *
   * @return int
   */
  public static function getBranchByPersonifyId($personify_branch) {
    $connection = \Drupal::service('database');
    /** @var \Drupal\Core\Database\Query\Select $query */
    $query = $connection->select('openy_campaign_mapping_branch', 'b');
    $query->condition('b.personify_branch', $personify_branch);
    $query->fields('b', ['branch']);
    $result = $query->execute()->fetchField();

    return $result;
  }

}
