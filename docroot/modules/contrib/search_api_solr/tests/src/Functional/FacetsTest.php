<?php

namespace Drupal\Tests\search_api_solr\Functional;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Tests\facets\Functional\BlockTestTrait;
use Drupal\Tests\facets\Functional\ExampleContentTrait;
use Drupal\Tests\facets\Functional\TestHelperTrait;
use Drupal\search_api\Entity\Index;
use Drupal\Tests\search_api\Functional\SearchApiBrowserTestBase;
use Drupal\views\Entity\View;

/**
 * Tests the facets functionality using the Solr backend.
 *
 * @group search_api_solr
 */
class FacetsTest extends SearchApiBrowserTestBase {

  use BlockTestTrait;
  use ExampleContentTrait {
    indexItems as doIndexItems;
  }
  use TestHelperTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = array(
    'block',
    'views',
    'search_api_solr',
    'search_api_solr_test',
    'search_api_solr_test_facets',
    'facets',
  );

  /**
   * {@inheritdoc}
   */
  protected function tearDown() {
    if ($this->indexId) {
      Index::load($this->indexId)->clear();
      sleep(2);
    }
    parent::tearDown();
  }

  /**
   * Tests basic facets integration.
   */
  public function testFacets() {
    $this->indexId = 'solr_search_index';
    $view = View::load('search_api_test_view');
    $this->assertEquals('search_api_index_solr_search_index', $view->get('base_table'));

    // Create the users used for the tests.
    $admin_user = $this->drupalCreateUser([
      'administer search_api',
      'administer facets',
      'access administration pages',
      'administer blocks',
    ]);
    $this->drupalLogin($admin_user);

    // Check that the test index is on the admin overview.
    $this->drupalGet('admin/config/search/search-api');
    $this->assertSession()->pageTextContains('Test index');

    $this->setUpExampleStructure();
    $this->insertExampleContent();
    $indexed_items = $this->indexItems($this->indexId);
    $this->assertEquals(5, $indexed_items, 'Five items are indexed.');

    // Create a facet, enable 'show numbers'.
    $this->createFacet('Owl', 'owl');
    $edit = ['widget' => 'links', 'widget_config[show_numbers]' => '1'];
    $this->drupalPostForm('admin/config/search/facets/owl/edit', $edit, 'Save');

    // Verify that the facet results are correct.
    $this->drupalGet('search-api-test-fulltext');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertFacetLabel('item (3)');
    $this->assertFacetLabel('article (2)');
    $this->assertSession()->pageTextContains('Displaying 5 search results');
    $this->clickLinkPartialName('item');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('Displaying 3 search results');
  }

  /**
   * Indexes all (unindexed) items on the specified index.
   *
   * @return int
   *   The number of successfully indexed items.
   */
  protected function indexItems($index_id) {
    $index_status = $this->doindexItems($index_id);
    sleep(2);
    return $index_status;
  }

  /**
   * Follows a link by partial name.
   *
   * If the link is discovered and clicked, the test passes. Fail otherwise.
   *
   * @param string|\Drupal\Component\Render\MarkupInterface $label
   *   Text between the anchor tags, uses starts-with().
   * @param int $index
   *   Link position counting from zero.
   *
   * @return string|bool
   *   Page contents on success, or FALSE on failure.
   *
   * @see ::clickLink()
   */
  protected function clickLinkPartialName($label, $index = 0) {
    return $this->clickLinkHelper($label, $index, '//a[starts-with(normalize-space(), :label)]');
  }

  /**
   * Provides a helper for ::clickLink() and ::clickLinkPartialName().
   *
   * @param string|\Drupal\Component\Render\MarkupInterface $label
   *   Text between the anchor tags, uses starts-with().
   * @param int $index
   *   Link position counting from zero.
   * @param string $pattern
   *   A pattern to use for the XPath.
   *
   * @return bool|string
   *   Page contents on success, or FALSE on failure.
   */
  protected function clickLinkHelper($label, $index, $pattern) {
    // Cast MarkupInterface objects to string.
    $label = (string) $label;
    $url_before = $this->getUrl();
    $urls = $this->xpath($pattern, array(':label' => $label));
    if (isset($urls[$index])) {
      /** @var \Behat\Mink\Element\NodeElement $url */
      $url = $urls[$index];
      $url_target = $this->getAbsoluteUrl($url->getAttribute('href'));
      $this->assertTrue(TRUE, new FormattableMarkup('Clicked link %label (@url_target) from @url_before', array('%label' => $label, '@url_target' => $url_target, '@url_before' => $url_before)));
      return $this->drupalGet($url_target);
    }
    $this->assertTrue(FALSE, new FormattableMarkup('Link %label does not exist on @url_before', array('%label' => $label, '@url_before' => $url_before)));
    return FALSE;
  }

}
