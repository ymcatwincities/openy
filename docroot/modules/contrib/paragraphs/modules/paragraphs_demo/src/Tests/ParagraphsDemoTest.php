<?php

namespace Drupal\paragraphs_demo\Tests;

use Drupal\filter\Entity\FilterFormat;
use Drupal\simpletest\WebTestBase;

/**
 * Tests the demo module for Paragraphs.
 *
 * @group paragraphs
 */
class ParagraphsDemoTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var string[]
   */
  public static $modules = array(
    'paragraphs_demo',
    'block',
  );

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->drupalPlaceBlock('local_tasks_block');
    $this->drupalPlaceBlock('local_actions_block');
    $this->drupalPlaceBlock('page_title_block');
  }

  /**
   * Asserts demo paragraphs have been created.
   */
  protected function testConfigurationsAndCreation() {
    $basic_html_format = FilterFormat::create(array(
      'format' => 'basic_html',
      'name' => 'Basic HTML',
    ));
    $basic_html_format->save();
    $admin_user = $this->drupalCreateUser(array(
      'administer site configuration',
      'administer nodes',
      'create paragraphed_content_demo content',
      'edit any paragraphed_content_demo content',
      'delete any paragraphed_content_demo content',
      'administer content translation',
      'create content translations',
      'administer languages',
      'administer content types',
      'administer node fields',
      'administer node display',
      'administer paragraphs types',
      'administer paragraph fields',
      'administer paragraph display',
      'administer paragraph form display',
      'administer node form display',
      $basic_html_format->getPermissionName(),
    ));

    $this->drupalLogin($admin_user);
    // Check for all pre-configured paragraphs_types.
    $this->drupalGet('admin/structure/paragraphs_type');
    $this->assertText('Image + Text');
    $this->assertText('Images');
    $this->assertText('Text');
    $this->assertText('Text + Image');
    $this->assertText('User');

    // Check for preconfigured languages.
    $this->drupalGet('admin/config/regional/language');
    $this->assertText('English');
    $this->assertText('German');
    $this->assertText('French');

    // Check for Content language translation checks.
    $this->drupalGet('admin/config/regional/content-language');
    $this->assertFieldChecked('edit-entity-types-node');
    $this->assertFieldChecked('edit-entity-types-paragraph');
    $this->assertFieldChecked('edit-settings-node-paragraphed-content-demo-translatable');
    $this->assertNoFieldChecked('edit-settings-node-paragraphed-content-demo-fields-field-paragraphs-demo');
    $this->assertFieldChecked('edit-settings-paragraph-images-translatable');
    $this->assertFieldChecked('edit-settings-paragraph-image-text-translatable');
    $this->assertFieldChecked('edit-settings-paragraph-text-translatable');
    $this->assertFieldChecked('edit-settings-paragraph-text-image-translatable');
    $this->assertFieldChecked('edit-settings-paragraph-user-translatable');

    // Check for paragraph type Image + text that has the correct fields set.
    $this->drupalGet('admin/structure/paragraphs_type/image_text/fields');
    $this->assertText('Text');
    $this->assertText('Image');

    // Check for paragraph type Text that has the correct fields set.
    $this->drupalGet('admin/structure/paragraphs_type/text/fields');
    $this->assertText('Text');
    $this->assertNoText('Image');

    // Make sure we have the paragraphed article listed as a content type.
    $this->drupalGet('admin/structure/types');
    $this->assertText('Paragraphed article');

    // Check that title and the descriptions are set.
    $this->drupalGet('admin/structure/types/manage/paragraphed_content_demo');
    $this->assertText('Paragraphed article');
    $this->assertText('Article with paragraphs.');

    // Check that the Paragraph field is added.
    $this->clickLink('Manage fields');
    $this->assertText('Paragraphs');

    // Check that all paragraphs types are enabled (disabled).
    $this->clickLink('Edit', 0);
    $this->assertNoFieldChecked('edit-settings-handler-settings-target-bundles-drag-drop-image-text-enabled');
    $this->assertNoFieldChecked('edit-settings-handler-settings-target-bundles-drag-drop-images-enabled');
    $this->assertNoFieldChecked('edit-settings-handler-settings-target-bundles-drag-drop-text-image-enabled');
    $this->assertNoFieldChecked('edit-settings-handler-settings-target-bundles-drag-drop-user-enabled');
    $this->assertNoFieldChecked('edit-settings-handler-settings-target-bundles-drag-drop-text-enabled');

    $this->drupalGet('node/add/paragraphed_content_demo');
    $this->assertRaw('<strong data-drupal-selector="edit-field-paragraphs-demo-title">Paragraphs</strong>', 'Field name is present on the page.');
    $this->drupalPostForm(NULL, NULL, t('Add Text'));
    $this->assertNoRaw('<strong data-drupal-selector="edit-field-paragraphs-demo-title">Paragraphs</strong>', 'Field name for empty field is not present on the page.');
    $this->assertRaw('<h4 class="label">Paragraphs</h4>', 'Field name appears in the table header.');
    $edit = array(
      'title[0][value]' => 'Paragraph title',
      'field_paragraphs_demo[0][subform][field_text_demo][0][value]' => 'Paragraph text',
    );
    $this->drupalPostForm(NULL, $edit, t('Add User'));
    $edit = [
      'field_paragraphs_demo[1][subform][field_user_demo][0][target_id]' => $admin_user->label() . ' (' . $admin_user->id() . ')',
    ];
    $this->drupalPostForm(NULL, $edit, t('Save and publish'));

    $this->assertText('Paragraphed article Paragraph title has been created.');
    $this->assertText('Paragraph title');
    $this->assertText('Paragraph text');

    // Search a nested Paragraph text.
    /**
     * @todo Reinstate this after search_api is fixed.
     *
     * search_api issue: https://www.drupal.org/node/2792277
     * paragraphs issue: https://www.drupal.org/node/2791315
    $this->drupalGet('paragraphs_search', ['query' => ['search_api_fulltext' => 'A search api example']]);
    $this->assertRaw('Welcome to the Paragraphs Demo module!');
    // Search a node paragraph field text.
    $this->drupalGet('paragraphs_search', ['query' => ['search_api_fulltext' => 'It allows you']]);
    $this->assertRaw('Welcome to the Paragraphs Demo module!');
    */
    // Search non existent text.
    $this->drupalGet('paragraphs_search', ['query' => ['search_api_fulltext' => 'foo']]);
    $this->assertNoRaw('Welcome to the Paragraphs Demo module!');
  }

}
