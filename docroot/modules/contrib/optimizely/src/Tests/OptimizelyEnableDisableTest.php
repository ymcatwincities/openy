<?php

namespace Drupal\optimizely\Tests;

use Drupal\simpletest\WebTestBase;


/**
 * Test enabling / disabling non-default project from update page
 *
 * @group Optimizely
 */
class OptimizelyEnableDisableTest extends WebTestBase {

  protected $addUpdatePage = 'admin/config/system/optimizely/add_update';
  protected $update2Page = 'admin/config/system/optimizely/add_update/2';
  protected $addAliasPage = 'admin/config/search/path/add';

  protected $privilegedUser;

  protected $optimizelyPermission = 'administer optimizely';

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('optimizely', 'node', 'path');
  // public static $modules = array('optimizely', 'node', 'language', 'path');

  /*
   * {@inheritdoc}
   */
  public static function getInfo() {
    return array(
      'name' => 'Optimizely Enable / Disable Project',
      'description' => 'Test enabling / disabling non-default projects.',
      'group' => 'Optimizely',
    );
  }

  /**
   * {@inheritdoc}
   */
  public function setUp() {

    parent::setUp();

    $this->drupalCreateContentType(array('type' => 'page', 'name' => 'Basic page'));

    $this->privilegedUser = $this->drupalCreateUser(array(
      'access content',
      'create page content',
      'administer url aliases',
      'create url aliases',
      $this->optimizelyPermission));
  }

  public function testEnableDisable( ) {

    $this->drupalLogin($this->privilegedUser);

    //----- create page
    $settings = array(
      'type' => 'page',
      'title' => $this->randomMachineName(32),
      'langcode' => \Drupal\Core\Language\LanguageInterface::LANGCODE_NOT_SPECIFIED,
      'body' => array(
                  array('value' => $this->randomMachineName(64),
                        'format' => filter_default_format(),
                        ),
                ),
    );
    $node = $this->drupalCreateNode($settings);

    // Create the url alias
    // N.B. The source and alias paths MUST start with a leading slash.
    $edit_node = array();
    $edit_node['source'] = '/node/' . $node->id();
    $edit_node['alias'] = '/' . $this->randomMachineName(10);
    $this->drupalPostForm($this->addAliasPage, $edit_node, t('Save'));

    // Add a project with a path to the alias.
    $edit = array(
      'optimizely_project_title' => $this->randomMachineName(8),
      'optimizely_project_code' => mt_rand(0,10000),
      'optimizely_path' => $edit_node['alias'],
      'optimizely_enabled' => 0,
    );
    $this->drupalPostForm($this->addUpdatePage, $edit, t('Add'));

    $edit_2 = array(
      'optimizely_enabled' => 1,
    );
    $this->drupalPostForm($this->update2Page, $edit_2, t('Update'));

    // test if project was enabled
    $enabled = db_query('SELECT enabled FROM {optimizely} WHERE oid = 2')->fetchField();
    $this->assertEqual($enabled, $edit_2['optimizely_enabled'],
                        t('<strong>The project was enabled from update page.</strong>'), 'Optimizely');

    $edit_3 = array(
      'optimizely_enabled' => 0,
    );
    $this->drupalPostForm($this->update2Page, $edit_3, t('Update'));

    // test if project was disabled
    $enabled = db_query('SELECT enabled FROM {optimizely} WHERE oid = 2')->fetchField();
    $this->assertEqual($enabled, $edit_3['optimizely_enabled'],
                        t('<strong>The project was disabled from update page.</strong>'),'Optimizely');

  }

}