<?php

namespace Drupal\Tests\address\Kernel;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\filter\Entity\FilterFormat;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\Tests\taxonomy\Functional\TaxonomyTestTrait;
use Drupal\Tests\token\Kernel\KernelTestBase;

/**
 * Tests the country name token.
 *
 * @requires module token
 * @group address
 */
class CountryNameTokenTest extends KernelTestBase {

  use TaxonomyTestTrait;

  /**
   * A test format.
   *
   * @var \Drupal\filter\FilterFormatInterface
   */
  protected $testFormat;

  /**
   * Vocabulary for testing chained token support.
   *
   * @var \Drupal\taxonomy\VocabularyInterface
   */
  protected $vocabulary;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'node', 'field', 'filter', 'address', 'taxonomy', 'language', 'text',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->installEntitySchema('node');
    $this->installEntitySchema('taxonomy_term');
    $this->installEntitySchema('user');

    // Create the article content type with an address field.
    $node_type = NodeType::create([
      'type' => 'article',
    ]);
    $node_type->save();

    $field_storage = FieldStorageConfig::create([
      'field_name' => 'test_address',
      'entity_type' => 'node',
      'type' => 'address',
    ]);
    $field_storage->save();

    $field = FieldConfig::create([
      'field_name' => 'test_address',
      'entity_type' => 'node',
      'bundle' => 'article',
      'label' => 'Test address field',
    ]);
    $field->save();

    // Create a reference field with the same name on user.
    $field_storage = FieldStorageConfig::create([
      'field_name' => 'test_address',
      'entity_type' => 'user',
      'type' => 'entity_reference',
    ]);
    $field_storage->save();

    $field = FieldConfig::create([
      'field_name' => 'test_address',
      'entity_type' => 'user',
      'bundle' => 'user',
      'label' => 'Test address field',
    ]);
    $field->save();

    $this->testFormat = FilterFormat::create([
      'format' => 'test',
      'weight' => 1,
      'filters' => [
        'filter_html_escape' => ['status' => TRUE],
      ],
    ]);
    $this->testFormat->save();

    // Create a multi-value address field.
    $field_storage = FieldStorageConfig::create([
      'field_name' => 'multi_address_test',
      'entity_type' => 'node',
      'type' => 'address',
      'cardinality' => 2,
    ]);
    $field_storage->save();

    $this->field = FieldConfig::create([
      'field_name' => 'multi_address_test',
      'entity_type' => 'node',
      'bundle' => 'article',
    ])->save();

    // Add an untranslatable node reference field.
    FieldStorageConfig::create([
      'field_name' => 'test_reference',
      'type' => 'entity_reference',
      'entity_type' => 'node',
      'settings' => [
        'target_type' => 'node',
      ],
      'translatable' => FALSE,
    ])->save();
    FieldConfig::create([
      'field_name' => 'test_reference',
      'entity_type' => 'node',
      'bundle' => 'article',
      'label' => 'Test reference',
    ])->save();

    // Add an untranslatable taxonomy term reference field.
    $this->vocabulary = $this->createVocabulary();

    FieldStorageConfig::create([
      'field_name' => 'test_term_reference',
      'type' => 'entity_reference',
      'entity_type' => 'node',
      'settings' => [
        'target_type' => 'taxonomy_term',
      ],
      'translatable' => FALSE,
    ])->save();
    FieldConfig::create([
      'field_name' => 'test_term_reference',
      'entity_type' => 'node',
      'bundle' => 'article',
      'label' => 'Test term reference',
      'settings' => [
        'handler' => 'default:taxonomy_term',
        'handler_settings' => [
          'target_bundles' => [
            $this->vocabulary->id() => $this->vocabulary->id(),
          ],
        ],
      ],
    ])->save();

    // Add an address field to terms of the created vocabulary.
    $storage = FieldStorageConfig::create([
      'field_name' => 'term_address_field',
      'entity_type' => 'taxonomy_term',
      'type' => 'address',
    ]);
    $storage->save();
    $field = FieldConfig::create([
      'field_name' => 'term_address_field',
      'entity_type' => 'taxonomy_term',
      'bundle' => $this->vocabulary->id(),
    ]);
    $field->save();

