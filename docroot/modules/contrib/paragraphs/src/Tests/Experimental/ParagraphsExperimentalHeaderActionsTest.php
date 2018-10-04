<?php

namespace Drupal\paragraphs\Tests\Experimental;

use Symfony\Component\CssSelector\CssSelectorConverter;

/**
 * Tests collapse all button.
 *
 * @group paragraphs
 */
class ParagraphsExperimentalHeaderActionsTest extends ParagraphsExperimentalTestBase {

  /**
   * Tests header actions.
   */
  public function testHeaderActions() {

    // To test with a single header action, ensure the drag and drop action is
    // shown, even without the library.
    \Drupal::state()->set('paragraphs_test_dragdrop_force_show', TRUE);

    $this->addParagraphedContentType('paragraphed_test');

    $this->loginAsAdmin([
      'create paragraphed_test content',
      'edit any paragraphed_test content',
    ]);

    // Add a Paragraph type.
    $paragraph_type = 'text_paragraph';
    $this->addParagraphsType($paragraph_type);

    // Add a text field to the text_paragraph type.
    static::fieldUIAddNewField(
      'admin/structure/paragraphs_type/' . $paragraph_type,
      'text',
      'Text',
      'text_long',
      [],
      []
    );

    // Add 2 paragraphs and check for Collapse/Edit all button.
    $this->drupalGet('node/add/paragraphed_test');
    $this->assertNoRaw('field_paragraphs_collapse_all');
    $this->assertNoRaw('field_paragraphs_edit_all');
    $this->assertRaw('field_paragraphs_dragdrop_mode');

    // Ensure there is only a single table row.
    $table_rows = $this->xpath('//table[contains(@class, :class)]/tbody/tr', [':class' => 'field-multiple-table']);
    $this->assertEqual(1, count($table_rows));

    // Add second paragraph and check for Collapse/Edit all button.
    $this->drupalPostAjaxForm(NULL, [], 'field_paragraphs_text_paragraph_add_more');
    $this->assertRaw('field_paragraphs_collapse_all');
    $this->assertRaw('field_paragraphs_edit_all');

    $edit = [
      'field_paragraphs[0][subform][field_text][0][value]' => 'First text',
      'field_paragraphs[1][subform][field_text][0][value]' => 'Second text',
    ];
    $this->drupalPostForm(NULL, $edit, 'Collapse all');

    // Checks that after collapsing all we can edit again these paragraphs.
    $this->assertRaw('field_paragraphs_0_edit');
    $this->assertRaw('field_paragraphs_1_edit');

    // Test Edit all button.
    $this->drupalPostAjaxForm(NULL, [], 'field_paragraphs_edit_all');
    $this->assertRaw('field_paragraphs_0_collapse');
    $this->assertRaw('field_paragraphs_1_collapse');

    $edit = [
      'title[0][value]' => 'Test',
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertText('paragraphed_test Test has been created.');

    $node = $this->getNodeByTitle('Test');
    $this->drupalGet('node/' . $node->id());
    $this->clickLink('Edit');
    $this->assertNoText('No Paragraph added yet.');

    // Add and remove another paragraph.
    $this->drupalPostAjaxForm(NULL, [], 'field_paragraphs_text_paragraph_add_more');
    $edit = [
      'field_paragraphs[2][subform][field_text][0][value]' => 'Third text',
    ];
    $this->drupalPostAjaxForm(NULL, $edit, 'field_paragraphs_2_remove');

    // Check that pressing "Collapse all" does not restore the removed
    // paragraph.
    $this->drupalPostAjaxForm(NULL, [], 'field_paragraphs_edit_all');
    $this->assertText('First text');
    $this->assertText('Second text');
    $this->assertNoText('Third text');

    // Check that pressing "Edit all" does not restore the removed paragraph,
    // either.
    $this->drupalPostAjaxForm(NULL, [], 'field_paragraphs_collapse_all');
    $this->assertText('First text');
    $this->assertText('Second text');
    $this->assertNoText('Third text');
  }

  /**
   * Tests that header actions works fine with nesting.
   */
  public function testHeaderActionsWithNesting() {
    $this->addParagraphedContentType('paragraphed_test');

    $this->loginAsAdmin([
      'create paragraphed_test content',
      'edit any paragraphed_test content',
    ]);

    // Add Paragraph types.
    $nested_paragraph_type = 'nested_paragraph';
    $this->addParagraphsType($nested_paragraph_type);
    $paragraph_type = 'text';
    $this->addParagraphsType($paragraph_type);

    // Add a text field to the text_paragraph type.
    static::fieldUIAddNewField(
      'admin/structure/paragraphs_type/' . $paragraph_type,
      'text',
      'Text',
      'text_long',
      [],
      []
    );

    // Add a ERR paragraph field to the nested_paragraph type.
    static::fieldUIAddNewField(
      'admin/structure/paragraphs_type/' . $nested_paragraph_type,
      'nested',
      'Nested',
      'field_ui:entity_reference_revisions:paragraph', [
        'settings[target_type]' => 'paragraph',
        'cardinality' => '-1',
      ],
      []
    );

    // Checks that Collapse/Edit all button is presented.
    $this->drupalGet('node/add/paragraphed_test');
    $this->drupalPostAjaxForm(NULL, [], 'field_paragraphs_nested_paragraph_add_more');
    $this->drupalPostAjaxForm(NULL, [], 'field_paragraphs_text_add_more');
    $this->assertRaw('field_paragraphs_collapse_all');
    $this->assertRaw('field_paragraphs_edit_all');

    $this->drupalPostAjaxForm(NULL, [], 'field_paragraphs_text_add_more');
    $this->drupalPostAjaxForm(NULL, [], 'field_paragraphs_0_subform_field_nested_text_add_more');
    $this->assertNoRaw('field_paragraphs_0_collapse_all');
    $this->assertNoRaw('field_paragraphs_0_edit_all');
    $edit = [
      'field_paragraphs[0][subform][field_nested][0][subform][field_text][0][value]' => 'Nested text',
      'field_paragraphs[1][subform][field_text][0][value]' => 'Second text paragraph',
    ];
    $this->drupalPostForm(NULL, $edit, 'Collapse all');
    $this->assertRaw('field-paragraphs-0-edit');
    $this->assertFieldByXPath((new CssSelectorConverter())->toXPath('[name="field_paragraphs_1_edit"] + .paragraphs-dropdown'));
    $this->drupalPostAjaxForm(NULL, [], 'field_paragraphs_edit_all');
    $this->assertRaw('field-paragraphs-0-collapse');

    $edit = [
      'title[0][value]' => 'Test',
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertText('paragraphed_test Test has been created.');

    $node = $this->getNodeByTitle('Test');
    $this->drupalGet('node/' . $node->id());
    $this->clickLink('Edit');
    $this->assertNoText('No Paragraph added yet.');
  }

  /**
   * Tests header actions with multi fields.
   */
  public function testHeaderActionsWithMultiFields() {
    $this->addParagraphedContentType('paragraphed_test');
    $this->loginAsAdmin([
      'create paragraphed_test content',
      'edit any paragraphed_test content',
    ]);
    $this->drupalGet('/admin/structure/types/manage/paragraphed_test/fields/add-field');

    // Add a Paragraph type.
    $paragraph_type = 'text_paragraph';
    $this->addParagraphsType($paragraph_type);
    $edit = [
      'new_storage_type' => 'field_ui:entity_reference_revisions:paragraph',
      'label' => 'Second paragraph',
      'field_name' => 'second',
    ];
    $this->drupalPostForm(NULL, $edit, 'Save and continue');
    $this->drupalPostForm(NULL, [], 'Save field settings');
    $this->drupalPostForm(NULL, [], 'Save settings');

    $this->drupalGet('/admin/structure/types/manage/paragraphed_test/form-display');
    $edit = [
      'fields[field_second][type]' => 'paragraphs',
    ];
    $this->drupalPostForm(NULL, $edit, 'Save');

    // Add a text field to the text_paragraph type.
    static::fieldUIAddNewField(
      'admin/structure/paragraphs_type/' . $paragraph_type,
      'text',
      'Text',
      'text_long',
      [],
      []
    );

    $this->drupalGet('node/add/paragraphed_test');
    $this->drupalPostAjaxForm(NULL, [], 'field_paragraphs_text_paragraph_add_more');
    $this->drupalPostAjaxForm(NULL, [], 'field_second_text_paragraph_add_more');

    // Checks that we have Collapse\Edit all for each field.
    $this->assertRaw('field_paragraphs_collapse_all');
    $this->assertRaw('field_paragraphs_edit_all');
    $this->assertRaw('field_second_collapse_all');
    $this->assertRaw('field_second_edit_all');

    $edit = [
      'field_second[0][subform][field_text][0][value]' => 'Second field',
    ];
    $this->drupalPostAjaxForm(NULL, $edit, 'field_second_collapse_all');

    // Checks that we collapsed only children from second field.
    $this->assertNoRaw('field_paragraphs_0_edit');
    $this->assertRaw('field_second_0_edit');

    $this->drupalPostAjaxForm(NULL, [], 'field_paragraphs_collapse_all');
    $this->assertRaw('field_paragraphs_0_edit');
    $this->assertRaw('field_second_0_edit');

    $this->drupalPostAjaxForm(NULL, [], 'field_second_edit_all');
    $this->assertRaw('field_second_0_collapse');

    $edit = [
      'title[0][value]' => 'Test',
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertText('paragraphed_test Test has been created.');

    $node = $this->getNodeByTitle('Test');
    $this->drupalGet('node/' . $node->id());
    $this->clickLink('Edit');
    $this->assertNoText('No Paragraph added yet.');
  }

}
