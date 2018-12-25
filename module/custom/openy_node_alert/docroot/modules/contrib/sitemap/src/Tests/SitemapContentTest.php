<?php

namespace Drupal\sitemap\Tests;

use Drupal\simpletest\WebTestBase;
use Drupal\filter\Entity\FilterFormat;

/**
 * Test page content provided via sitemap settings.
 *
 * @group sitemap
 */
class SitemapContentTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('sitemap', 'block', 'filter');

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Place page title block.
    $this->drupalPlaceBlock('page_title_block');

    // Create filter format.
    $restricted_html_format = FilterFormat::create(array(
      'format' => 'restricted_html',
      'name' => 'Restricted HTML',
      'filters' => array(
        'filter_html' => array(
          'status' => TRUE,
          'weight' => -10,
          'settings' => array(
            'allowed_html' => '<p> <br /> <strong> <a> <em> <h4>',
          ),
        ),
        'filter_autop' => array(
          'status' => TRUE,
          'weight' => 0,
        ),
        'filter_url' => array(
          'status' => TRUE,
          'weight' => 0,
        ),
        'filter_htmlcorrector' => array(
          'status' => TRUE,
          'weight' => 10,
        ),
      ),
    ));
    $restricted_html_format->save();

    // Create user then login.
    $this->user = $this->drupalCreateUser(array(
      'administer sitemap',
      'access sitemap',
      $restricted_html_format->getPermissionName(),
    ));
    $this->drupalLogin($this->user);
  }

  /**
   * Tests page title.
   */
  public function testPageTitle() {
    // Assert default page title.
    $this->drupalGet('/sitemap');
    $this->assertTitle('Sitemap | Drupal', 'The title on the sitemap page is "Sitemap | Drupal".');

    // Change page title.
    $new_title = $this->randomMachineName();
    $edit = array(
      'page_title' => $new_title,
    );
    $this->drupalPostForm('admin/config/search/sitemap', $edit, t('Save configuration'));

    // Assert that page title is changed.
    $this->drupalGet('/sitemap');
    $this->assertTitle("$new_title | Drupal", 'The title on the sitemap page is "' . "$new_title | Drupal" . '".');
  }

  /**
   * Tests sitemap message.
   */
  public function testSitemapMessage() {
    // Assert that sitemap message is not included in the sitemap by default.
    $this->drupalGet('/sitemap');
    $elements = $this->cssSelect('.sitemap-message');
    $this->assertEqual(count($elements), 0, 'Sitemap message is not included.');

    // Change sitemap message.
    $new_message = $this->randomMachineName(16);
    $edit = array(
      'message[value]' => $new_message,
    );
    $this->drupalPostForm('admin/config/search/sitemap', $edit, t('Save configuration'));

    // Assert sitemap message is included in the sitemap.
    $this->drupalGet('/sitemap');
    $elements = $this->cssSelect(".sitemap-message:contains('" . $new_message . "')");
    $this->assertEqual(count($elements), 1, 'Sitemap message is included.');
  }

  /**
   * Tests front page.
   */
  public function testFrontPage() {
    // Assert that front page is included in the sitemap by default.
    $this->drupalGet('/sitemap');
    $elements = $this->cssSelect(".sitemap-box h2:contains('Front page')");
    $this->assertEqual(count($elements), 1, 'Front page is included.');

    // Configure module to hide front page.
    $edit = array(
      'show_front' => FALSE,
    );
    $this->drupalPostForm('admin/config/search/sitemap', $edit, t('Save configuration'));

    // Assert that front page is not included in the sitemap.
    $this->drupalGet('/sitemap');
    $elements = $this->cssSelect(".sitemap-box h2:contains('Front page')");
    $this->assertEqual(count($elements), 0, 'Front page is not included.');
  }

  /**
   * Tests titles.
   */
  public function testTitles() {
    // Assert that titles are included in the sitemap by default.
    $this->drupalGet('/sitemap');
    $elements = $this->cssSelect('.sitemap-box h2');
    $this->assertTrue(count($elements) > 0, 'Titles are included.');

    // Configure module to hide titles.
    $edit = array(
      'show_titles' => FALSE,
    );
    $this->drupalPostForm('admin/config/search/sitemap', $edit, t('Save configuration'));

    // Assert that titles are not included in the sitemap.
    $this->drupalGet('/sitemap');
    $elements = $this->cssSelect('.sitemap-box h2');
    $this->assertEqual(count($elements), 0, 'Section titles are not included.');
  }

}
