<?php

/**
 * @file
 * Contains \Drupal\Tests\search_api\Kernel\DependencyRemovalTest.
 */

namespace Drupal\Tests\search_api\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\search_api\Entity\Index;
use Drupal\search_api\Entity\Server;

/**
 * Tests what happens when an index's dependencies are removed.
 *
 * @group search_api
 */
class DependencyRemovalTest extends KernelTestBase {

  /**
   * A search index.
   *
   * @var \Drupal\search_api\IndexInterface
   */
  protected $index;

  /**
   * A config entity, to be used as a dependency in the tests.
   *
   * @var \Drupal\Core\Config\Entity\ConfigEntityInterface
   */
  protected $dependency;

  /**
   * {@inheritdoc}
   */
  public static $modules = array(
    'user',
    'search_api',
    'search_api_test_backend',
    'search_api_test_dependencies',
  );

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // The server tasks manager is needed when removing a server.
    $mock = $this->getMock('Drupal\search_api\Task\ServerTaskManagerInterface');
    $this->container->set('search_api.server_task_manager', $mock);

    // Create the index object, but don't save it yet since we want to change
    // its settings anyways in every test.
    $this->index = Index::create(array(
      'id' => 'test_index',
      'name' => 'Test index',
      'tracker' => 'default',
      'datasources' => array(
        'entity:user',
      ),
    ));

