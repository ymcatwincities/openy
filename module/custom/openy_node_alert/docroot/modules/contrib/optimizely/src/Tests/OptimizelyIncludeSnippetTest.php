<?php

namespace Drupal\optimizely\Tests;

use Drupal\Core\Language\LanguageInterface;
use Drupal\simpletest\WebTestBase;

/**
 * Test that the javascript snippet is included on project pages.
 *
 * And not included on non-project pages.
 *
 * @group Optimizely
 */
class OptimizelyIncludeSnippetTest extends WebTestBase {

  protected $addUpdatePage = 'admin/config/system/optimizely/add_update';

  protected $privilegedUser;

  protected $optimizelyPermission = 'administer optimizely';

  /**
   * List of modules to enable.
   *
   * @var array
   */
  public static $modules = ['optimizely', 'node'];

  /**
   * {@inheritdoc}
   */
  public static function getInfo() {
    return [
      'name' => 'Optimizely Include Snippet',
      'description' => 'Ensure that the Optimizely snippet is included' .
      ' in project path when not using aliases.',
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
      $this->optimizelyPermission,
    ]);

  }

  /**
   * Test whether snippet is correctly included or not included.
   */
  public function testIncludeSnippet() {

    $this->drupalLogin($this->privilegedUser);

    $node1 = $this->makePage();
    $node2 = $this->makePage();
    $node3 = $this->makePage();
    $node4 = $this->makePage();

    // Array holding project field values.
    $edit = [
      'optimizely_project_title' => $this->randomMachineName(8),
      'optimizely_project_code' => rand(0, 10000),
      'optimizely_path' => "/node/" . $node1->id() . "\n" . "/node/" . $node2->id(),
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
    // Check if snippet does appears on project path pages
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

}
