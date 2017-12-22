<?php

namespace Drupal\file_entity\Tests;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Url;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\file\Entity\File;
use Drupal\node\Entity\Node;
use Drupal\rest\Tests\RESTTestBase;

/**
 * Tests File entity REST services
 *
 * @group file_entity
 */
class FileEntityServicesTest extends RESTTestBase {

  /**
   * Modules to enable.
   *
   * @var string[]
   */
  public static $modules = array(
    'node',
    'hal',
    'rest',
    'file_entity'
  );

  /**
   * Tests that a file field is correctly handled with REST.
   */
  public function testFileFieldREST() {
    $this->enableService('entity:node', 'GET');

    // Create user and log in.
    $account = $this->drupalCreateUser(array(
      'access content',
      'create resttest content',
    ));
    $this->drupalLogin($account);

    // Add a file field to the resttest content type.
    $file_field_storage = FieldStorageConfig::create(array(
      'type' => 'file',
      'entity_type' => 'node',
      'field_name' => 'field_file',
    ));
    $file_field_storage->save();
    $file_field_instance = FieldConfig::create(array(
      'field_storage' => $file_field_storage,
      'entity_type' => 'node',
      'bundle' => 'resttest',
    ));
    $file_field_instance->save();

    // Create a file.
    $file_uri = 'public://' . $this->randomMachineName() . '.txt';
    file_put_contents($file_uri, 'This is some file contents');
    $file = File::create(array('uri' => $file_uri, 'status' => FILE_STATUS_PERMANENT, 'uid' => $account->id()));
    $file->save();

    // Create a node with a file.
    $node = Node::create(array(
      'title' => 'A node with a file',
      'type' => 'resttest',
      'field_file' => array(
        'target_id' => $file->id(),
        'display' => 0,
        'description' => 'An attached file',
      ),
      'status' => TRUE,
    ));
    $node->save();

    // GET node.
    $response_json = $this->httpRequest($node->urlInfo()->setRouteParameter('_format', $this->defaultFormat), 'GET', NULL);
    $this->assertResponse(200);
    $response_data = Json::decode($response_json);

    // Test that field_file refers to the file entity.
    $normalized_field = $response_data['_embedded'][$this->getAbsoluteUrl('/rest/relation/node/resttest/field_file')];
    $this->assertEqual($normalized_field[0]['_links']['self']['href'], $file->urlInfo()->setAbsolute()->setRouteParameter('_format', $this->defaultFormat)->toString());

    // Remove the node.
    $node->delete();
    $this->httpRequest($node->urlInfo()->setRouteParameter('_format', $this->defaultFormat), 'GET', NULL);
    $this->assertResponse(404);

    // POST node to create new.
    unset($response_data['nid']);
    unset($response_data['created']);
    unset($response_data['changed']);
    unset($response_data['status']);
    unset($response_data['promote']);
    unset($response_data['sticky']);
    unset($response_data['revision_timestamp']);
    unset($response_data['_embedded'][$this->getAbsoluteUrl('/rest/relation/node/resttest/uid')]);
    unset($response_data['_embedded'][$this->getAbsoluteUrl('/rest/relation/node/resttest/revision_uid')]);

    $serialized = Json::encode($response_data);
    $this->enableService('entity:node', 'POST');
    $this->httpRequest(Url::fromRoute('rest.entity.node.POST')->setRouteParameter('_format', $this->defaultFormat), 'POST', $serialized);
    $this->assertResponse(201);

    // Test that the new node has a valid file field.
    $nodes = Node::loadMultiple();
    $last_node = array_pop($nodes);
    $this->assertEqual($last_node->get('field_file')->target_id, $file->id());
  }

}
