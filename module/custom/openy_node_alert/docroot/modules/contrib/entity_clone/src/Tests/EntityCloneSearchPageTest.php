<?php

/**
 * @file
 * Definition of Drupal\entity_clone\Tests\EntityCloneSearchPageTest.
 */

namespace Drupal\entity_clone\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Create a search page and test a clone.
 *
 * @group entity_clone
 */
class EntityCloneSearchPageTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['entity_clone', 'search', 'node'];

  /**
   * Permissions to grant admin user.
   *
   * @var array
   */
  protected $permissions = [
    'administer search',
    'clone search_page entity'
  ];

  /**
   * An administrative user with permission to configure search pages settings.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * Sets the test up.
   */
  protected function setUp() {
    parent::setUp();

    $this->adminUser = $this->drupalCreateUser($this->permissions);
    $this->drupalLogin($this->adminUser);
  }

  public function testSearchPageEntityClone() {
    $edit = [
      'label' => 'Test search page for clone',
      'id' => 'test_search_page_for_clone',
      'path' => 'test_search_page_for_clone_url',
    ];
    $this->drupalPostForm("/admin/config/search/pages/add/node_search", $edit, t('Save'));

    $search_pages = \Drupal::entityTypeManager()
      ->getStorage('search_page')
      ->loadByProperties([
        'id' => $edit['id'],
      ]);
    $search_page = reset($search_pages);

    $edit = [
      'id' => 'test_search_page_cloned',
      'label' => 'Test search page cloned',
    ];
    $this->drupalPostForm('entity_clone/search_page/' . $search_page->id(), $edit, t('Clone'));

    $search_pages = \Drupal::entityTypeManager()
      ->getStorage('search_page')
      ->loadByProperties([
        'id' => $edit['id'],
      ]);
    $search_page = reset($search_pages);
    $this->assertTrue($search_page, 'Test search page cloned found in database.');
  }

}