    // Add a second language.
    $language = ConfigurableLanguage::create([
      'id' => 'de',
      'label' => 'German',
    ]);
    $language->save();
  }

  /**
   * Tests [entity:country_name] tokens.
   */
  public function testEntityCountryNameTokens() {
    // Create a node with a value in its fields and test its country_name tokens.
    $entity = Node::create([
      'title' => 'Test node title',
      'type' => 'article',
      'test_address' => [
        'country_code' => 'AD',
        'locality' => 'Canillo',
        'postal_code' => 'AD500',
        'address_line1' => 'C. Prat de la Creu, 62-64',
      ],
      'multi_address_test' => [
        [
          'country_code' => 'SV',
          'administrative_area' => 'Ahuachapán',
          'locality' => 'Ahuachapán',
          'address_line1' => 'Some Street 12',
        ],
        [
          'country_code' => 'US',
          'administrative_area' => 'CA',
          'address_line1' => '1098 Alta Ave',
          'postal_code' => '94043',
        ],
      ],
    ]);
    $entity->save();
    $this->assertTokens('node', ['node' => $entity], [
      'test_address:country_name' => 'Andorra',
      'multi_address_test:0:country_name' => 'El Salvador',
      'multi_address_test:1:country_name' => 'United States',
    ]);
  }

  /**
   * Test tokens for multilingual fields and entities.
   */
  public function testMultilingualFields() {
    // Create an english term and add a german translation for it.
    $term = $this->createTerm($this->vocabulary, [
      'name' => 'english-test-term',
      'langcode' => 'en',
      'term_address_field' => [
        'country_code' => 'US',
        'administrative_area' => 'CA',
        'address_line1' => '1098 Alta Ave',
        'postal_code' => '94043',
      ],
    ]);
    $term->addTranslation('de', [
      'name' => 'german-test-term',
      'term_address_field' => [
        'country_code' => 'US',
        'administrative_area' => 'CA',
        'address_line1' => '1098 Alta Ave',
        'postal_code' => '94043',
      ],
    ])->save();
    $german_term = $term->getTranslation('de');

    // Create an english node, add a german translation for it and add the
    // english term to the english node's entity reference field and the
    // german term to the german's entity reference field.
    $node = Node::create([
      'title' => 'english-node-title',
      'type' => 'article',
      'test_term_reference' => [
        'target_id' => $term->id(),
      ],
      'test_address' => [
        'country_code' => 'FR',
        'locality' => 'Paris',
        'postal_code' => '75014',
        'address_line1' => '218 rue de la Tombe-Issoire',
      ],
    ]);
    $node->addTranslation('de', [
      'title' => 'german-node-title',
      'test_term_reference' => [
        'target_id' => $german_term->id(),
      ],
      'test_address' => [
        'country_code' => 'FR',
        'locality' => 'Paris',
        'postal_code' => '75014',
        'address_line1' => '218 rue de la Tombe-Issoire',
      ],
    ])->save();

    // Verify the :country_name token of the english term the english node
    // refers to. Also verify the value of the term's country_name token.
    $this->assertTokens('node', ['node' => $node], [
      'test_term_reference:entity:term_address_field:country_name' => 'United States',
      'test_address:country_name' => 'France',
    ]);

    // Same test for the german node and its german term.
    $german_node = $node->getTranslation('de');
    $this->assertTokens('node', ['node' => $german_node], [
      'test_term_reference:entity:term_address_field:country_name' => 'Vereinigte Staaten',
      'test_address:country_name' => 'Frankreich',
    ]);

    // If the langcode is specified, it should have priority over the node's
    // active language.
    $tokens = [
      'test_term_reference:entity:term_address_field:country_name' => 'Vereinigte Staaten',
      'test_address:country_name' => 'Frankreich',
    ];
    $this->assertTokens('node', ['node' => $node], $tokens, ['langcode' => 'de']);
  }

}
