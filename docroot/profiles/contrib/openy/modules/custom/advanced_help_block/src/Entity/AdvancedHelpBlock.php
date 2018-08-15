<?php

namespace Drupal\advanced_help_block\Entity;

use Drupal\advanced_help_block\AdvancedHelpBlockInterface;
use Drupal\Core\Annotation\PluralTranslation;
use Drupal\Core\Annotation\Translation;
use Drupal\Core\Entity\Annotation\ContentEntityType;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\user\UserInterface;

/**
 * Defines the Contact entity.
 *
 * @ingroup advanced_help_block
 *
 * This is the main definition of the entity type. From it, an entityType is
 * derived. The most important properties in this example are listed below.
 *
 * id: The unique identifier of this entityType. It follows the pattern
 * 'moduleName_xyz' to avoid naming conflicts.
 *
 * label: Human readable name of the entity type.
 *
 * handlers: Handler classes are used for different tasks. You can use
 * standard handlers provided by D8 or build your own, most probably derived
 * from the standard class. In detail:
 *
 * - view_builder: we use the standard controller to view an instance. It is
 *   called when a route lists an '_entity_view' default for the entityType
 *   (see routing.yml for details. The view can be manipulated by using the
 *   standard drupal tools in the settings.
 *
 * - list_builder: We derive our own list builder class from the
 *   entityListBuilder to control the presentation.
 *   If there is a view available for this entity from the views module, it
 *   overrides the list builder. @todo: any view? naming convention?
 *
 * - form: We derive our own forms to add functionality like additional fields,
 *   redirects etc. These forms are called when the routing list an
 *   '_entity_form' default for the entityType. Depending on the suffix
 *   (.add/.edit/.delete) in the route, the correct form is called.
 *
 * - access: Our own accessController where we determine access rights based on
 *   permissions.
 *
 * More properties:
 *
 *  - base_table: Define the name of the table used to store the data. Make sure
 *    it is unique. The schema is automatically determined from the
 *    BaseFieldDefinitions below. The table is automatically created during
 *    installation.
 *
 *  - fieldable: Can additional fields be added to the entity via the GUI?
 *    Analog to content types.
 *
 *  - entity_keys: How to access the fields. Analog to 'nid' or 'uid'.
 *
 *  - links: Provide links to do standard tasks. The 'edit-form' and
 *    'delete-form' links are added to the list built by the
 *    entityListController. They will show up as action buttons in an additional
 *    column.
 *
 * There are many more properties to be used in an entity type definition. For
 * a complete overview, please refer to the '\Drupal\Core\Entity\EntityType'
 * class definition.
 *
 * The following construct is the actual definition of the entity type which
 * is read and cached. Don't forget to clear cache after changes.
 *
 * @ContentEntityType(
 *   id = "advanced_help_block",
 *   label = @Translation("Advanced Help Block"),
 *   label_singular = @Translation("advanced help block"),
 *   label_plural = @Translation("advanced help blocks"),
 *   label_count = @PluralTranslation(
 *     singular = "@count advanced help block",
 *     plural = "@count advanced help blocks"
 *   ),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\advanced_help_block\Entity\Controller\AdvancedHelpBlockBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\advanced_help_block\Form\AdvancedHelpBlockForm",
 *       "add" = "Drupal\advanced_help_block\Form\AdvancedHelpBlockForm",
 *       "edit" = "Drupal\advanced_help_block\Form\AdvancedHelpBlockForm",
 *       "delete" = "Drupal\advanced_help_block\Form\AdvancedHelpBlockDeleteForm",
 *     },
 *     "access" = "Drupal\advanced_help_block\AdvancedHelpBlockAccessControlHandler",
 *   },
 *   base_table = "advanced_help_block",
 *   admin_permission = "administer advanced_help_block entity",
 *   fieldable = TRUE,
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "published" = "status",
 *   },
 *   field_ui_base_route = "entity.advanced_help_block.edit_form",
 *   common_reference_target = TRUE,
 *   links = {
 *     "canonical" = "/advanced_help_block/{advanced_help_block}",
 *     "edit-form" = "/advanced_help_block/{advanced_help_block}/edit",
 *     "delete-form" = "/advanced_help_block/{advanced_help_block}/delete",
 *     "collection" = "/admin/advanced_help_block/list"
 *   },
 *   field_ui_base_route = "advanced_help_block.advanced_help_block_settings",
 * )
 *
 * The 'links' above are defined by their path. For core to find the corresponding
 * route, the route name must follow the correct pattern:
 *
 * entity.<entity-name>.<link-name> (replace dashes with underscores)
 * Example: 'entity.advanced_help_block.canonical'
 *
 * See routing file above for the corresponding implementation
 *
 * The 'Contact' class defines methods and fields for the contact entity.
 *
 * Being derived from the ContentEntityBase class, we can override the methods
 * we want. In our case we want to provide access to the standard fields about
 * creation and changed time stamps.
 *
 * Our interface (see ContactInterface) also exposes the EntityOwnerInterface.
 * This allows us to provide methods for setting and providing ownership
 * information.
 *
 * The most important part is the definitions of the field properties for this
 * entity type. These are of the same type as fields added through the GUI, but
 * they can by changed in code. In the definition we can define if the user with
 * the rights privileges can influence the presentation (view, edit) of each
 * field.
 */
