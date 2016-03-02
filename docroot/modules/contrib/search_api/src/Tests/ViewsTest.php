<?php

/**
 * @file
 * Contains \Drupal\search_api\Tests\ViewsTest.
 */

namespace Drupal\search_api\Tests;

use Drupal\search_api\Entity\Index;
use Drupal\search_api\Utility;

/**
 * Tests the Views integration of the Search API.
 *
 * @group search_api
 */
class ViewsTest extends WebTestBase {

  use ExampleContentTrait;

  /**
   * Modules to enable for this test.
   *
   * @var string[]
   */
  public static $modules = array('search_api_test_views');

  /**
   * A search index ID.
   *
   * @var string
   */
  protected $indexId = 'database_search_index';

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->setUpExampleStructure();

    Utility::getIndexTaskManager()->addItemsAll(Index::load($this->indexId));
  }

  /**
   * Tests a view with a fulltext search field.
   */
  public function testFulltextSearch() {
    $this->insertExampleContent();
    $this->assertEqual($this->indexItems($this->indexId), 5, '5 items were indexed.');

    $this->drupalGet('search-api-test-fulltext');
    // By default, the view should show all entities.
    $this->assertText('Displaying 5 search results', 'The search view displays the correct number of results.');
    foreach ($this->entities as $id => $entity) {
      $this->assertText($entity->label(), "Entity #$id found in the results.");
    }

    // Search for something.
    $this->drupalGet('search-api-test-fulltext', array('query' => array('search_api_fulltext' => 'foobar')));

    // Now it should only find one entity.
    $this->assertText('Displaying 1 search results', 'The search view displays the correct number of results.');
    foreach ($this->entities as $id => $entity) {
      if ($id == 3) {
        $this->assertText($entity->label(), "Entity #$id found in the results.");
      }
      else {
        $this->assertNoText($entity->label(), "Entity #$id not found in the results.");
      }
    }
  }

}
