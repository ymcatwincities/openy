<?php

namespace Drupal\Tests\datalayer\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Language\Language;
use Drupal\node\Entity\Node;
use Drupal\user\Entity\User;
use Drupal\taxonomy\Entity\Term;
use Drupal\node\Entity\NodeType;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Symfony\Component\Routing\Route;

/**
 * Kernel test cases for datalayer module.
 *
 * @group DataLayer
 */
class DataLayerKernelTest extends KernelTestBase {

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = [
    'datalayer',
    'system',
    'user',
    'node',
    'taxonomy',
    'field',
    'text',
  ];

  /**
   * A test user.
   *
   * @var \Drupal\user\User
   */
  protected $user;


  /**
   * A test node.
   *
   * @var \Drupal\node\Node
   */
  protected $node;


  /**
   * A test taxonomy term.
   *
   * @var \Drupal\taxonomy\Term
   */
  protected $term;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->installEntitySchema('user');
    $this->installEntitySchema('node');
    $this->installEntitySchema('taxonomy_term');
    $this->installConfig(['system', 'datalayer']);

    $this->setupMockFieldConfig();
  }

  /**
   * Create field definitions for taxonomy term.
   */
  public function setupMockFieldConfig() {
    NodeType::create([
      'type' => 'article',
    ])->save();

    FieldStorageConfig::create([
      'entity_type' => 'node',
      'field_name' => 'field_tags',
      'type' => 'entity_reference',
      'settings' => [
        'target_type' => 'taxonomy_term',
      ],
    ])->save();

    FieldConfig::create([
      'field_name' => 'field_tags',
      'entity_type' => 'node',
      'bundle' => 'article',
    ])->save();
  }

  /**
   * Test DataLayer Defaults function.
   */
  public function testDataLayerDefaults() {
    $this->assertEqual(
      [
        'drupalLanguage' => Language::$defaultValues['id'],
        'drupalCountry' => $this->config('system.date')->get('country.default'),
        'siteName' => $this->config('system.site')->get('name'),
      ],
      _datalayer_defaults()
    );
  }

  /**
   * Test DataLayer Add Will Add Data.
   */
  public function testDataLayerAddWillAddData() {
    $this->setupEmptyDataLayer();
    $this->assertEqual(
      ['foo' => 'bar'],
      datalayer_add(['foo' => 'bar'])
    );
  }

  /**
   * Test DataLayer Add Does Not Overwrite By Default.
   */
  public function testDataLayerAddDoesNotOverwriteByDefault() {
    $this->setupEmptyDataLayer();
    datalayer_add(['foo' => 'bar']);
    $this->assertEqual(
      ['foo' => 'bar'],
      datalayer_add(['foo' => 'baz'])
    );
  }

  /**
   * Test DataLayer Add Will Overwrite With Flag.
   */
  public function testDataLayerAddWillOverwriteWithFlag() {
    $this->setupEmptyDataLayer();
    datalayer_add(['foo' => 'bar']);
    $this->assertEqual(
      ['foo' => 'baz'],
      datalayer_add(['foo' => 'baz'], TRUE)
    );
  }

  /**
   * Test DataLayer Menu Get Any Object.
   *
   * Returns False Without Load Functions.
   */
  public function testDataLayerMenuGetAnyObjectReturnsNullWithoutContentEntityInterface() {
    $result = _datalayer_menu_get_any_object();
    $this->assertNull($result);
  }

  /**
   * Test DataLayer Menu Get Any Object Returns Object.
   */
  public function testDataLayerMenuGetAnyObjectReturnsObject() {
    $this->setupMockRouteMatch();
    $object = _datalayer_menu_get_any_object();
    $this->assertTrue(is_object($object));
    $this->assertEqual($object->getEntityTypeId(), 'node');
  }

  /**
   * Test DataLayer Get Entity Terms Returns Empty Array.
   */
  public function testDataLayerGetEntityTermsReturnsEmptyArray() {
    $this->setupMockNode();
    $terms = _datalayer_get_entity_terms($this->node);
    $this->assertEqual([], $terms);
  }

  /**
   * Test DataLayer Get Entity Terms Returns Term Array.
   */
  public function testDataLayerGetEntityTermsReturnsTermArray() {
    $this->setupMockNodeWithTerm();
    $terms = _datalayer_get_entity_terms($this->node);
    $this->assertEqual(['tags' => [1 => 'someTag']], $terms);
  }

  /**
   * Test DataLayer Get Entity Terms Returns Entity Data Array.
   */
  public function testDataLayerGetEntityDataReturnsEntityDataArray() {
    $this->setupEmptyDataLayer();
    $this->setupMockNodeWithTerm();
    $entity_data = _datalayer_get_entity_data($this->node);
    $this->assertEqual(
      $this->getExpectedEntityDataArray(),
      $entity_data
    );
  }

  /**
   * Setup user.
   */
  public function setupMockUser() {
    $data = [
      'uid'      => 1,
      'name'     => 'admin',
      'password' => 'password',
      'langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED,
    ];

    $this->user = User::create($data);
    $this->user->save();
  }

  /**
   * Setup empty datalayer.
   */
  public function setupEmptyDataLayer() {
    $data = &drupal_static('datalayer_add', []);
  }

  /**
   * Setup mock node.
   */
  public function setupMockNode() {
    $this->setupMockUser();
    // Create a node.
    $data = [
      'uid'      => $this->user->id(),
      'name'     => 'admin',
      'type'     => 'article',
      'langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED,
      'title'    => 'My Article',
      'status' => 1,
      'nid' => 1,
      'vid' => 1,
      'type' => 'article',
      'created' => '1435019805',
    ];
    $this->node = Node::create($data);
    $this->node->save();
  }

  /**
   * Setup mock node.
   */
  public function setupMockNodeWithTerm() {
    $this->setupMockNode();
    $this->setupMockEntityTerm();
    $this->node->set('field_tags', ['target_id' => $this->term->id()]);
  }

  /**
   * Setup Mock RouteMatch.
   */
  public function setupMockRouteMatch() {
    $this->setupMockNode();
    $request = &drupal_static(__FUNCTION__);
    if (!$request) {
      $request = \Drupal::request()->create('/node/1', 'GET', ['node' => $this->node]);
      $request->attributes->set(RouteObjectInterface::ROUTE_OBJECT, new Route('/node/{node}', ['node' => 1]));
      $request->attributes->set(RouteObjectInterface::ROUTE_NAME, 'entity.node.canonical');
      $request->attributes->set('node', $this->node);
      $this->container->get('request_stack')->push($request);
    }
  }

  /**
   * Setup Mock Entity Terms.
   */
  public function setupMockEntityTerm() {
    $this->term = Term::create([
      'name' => 'someTag',
      'vid' => 'tags',
      'tid' => 1,
    ]);
    $this->term->save();
  }

  /**
   * Get expected entity data array.
   */
  public function getExpectedEntityDataArray() {
    return [
      'entityType' => 'node',
      'entityBundle' => 'article',
      'entityId' => '1',
      'entityUuid' => $this->node->uuid->getString(),
      'entityTitle' => 'My Article',
      'entityLangcode' => 'und',
      'entityVid' => '1',
      'entityName' => 'admin',
      'entityUid' => '1',
      'entityCreated' => '1435019805',
      'entityStatus' => '1',
      'entityTaxonomy' => [
        'tags' => [
          1 => 'someTag',
        ],
      ],
    ];
  }

}
