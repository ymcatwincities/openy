<?php

namespace Drupal\optimizely\Tests;

use Drupal\simpletest\WebTestBase;


/**
 * Test adding a project with a path that is an alias.
 *
 * @group Optimizely
 */
class OptimizelyAddUpdateTest extends WebTestBase {

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
  public static $modules = array('optimizely', 'node', 'language', 'path');

  /*
   * {@inheritdoc}
   */
  public static function getInfo() {
    return array(
      'name' => 'Optimizely Add / Update Project',
      'description' => 'Ensure that the add / update features function properly.',
      'group' => 'Optimizely',
    );
  }

  /**
   * {@inheritdoc}
   */
  public function setUp() {

    parent::setUp();

    $this->drupalCreateContentType(array('type' => 'page', 'name' => 'Basic page'));

    // Create an admin user. The user will have the privilege
    // 'administer optimizely'. This privilege is needed to access all
    // administration functionality of the module.
    $this->privilegedUser = $this->drupalCreateUser(array(
      'access content',
      'create page content',
      'edit own page content',
      'administer url aliases',
      'create url aliases',
      $this->optimizelyPermission));

  }

  
  public function testAddUpdateProject() {

    $this->drupalLogin($this->privilegedUser);

    // N.B. Do NOT use randomString() to generate string values because the
    // resulting strings may contain special chars that break the SQL
    // statements as well as possibly causing other problems. 
    // Use randomMachineName() instead since it generates letters and numbers only.

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
    $node1 = $this->drupalCreateNode($settings);

    // Create the url alias
    $edit_node1 = array();
    $edit_node1['source'] = '/node/' . $node1->id();
    $edit_node1['alias'] = '/' . $this->randomMachineName(10);
    $this->drupalPostForm($this->addAliasPage, $edit_node1, t('Save'));

    // Add a project with a path to the alias.
    $edit = array(
      'optimizely_project_title' => $this->randomMachineName(8),
      'optimizely_project_code' => rand(0,10000),
      'optimizely_path' => $edit_node1['alias'],
      'optimizely_enabled' => rand(0, 1),
    );
    $this->drupalPostForm($this->addUpdatePage, $edit, t('Add'));

    $project_title = db_query(
      'SELECT project_title FROM {optimizely} WHERE project_title = :optimizely_project_title',
       array(':optimizely_project_title' => $edit['optimizely_project_title']))
        ->fetchField();

    $this->assertEqual($project_title, $edit['optimizely_project_title'], 
                        t('<strong>The project was added to the database.</strong>'), 'Optimizely');

    //----- create page  
    $settings_2 = array(
      'type' => 'page',
      'title' => $this->randomMachineName(32),
      'langcode' => \Drupal\Core\Language\LanguageInterface::LANGCODE_NOT_SPECIFIED,
      'body' => array(
                  array('value' => $this->randomMachineName(64),
                        'format' => filter_default_format(),
                        ),
                ),
    );
    $node2 = $this->drupalCreateNode($settings_2);

    // Create another url alias
    $edit_node2 = array();
    $edit_node2['source'] = '/node/' . $node2->id();
    $edit_node2['alias'] = '/' . $this->randomMachineName(10);
    $this->drupalPostForm($this->addAliasPage, $edit_node2, t('Save'));

    // Update the existing project with the other alias.
    $edit_2 = array(
      'optimizely_project_title' => $this->randomMachineName(8),
      'optimizely_project_code' => rand(0,10000),
      'optimizely_path' => $edit_node2['alias'],
      'optimizely_enabled' => rand(0, 1),
    );
    $this->drupalPostForm($this->update2Page, $edit_2, t('Update'));

    // test if database was updated
    $project_title = db_query(
      'SELECT project_title FROM {optimizely} WHERE project_title = :optimizely_project_title',
       array(':optimizely_project_title' => $edit_2['optimizely_project_title']))
        ->fetchField();

    $this->assertEqual($project_title, $edit_2['optimizely_project_title'], 
                        t('<strong>The project was updated in the database.</strong>'), 'Optimizely');
  }

}
