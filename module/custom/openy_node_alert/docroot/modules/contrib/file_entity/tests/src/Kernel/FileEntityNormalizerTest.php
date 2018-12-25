<?php

namespace Drupal\Tests\file_entity\Kernel;

use Drupal\Core\StreamWrapper\PublicStream;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\file\Entity\File;
use Drupal\file_entity\Entity\FileType;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\KernelTests\KernelTestBase;
use Drupal\system\Tests\Routing\MockRouteProvider;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Tests the File entity normalizer.
 *
 * @see Drupal\file_entity\Normalizer\FileEntityNormalizer
 *
 * @group file_entity
 */
class FileEntityNormalizerTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = array(
    'simpletest',
    'field',
    'file',
    'image',
    'file_entity',
    'node',
    'serialization',
    'text',
    'user',
    'rest',
    'hal',
    'system',
  );

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->installEntitySchema('node');
    $this->installEntitySchema('file');
    $this->installEntitySchema('user');
    $this->installSchema('file', array('file_usage'));
    $this->installSchema('file_entity', array('file_metadata'));

    // Set the file route to provide entity URI for serialization.
    $route_collection = new RouteCollection();
    $route_collection->add('entity.file.canonical', new Route('file/{file}'));
    $this->container->set('router.route_provider', new MockRouteProvider($route_collection));
  }

  /**
   * Tests that file field is identical before and after de/serialization.
   */
  public function testFileFieldSerializePersist() {
    // Create a node type.
    $node_type = NodeType::create(array('type' => $this->randomMachineName()));
    $node_type->save();

    // Create a file.
    $file_name = $this->randomMachineName() . '.txt';
    file_put_contents("public://$file_name", $this->randomString());
    $file = File::create(array(
      'uri' => "public://$file_name",
    ));
    $file->save();

    // Attach a file field to the node type.
    $file_field_storage = FieldStorageConfig::create(array(
      'type' => 'file',
      'entity_type' => 'node',
      'field_name' => 'field_file',
    ));
    $file_field_storage->save();
    $file_field_instance = FieldConfig::create(array(
      'field_storage' => $file_field_storage,
      'entity_type' => 'node',
      'bundle' => $node_type->id(),
    ));
    $file_field_instance->save();

    // Create a node referencing the file.
    $node = Node::create(array(
      'title' => 'A node with a file',
      'type' => $node_type->id(),
      'field_file' => array(
        'target_id' => $file->id(),
        'display' => 0,
        'description' => 'An attached file',
      ),
      'status' => TRUE,
    ));

    // Export.
    $serialized = $this->container->get('serializer')->serialize($node, 'hal_json');

    // Import again.
    $deserialized = $this->container->get('serializer')->deserialize($serialized, 'Drupal\node\Entity\Node', 'hal_json');

    // Compare.
    $this->assertEqual($node->toArray()['field_file'], $deserialized->toArray()['field_file'], "File field persists.");
  }


  /**
   * Tests that file entities are correctly serialized, including file contents.
   */
  public function testFileSerialize() {

    FileType::create(array(
      'id' => 'undefined',
    ))->save();
    foreach ($this->getTestFiles() as $file_obj) {
      $file_contents = file_get_contents($file_obj->uri);

      // Create file entity.
      $file = File::create(array(
        'uri' => $file_obj->uri,
        'status' => TRUE,
      ));
      $file->save();

      // Serialize.
      $serialized = $this->container->get('serializer')->serialize($file, 'hal_json');

      // Remove file.
      $file->delete();
      $this->container->get('entity.manager')->getStorage('file')->resetCache();
      $this->assertFalse(file_exists($file_obj->uri), "Deleted file $file_obj->uri from disk");
      $this->assertFalse(File::load($file->id()), "Deleted file {$file->id()} entity");

      // Deserialize again.
      $deserialized = $this->container->get('serializer')->deserialize($serialized, 'Drupal\file\Entity\File', 'hal_json');
      $deserialized->save();

      // Compare.
      $files = File::loadMultiple();
      $last_file = array_pop($files);
      $this->assertNotNull($last_file, 'A file entity was created');
      $this->assertTrue(file_exists($file_obj->uri), "A file was created on disk");

      // Assert file is equal.
      foreach (array('filename', 'uri', 'filemime', 'filesize', 'type') as $property) {
        $this->assertEqual($file->get($property)->value, $last_file->get($property)->value);
      }
      $this->assertEqual($file->get('type')->target_id, $last_file->get('type')->target_id);
      $this->assertEqual($file_contents, file_get_contents($last_file->getFileUri()), 'File contents are equal');
    }
  }

  /**
   * Tests that image field is identical before and after de/serialization.
   */
  public function testImageFieldSerializePersist() {
    // Create a node type.
    $node_type = NodeType::create(array('type' => $this->randomMachineName()));
    $node_type->save();

    // Create a file.
    $file_name = $this->randomMachineName() . '.jpg';
    file_put_contents("public://$file_name", $this->randomString());
    $image = File::create(array(
      'uri' => "public://$file_name",
    ));
    $image->save();

    // Attach a file field to the node type.
    $image_field_storage = FieldStorageConfig::create(array(
      'type' => 'image',
      'entity_type' => 'node',
      'field_name' => 'field_image',
    ));
    $image_field_storage->save();
    $file_field_instance = FieldConfig::create(array(
      'field_storage' => $image_field_storage,
      'entity_type' => 'node',
      'bundle' => $node_type->id(),
    ));
    $file_field_instance->save();

    // Create a node referencing the image.
    $node = Node::create(array(
      'title' => 'A node with a image',
      'type' => $node_type->id(),
      'field_image' => array(
        'target_id' => $image->id(),
        'alt' => 'the image alternative',
        'title' => 'the title',
        'width' => 50,
        'height' => 50,
      ),
      'status' => TRUE,
    ));

    // Export.
    $serialized = $this->container->get('serializer')->serialize($node, 'hal_json');

    // Import again.
    $deserialized = $this->container->get('serializer')->deserialize($serialized, 'Drupal\node\Entity\Node', 'hal_json');

    // Compare.
    $this->assertEqual($node->toArray()['field_image'], $deserialized->toArray()['field_image'], "Image field persists.");
  }

  /**
   * Create some test files like WebTestBase::drupalGetTestFiles().
   *
   * @return array
   *   An associative array (keyed on uri) of objects with 'uri', 'filename',
   *   and 'name' properties corresponding to the test files.
   */
  protected function getTestFiles() {
    $original = drupal_get_path('module', 'simpletest') . '/files';
    $files = file_scan_directory($original, '/(html|image|javascript|php|sql)-.*/');
    foreach ($files as $file) {
      unset($files[$file->uri]);
      $new_path = file_unmanaged_copy($file->uri, PublicStream::basePath());
      $file->uri = $new_path;
      $files[$new_path] = $file;
    }
    return $files;
  }

}
