<?php

namespace Drupal\file_entity\Tests;

/**
 * Tests file entity settings.
 *
 * @group file_entity
 */
class FileEntitySettingsTest extends FileEntityTestBase {

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = ['field_ui'];

  /**
   * Tests file image formatter settings.
   */
  public function testFileImageFormatterSettings() {
    $account = $this->drupalCreateUser([
      'administer file display'
    ]);
    $this->drupalLogin($account);
    $this->drupalGet('admin/structure/file-types/manage/image/edit/display');
    $this->assertText('Field used for the image title attribute: field_image_title_text', 'Settings summary for title field is displayed correctly.');
    $this->assertText('Field used for the image title attribute: field_image_title_text', 'Settings summary for alt field is displayed correctly.');

    $this->drupalPostAjaxForm(NULL, [], 'uri_settings_edit');
    $this->assertRaw('fields[uri][settings_edit_form][settings][title]', 'Field for setting title field is available.');
    $this->assertRaw('fields[uri][settings_edit_form][settings][alt]', 'Field for setting alt field is available.');

    $edit = [
      'fields[uri][settings_edit_form][settings][title]' => '_none',
      'fields[uri][settings_edit_form][settings][alt]' => '_none',
    ];
    $this->drupalPostAjaxForm(NULL, $edit, ['uri_plugin_settings_update' => t('Update')]);
    $this->drupalPostForm(NULL, [], t('Save'));
    $this->assertText('Title attribute is hidden.');
    $this->assertText('Alt attribute is hidden.');

    $this->drupalLogin($this->drupalCreateUser(['create files']));
    $test_file = $this->getTestFile('image');
    $this->drupalGet('file/add');
    $edit = [
      'files[upload]' => $this->container->get('file_system')->realpath($test_file->uri),
    ];
    $this->drupalPostForm(NULL, $edit, t('Next'));
    $this->drupalPostForm(NULL, [], t('Next'));
    $edit = [
      'field_image_alt_text[0][value]' => 'Alt text',
      'field_image_title_text[0][value]' => 'Title text',
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertNoRaw('alt="Alt text"', 'Alt attribute is hidden.');
    $this->assertNoRaw('title="Title text"', 'Title attribute is hidden.');
  }
}
