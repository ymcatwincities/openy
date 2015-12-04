<?php
/**
 * @file
 * Global Redirect functionality tests
 */

namespace Drupal\redirect\Tests;

use Drupal\Component\Utility\SafeMarkup;
use Drupal\Core\Language\Language;
use Drupal\simpletest\WebTestBase;

/**
 * Global redirect test cases.
 *
 * @group redirect
 */
class GlobalRedirectTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('path', 'node', 'redirect', 'taxonomy', 'forum', 'views');

  /**
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $normalUser;

  /**
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $adminUser;

  /**
   * @var \Drupal\Core\Config\Config
   */
  protected $config;

  /**
   * @var \Drupal\Core\Entity\ContentEntityInterface
   */
  protected $forumTerm;

  /**
   * @var \Drupal\Core\Entity\ContentEntityInterface
   */
  protected $term;

  /**
   * @var \Drupal\Core\Entity\ContentEntityInterface
   */
  protected $node;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->config = $this->config('redirect.settings');

    $this->drupalCreateContentType(['type' => 'page', 'name' => 'Page']);
    $this->drupalCreateContentType(['type' => 'article', 'name' => 'Article']);

    // Create a users for testing the access.
    $this->normalUser = $this->drupalCreateUser([
      'access content',
      'create page content',
      'create url aliases',
      'access administration pages',
    ]);
    $this->adminUser = $this->drupalCreateUser([
      'administer site configuration',
      'access administration pages',
    ]);

    // Save the node.
    $this->node = $this->drupalCreateNode([
      'type' => 'page',
      'title' => 'Test Page Node',
      'path' => ['alias' => '/test-node'],
      'language' => Language::LANGCODE_NOT_SPECIFIED,
    ]);

    // Create an alias for the create story path - this is used in the
    // "redirect with permissions testing" test.
    \Drupal::service('path.alias_storage')->save('/admin/config/system/site-information', '/site-info');

    // Create a taxonomy term for the forum.
    $term = entity_create('taxonomy_term', [
      'name' => 'Test Forum Term',
      'vid' => 'forums',
      'langcode' => Language::LANGCODE_NOT_SPECIFIED,
    ]);
    $term->save();
    $this->forumTerm = $term;

    // Create another taxonomy vocabulary with a term.
    $vocab = entity_create('taxonomy_vocabulary', [
      'name' => 'test vocab',
      'vid' => 'test-vocab',
      'langcode' => Language::LANGCODE_NOT_SPECIFIED,
    ]);
    $vocab->save();
    $term = entity_create('taxonomy_term', [
      'name' => 'Test Term',
      'vid' => $vocab->id(),
      'langcode' => Language::LANGCODE_NOT_SPECIFIED,
      'path' => ['alias' => '/test-term'],
    ]);
    $term->save();

    $this->term = $term;
  }

  /**
   * Will test the redirects.
   */
  public function testRedirects() {

    // Test alias normalization.
    $this->config->set('normalize_aliases', TRUE)->save();
    $this->assertRedirect('node/' . $this->node->id(), 'test-node');
    $this->assertRedirect('Test-node', 'test-node');

    $this->config->set('normalize_aliases', FALSE)->save();
    $this->assertRedirect('node/' . $this->node->id(), NULL, 'HTTP/1.1 200 OK');
    $this->assertRedirect('Test-node', NULL, 'HTTP/1.1 200 OK');

    // Test deslashing.
    $this->config->set('deslash', TRUE)->save();
    $this->assertRedirect('test-node/', 'test-node');

    $this->config->set('deslash', FALSE)->save();
    $this->assertRedirect('test-node/', NULL, 'HTTP/1.1 200 OK');

    // Test front page redirects.
    $this->config->set('frontpage_redirect', TRUE)->save();
    $this->config('system.site')->set('page.front', '/node')->save();
    $this->assertRedirect('node', '<front>');

    // Test front page redirects with an alias.
    \Drupal::service('path.alias_storage')->save('/node', '/node-alias');
    $this->assertRedirect('node-alias', '<front>');

    $this->config->set('frontpage_redirect', FALSE)->save();

    $this->assertRedirect('node', NULL, 'HTTP/1.1 200 OK');
    $this->assertRedirect('node-alias', NULL, 'HTTP/1.1 200 OK');

    // Test post request.
    $this->config->set('normalize_aliases', TRUE)->save();
    $this->drupalPost('Test-node', 'application/json', array());
    // Does not do a redirect, stays in the same path.
    $this->assertEqual(basename($this->getUrl()), 'Test-node');

    // Test the access checking.
    $this->config->set('normalize_aliases', TRUE)->save();
    $this->config->set('access_check', TRUE)->save();
    $this->assertRedirect('admin/config/system/site-information', NULL, 'HTTP/1.1 403 Forbidden');

    $this->config->set('access_check', FALSE)->save();
    // @todo - here it seems that the access check runs prior to our redirecting
    //   check why so and enable the test.
    //$this->assertRedirect('admin/config/system/site-information', 'site-info');

    // Login as user with admin privileges.
    $this->drupalLogin($this->adminUser);

    // Test ignoring admin paths.
    $this->config->set('ignore_admin_path', FALSE)->save();
    $this->assertRedirect('admin/config/system/site-information', 'site-info');

    $this->config->set('ignore_admin_path', TRUE)->save();
    $this->assertRedirect('admin/config/system/site-information', NULL, 'HTTP/1.1 200 OK');
  }

  /**
   * Asserts the redirect from $path to the $expected_ending_url.
   *
   * @param string $path
   *   The request path.
   * @param $expected_ending_url
   *   The path where we expect it to redirect. If NULL value provided, no
   *   redirect is expected.
   * @param string $expected_ending_status
   *   The status we expect to get with the first request.
   */
  public function assertRedirect($path, $expected_ending_url, $expected_ending_status = 'HTTP/1.1 301 Moved Permanently') {
    $this->drupalHead($GLOBALS['base_url'] . '/' . $path);
    $headers = $this->drupalGetHeaders(TRUE);

    $ending_url = isset($headers[0]['location']) ? $headers[0]['location'] : NULL;
    $message = SafeMarkup::format('Testing redirect from %from to %to. Ending url: %url', array(
      '%from' => $path,
      '%to' => $expected_ending_url,
      '%url' => $ending_url,
    ));


    if ($expected_ending_url == '<front>') {
      $expected_ending_url = $GLOBALS['base_url'] . '/';
    }
    elseif (!empty($expected_ending_url)) {
      $expected_ending_url = $GLOBALS['base_url'] . '/' . $expected_ending_url;
    }
    else {
      $expected_ending_url = NULL;
    }

    $this->assertEqual($expected_ending_url, $ending_url);

    $this->assertEqual($headers[0][':status'], $expected_ending_status);
  }
}
