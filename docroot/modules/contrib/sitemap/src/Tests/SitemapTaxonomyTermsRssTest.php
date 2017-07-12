<?php

namespace Drupal\sitemap\Tests;

/**
 * Tests the display of RSS links based on sitemap settings.
 *
 * @group sitemap
 */
class SitemapTaxonomyTermsRssTest extends SitemapTaxonomyTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('sitemap', 'node', 'taxonomy');

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Create terms
    $this->terms = $this->createTerms($this->vocabulary);

    // Set to show all taxonomy terms, even if they are not assigned to any
    // nodes.
    $edit = array(
      'term_threshold' => -1,
    );
    $this->drupalPostForm('admin/config/search/sitemap', $edit, t('Save configuration'));

  }

  /**
   * Tests included RSS links.
   */
  public function testIncludeRssLinks() {

    // Assert that RSS links for terms are included in the sitemap.
    $this->drupalGet('/sitemap');
    foreach ($this->terms as $term) {
      $this->assertLinkByHref('/taxonomy/term/' . $term->id() . '/feed');
    }

    // Change module not to include RSS links.
    $edit = array(
      'show_rss_links' => 0,
    );
    $this->drupalPostForm('admin/config/search/sitemap', $edit, t('Save configuration'));

    // Assert that RSS links are not included in the sitemap.
    $this->drupalGet('/sitemap');
    foreach ($this->terms as $term) {
      $this->assertNoLinkByHref('/taxonomy/term/' . $term->id() . '/feed');
    }
  }

  /**
   * Tests RSS feed depth.
   */
  public function testRssFeedDepth() {
    $terms = $this->terms;

    // Set RSS feed depth to -1.
    $edit = array(
      'rss_taxonomy' => -1,
    );
    $this->drupalPostForm('admin/config/search/sitemap', $edit, t('Save configuration'));

    // Assert that all RSS links are included in the sitemap.
    $this->drupalGet('sitemap');
    foreach ($terms as $term) {
      $this->assertLinkByHref('/taxonomy/term/' . $term->id() . '/feed');
    }

    // Change RSS feed depth to 0.
    $edit = array(
      'rss_taxonomy' => 0,
    );
    $this->drupalPostForm('admin/config/search/sitemap', $edit, t('Save configuration'));

    // Assert that RSS links are not included in the sitemap.
    $this->drupalGet('sitemap');
    foreach ($terms as $term) {
      $this->assertNoLinkByHref('/taxonomy/term/' . $term->id() . '/feed');
    }

    // Change RSS feed depth to 1.
    $edit = array(
      'rss_taxonomy' => 1,
    );
    $this->drupalPostForm('admin/config/search/sitemap', $edit, t('Save configuration'));

    // Assert that only RSS feed link for term 1 is included in the sitemap.
    $this->drupalGet('sitemap');
    $this->assertLinkByHref('/taxonomy/term/' . $terms[0]->id() . '/feed');
    $this->assertNoLinkByHref('/taxonomy/term/' . $terms[1]->id() . '/feed');
    $this->assertNoLinkByHref('/taxonomy/term/' . $terms[2]->id() . '/feed');

    // Change RSS feed depth to 2.
    $edit = array(
      'rss_taxonomy' => 2,
    );
    $this->drupalPostForm('admin/config/search/sitemap', $edit, t('Save configuration'));

    // Assert that RSS feed link for term 1 and term 2 is included in the site
    // map.
    $this->drupalGet('sitemap');
    $this->assertLinkByHref('/taxonomy/term/' . $terms[0]->id() . '/feed');
    $this->assertLinkByHref('/taxonomy/term/' . $terms[1]->id() . '/feed');
    $this->assertNoLinkByHref('/taxonomy/term/' . $terms[2]->id() . '/feed');

    // Change RSS feed depth to 3.
    $edit = array(
      'rss_taxonomy' => 3,
    );
    $this->drupalPostForm('admin/config/search/sitemap', $edit, t('Save configuration'));

    // Assert that all RSS links are included in the sitemap.
    $this->drupalGet('sitemap');
    foreach ($terms as $term) {
      $this->assertLinkByHref('/taxonomy/term/' . $term->id() . '/feed');
    }
  }

}
