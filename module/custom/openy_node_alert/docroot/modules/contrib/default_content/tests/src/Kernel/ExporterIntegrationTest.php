<?php

namespace Drupal\Tests\default_content\Kernel;

use Drupal\field\Tests\EntityReference\EntityReferenceTestTrait;
use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\user\Entity\User;

/**
 * Tests export functionality.
 *
 * @coversDefaultClass \Drupal\default_content\Exporter
 * @group default_content
 */
class ExporterIntegrationTest extends KernelTestBase {

  use EntityReferenceTestTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = ['system'];

  /**
   * The tested default content exporter.
   *
   * @var \Drupal\default_content\Exporter
   */
  protected $exporter;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installSchema('system', ['router', 'sequences']);
  }

  /**
   * Tests exportContent().
   */
  public function testExportContent() {
    \Drupal::service('module_installer')->install([
      'taxonomy',
      'default_content',
    ]);
    \Drupal::service('router.builder')->rebuild();
    $this->exporter = \Drupal::service('default_content.exporter');

    $vocabulary = Vocabulary::create(['vid' => 'test']);
    $vocabulary->save();
    $term = Term::create(['vid' => $vocabulary->id(), 'name' => 'test_name']);
    $term->save();
    $term = Term::load($term->id());

    /** @var \Symfony\Component\Serializer\Serializer $serializer */
    $serializer = \Drupal::service('serializer');
    \Drupal::service('hal.link_manager')
      ->setLinkDomain($this->container->getParameter('default_content.link_domain'));
    $expected = $serializer->serialize($term, 'hal_json', ['json_encode_options' => JSON_PRETTY_PRINT]);

    $exported = $this->exporter->exportContent('taxonomy_term', $term->id());
    $exported_decoded = json_decode($exported);

    // Ensure the proper UUID is part of it.
    $this->assertEqual($exported_decoded->uuid[0]->value, $term->uuid());
    $this->assertEqual($exported, $expected);

    // Tests export of taxonomy parent field.
    // @todo Get rid of after https://www.drupal.org/node/2543726
    $child_term = $term = Term::create([
      'vid' => $vocabulary->id(),
      'name' => 'child_name',
      'parent' => $term->id(),
    ]);
    $child_term->save();
    // Make sure parent relation is exported.
    $exported = $this->exporter->exportContent('taxonomy_term', $child_term->id());
    $relation_uri = 'http://drupal.org/rest/relation/taxonomy_term/test/parent';
    $exported_decoded = json_decode($exported);
    $this->assertFalse(empty($exported_decoded->_links->{$relation_uri}));
    $this->assertFalse(empty($exported_decoded->_embedded->{$relation_uri}));
  }

  /**
   * Tests exportContentWithReferences().
   */
  public function testExportWithReferences() {
    \Drupal::service('module_installer')->install(['node', 'default_content']);
    \Drupal::service('router.builder')->rebuild();
    $this->exporter = \Drupal::service('default_content.exporter');

    $user = User::create(['name' => 'my username']);
    $user->save();
    // Reload the user to get the proper casted values from the DB.
    $user = User::load($user->id());

    $node_type = NodeType::create(['type' => 'test']);
    $node_type->save();
    $node = Node::create([
      'type' => $node_type->id(),
      'title' => 'test node',
      'uid' => $user->id(),
    ]);
    $node->save();
    // Reload the node to get the proper casted values from the DB.
    $node = Node::load($node->id());

    /** @var \Symfony\Component\Serializer\Serializer $serializer */
    $serializer = \Drupal::service('serializer');
    \Drupal::service('hal.link_manager')
      ->setLinkDomain($this->container->getParameter('default_content.link_domain'));
    $expected_node = $serializer->serialize($node, 'hal_json', ['json_encode_options' => JSON_PRETTY_PRINT]);
    $expected_user = $serializer->serialize($user, 'hal_json', ['json_encode_options' => JSON_PRETTY_PRINT]);

    $exported_by_entity_type = $this->exporter->exportContentWithReferences('node', $node->id());

    // Ensure that the node type is not tryed to be exported.
    $this->assertEqual(array_keys($exported_by_entity_type), ['node', 'user']);

    // Ensure the right UUIDs are exported.
    $this->assertEqual([$node->uuid()], array_keys($exported_by_entity_type['node']));
    $this->assertEqual([$user->uuid()], array_keys($exported_by_entity_type['user']));

    // Compare the actual serialized data.
    $this->assertEqual(reset($exported_by_entity_type['node']), $expected_node);
    $this->assertEqual(reset($exported_by_entity_type['user']), $expected_user);

    // Ensure no recursion on export.
    $field_name = 'field_test_self_ref';
    $this->createEntityReferenceField('node', $node_type->id(), $field_name, 'Self reference field', 'node');

    $node1 = Node::create(['type' => $node_type->id(), 'title' => 'ref 1->3']);
    $node1->save();
    $node2 = Node::create([
      'type' => $node_type->id(),
      'title' => 'ref 2->1',
      $field_name => $node1->id(),
    ]);
    $node2->save();
    $node3 = Node::create([
      'type' => $node_type->id(),
      'title' => 'ref 3->2',
      $field_name => $node2->id(),
    ]);
    $node3->save();
    // Loop reference.
    $node1->{$field_name}->target_id = $node3->id();
    $node1->save();
    $exported_by_entity_type = $this->exporter->exportContentWithReferences('node', $node3->id());
    // Ensure all 3 nodes are exported.
    $this->assertEquals(3, count($exported_by_entity_type['node']));
  }

  /**
   * Tests exportModuleContent().
   */
  public function testModuleExport() {
    \Drupal::service('module_installer')->install([
      'node',
      'default_content',
      'default_content_export_test',
    ]);
    \Drupal::service('router.builder')->rebuild();
    $this->exporter = \Drupal::service('default_content.exporter');

    $test_uuid = '0e45d92f-1919-47cd-8b60-964a8a761292';
    $node_type = NodeType::create(['type' => 'test']);
    $node_type->save();
    $node = Node::create(['type' => $node_type->id(), 'title' => 'test node']);
    $node->uuid = $test_uuid;
    $node->save();
    $node = Node::load($node->id());
    $serializer = \Drupal::service('serializer');
    \Drupal::service('hal.link_manager')
      ->setLinkDomain($this->container->getParameter('default_content.link_domain'));
    $expected_node = $serializer->serialize($node, 'hal_json', ['json_encode_options' => JSON_PRETTY_PRINT]);

    $content = $this->exporter->exportModuleContent('default_content_export_test');
    $this->assertEqual($content['node'][$test_uuid], $expected_node);
  }

  /**
   * Tests exportModuleContent()
   */
  public function testModuleExportException() {
    \Drupal::service('module_installer')->install([
      'node',
      'default_content',
      'default_content_export_test',
    ]);
    \Drupal::service('router.builder')->rebuild();
    $this->defaultContentManager = \Drupal::service('default_content.exporter');

    $this->setExpectedException(\InvalidArgumentException::class);

    // Should throw an exception for missing uuid for the testing module.
    $this->defaultContentManager->exportModuleContent('default_content_export_test');
  }

}