    // Use a search server as the dependency, since we have that available
    // anyways. The entity type should not matter at all, though.
    $this->dependency = Server::create(array(
      'id' => 'dependency',
      'name' => 'Test dependency',
      'backend' => 'search_api_test_backend',
    ));
    $this->dependency->save();
  }

  /**
   * Tests a backend with a dependency that gets removed.
   *
   * If the dependency does not get removed, proper cascading to the index is
   * also verified.
   *
   * @param bool $remove_dependency
   *   Whether to remove the dependency from the backend when the object
   *   depended on is deleted.
   *
   * @dataProvider dependencyTestDataProvider
   */
  public function testBackendDependency($remove_dependency) {
    $dependency_key = $this->dependency->getConfigDependencyKey();
    $dependency_name = $this->dependency->getConfigDependencyName();

    // Create a server using the test backend, and set the dependency in the
    // configuration.
    /** @var \Drupal\search_api\ServerInterface $server */
    $server = Server::create(array(
      'id' => 'test_server',
      'name' => 'Test server',
      'backend' => 'search_api_test_backend',
      'backend_config' => array(
        'dependencies' => array(
          $dependency_key => array(
            $dependency_name,
          ),
        ),
      ),
    ));
    $server->save();
    $server_dependency_key = $server->getConfigDependencyKey();
    $server_dependency_name = $server->getConfigDependencyName();

    // Set the server on the index and save that, too. However, we don't want
    // the index enabled, since that would lead to all kinds of overhead which
    // is completely irrelevant for this test.
    $this->index->set('server', $server->id());
    $this->index->disable();
    $this->index->save();

    // Check that the dependencies were calculated correctly.
    $server_dependencies = $server->getDependencies();
    $this->assertContains($dependency_name, $server_dependencies[$dependency_key], 'Backend dependency correctly inserted');
    $index_dependencies = $this->index->getDependencies();
    $this->assertContains($server_dependency_name, $index_dependencies[$server_dependency_key], 'Server dependency correctly inserted');

    // Set our magic state key to let the test plugin know whether the
    // dependency should be removed or not. See
    // \Drupal\search_api_test_backend\Plugin\search_api\backend\TestBackend::onDependencyRemoval().
    $key = 'search_api_test_backend.dependencies.remove';
    \Drupal::state()->set($key, $remove_dependency);

    // Delete the backend's dependency.
    $this->dependency->delete();

    // Reload the index and check it's still there.
    $this->reloadIndex();
    $this->assertInstanceOf('Drupal\search_api\IndexInterface', $this->index, 'Index not removed');

    // Reload the server.
    $storage = \Drupal::entityTypeManager()->getStorage('search_api_server');
    $storage->resetCache();
    $server = $storage->load($server->id());

    if ($remove_dependency) {
      $this->assertInstanceOf('Drupal\search_api\ServerInterface', $server, 'Server was not removed');
      $this->assertArrayNotHasKey('dependencies', $server->get('backend_config'), 'Backend config was adapted');
      // @todo Logically, this should not be changed: if the server does not get
      //   removed, there is no need to adapt the index's configuration.
      //   However, the way this config dependency cascading is actually
      //   implemented in
      //   \Drupal\Core\Config\ConfigManager::getConfigEntitiesToChangeOnDependencyRemoval()
      //   does not seem to follow that logic, but just computes the complete
      //   tree of dependencies once and operates generally on the assumption
      //   that all of them will be deleted. See #2642374.
//      $this->assertEquals($server->id(), $this->index->getServerId(), "Index's server was not changed");
    }
    else {
      $this->assertNull($server, 'Server was removed');
      $this->assertEquals(NULL, $this->index->getServerId(), 'Index server was changed');
    }
  }

  /**
   * Tests a datasource with a dependency that gets removed.
   *
   * @param bool $remove_dependency
   *   Whether to remove the dependency from the datasource when the object
   *   depended on is deleted.
   *
   * @dataProvider dependencyTestDataProvider
   */
  public function testDatasourceDependency($remove_dependency) {
    // Add the datasource to the index and save it. The datasource configuration
    // contains the dependencies it will return – in our case, we use the test
    // server.
    $dependency_key = $this->dependency->getConfigDependencyKey();
    $dependency_name = $this->dependency->getConfigDependencyName();
    $this->index->set('datasources', array(
      'entity:user',
      'search_api_test_dependencies',
    ));
    $this->index->set('datasource_configs', array(
      'search_api_test_dependencies' => array(
        $dependency_key => array(
          $dependency_name,
        ),
      ),
    ));
    $this->index->save();

    // Check the dependencies were calculated correctly.
    $dependencies = $this->index->getDependencies();
    $this->assertContains($dependency_name, $dependencies[$dependency_key], 'Datasource dependency correctly inserted');

    // Set our magic state key to let the test plugin know whether the
    // dependency should be removed or not. See
    // \Drupal\search_api_test_dependencies\Plugin\search_api\datasource\TestDatasource::onDependencyRemoval().
    $key = 'search_api_test_dependencies.datasource.remove';
    \Drupal::state()->set($key, $remove_dependency);

    // Delete the datasource's dependency.
    $this->dependency->delete();

    // Reload the index and check it's still there.
    $this->reloadIndex();
    $this->assertInstanceOf('Drupal\search_api\IndexInterface', $this->index, 'Index not removed');

    // Make sure the dependency has been removed, one way or the other.
    $dependencies = $this->index->getDependencies();
    $dependencies += array($dependency_key => array());
    $this->assertNotContains($dependency_name, $dependencies[$dependency_key], 'Datasource dependency removed from index');

    // Depending on whether the plugin should have removed the dependency or
    // not, make sure the right action was taken.
    $datasources = $this->index->get('datasources');
    $datasource_configs = $this->index->get('datasource_configs');
    if ($remove_dependency) {
      $this->assertContains('search_api_test_dependencies', $datasources, 'Datasource not removed');
      $this->assertEmpty($datasource_configs['search_api_test_dependencies'], 'Datasource settings adapted');
    }
    else {
      $this->assertNotContains('search_api_test_dependencies', $datasources, 'Datasource removed');
      $this->assertArrayNotHasKey('search_api_test_dependencies', $datasource_configs, 'Datasource config removed');
    }
  }

  /**
   * Tests removing the (hard) dependency of the index's single datasource.
   */
  public function testSingleDatasourceDependency() {
    // Add the datasource to the index and save it. The datasource configuration
    // contains the dependencies it will return – in our case, we use the test
    // server.
    $dependency_key = $this->dependency->getConfigDependencyKey();
    $dependency_name = $this->dependency->getConfigDependencyName();
    $this->index->set('datasources', array(
      'search_api_test_dependencies',
    ));
    $this->index->set('datasource_configs', array(
      'search_api_test_dependencies' => array(
        $dependency_key => array(
          $dependency_name,
        ),
      ),
    ));
    $this->index->save();

    // Since in this test the index will be removed, we need a mock key/value
    // store (the index will purge any unsaved configuration of it upon
    // deletion, which uses a "user-shared temp store", which in turn uses a
    // key/value store).
    $mock = $this->getMock('Drupal\Core\KeyValueStore\KeyValueStoreExpirableInterface');
    $mock_factory = $this->getMock('Drupal\Core\KeyValueStore\KeyValueExpirableFactoryInterface');
    $mock_factory->method('get')->willReturn($mock);
    $this->container->set('keyvalue.expirable', $mock_factory);

    // Delete the datasource's dependency.
    $this->dependency->delete();

    // Reload the index to ensure it was deleted.
    $this->reloadIndex();
    $this->assertNull($this->index, 'Index was removed');
  }

  /**
   * Tests a processor with a dependency that gets removed.
   *
   * @param bool $remove_dependency
   *   Whether to remove the dependency from the processor when the object
   *   depended on is deleted.
   *
   * @dataProvider dependencyTestDataProvider
   */
  public function testProcessorDependency($remove_dependency) {
    // Add the processor to the index and save it. The processor configuration
    // contains the dependencies it will return – in our case, we use the test
    // server.
    $dependency_key = $this->dependency->getConfigDependencyKey();
    $dependency_name = $this->dependency->getConfigDependencyName();
    $this->index->set('processors', array(
      'search_api_test_dependencies' => array(
        'processor_id' => 'search_api_test_dependencies',
        'settings' => array(
          $dependency_key => array(
            $dependency_name,
          ),
        ),
      ),
    ));
    $this->index->save();

    // Check the dependencies were calculated correctly.
    $dependencies = $this->index->getDependencies();
    $this->assertContains($dependency_name, $dependencies[$dependency_key], 'Processor dependency correctly inserted');

    // Set our magic state key to let the test plugin know whether the
    // dependency should be removed or not. See
    // \Drupal\search_api_test_dependencies\Plugin\search_api\processor\TestProcessor::onDependencyRemoval().
    $key = 'search_api_test_dependencies.processor.remove';
    \Drupal::state()->set($key, $remove_dependency);

    // Delete the processor's dependency.
    $this->dependency->delete();

    // Reload the index and check it's still there.
    $this->reloadIndex();
    $this->assertInstanceOf('Drupal\search_api\IndexInterface', $this->index, 'Index not removed');

    // Make sure the dependency has been removed, one way or the other.
    $dependencies = $this->index->getDependencies();
    $dependencies += array($dependency_key => array());
    $this->assertNotContains($dependency_name, $dependencies[$dependency_key], 'Processor dependency removed from index');

    // Depending on whether the plugin should have removed the dependency or
    // not, make sure the right action was taken.
    $processors = $this->index->get('processors');
    if ($remove_dependency) {
      $this->assertArrayHasKey('search_api_test_dependencies', $processors, 'Processor not removed');
      $this->assertEmpty($processors['search_api_test_dependencies']['settings'], 'Processor settings adapted');
    }
    else {
      $this->assertArrayNotHasKey('search_api_test_dependencies', $processors, 'Processor removed');
    }
  }

  /**
   * Tests a tracker with a dependency that gets removed.
   *
   * @param bool $remove_dependency
   *   Whether to remove the dependency from the tracker when the object
   *   depended on is deleted.
   *
   * @dataProvider dependencyTestDataProvider
   */
  public function testTrackerDependency($remove_dependency) {
    // Set the tracker for the index and save it. The tracker configuration
    // contains the dependencies it will return – in our case, we use the test
    // server.
    $dependency_key = $this->dependency->getConfigDependencyKey();
    $dependency_name = $this->dependency->getConfigDependencyName();
    $this->index->set('tracker', 'search_api_test_dependencies');
    $this->index->set('tracker_config', array(
      $dependency_key => array(
        $dependency_name,
      ),
    ));
    $this->index->save();

    // Check the dependencies were calculated correctly.
    $dependencies = $this->index->getDependencies();
    $this->assertContains($dependency_name, $dependencies[$dependency_key], 'Tracker dependency correctly inserted');

    // Set our magic state key to let the test plugin know whether the
    // dependency should be removed or not. See
    // \Drupal\search_api_test_dependencies\Plugin\search_api\tracker\TestTracker::onDependencyRemoval().
    $key = 'search_api_test_dependencies.tracker.remove';
    \Drupal::state()->set($key, $remove_dependency);
    // If the index resets the tracker, it needs to know the ID of the default
    // tracker.
    if (!$remove_dependency) {
      \Drupal::configFactory()->getEditable('search_api.settings')
        ->set('default_tracker', 'default')
        ->save();
    }

    // Delete the tracker's dependency.
    $this->dependency->delete();

    // Reload the index and check it's still there.
    $this->reloadIndex();
    $this->assertInstanceOf('Drupal\search_api\IndexInterface', $this->index, 'Index not removed');

    // Make sure the dependency has been removed, one way or the other.
    $dependencies = $this->index->getDependencies();
    $dependencies += array($dependency_key => array());
    $this->assertNotContains($dependency_name, $dependencies[$dependency_key], 'Tracker dependency removed from index');

    // Depending on whether the plugin should have removed the dependency or
    // not, make sure the right action was taken.
    $tracker = $this->index->get('tracker');
    $tracker_config = $this->index->get('tracker_config');
    if ($remove_dependency) {
      $this->assertEquals('search_api_test_dependencies', $tracker, 'Tracker not reset');
      $this->assertEmpty($tracker_config, 'Tracker settings adapted');
    }
    else {
      $this->assertEquals('default', $tracker, 'Tracker was reset');
      $this->assertEmpty($tracker_config, 'Tracker settings were cleared');
    }
  }

  /**
   * Data provider for this class's test methods.
   *
   * @return array
   *   An array of argument arrays for this class's test methods.
   */
  public function dependencyTestDataProvider() {
    return array(
      'Remove dependency' => array(TRUE),
      'Keep dependency' => array(FALSE),
    );
  }

  /**
   * Reloads the index with the latest copy from storage.
   */
  protected function reloadIndex() {
    $storage = \Drupal::entityTypeManager()->getStorage('search_api_index');
    $storage->resetCache();
    $this->index = $storage->load($this->index->id());
  }

}