class AdvancedHelpBlock extends ContentEntityBase implements AdvancedHelpBlockInterface {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   *
   * When a new entity instance is added, set the user_id entity reference to
   * the current user as the creator of the instance.
   */
  public static function preCreate(EntityStorageInterface $storage_controller, array &$values) {
    parent::preCreate($storage_controller, $values);
    $values += array(
      'user_id' => \Drupal::currentUser()->id(),
    );
  }

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
    $fields = parent::baseFieldDefinitions($entity_type);

    // Standard field, used as unique if primary index.
    $fields['id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('ID'))
      ->setDescription(t('The ID of the Contact entity.'))
      ->setReadOnly(TRUE);

    $fields['changed'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('changed'))
      ->setDescription(t('Changed time.'))
      ->setReadOnly(TRUE);


    // Standard field, unique outside of the scope of the current project.
    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The UUID of the Contact entity.'))
      ->setReadOnly(TRUE);

    // Name field for the contact.
    // We set display options for the view as well as the form.
    // Users with correct privileges can change the view and edit configuration.

    $fields['field_ahb_title'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Title'))
      ->setDescription(t('The name of the Advanced Help Block entity.'))
      ->setSettings(
        array(
          'default_value' => '',
          'max_length' => 255,
          'text_processing' => 0,
        )
      )
      ->setDisplayOptions(
        'view', array(
          'label' => 'above',
          'type' => 'string',
          'weight' => -6,
        )
      )
      ->setDisplayOptions(
        'form', array(
          'type' => 'string_textfield',
          'weight' => -6,
        )
      )
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setRequired(TRUE)
      ->setReadOnly(FALSE);

    $fields['field_ahb_pages'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Pages'))
      ->setDescription(t('Specify pages by using their paths. <b>Pages must be separated by comma.</b> Leave it empty, if you don\'t want show it anywhere. 
                        The \'*\' character is a wildcard. An example path is /user/* for every user page. @front is the front page.', ['@front' => '<front>']))
      ->setTranslatable(TRUE)
      ->setSettings(
        array(
          'default_value' => '',
          'text_processing' => 0,
        )
      )
      ->setDisplayOptions(
        'view', array(
          'label' => 'above',
          'type' => 'string',
          'weight' => -3,
        )
      )
      ->setDisplayOptions(
        'form', array(
          'type' => 'textarea',
          'weight' => -5
        )
      )
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['field_ahb_visibility'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Visibility rules'))
      ->setDescription(t('Visibility rules.'))
      ->setRequired(TRUE)
      ->setSettings(
        array(
          'allowed_values' => array(
            'include' => 'Show for the listed pages',
            'exclude' => 'Hide for the listed pages',
          ),
          'default_value' => '',
        )
      )
      ->setDisplayOptions(
        'view', array(
          'label' => 'above',
          'type' => 'list_default',
          'weight' => -2,
        )
      )
      ->setDisplayOptions(
        'form', array(
          'type' => 'options_select',
          'weight' => -2,
          'default_value' => 'include',
        )
      )
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    return $fields;
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
   * Gets the node title.
   *
   * @return string
   *   Title of the node.
   */
  public function getTitle() {
    return $this->label();
  }

  /**
   * Sets the node title.
   *
   * @param string $title
   *   The node title.
   *
   * @return \Drupal\advanced_help_block\AdvancedHelpBlockInterface
   *   The called node entity.
   */
  public function setTitle($title) {
    $this->set('label', $title);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime() {
    return $this->get('created')->value;
  }
}
