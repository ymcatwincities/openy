<?php

namespace Drupal\paragraphs\Tests\Classic;

use Drupal\field_ui\Tests\FieldUiTestTrait;

/**
 * Tests paragraphs edit modes.
 *
 * @group paragraphs
 */
class ParagraphsEditModesTest extends ParagraphsTestBase {

  use FieldUiTestTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'image',
  ];

  /**
   * Tests the collapsed summary of paragraphs.
   */
  public function testCollapsedSummary() {
    $this->addParagraphedContentType('paragraphed_test', 'field_paragraphs');
    $this->loginAsAdmin(['create paragraphed_test content', 'edit any paragraphed_test content']);

    // Add a Paragraph type.
    $paragraph_type = 'image_text_paragraph';
    $this->addParagraphsType($paragraph_type);
    $this->addParagraphsType('text');
    static::fieldUIAddNewField('admin/structure/paragraphs_type/' . $paragraph_type, 'image', 'Image', 'image', [], ['settings[alt_field_required]' => FALSE]);
    static::fieldUIAddNewField('admin/structure/paragraphs_type/' . $paragraph_type, 'text', 'Text', 'text_long', [], []);

    // Set edit mode to closed.
    $this->drupalGet('admin/structure/types/manage/paragraphed_test/form-display');
    $this->drupalPostAjaxForm(NULL, [], "field_paragraphs_settings_edit");
    $edit = ['fields[field_paragraphs][settings_edit_form][settings][edit_mode]' => 'closed'];
    $this->drupalPostForm(NULL, $edit, t('Save'));

    // Add a paragraph.
    $this->drupalPostAjaxForm('node/add/paragraphed_test', [], 'field_paragraphs_image_text_paragraph_add_more');

    $text = 'Trust me I am an image';
    file_put_contents('temporary://myImage1.jpg', $text);

    // Create a node with an image and text.
    $edit = [
      'title[0][value]' => 'Test article',
      'field_paragraphs[0][subform][field_text][0][value]' => 'text_summary',
      'files[field_paragraphs_0_subform_field_image_0]' => drupal_realpath('temporary://myImage1.jpg'),
    ];
    $this->drupalPostForm(NULL, $edit, t('Save and publish'));

    // Assert the summary is correctly generated.
    $this->clickLink(t('Edit'));
    $this->assertRaw('<div class="paragraphs-collapsed-description">myImage1.jpg, text_summary');

    // Edit and remove alternative text.
    $this->drupalPostAjaxForm(NULL, [], 'field_paragraphs_0_edit');
    $edit = [
      'field_paragraphs[0][subform][field_image][0][alt]' => 'alternative_text_summary',
      'field_paragraphs[0][subform][field_image][0][width]' => 300,
      'field_paragraphs[0][subform][field_image][0][height]' => 300,
    ];
    $this->drupalPostAjaxForm(NULL, $edit, 'field_paragraphs_0_collapse');
    // Assert the summary is correctly generated.
    $this->assertRaw('<div class="paragraphs-collapsed-description">alternative_text_summary, text_summary');

    // Remove image.
    $this->drupalPostAjaxForm(NULL, [], 'field_paragraphs_0_edit');
    $this->drupalPostAjaxForm(NULL, [], 'field_paragraphs_0_subform_field_image_0_remove_button');
    $this->drupalPostForm(NULL, [], t('Save and keep published'));

    // Assert the summary is correctly generated.
    $this->clickLink(t('Edit'));
    $this->assertRaw('<div class="paragraphs-collapsed-description">text_summary');
  }

}
