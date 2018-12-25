<?php

namespace Drupal\paragraphs\Tests\Experimental;

/**
 * Tests paragraphs duplicate feature.
 *
 * @group paragraphs
 */
class ParagraphsExperimentalDuplicateFeatureTest extends ParagraphsExperimentalTestBase {

  public static $modules = [
    'node',
    'paragraphs',
    'field',
    'field_ui',
    'block',
    'paragraphs_test',
  ];

  /**
   * Tests duplicate paragraph feature.
   */
  public function testDuplicateButton() {
    $this->addParagraphedContentType('paragraphed_test', 'field_paragraphs');

    $this->loginAsAdmin(['create paragraphed_test content', 'edit any paragraphed_test content']);
    // Add a Paragraph type.
    $paragraph_type = 'text_paragraph';
    $this->addParagraphsType($paragraph_type);
    $this->addParagraphsType('text');

    // Add a text field to the text_paragraph type.
    static::fieldUIAddNewField('admin/structure/paragraphs_type/' . $paragraph_type, 'text', 'Text', 'text_long', [], []);
    $this->drupalPostAjaxForm('node/add/paragraphed_test', [], 'field_paragraphs_text_paragraph_add_more');

    // Create a node with a Paragraph.
    $text = 'recognizable_text';
    $edit = [
      'title[0][value]' => 'paragraphs_mode_test',
      'field_paragraphs[0][subform][field_text][0][value]' => $text,
    ];
    $this->drupalPostForm(NULL, $edit, t('Save and publish'));
    $node = $this->drupalGetNodeByTitle('paragraphs_mode_test');

    // Change edit mode to "closed".
    $this->setParagraphsWidgetMode('paragraphed_test', 'field_paragraphs', 'closed');
    $this->drupalGet('node/' . $node->id() . '/edit');

    // Click "Duplicate" button.
    $this->drupalPostAjaxForm(NULL, [], 'field_paragraphs_0_duplicate');
    $this->drupalPostAjaxForm(NULL, [], 'field_paragraphs_0_edit');
    $this->assertFieldByName('field_paragraphs[0][subform][field_text][0][value]', $text);
    $this->assertFieldByName('field_paragraphs[1][subform][field_text][0][value]', $text);

    // Save and check if both paragraphs are present.
    $this->drupalPostForm(NULL, $edit, t('Save and keep published'));
    $this->assertNoUniqueText($text);
  }

  /**
   * Tests duplicate paragraph feature with nested paragraphs.
   */
  public function testDuplicateButtonWithNesting() {
    $this->addParagraphedContentType('paragraphed_test', 'field_paragraphs');

    $this->loginAsAdmin(['create paragraphed_test content', 'edit any paragraphed_test content']);
    // Add nested Paragraph type.
    $nested_paragraph_type = 'nested_paragraph';
    $this->addParagraphsType($nested_paragraph_type);
    // Add text Paragraph type.
    $paragraph_type = 'text';
    $this->addParagraphsType($paragraph_type);

    // Add a text field to the text_paragraph type.
    static::fieldUIAddNewField('admin/structure/paragraphs_type/' . $paragraph_type, 'text', 'Text', 'text_long', [], []);

    // Add a ERR paragraph field to the nested_paragraph type.
    static::fieldUIAddNewField('admin/structure/paragraphs_type/' . $nested_paragraph_type, 'nested', 'Nested', 'field_ui:entity_reference_revisions:paragraph', [
      'settings[target_type]' => 'paragraph',
      'cardinality' => '-1',
    ], []);
    $this->drupalPostAjaxForm('node/add/paragraphed_test', [], 'field_paragraphs_nested_paragraph_add_more');

    // Create a node with a Paragraph.
    $edit = [
      'title[0][value]' => 'paragraphs_mode_test',
    ];
    $this->drupalPostForm(NULL, $edit, t('Save and publish'));
    $node = $this->drupalGetNodeByTitle('paragraphs_mode_test');

    // Add a text field to nested paragraph.
    $text = 'recognizable_text';
    $this->drupalPostAjaxForm('node/' . $node->id() . '/edit', [], 'field_paragraphs_0_subform_field_nested_text_add_more');
    $edit = [
      'field_paragraphs[0][subform][field_nested][0][subform][field_text][0][value]' => $text,
    ];
    $this->drupalPostForm(NULL, $edit, 'Save and keep published');

    // Switch mode to closed.
    $this->setParagraphsWidgetMode('paragraphed_test', 'field_paragraphs', 'closed');
    $this->drupalGet('node/' . $node->id() . '/edit');

    // Click "Duplicate" button.
    $this->drupalPostAjaxForm(NULL, [], 'field_paragraphs_0_duplicate');
    $this->drupalPostAjaxForm(NULL, [], 'field_paragraphs_0_edit');
    $this->assertFieldByName('field_paragraphs[0][subform][field_nested][0][subform][field_text][0][value]', $text);
    $this->assertFieldByName('field_paragraphs[1][subform][field_nested][0][subform][field_text][0][value]', $text);

    // Change the text paragraph value of duplicated nested paragraph.
    $second_paragraph_text = 'duplicated_text';
    $edit = [
      'field_paragraphs[1][subform][field_nested][0][subform][field_text][0][value]' => $second_paragraph_text,
    ];

    // Save and check if the changed text paragraph value of the duplicated
    // paragraph is not the same as in the original paragraph.
    $this->drupalPostForm(NULL, $edit, t('Save and keep published'));
    $this->assertUniqueText($text);
    $this->assertUniqueText($second_paragraph_text);
  }

}
