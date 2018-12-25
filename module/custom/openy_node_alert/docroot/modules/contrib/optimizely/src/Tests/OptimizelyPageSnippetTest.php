<?php

namespace Drupal\optimizely\Tests;

use Drupal\simpletest\WebTestBase;
use Drupal\Core\Language\LanguageInterface;

/**
 * Tests that the javascript snippet is included on a variety of paths.
 *
 * @group Optimizely
 */
class OptimizelyPageSnippetTest extends WebTestBase {

  protected $addUpdatePage = 'admin/config/system/optimizely/add_update';
  protected $update2Page = 'admin/config/system/optimizely/add_update/2';
  protected $update3Page = 'admin/config/system/optimizely/add_update/3';
  protected $update4Page = 'admin/config/system/optimizely/add_update/4';
  protected $update5Page = 'admin/config/system/optimizely/add_update/5';

  protected $listingPage = 'admin/config/system/optimizely';
  protected $addAliasPage = 'admin/config/search/path/add';

  protected $anonymousUser;
  protected $privilegedUser;

  protected $optimizelyPermission = 'administer optimizely';

  protected $projectCode;
  protected $projectPaths;

  /**
   * Currently, $projectNodes and $projectAliases are not really used. EF.
   *
   * @var array
   */
  protected $projectNodes;
  protected $projectAliases;

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
      'name' => 'Optimizely Presence of Javascript Snippet',
      'description' => 'Test the presence of the Optimizely snippet
         (Javascript call) on pages (paths) defined in project entries.',
      'group' => 'Optimizely',
    ];

  }

  /**
   * {@inheritdoc}
   */
  public function setUp() {

    parent::setUp();

    $this->drupalCreateContentType(['type' => 'page', 'name' => 'Basic page']);

    $this->anonymousUser = $this->drupalCreateUser(['access content']);

    $this->privilegedUser = $this->drupalCreateUser([
      'access content',
      'create page content',
      'administer url aliases',
      'create url aliases',
      $this->optimizelyPermission,
    ]);

    /*
     * Pages
     * 1. 1 x page (node/x), no alias
     * 2. 1 x page, article
     * 2. 3 x page (node/x), 2 x alias - "article/one, article/two"
     * 3. 2 x sub page (node/x), 2 x alias - "article/one/sub, article/two/sub"
     * 4. <front>, node/x, article/three
     *
     * Projects
     * 1. node/x,
     * 2. article/one
     * 3. node/x, article/one, node/x
     * 4. article/one, node/x, article/two
     * 5. node/*
     * 6. article/* <-- Multi matches:
     *   article, article/one, article/two,
     *   article/one/sub, article/two/sub
     * 7. <front> (article/one)
     * 8. <front>, article/one <-- non unique path
     * 9. node/*, article/* <-- non unique path
     * 10. article/one/* <-- Multi matches: article/one/sub
     * 11. article, article/one, article/one/*
     * 12. article, node/x, article/one, article/two/*
     * 13. node/x, article/one, article/two/*, user, user/*
     * 14. article/one?param=xx&parm2=xxx
     * 15. node/x, article/one, article/two/*, user/*,
     *     article?param=xx&parm2=xxx
     *
     * ++ multi projects enabled
     */

    // Test page creation at base alias path.
    $this->projectNodes = [];
    $this->projectAliases = [];
    $this->projectPaths = [];

    $subpath = '';

    // Access with privileged user.
    $this->drupalLogin($this->privilegedUser);

    for ($project_count = 0; $project_count < 4; $project_count++) {

      $this->projectPaths[$project_count] = '';
      for ($page_count = 0; $page_count < 5; $page_count++) {

        // Create page.
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

        // Keep track of the nids created.
        $this->projectNodes[] = $node->id();

        $random_alias_node = mt_rand(0, 1);
        if ($random_alias_node) {
          $alias = '/' . $this->randomMachineName(10);

          // Random subpath alias.
          $random_subalias_node = mt_rand(0, 1);
          if ($random_subalias_node) {
            $subpath = '/' . $this->randomMachineName(10);
            $alias = $subpath . $alias;
          }

          // Create the url alias.
          $edit_node = [];
          $edit_node['source'] = '/node/' . $node->id();
          $edit_node['alias'] = $alias;
          $this->drupalPostForm($this->addAliasPage, $edit_node, t('Save'));

          // Keep track of alias created.
          $this->projectAliases[] = $alias;

          // Randomly create wildcard project path entry.
          $random_project_wildcard = mt_rand(0, 1);
          if ($random_project_wildcard && $subpath != '') {

            $edit_node['alias'] = $subpath . '/*';
            $subpath = '';

            // Add alias to project path setting variable.
            if (!empty($this->projectPaths[$project_count])) {
              $this->projectPaths[$project_count] =
                $edit_node['alias'] . "\n" . $this->projectPaths[$project_count];
            }
            else {
              $this->projectPaths[$project_count] = $edit_node['alias'];
            }
          }
          elseif ($subpath == '') {
            if (!empty($this->projectPaths[$project_count])) {
              $this->projectPaths[$project_count] =
                $edit_node['alias'] . "\n" . $this->projectPaths[$project_count];
            }
            else {
              $this->projectPaths[$project_count] = $edit_node['alias'];
            }
          }

        }
        // No alias, use system node path.
        else {
          if (!empty($this->projectPaths[$project_count])) {
            $this->projectPaths[$project_count] =
                '/node/' . $node->id() . "\n" . $this->projectPaths[$project_count];
          }
          else {
            $this->projectPaths[$project_count] = '/node/' . $node->id();
          }

        }

      }

      // Create Projects.
      $this->projectCode[$project_count] = mt_rand(0, 10000);

      // Add project with path setting to page.
      $edit = [
        'optimizely_project_title' => $this->randomMachineName(8),
        'optimizely_project_code' => $this->projectCode[$project_count],
        'optimizely_path' => $this->projectPaths[$project_count],
        'optimizely_enabled' => 0,
      ];

      $this->drupalPostForm($this->addUpdatePage, $edit, t('Add'));

    }

  }

  /**
   * Test that snippet is present after project is enabled.
   *
   * 1. node/x,
   * 2. article/one
   * 3. node/x, article/one, node/x
   * 4. article/one, node/x, article/two.
   */
  public function testPageSnippetPresence() {

    $this->drupalLogin($this->privilegedUser);

    // Enable all four projects.
    $edit = [
      'optimizely_enabled' => 1,
    ];

    $this->drupalPostForm($this->update2Page, $edit, t('Update'));
    $this->drupalPostForm($this->update3Page, $edit, t('Update'));
    $this->drupalPostForm($this->update4Page, $edit, t('Update'));
    $this->drupalPostForm($this->update5Page, $edit, t('Update'));

    // Test that Project 2 was enabled.
    $this->drupalGet($this->listingPage);
    $this->assertRaw('name="project-2" checked="checked"',
      '<strong>Project 2 is enabled, ready to test path settings for presence of snippet.</strong>',
      'Optimizely');

    $this->drupalLogout();

    $this->drupalLogin($this->anonymousUser);

    $num_projects = count($this->projectCode);
    for ($project_count = 0; $project_count <= $num_projects; $project_count++) {

      // Test project paths for presence of snippet.
      if (!empty($this->projectPaths[$project_count])) {
        $paths = explode("\n", $this->projectPaths[$project_count]);
      }
      else {
        $paths = [];
      }

      foreach ($paths as $path) {
        // End test if path value is invalid.
        if ($path == '') {
          break;
        }

        // Only test non wildcard paths.
        if (strpos($path, '/*') === FALSE) {
          // Look up the page defined in the project.
          $this->drupalGet($path);

          $target_html =
            '"//cdn.optimizely.com/js/' . $this->projectCode[$project_count] . '.js"';
          $this->assertRaw("src=$target_html",
            "<strong>Optimizely snippet call $target_html found</strong> at: " . $path,
            'Optimizely');
        }
      }

    }

  }

}
