<?php

namespace Drupal\paragraphs\Tests;

use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\field_ui\Tests\FieldUiTestTrait;
use Drupal\node\Entity\NodeType;
use Drupal\paragraphs\Entity\ParagraphsType;
use Drupal\simpletest\WebTestBase;

/**
 * Base class for tests.
 */
abstract class ParagraphsTestBase extends WebTestBase {

  use FieldUiTestTrait;

  /**
   * Drupal user object created by loginAsAdmin().
   *
   * @var \Drupal\user\UserInterface
   */
  protected $admin_user = NULL;

  /**
   * List of permissions used by loginAsAdmin().
   *
   * @var array
   */
  protected $admin_permissions = [];

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'node',
    'paragraphs',
    'field',
    'field_ui',
    'block',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    // Place the breadcrumb, tested in fieldUIAddNewField().
    $this->drupalPlaceBlock('system_breadcrumb_block');
    $this->drupalPlaceBlock('local_tasks_block');
    $this->drupalPlaceBlock('local_actions_block');
    $this->drupalPlaceBlock('page_title_block');

    $this->admin_permissions = [
      'administer nodes',
      'administer content types',
      'administer node fields',
      'administer paragraphs types',
      'administer node form display',
      'administer paragraph fields',
      'administer paragraph form display',
    ];
  }

  /**
   * Creates an user with admin permissions and log in.
   *
   * @param array $additional_permissions
   *   Additional permissions that will be granted to admin user.
   * @param bool $reset_permissions
   *   Flag to determine if default admin permissions will be replaced by
   *   $additional_permissions.
   *
   * @return object
   *   Newly created and logged in user object.
   */
  function loginAsAdmin($additional_permissions = [], $reset_permissions = FALSE) {
    $permissions = $this->admin_permissions;

    if ($reset_permissions) {
      $permissions = $additional_permissions;
    }
    elseif (!empty($additional_permissions)) {
      $permissions = array_merge($permissions, $additional_permissions);
    }

    $this->admin_user = $this->drupalCreateUser($permissions);
    $this->drupalLogin($this->admin_user);
    return $this->admin_user;
  }

  /**
   * Adds a content type with a Paragraphs field.
   *
   * @param string $content_type_name
   *   Content type name to be used.
   * @param string $paragraphs_field_name
   *   Paragraphs field name to be used.
   */
  protected function addParagraphedContentType($content_type_name, $paragraphs_field_name) {
    // Create the content type.
    $node_type = NodeType::create([
      'type' => $content_type_name,
      'name' => $content_type_name,
    ]);
    $node_type->save();

    $this->addParagraphsField($content_type_name, $paragraphs_field_name, 'node');
  }

  /**
   * Adds a Paragraphs field to a given $entity_type.
   *
   * @param string $entity_type_name
   *   Entity type name to be used.
   * @param string $paragraphs_field_name
   *   Paragraphs field name to be used.
   * @param string $entity_type
   *   Entity type where to add the field.
   */
  protected function addParagraphsField($entity_type_name, $paragraphs_field_name, $entity_type) {
    // Add a paragraphs field.
    $field_storage = FieldStorageConfig::create([
      'field_name' => $paragraphs_field_name,
      'entity_type' => $entity_type,
      'type' => 'entity_reference_revisions',
      'settings' => [
        'target_type' => 'paragraph',
        'cardinality' => '-1',
      ],
    ]);
    $field_storage->save();
    $field = FieldConfig::create([
      'field_storage' => $field_storage,
      'bundle' => $entity_type_name,
      'settings' => [
        'handler' => 'default:paragraph',
        'handler_settings' => ['target_bundles' => NULL],
      ],
    ]);
    $field->save();

    $form_display = EntityFormDisplay::create([
      'targetEntityType' => $entity_type,
      'bundle' => $entity_type_name,
      'mode' => 'default',
      'status' => TRUE,
    ])
      ->setComponent($paragraphs_field_name, ['type' => 'entity_reference_paragraphs']);
    $form_display->save();

    $view_display = EntityViewDisplay::create([
      'targetEntityType' => $entity_type,
      'bundle' => $entity_type_name,
      'mode' => 'default',
      'status' => TRUE,
    ])->setComponent($paragraphs_field_name, ['type' => 'entity_reference_revisions_entity_view']);
    $view_display->save();
  }

  /**
   * Adds a Paragraphs type.
   *
   * @param string $paragraphs_type_name
   *   Paragraph type name used to create.
   */
  protected function addParagraphsType($paragraphs_type_name) {
    $paragraphs_type = ParagraphsType::create([
      'id' => $paragraphs_type_name,
      'label' => $paragraphs_type_name,
    ]);
    $paragraphs_type->save();
  }

  /**
   * Sets the Paragraphs widget add mode.
   *
   * @param string $content_type
   *   Content type name where to set the widget mode.
   * @param string $paragraphs_field
   *   Paragraphs field to change the mode.
   * @param string $mode
   *   Mode to be set. ('dropdown', 'select' or 'button').
   */
  protected function setAddMode($content_type, $paragraphs_field, $mode) {
    $form_display = EntityFormDisplay::load('node.' . $content_type . '.default')
      ->setComponent($paragraphs_field, [
        'type' => 'entity_reference_paragraphs',
        'settings' => ['add_mode' => $mode]
      ]);
    $form_display->save();
  }

  /**
   * Sets the allowed Paragraphs types that can be added.
   *
   * @param string $content_type
   *   Content type name that contains the paragraphs field.
   * @param array $paragraphs_types
   *   Array of paragraphs types that will be modified.
   * @param bool $selected
   *   Whether or not the paragraphs types will be enabled.
   * @param string $paragraphs_field
   *   Paragraphs field name that does the reference.
   */
  protected function setAllowedParagraphsTypes($content_type, $paragraphs_types, $selected, $paragraphs_field) {
    $edit = [];
    $this->drupalGet('admin/structure/types/manage/' . $content_type . '/fields/node.' . $content_type . '.' . $paragraphs_field);
    foreach ($paragraphs_types as $paragraphs_type) {
      $edit['settings[handler_settings][target_bundles_drag_drop][' . $paragraphs_type . '][enabled]'] = $selected;
    }
    $this->drupalPostForm(NULL, $edit, t('Save settings'));
  }

  /**
   * Sets the weight of a given Paragraphs type.
   *
   * @param string $content_type
   *   Content type name that contains the paragraphs field.
   * @param string $paragraphs_type
   *   ID of Paragraph type that will be modified.
   * @param int $weight
   *   Weight to be set.
   * @param string $paragraphs_field
   *   Paragraphs field name that does the reference.
   */
  protected function setParagraphsTypeWeight($content_type, $paragraphs_type, $weight, $paragraphs_field) {
    $this->drupalGet('admin/structure/types/manage/' . $content_type . '/fields/node.' . $content_type . '.' . $paragraphs_field);
    $edit['settings[handler_settings][target_bundles_drag_drop][' . $paragraphs_type . '][weight]'] = $weight;
    $this->drupalPostForm(NULL, $edit, t('Save settings'));
  }

}
