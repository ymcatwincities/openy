<?php

namespace Drupal\paragraphs\Tests;

use Drupal\Core\Entity\Entity;
use Drupal\field_ui\Tests\FieldUiTestTrait;
use Drupal\simpletest\WebTestBase;

/**
 * Tests the configuration of paragraphs.
 *
 * @group paragraphs
 */
class ParagraphsPreviewTest extends WebTestBase {

  use FieldUiTestTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array(
    'node',
    'paragraphs',
    'field',
    'image',
    'field_ui',
    'block',
  );

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    // Create paragraphs and article content types.
    $this->drupalCreateContentType(array('type' => 'article', 'name' => 'Article'));
    // Place the breadcrumb, tested in fieldUIAddNewField().
    $this->drupalPlaceBlock('local_tasks_block');
    $this->drupalPlaceBlock('local_actions_block');
    $this->drupalPlaceBlock('system_breadcrumb_block');
  }

  /**
   * Tests the revision of paragraphs.
   */
  public function testParagraphsPreview() {
    $admin_user = $this->drupalCreateUser(array(
      'administer nodes',
      'administer content types',
      'administer node fields',
      'administer node display',
      'administer paragraphs types',
      'administer paragraph fields',
      'administer node form display',
      'create article content',
      'edit any article content',
      'delete any article content',
    ));
    $this->drupalLogin($admin_user);

    $this->drupalGet('admin/structure/paragraphs_type');
    $this->clickLink(t('Add paragraphs type'));
    // Create paragraph type Headline + Block.
    $edit = array(
      'label' => 'Text',
      'id' => 'text',
    );
    $this->drupalPostForm(NULL, $edit, t('Save and manage fields'));
    // Create field types for the text.
    $this->fieldUIAddNewField('admin/structure/paragraphs_type/text', 'text', 'Text', 'text', array(), array());
    $this->assertText('Saved Text configuration.');

    // Create an article with paragraphs field.
    static::fieldUIAddNewField('admin/structure/types/manage/article', 'paragraphs', 'Paragraphs', 'entity_reference_revisions', array(
      'settings[target_type]' => 'paragraph',
      'cardinality' => '-1',
    ), array(
      'settings[handler_settings][target_bundles_drag_drop][text][enabled]' => TRUE,
    ));
    // Configure article fields.
    $this->drupalGet('admin/structure/types/manage/article/fields');
    $this->clickLink(t('Manage form display'));
    $this->drupalPostForm(NULL, array('fields[field_paragraphs][type]' => 'entity_reference_paragraphs'), t('Save'));

    $test_text_1 = 'dummy_preview_text_1';
    $test_text_2 = 'dummy_preview_text_2';
    // Create node with two paragraphs.
    $this->drupalGet('node/add/article');
    $this->drupalPostAjaxForm(NULL, array(), 'field_paragraphs_text_add_more');
    // Set the value of the paragraphs.
    $edit = [
      'title[0][value]' => 'Page_title',
      'field_paragraphs[0][subform][field_text][0][value]' => $test_text_1,
    ];
    // Preview the article.
    $this->drupalPostForm(NULL, $edit, t('Preview'));
    // Check if the text is displayed.
    $this->assertRaw($test_text_1);

    // Go back to the editing form.
    $this->clickLink('Back to content editing');

    $paragraph_1 = $this->xpath('//*[@id="edit-field-paragraphs-0-subform-field-text-0-value"]')[0];
    $this->assertEqual($paragraph_1['value'], $test_text_1);

    $this->drupalPostForm(NULL, $edit, t('Save and publish'));

    $this->clickLink('Edit');
    $this->drupalPostAjaxForm(NULL, array(), 'field_paragraphs_text_add_more');
    $edit = [
      'field_paragraphs[1][subform][field_text][0][value]' => $test_text_2,
    ];
    // Preview the article.
    $this->drupalPostForm(NULL, $edit, t('Preview'));
    $this->assertRaw($test_text_1);
    $this->assertRaw($test_text_2);

    // Go back to the editing form.
    $this->clickLink('Back to content editing');
    $new_test_text_2 = 'less_dummy_preview_text_2';

    $edit = [
      'field_paragraphs[1][subform][field_text][0][value]' => $new_test_text_2,
    ];
    // Preview the article.
    $this->drupalPostForm(NULL, $edit, t('Preview'));
    $this->assertRaw($test_text_1);
    // @todo Uncomment the lines after core issue is fixed.
    // https://www.drupal.org/node/2548713
    //$this->assertRaw($new_test_text_2);
    // Go back to the editing form.
    $this->clickLink('Back to content editing');
    $paragraph_1 = $this->xpath('//*[@id="edit-field-paragraphs-0-subform-field-text-0-value"]')[0];
    //$paragraph_2 = $this->xpath('//*[@id="edit-field-paragraphs-1-subform-field-text-0-value"]')[0];
    $this->assertEqual($paragraph_1['value'], $test_text_1);
    //$this->assertEqual($paragraph_2['value'], $new_test_text_2);
    $this->drupalPostForm(NULL, [], t('Save and keep published'));

    $this->assertRaw($test_text_1);
    //$this->assertRaw($new_test_text_2);
    $this->assertRaw('Page_title');
  }

}
