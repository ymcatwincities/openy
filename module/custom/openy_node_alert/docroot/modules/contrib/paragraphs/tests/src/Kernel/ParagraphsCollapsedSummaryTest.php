<?php

namespace Drupal\Tests\paragraphs\Kernel;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\field_ui\Tests\FieldUiTestTrait;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\paragraphs\Entity\ParagraphsType;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests the collapsed summary options.
 *
 * @group paragraphs
 */
class ParagraphsCollapsedSummaryTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'paragraphs',
    'user',
    'system',
    'field',
    'entity_reference_revisions',
    'paragraphs_test',
    'file',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installEntitySchema('user');
    $this->installEntitySchema('paragraph');
    $this->installSchema('system', ['sequences']);
    \Drupal::moduleHandler()->loadInclude('paragraphs', 'install');

    // Create a text paragraph type with test_text_color plugin enabled.
    $paragraph_type = ParagraphsType::create(array(
      'label' => 'text_paragraph',
      'id' => 'text_paragraph',
      'behavior_plugins' => [
        'test_text_color' => [
          'enabled' => TRUE,
        ]
      ],
    ));
    $paragraph_type->save();
    $this->addParagraphsField('text_paragraph', 'text', 'string');

    // Add a nested Paragraph type.
    $paragraphs_type = ParagraphsType::create([
      'id' => 'nested_paragraph',
      'label' => 'nested_paragraph',
    ]);
    $paragraphs_type->save();
    $this->addParagraphsField('nested_paragraph', 'nested_paragraph_field', 'entity_reference_revisions', ['target_type' => 'paragraph']);
  }

  /**
   * Tests the collapsed summary additional options.
   */
  public function testCollapsedSummaryOptions() {
    // Create a paragraph and set its feature settings.
    $paragraph = Paragraph::create([
      'type' => 'text_paragraph',
      'text' => 'Example text for a text paragraph',
    ]);
    $feature_settings = [
      'test_text_color' => [
        'text_color' => 'red'
      ],
    ];
    $paragraph->setAllBehaviorSettings($feature_settings);
    $paragraph->save();

    // Load the paragraph and assert its stored feature settings.
    $paragraph = Paragraph::load($paragraph->id());
    $this->assertEquals($paragraph->getAllBehaviorSettings(), $feature_settings);
    $this->assertEquals($paragraph->getSummary(), 'Example text for a text paragraph, Text color: red');

    // Check the summary and the additional options.
    $paragraph_1 = Paragraph::create([
      'type' => 'nested_paragraph',
      'nested_paragraph_field' => [$paragraph],
    ]);
    $paragraph_1->save();
    $this->assertEquals($paragraph_1->getSummary(), '1 child, Example text for a text paragraph, Text color: red');
    $this->assertEquals($paragraph_1->getSummary(['show_behavior_summary' => FALSE]), '1 child, Example text for a text paragraph');
    $this->assertEquals($paragraph_1->getSummary(['depth_limit' => 0]), '');
  }

  /**
   * Tests nested paragraph summary.
   */
  public function testNestedParagraphSummary() {
    // Create a text paragraph.
    $paragraph_text_1 = Paragraph::create([
      'type' => 'text_paragraph',
      'text' => 'Text paragraph on nested level',
    ]);
    $paragraph_text_1->save();

    // Add a nested paragraph with the text inside.
    $paragraph_nested_1 = Paragraph::create([
      'type' => 'nested_paragraph',
      'nested_paragraph_field' => [$paragraph_text_1],
    ]);
    $paragraph_nested_1->save();

    // Create a new text paragraph.
    $paragraph_text_2 = Paragraph::create([
      'type' => 'text_paragraph',
      'text' => 'Text paragraph on top level',
    ]);
    $paragraph_text_2->save();

    // Add a nested paragraph with the new text and nested paragraph inside.
    $paragraph_nested_2 = Paragraph::create([
      'type' => 'nested_paragraph',
      'nested_paragraph_field' => [$paragraph_text_2, $paragraph_nested_1],
    ]);
    $paragraph_nested_2->save();
    $this->assertEquals($paragraph_nested_2->getSummary(['show_behavior_summary' => FALSE]), '2 children, Text paragraph on top level');
    $this->assertEquals($paragraph_nested_2->getSummary(['show_behavior_summary' => FALSE, 'depth_limit' => 2]), '2 children, Text paragraph on top level, 1 child, Text paragraph on nested level');
  }

  /**
   * Adds a field to a given paragraph type.
   *
   * @param string $paragraph_type_name
   *   Paragraph type name to be used.
   * @param string $field_name
   *   Paragraphs field name to be used.
   * @param string $field_type
   *   Type of the field.
   * @param array $field_edit
   *   Edit settings for the field.
   */
  protected function addParagraphsField($paragraph_type_name, $field_name, $field_type, $field_edit = []) {
    // Add a paragraphs field.
    $field_storage = FieldStorageConfig::create([
      'field_name' => $field_name,
      'entity_type' => 'paragraph',
      'type' => $field_type,
      'cardinality' => '-1',
      'settings' => $field_edit
    ]);
    $field_storage->save();
    $field = FieldConfig::create([
      'field_storage' => $field_storage,
      'bundle' => $paragraph_type_name,
      'settings' => [
        'handler' => 'default:paragraph',
        'handler_settings' => ['target_bundles' => NULL],
      ],
    ]);
    $field->save();
  }
}
