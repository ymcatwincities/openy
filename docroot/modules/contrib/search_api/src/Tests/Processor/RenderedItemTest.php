<?php

/**
 * @file
 * Contains \Drupal\search_api\Tests\Processor\RenderedItemTest.
 */

namespace Drupal\search_api\Tests\Processor;

use Drupal\Core\TypedData\DataDefinition;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\search_api\Utility;
use Drupal\user\Entity\Role;
use Drupal\user\Entity\User;

/**
 * Tests the "Rendered item" processor.
 *
 * @group search_api
 *
 * @see \Drupal\search_api\Plugin\search_api\processor\RenderedItem
 */
class RenderedItemTest extends ProcessorTestBase {

  /**
   * List of nodes which are published.
   *
   * @var \Drupal\node\Entity\Node[]
   */
  protected $nodes;

  /**
   * Data for all nodes which are published.
   *
   * @var array
   */
  protected $nodeData;

  /**
   * Modules to enable for this test.
   *
   * @var string[]
   */
  public static $modules = array('user', 'node', 'search_api','search_api_db', 'search_api_test_backend', 'comment', 'system');

  /**
   * Performs setup tasks before each individual test method is run.
   */
  public function setUp() {
    parent::setUp('rendered_item');

    // Load configuration and needed schemas. (The necessary schemas for using
    // nodes are already installed by the parent method.)
    $this->installConfig(array('system', 'filter', 'node', 'comment'));
    $this->installSchema('system', array('router'));
    \Drupal::service('router.builder')->rebuild();

    // Create a node type for testing.
    $type = NodeType::create(array(
      'type' => 'page',
      'name' => 'page',
    ));
    $type->save();
    node_add_body_field($type);

    // Create anonymous user role.
    $role = Role::create(array(
      'id' => 'anonymous',
      'label' => 'anonymous',
    ));
    $role->save();

    // Insert the anonymous user into the database.
    $anonymous_user = User::create(array(
      'uid' => 0,
      'name' => '',
    ));
    $anonymous_user->save();

    // Default node values for all nodes we create below.
    $this->nodeData = array(
      'status' => NODE_PUBLISHED,
      'type' => 'page',
      'title' => $this->randomMachineName(8),
      'body' => array('value' => $this->randomMachineName(32), 'summary' => $this->randomMachineName(16), 'format' => 'plain_text'),
      'uid' => $anonymous_user->id(),
    );

    // Create some test nodes with valid user on it for rendering a picture.
    $this->nodes[0] = Node::create($this->nodeData);
    $this->nodes[0]->save();
    $this->nodes[1] = Node::create($this->nodeData);
    $this->nodes[1]->save();

    // Set proper configuration for the tested processor.
    $config = $this->processor->getConfiguration();
    $config['view_mode'] = array(
      'entity:node' => [
        'page' => 'full',
        'article' => 'teaser',
      ],
      'entity:user' => 'compact',
      'entity:comment' => 'teaser',
    );
    $config['roles'] = array($role->id());
    $this->processor->setConfiguration($config);

    // Enable the processor's field on the index.
    $fields = $this->index->getOption('fields');
    $fields['rendered_item'] = array(
      'type' => 'string',
    );
    $this->index->setOption('fields', $fields);
    $this->index->save();

    $this->index->getDatasources();

    // Enable the classy theme as the tests rely on markup from that.
    \Drupal::service('theme_handler')->install(array('classy'));
    \Drupal::theme()->setActiveTheme(\Drupal::service('theme.initialization')->initTheme('classy'));
  }

  /**
   * Tests whether the rendered_item field is correctly filled by the processor.
   */
  public function testPreprocessIndexItems() {
    $items = array();
    foreach ($this->nodes as $node) {
      $items[] = array(
        'datasource' => 'entity:node',
        'item' => $node->getTypedData(),
        'item_id' => $node->id(),
        'text' => $this->randomMachineName(),
      );
    }
    $items = $this->generateItems($items);

    $this->processor->preprocessIndexItems($items);
    foreach ($items as $key => $item) {
      list(, $nid) = Utility::splitCombinedId($key);
      $field = $item->getField('rendered_item');
      $this->assertEqual($field->getType(), 'text', 'Node item ' . $nid . ' rendered value is identified as text.');
      $values = $field->getValues();
      // Test that the value is a string (not, e.g., a SafeString object).
      $this->assertTrue(is_string($values[0]), 'Node item ' . $nid . ' rendered value is a string.');
      $this->assertEqual(1, count($values), 'Node item ' . $nid . ' rendered value is a single value.');
      // These tests rely on the template not changing. However, if we'd only
      // check whether the field values themselves are included, there could
      // easier be false positives. For example the title text was present even
      // when the processor was broken, because the schema metadata was also
      // adding it to the output.
      $this->assertTrue(substr_count($values[0], 'view-mode-full') > 0, 'Node item ' . $nid . ' rendered in view-mode "full".');
      $this->assertTrue(substr_count($values[0], 'field--name-title') > 0, 'Node item ' . $nid . ' has a rendered title field.');
      $this->assertTrue(substr_count($values[0], '>' . $this->nodeData['title'] . '<') > 0, 'Node item ' . $nid . ' has a rendered title inside HTML-Tags.');
      $this->assertTrue(substr_count($values[0], '>Member for<') > 0, 'Node item ' . $nid . ' has rendered member information HTML-Tags.');
      $this->assertTrue(substr_count($values[0], '>' . $this->nodeData['body']['value'] . '<') > 0, 'Node item ' . $nid . ' has rendered content inside HTML-Tags.');
    }
  }

  /**
   * Tests whether the property is correctly added by the processor.
   */
  public function testAlterPropertyDefinitions() {
    // Check for modified properties when no datasource is given.
    /** @var \Drupal\Core\TypedData\DataDefinitionInterface[] $properties */
    $properties = array();
    $this->processor->alterPropertyDefinitions($properties, NULL);
    $this->assertTrue(array_key_exists('rendered_item', $properties), 'The Properties where modified with the "rendered_item".');
    $this->assertTrue(($properties['rendered_item'] instanceof DataDefinition), 'The "rendered_item" contains a valid DataDefinition instance.');
    $this->assertEqual('text', $properties['rendered_item']->getDataType(), 'Correct DataType set in the DataDefinition.');

    // Check if the properties stay untouched if a datasource is given.
    $properties = array();
    $this->processor->alterPropertyDefinitions($properties, $this->index->getDatasource('entity:node'));
    $this->assertEqual($properties, array(), '"render_item" property not added when data source is given.');
  }

}
