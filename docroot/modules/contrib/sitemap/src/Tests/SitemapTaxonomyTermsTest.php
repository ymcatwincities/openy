<?php

namespace Drupal\sitemap\Tests;

use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Field\FieldStorageDefinitionInterface;

/**
 * Tests the display of taxonomies based on sitemap settings.
 *
 * @group sitemap
 */
class SitemapTaxonomyTermsTest extends SitemapTaxonomyTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Create terms
    $this->terms = $this->createTerms($this->vocabulary);

  }

  /**
   * Tests the term_threshold setting
   */
  public function testTermThreshold() {
    // Get term names from terms.
    $names = array();
    foreach ($this->terms as $term) {
      $names[] = $term->label();
    }

    // Confirm that terms without content are not displayed by default.
    $this->drupalGet('sitemap');
    foreach ($names as $term_name) {
      $this->assertNoText($term_name);
    }

    // Show all taxonomy terms, even if they are not assigned to any nodes.
    $edit = array(
      'term_threshold' => -1,
    );
    $this->drupalPostForm('admin/config/search/sitemap', $edit, t('Save configuration'));

    // Assert that terms without nodes are now displayed on the sitemap.
    $this->drupalGet('sitemap');
    foreach ($names as $term_name) {
      $this->assertText($term_name);
      $this->assertNoLink($term_name);
    }

    // Create test node with terms.
    $this->createNodeWithTerms($this->terms);
    drupal_flush_all_caches();

    // Assert that terms with content are displayed on the sitemap as links when
    // term_threshold is set to -1.
    $this->drupalGet('sitemap');
    foreach ($names as $term_name) {
      $this->assertLink($term_name);
    }

    // Require at least one node for taxonomy terms to show up.
    $edit = array(
      'term_threshold' => 0,
    );
    $this->drupalPostForm('admin/config/search/sitemap', $edit, t('Save configuration'));

    // Assert that terms with content are displayed on the sitemap as links.
    $this->drupalGet('sitemap');
    foreach ($names as $term_name) {
      $this->assertLink($term_name);
    }

    // Require at least two nodes for taxonomy terms to show up.
    $edit = array(
      'term_threshold' => 1,
    );
    $this->drupalPostForm('admin/config/search/sitemap', $edit, t('Save configuration'));

    $terms = $this->terms;
    unset($terms[0]);

    // Create a second test node with only two terms.
    $this->createNodeWithTerms($terms);

    $this->drupalGet('sitemap');
    $this->assertNoLink($this->terms[0]->label());
    $this->assertLink($this->terms[1]->label());
    $this->assertLink($this->terms[2]->label());
  }

  /**
   * Tests appearance of node counts.
   */
  public function testNodeCounts() {

    // Create test node with terms.
    $this->createNodeWithTerms($this->terms);

    // Assert that node counts are included in the sitemap by default.
    $this->drupalGet('/sitemap');
    $this->assertEqual(substr_count($this->getTextContent(), '(1)'), 3, 'Node counts are included');

    // Configure module to hide node counts.
    $edit = array(
      'show_count' => FALSE,
    );
    $this->drupalPostForm('admin/config/search/sitemap', $edit, t('Save configuration'));

    // Assert that node counts are not included in the sitemap.
    $this->drupalGet('sitemap');
    $this->assertEqual(substr_count($this->getTextContent(), '(1)'), 0, 'Node counts are not included');
  }

  /**
   * Tests vocabulary depth settings.
   */
  public function testVocabularyDepth() {

    // Set to show all taxonomy terms, even if they are not assigned to any
    // nodes.
    $edit = array(
      'term_threshold' => -1,
    );
    $this->drupalPostForm('admin/config/search/sitemap', $edit, t('Save configuration'));

    // Get tags from terms.
    $tags = array();
    foreach ($this->terms as $term) {
      $tags[] = $term->label();
    }

    // Change vocabulary depth to -1.
    $edit = array(
      'vocabulary_depth' => -1,
    );
    $this->drupalPostForm('admin/config/search/sitemap', $edit, t('Save configuration'));

    // Assert that all tags are listed in the sitemap.
    $this->drupalGet('sitemap');
    foreach ($tags as $tag) {
      $this->assertText($tag);
    }

    // Change vocabulary depth to 0.
    $edit = array(
      'vocabulary_depth' => 0,
    );
    $this->drupalPostForm('admin/config/search/sitemap', $edit, t('Save configuration'));

    // Assert that no tags are listed in the sitemap.
    $this->drupalGet('sitemap');
    foreach ($tags as $tag) {
      $this->assertNoText($tag);
    }

    // Change vocabulary depth to 1.
    $edit = array(
      'vocabulary_depth' => 1,
    );
    $this->drupalPostForm('admin/config/search/sitemap', $edit, t('Save configuration'));

    // Assert that only tag 1 is listed in the sitemap.
    $this->drupalGet('sitemap');
    $this->assertText($tags[0]);
    $this->assertNoText($tags[1]);
    $this->assertNoText($tags[2]);

    // Change vocabulary depth to 2.
    $edit = array(
      'vocabulary_depth' => 2,
    );
    $this->drupalPostForm('admin/config/search/sitemap', $edit, t('Save configuration'));

    // Assert that tag 1 and tag 2 are listed in the sitemap.
    $this->drupalGet('sitemap');
    $this->assertText($tags[0]);
    $this->assertText($tags[1]);
    $this->assertNoText($tags[2]);

    // Change vocabulary depth to 3.
    $edit = array(
      'vocabulary_depth' => 3,
    );
    $this->drupalPostForm('admin/config/search/sitemap', $edit, t('Save configuration'));

    // Assert that all tags are listed in the sitemap.
    $this->drupalGet('sitemap');
    foreach ($tags as $tag) {
      $this->assertText($tag);
    }
  }

}
