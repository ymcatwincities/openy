<?php

namespace Drupal\optimizely\Tests;

use Drupal\Core\Language\LanguageInterface;
use Drupal\simpletest\WebTestBase;

/**
 * Tests pages that have aliases.
 *
 * Tests that the javascript snippet is included on project pages
 * through aliases and not included on non-project pages through aliases.
 *
 * @group Optimizely
 */
class OptimizelyAliasIncludeSnippetTest extends WebTestBase {

  protected $addUpdatePage = 'admin/config/system/optimizely/add_update';
  protected $addAliasPage = 'admin/config/search/path/add';

  protected $privilegedUser;

  protected $optimizelyPermission = 'administer optimizely';

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['optimizely', 'node', 'path'];

  /**
   * {@inheritdoc}
   */
  public static function getInfo() {
    return [
      'name' => 'Optimizely Alias Include Snippet',
      'description' => 'Ensure that the Optimizely snippet is included in project path when using aliases.',
      'group' => 'Optimizely',
    ];

  }

  /**
   * {@inheritdoc}
   */
  public function setUp() {

    parent::setUp();

    $this->drupalCreateContentType(['type' => 'page', 'name' => 'Basic page']);

    $this->privilegedUser = $this->drupalCreateUser([
      'access content',
      'create page content',
      'administer url aliases',
      'create url aliases',
      $this->optimizelyPermission,
    ]);

  }

  /**
   * Test inclusion and non-inclusion of snippet.
   */
  public function testIncludeSnippet() {

    $this->drupalLogin($this->privilegedUser);

    $node1 = $this->makePage();
    $node2 = $this->makePage();
    $node3 = $this->makePage();
    $node4 = $this->makePage();

    $alias1 = $this->makePageAlias($node1);
    $alias2 = $this->makePageAlias($node2);
    $alias3 = $this->makePageAlias($node3);
    $alias4 = $this->makePageAlias($node4);

    // Array holding project field values.
    $edit = [
      'optimizely_project_title' => $this->randomMachineName(8),
      'optimizely_project_code' => rand(0, 10000),
      'optimizely_path' => $alias1 . "\n" . $alias2,
      'optimizely_enabled' => 1,
    ];

    // Create snippet.
    $snippet = '//cdn.optimizely.com/js/' . $edit['optimizely_project_code'] . '.js';

    // Create the project.
    $this->drupalPostForm($this->addUpdatePage, $edit, t('Add'));

    // Log out to make sure cache refreshing works.
    $this->drupalLogout();
    // @todo check how to turn on "cache pages for anonymous users"
    // and "Aggregate JavaScript files" on Performance page
    // Check if snippet does appears on project path pages thru alias
    $this->drupalGet("node/" . $node1->id());
    $this->assertRaw($snippet, '<strong>Snippet found in markup of project path page</strong>',
                      'Optimizely');

    $this->drupalGet("node/" . $node2->id());
    $this->assertRaw($snippet, '<strong>Snippet found in markup of project path page</strong>',
                      'Optimizely');

    // Check if snippet does not appear on other non-project pages.
    $this->drupalGet("node/" . $node3->id());
    $this->assertNoRaw($snippet, '<strong>Snippet not found in markup of other page</strong>',
                      'Optimizely');

    $this->drupalGet("node/" . $node4->id());
    $this->assertNoRaw($snippet, '<strong>Snippet not found in markup of other page</strong>',
                      'Optimizely');

  }

  /**
   * Make a page with a random title.
   */
  private function makePage() {

    $settings = [
      'type' => 'page',
      'title' => $this->randomMachineName(32),
      'langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED,
      'body' => [
                  [
                    'value' => $this->randomMachineName(64),
                    'format' => filter_default_format(),
                  ],
      ],
    ];
    $node = $this->drupalCreateNode($settings);
    return $node;
  }

  /**
   * Make a random alias to an existing page.
   */
  private function makePageAlias($node) {

    $edit_node = [];
    $edit_node['source'] = '/node/' . $node->id();
    $edit_node['alias'] = '/' . $this->randomMachineName(10);
    $this->drupalPostForm($this->addAliasPage, $edit_node, t('Save'));
    // @todo create alias in 'node/add/page'

    return $edit_node['alias'];

  }

}
