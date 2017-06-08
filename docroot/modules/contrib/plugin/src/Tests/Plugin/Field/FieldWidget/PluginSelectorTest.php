<?php

namespace Drupal\plugin\Tests\Plugin\Field\FieldWidget;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\simpletest\WebTestBase;

/**
 * \Drupal\plugin\Plugin\Field\FieldWidget\PluginSelector integration test.
 *
 * @group Plugin
 */
class PluginSelectorTest extends WebTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['block', 'field_ui', 'plugin'];

  /**
   * Tests the widget.
   */
  protected function testWidget() {
    $user = $this->drupalCreateUser(['administer user fields']);
    $this->drupalLogin($user);

    // Test the widget when setting a default field value.
    $field_name = strtolower($this->randomMachineName());
    $selectable_plugin_type_id = 'block';
    $field_type = 'plugin:' . $selectable_plugin_type_id;
    $default_selected_plugin_id = 'broken';
    $this->drupalPostForm('admin/config/people/accounts/fields/add-field', [
      'label' => $this->randomString(),
      'field_name' => $field_name,
      'new_storage_type' => $field_type,
    ], t('Save and continue'));
    $this->drupalPostForm(NULL, [], t('Save field settings'));
    $this->drupalPostForm(NULL, [
      sprintf('default_value_input[field_%s][0][plugin_selector][container][select][container][plugin_id]', $field_name) => $default_selected_plugin_id,
    ], t('Choose'));
    $this->drupalPostForm(NULL, [], t('Save settings'));
    \Drupal::entityManager()->clearCachedFieldDefinitions();
    // Get all plugin fields.
    $field_storage_id = 'user.field_' . $field_name;
    $field_storage = FieldStorageConfig::load($field_storage_id);
    $this->assertNotNull($field_storage);
    $field_id = 'user.user.field_' . $field_name;
    /** @var \Drupal\field\FieldConfigInterface $field */
    $field = FieldConfig::load($field_id);
    $this->assertNotNull($field);
    $this->assertEqual($field->getDefaultValueLiteral()[0]['plugin_id'], $default_selected_plugin_id);
    $this->assertTrue(is_array($field->getDefaultValueLiteral()[0]['plugin_configuration']));

    // Test the widget when creating an entity.
    $entity_selected_plugin_id = 'system_breadcrumb_block';
    $this->drupalPostForm('user/' . $user->id() . '/edit', [
      sprintf('field_%s[0][plugin_selector][container][select][container][plugin_id]', $field_name) => $entity_selected_plugin_id,
    ], t('Choose'));
    $this->drupalPostForm(NULL, [], t('Save'));

    // Test whether the widget displays field values.
    /** @var \Drupal\Core\Entity\ContentEntityInterface $user */
    $user = entity_load_unchanged('user', $user->id());
    $this->assertEqual($user->get('field_' . $field_name)->get(0)->get('plugin_id')->getValue(), $entity_selected_plugin_id);
  }
}
