<?php

/**
 * @file
 * Contains \Drupal\sitemap\Tests\SitemapTaxonomyTest.
 */

namespace Drupal\sitemap\Tests;

/**
 * Tests the display of taxonomies based on sitemap settings.
 *
 * @group sitemap
 */
class SitemapTaxonomyTest extends SitemapTestBase {

  /**
   * Tests vocabulary description.
   */
  public function testVocabularyDescription() {
    // Assert that the vocabulary description is included in the sitemap by
    // default.
    $this->drupalGet('/sitemap');
    $this->assertText($this->vocabulary->getDescription(), 'Vocabulary description is included.');

    // Configure module not to show vocabulary descriptions.
    $edit = array(
      'show_description' => FALSE,
    );
    $this->drupalPostForm('admin/config/search/sitemap', $edit, t('Save configuration'));

    // Assert that vocabulary description is not included in the sitemap.
    $this->drupalGet('/sitemap');
    $this->assertNoText($this->vocabulary->getDescription(), 'Vocabulary description is not included.');
  }

  /**
   * Tests appearance of node counts.
   */
  public function testNodeCounts() {
    // Create dummy node.
    $title = $this->randomString();
    $edit = array(
      'title[0][value]' => $title,
      $this->field_tags_name . '[target_id]' => implode(',', $this->tags),
    );
    $this->drupalPostForm('node/add/article', $edit, t('Save and publish'));

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
    $terms = $this->createTerms($this->vocabulary);
    $tags = array();

    // Get tags from terms.
    foreach ($terms as $term) {
      $tags[] = $term->label();
    }

    // Assert that no tags are listed in the sitemap.
    $this->drupalGet('sitemap');
    foreach ($tags as $tag) {
      $this->assertNoLink($tag);
    }

    // Create dummy node.
    $title = $this->randomString();
    $edit = array(
      'title[0][value]' => $title,
      $this->field_tags_name . '[target_id]' => implode(',', $tags),
    );
    $this->drupalPostForm('node/add/article', $edit, t('Save and publish'));

    // Change vocabulary depth to -1.
    $edit = array(
      'vocabulary_depth' => -1,
    );
    $this->drupalPostForm('admin/config/search/sitemap', $edit, t('Save configuration'));

    // Assert that all tags are listed in the sitemap.
    $this->drupalGet('sitemap');
    foreach ($tags as $tag) {
      $this->assertLink($tag);
    }

    // Change vocabulary depth to 0.
    $edit = array(
      'vocabulary_depth' => 0,
    );
    $this->drupalPostForm('admin/config/search/sitemap', $edit, t('Save configuration'));

    // Assert that no tags are listed in the sitemap.
    $this->drupalGet('sitemap');
    foreach ($tags as $tag) {
      $this->assertNoLink($tag);
    }

    // Change vocabulary depth to 1.
    $edit = array(
      'vocabulary_depth' => 1,
    );
    $this->drupalPostForm('admin/config/search/sitemap', $edit, t('Save configuration'));

    // Assert that only tag 1 is listed in the sitemap.
    $this->drupalGet('sitemap');
    $this->assertLink($tags[0]);
    $this->assertNoLink($tags[1]);
    $this->assertNoLink($tags[2]);

    // Change vocabulary depth to 2.
    $edit = array(
      'vocabulary_depth' => 2,
    );
    $this->drupalPostForm('admin/config/search/sitemap', $edit, t('Save configuration'));

    // Assert that tag 1 and tag 2 are listed in the sitemap.
    $this->drupalGet('sitemap');
    $this->assertLink($tags[0]);
    $this->assertLink($tags[1]);
    $this->assertNoLink($tags[2]);

    // Change vocabulary depth to 3.
    $edit = array(
      'vocabulary_depth' => 3,
    );
    $this->drupalPostForm('admin/config/search/sitemap', $edit, t('Save configuration'));

    // Assert that all tags are listed in the sitemap.
    $this->drupalGet('sitemap');
    foreach ($tags as $tag) {
      $this->assertLink($tag);
    }
  }

}
