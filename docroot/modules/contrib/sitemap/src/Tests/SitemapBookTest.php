<?php

/**
 * @file
 * Contains \Drupal\sitemap\Tests\SitemapBookTest.
 */

namespace Drupal\sitemap\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Test the display of books based on sitemap settings.
 *
 * @group sitemap
 */
class SitemapBookTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('sitemap', 'book');

  /**
   * A book node.
   *
   * @var object
   */
  protected $book;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Create user then login.
    $this->user = $this->drupalCreateUser(array(
      'administer sitemap',
      'access sitemap',
      'create book content',
      'create new books',
      'administer book outlines',
    ));
    $this->drupalLogin($this->user);
  }

  /**
   * Tests books.
   */
  public function testBooks() {
    // Assert that books are not included in the sitemap by default.
    $this->drupalGet('/sitemap');
    $elements = $this->cssSelect(".sitemap-box h2:contains('Books')");
    $this->assertEqual(count($elements), 0, 'Books are not included.');

    // Create new book.
    $nodes = $this->createBook();
    $book = $this->book;

    // Configure sitemap to show the test book.
    $bid = $book->id();
    $edit = array(
      'show_books[' . $bid . ']' => $bid,
    );
    $this->drupalPostForm('admin/config/search/sitemap', $edit, t('Save configuration'));

    // Assert that all book links are displayed by default.
    $this->drupalGet('/sitemap');
    $this->assertLink($this->book->getTitle());
    foreach ($nodes as $node) {
      $this->assertLink($node->getTitle());
    }

    // Configure sitemap to not expand books.
    $edit = array(
      'books_expanded' => FALSE,
    );
    $this->drupalPostForm('admin/config/search/sitemap', $edit, t('Save configuration'));

    // Assert that the top-level book link is displayed, but that the others are
    // not.
    $this->drupalGet('/sitemap');
    $this->assertLink($this->book->getTitle());
    foreach ($nodes as $node) {
      $this->assertNoLink($node->getTitle());
    }

  }


  /**
   * Creates a new book with a page hierarchy. Adapted from BookTest.
   */
  protected function createBook() {
    $this->book = $this->createBookNode('new');
    $book = $this->book;

    /*
     * Add page hierarchy to book.
     * Node 00 (top level), created above
     *  |- Node 01
     *   |- Node 02
     *   |- Node 03
     *  |- Node 04
     *  |- Node 05
     */
    $nodes = array();
    $nodes[] = $this->createBookNode($book->id());
    $nodes[] = $this->createBookNode($book->id(), $nodes[0]->book['nid']);
    $nodes[] = $this->createBookNode($book->id(), $nodes[0]->book['nid']);
    $nodes[] = $this->createBookNode($book->id());
    $nodes[] = $this->createBookNode($book->id());

    return $nodes;
  }


  /**
   * Creates a book node. From BookTest.
   *
   * @param int|string $book_nid
   *   A book node ID or set to 'new' to create a new book.
   * @param int|null $parent
   *   (optional) Parent book reference ID. Defaults to NULL.
   *
   * @return object
   *   Returns object
   */
  protected function createBookNode($book_nid, $parent = NULL) {
    // $number does not use drupal_static as it should not be reset
    // since it uniquely identifies each call to createBookNode().
    // Used to ensure that when sorted nodes stay in same order.
    static $number = 0;

    $edit = array();
    $edit['title[0][value]'] = str_pad($number, 2, '0', STR_PAD_LEFT) . ' - SimpleTest test node ' . $this->randomMachineName(10);
    $edit['body[0][value]'] = 'SimpleTest test body ' . $this->randomMachineName(32) . ' ' . $this->randomMachineName(32);
    $edit['book[bid]'] = $book_nid;

    if ($parent !== NULL) {
      $this->drupalPostForm('node/add/book', $edit, t('Change book (update list of parents)'));

      $edit['book[pid]'] = $parent;
      $this->drupalPostForm(NULL, $edit, t('Save'));
    }
    else {
      $this->drupalPostForm('node/add/book', $edit, t('Save'));
    }

    $node = $this->drupalGetNodeByTitle($edit['title[0][value]']);
    $number++;

    return $node;
  }

}
