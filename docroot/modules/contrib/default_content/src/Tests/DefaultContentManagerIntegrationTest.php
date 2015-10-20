<?php

/**
 * @file
 * Contains \Drupal\default_content\Tests\DefaultContentManagerIntegrationTest.
 */

namespace Drupal\default_content\Tests;

use Drupal\default_content\DefaultContentManager;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\simpletest\KernelTestBase;
use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\user\Entity\User;

/**
 * @coversDefaultClass \Drupal\default_content\DefaultContentManager
 * @group default_content
 */
class DefaultContentManagerIntegrationTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['system'];

  /**
   * The tested default content manager.
   *
   * @var \Drupal\default_content\DefaultContentManager
   */
  protected $defaultContentManager;

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
    \Drupal::service('module_installer')->install(['taxonomy', 'default_content']);
    \Drupal::service('router.builder')->rebuild();
    $this->defaultContentManager = \Drupal::service('default_content.manager');

    $vocabulary = Vocabulary::create(['vid' => 'test']);
    $vocabulary->save();
    $term = Term::create(['vid' => $vocabulary->id(), 'name' => 'test_name']);
    $term->save();
    $term = Term::load($term->id());

    /** @var \Symfony\Component\Serializer\Serializer $serializer */
    $serializer = \Drupal::service('serializer');
    \Drupal::service('rest.link_manager')->setLinkDomain(DefaultContentManager::LINK_DOMAIN);
    $expected = $serializer->serialize($term, 'hal_json', ['json_encode_options' => JSON_PRETTY_PRINT]);

    $exported = $this->defaultContentManager->exportContent('taxonomy_term', $term->id());
    $exported_decoded = json_decode($exported);

    // Ensure the proper UUID is part of it.
    $this->assertEqual($exported_decoded->uuid[0]->value, $term->uuid());
    $this->assertEqual($exported, $expected);
  }

  /**
   * Tests exportContentWithReferences().
   */
  public function testExportWithReferences() {
    \Drupal::service('module_installer')->install(['node', 'default_content']);
    \Drupal::service('router.builder')->rebuild();
    $this->defaultContentManager = \Drupal::service('default_content.manager');

    $user = User::create(['name' => 'my username']);
    $user->save();
    // Reload the user to get the proper casted values from the DB.
    $user = User::load($user->id());

    $node_type = NodeType::create(['type' => 'test']);
    $node_type->save();
    $node = Node::create(['type' => $node_type->id(), 'title' => 'test node', 'uid' => $user->id()]);
    $node->save();
    // Reload the node to get the proper casted values from the DB.
    $node = Node::load($node->id());

    /** @var \Symfony\Component\Serializer\Serializer $serializer */
    $serializer = \Drupal::service('serializer');
    \Drupal::service('rest.link_manager')->setLinkDomain(DefaultContentManager::LINK_DOMAIN);
    $expected_node = $serializer->serialize($node, 'hal_json', ['json_encode_options' => JSON_PRETTY_PRINT]);
    $expected_user = $serializer->serialize($user, 'hal_json', ['json_encode_options' => JSON_PRETTY_PRINT]);

    $exported_by_entity_type = $this->defaultContentManager->exportContentWithReferences('node', $node->id());

    // Ensure that the node type is not tryed to be exported.
    $this->assertEqual(array_keys($exported_by_entity_type), ['node', 'user']);

    // Ensure the right UUIDs are exported.
    $this->assertEqual([$node->uuid()], array_keys($exported_by_entity_type['node']));
    $this->assertEqual([$user->uuid()], array_keys($exported_by_entity_type['user']));

    // Compare the actual serialized data.
    $this->assertEqual(reset($exported_by_entity_type['node']), $expected_node);
    $this->assertEqual(reset($exported_by_entity_type['user']), $expected_user);
  }

}
