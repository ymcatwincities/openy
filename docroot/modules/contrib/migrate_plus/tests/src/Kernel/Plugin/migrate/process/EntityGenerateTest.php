<?php

namespace Drupal\Tests\migrate_plus\Kernel\Plugin\migrate\process;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\field\Tests\EntityReference\EntityReferenceTestTrait;
use Drupal\KernelTests\KernelTestBase;
use Drupal\migrate\MigrateExecutable;
use Drupal\migrate\MigrateMessageInterface;
use Drupal\migrate\Plugin\Migration;
use Drupal\node\Entity\NodeType;
use Drupal\taxonomy\Entity\Vocabulary;

/**
 * Tests the migration plugin.
 *
 * @coversDefaultClass \Drupal\migrate_plus\Plugin\migrate\process\EntityGenerate
 * @group migrate_plus
 */
class EntityGenerateTest extends KernelTestBase implements MigrateMessageInterface {

  use EntityReferenceTestTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'migrate_plus',
    'migrate',
    'user',
    'system',
    'node',
    'taxonomy',
    'field',
    'text',
    'filter',
  ];

  /**
   * The bundle used in this test.
   *
   * @var string
   */
  protected $bundle = 'page';

  /**
   * The name of the field used in this test.
   *
   * @var string
   */
  protected $fieldName = 'field_entity_reference';

  /**
   * The vocabulary id.
   *
   * @var string
   */
  protected $vocabulary = 'fruit';

  /**
   * @var \Drupal\migrate\Plugin\MigrationPluginManager $migrationManager
   *
   * The migration plugin manager.
   */
  protected $migrationPluginManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    // Create article content type.
    $values = [
      'type' => $this->bundle,
      'name' => 'Page',
    ];
    $node_type = NodeType::create($values);
    $node_type->save();

    $this->installEntitySchema('node');
    $this->installEntitySchema('taxonomy_term');
    $this->installEntitySchema('taxonomy_vocabulary');
    $this->installEntitySchema('user');
    $this->installSchema('system', ['sequences']);
    $this->installSchema('user', 'users_data');
    $this->installConfig($this->modules);

    // Create a vocabulary.
    $vocabulary = Vocabulary::create([
      'name' => $this->vocabulary,
      'description' => $this->vocabulary,
      'vid' => $this->vocabulary,
      'langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED,
    ]);
    $vocabulary->save();

    // Create a field.
    $this->createEntityReferenceField(
      'node',
      $this->bundle,
      $this->fieldName,
      'Term reference',
      'taxonomy_term',
      'default',
      ['target_bundles' => [$this->vocabulary]],
      FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED
    );

    $this->migrationPluginManager = \Drupal::service('plugin.manager.migration');
  }

  /**
   * Tests generating an entity.
   *
   * @dataProvider transformDataProvider
   *
   * @covers ::transform
   */
  public function testTransform(array $definition, array $expected, array $preSeed = []) {
    // Pre seed some test data.
    foreach ($preSeed as $storageName => $values) {
      /** @var \Drupal\Core\Entity\ContentEntityStorageInterface $storage */
      $storage = $this->container
        ->get('entity_type.manager')
        ->getStorage($storageName);
      $entity = $storage->create($values);
      $entity->save();
    }

    /** @var Migration $migration */
    $migration = $this->migrationPluginManager->createStubMigration($definition);
    /** @var EntityStorageBase $storage */
    $storage = $this->readAttribute($migration->getDestinationPlugin(), 'storage');
    $migrationExecutable = (new MigrateExecutable($migration, $this));
    $migrationExecutable->import();

    foreach ($expected as $row) {
      $entity = $storage->load($row['id']);
      $properties = array_diff_key($row, array_flip(['id']));
      foreach ($properties as $property => $value) {
        if (is_array($value)) {
          foreach ($value as $key => $expectedValue) {
            if (empty($expectedValue)) {
              $this->assertEmpty($entity->{$property}->getValue(), "Expected value is empty but field $property is not empty.");
            }
            elseif ($entity->{$property}->getValue()) {
              $this->assertEquals($expectedValue, $entity->{$property}[0]->entity->$key->value);
            }
            else {
              $this->fail("Expected value: $expectedValue does not exist in $property.");
            }
          }
        }
        else {
          $this->assertNotEmpty($entity, 'Entity with label ' . $row[$property] .' is empty');
          $this->assertEquals($row[$property], $entity->label());
        }
      }
    }
  }

  /**
   * Provides multiple migration definitions for "transform" test.
   */
  public function transformDataProvider() {
    return [
      'no arguments' => [
        'definition' => [
          'source' => [
            'plugin' => 'embedded_data',
            'data_rows' => [
              [
                'id' => 1,
                'title' => 'content item 1',
                'term' => 'Apples',
              ],
              [
                'id' => 2,
                'title' => 'content item 2',
                'term' => 'Bananas',
              ],
              [
                'id' => 3,
                'title' => 'content item 3',
                'term' => 'Grapes',
              ],
            ],
            'ids' => [
              'id' => ['type' => 'integer'],
            ],
          ],
          'process' => [
            'id' => 'id',
            'type' => [
              'plugin' => 'default_value',
              'default_value' => $this->bundle,
            ],
            'title' => 'title',
            $this->fieldName => [
              'plugin' => 'entity_generate',
              'source' => 'term',
            ],
          ],
          'destination' => [
            'plugin' => 'entity:node',
          ],
        ],
        'expected' => [
          'row 1' => [
            'id' => 1,
            'title' => 'content item 1',
            $this->fieldName => [
              'tid' => 2,
              'name' => 'Apples',
            ],
          ],
          'row 2' => [
            'id' => 2,
            'title' => 'content item 2',
            $this->fieldName => [
              'tid' => 3,
              'name' => 'Bananas',
            ],
          ],
          'row 3' => [
            'id' => 3,
            'title' => 'content item 3',
            $this->fieldName => [
              'tid' => 1,
              'name' => 'Grapes',
            ],
          ],
        ],
        'pre seed' => [
          'taxonomy_term' => [
            'name' => 'Grapes',
            'vid' => $this->vocabulary,
            'langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED,
          ],
        ],
      ],
      'no arguments_lookup_only' => [
        'definition' => [
          'source' => [
            'plugin' => 'embedded_data',
            'data_rows' => [
              [
                'id' => 1,
                'title' => 'content item 1',
                'term' => 'Apples',
              ],
              [
                'id' => 2,
                'title' => 'content item 2',
                'term' => 'Bananas',
              ],
              [
                'id' => 3,
                'title' => 'content item 3',
                'term' => 'Grapes',
              ],
            ],
            'ids' => [
              'id' => ['type' => 'integer'],
            ],
          ],
          'process' => [
            'id' => 'id',
            'type' => [
              'plugin' => 'default_value',
              'default_value' => $this->bundle,
            ],
            'title' => 'title',
            $this->fieldName => [
              'plugin' => 'entity_lookup',
              'source' => 'term',
            ],
          ],
          'destination' => [
            'plugin' => 'entity:node',
          ],
        ],
        'expected' => [
          'row 1' => [
            'id' => 1,
            'title' => 'content item 1',
            $this->fieldName => [
              'tid' => NULL,
              'name' => NULL,
            ],
          ],
          'row 2' => [
            'id' => 2,
            'title' => 'content item 2',
            $this->fieldName => [
              'tid' => NULL,
              'name' => NULL,
            ],
          ],
          'row 3' => [
            'id' => 3,
            'title' => 'content item 3',
            $this->fieldName => [
              'tid' => 1,
              'name' => 'Grapes',
            ],
          ],
        ],
        'pre seed' => [
          'taxonomy_term' => [
            'name' => 'Grapes',
            'vid' => $this->vocabulary,
            'langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED,
          ],
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function display($message, $type = 'status') {
    $this->assertTrue($type == 'status', $message);
  }

}
