<?php

namespace Drupal\panels\Tests;

use Drupal\simpletest\WebTestBase;
use Drupal\user\Entity\User;

/**
 * Tests using PanelsVariant with page_manager.
 *
 * @group panels
 */
class PanelsTest extends WebTestBase {

  /**
   * {@inheritdoc}
   */
  protected $strictConfigSchema = FALSE;

  /**
   * {@inheritdoc}
   */
  public static $modules = ['block', 'page_manager', 'page_manager_ui', 'panels_test'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->drupalPlaceBlock('local_tasks_block');
    $this->drupalPlaceBlock('local_actions_block');
    $this->drupalPlaceBlock('system_branding_block');
    $this->drupalPlaceBlock('page_title_block');

    \Drupal::service('theme_handler')->install(['bartik', 'classy']);
    $this->config('system.theme')->set('admin', 'classy')->save();

    $this->drupalLogin($this->drupalCreateUser(['administer pages', 'access administration pages', 'view the administration theme']));
  }

  /**
   * Tests adding a layout with settings.
   */
  public function testLayoutSettings() {
    // Create new page.
    $this->drupalGet('admin/structure/page_manager/add');
    $edit = [
      'id' => 'foo',
      'label' => 'foo',
      'path' => 'testing',
      'variant_plugin_id' => 'panels_variant',
    ];
    $this->drupalPostForm(NULL, $edit, 'Next');

    // Add variant with a layout that has settings.
    $edit = [
      'page_variant_label' => 'Default',
    ];
    $this->drupalPostForm(NULL, $edit, 'Next');

    // Choose a layout.
    $edit = [
      'layout' => 'layout_example_test',
    ];
    $this->drupalPostForm(NULL, $edit, 'Next');

    // Update the layout's settings.
    $this->assertFieldByName('layout_settings_wrapper[layout_settings][setting_1]', 'Default');
    $edit = [
      'layout_settings_wrapper[layout_settings][setting_1]' => 'Abracadabra',
    ];
    $this->drupalPostForm(NULL, $edit, 'Next');

    // Add a block.
    $this->clickLink('Add new block');
    $this->clickLink('Powered by Drupal');
    $edit = [
      'region' => 'top',
    ];
    $this->drupalPostForm(NULL, $edit, 'Add block');

    // Finish the page add wizard.
    $this->drupalPostForm(NULL, [], 'Finish');

    // View the page and make sure the setting is present.
    $this->drupalGet('testing');
    $this->assertText('Blah:');
    $this->assertText('Abracadabra');
    $this->assertText('Powered by Drupal');
  }

  /**
   * Tests that special characters are not escaped when using tokens in titles.
   */
  public function testPageTitle() {
    // Change the logged in user's name to include a special character.
    $user = User::load($this->loggedInUser->id());
    $user->setUsername("My User's Name");
    $user->save();

    // Create new page.
    $this->drupalGet('admin/structure/page_manager/add');
    $edit = [
      'id' => 'foo',
      'label' => 'foo',
      'path' => 'testing',
      'variant_plugin_id' => 'panels_variant',
    ];
    $this->drupalPostForm(NULL, $edit, 'Next');

    // Use default variant settings.
    $edit = [
      'page_variant_label' => 'Default',
    ];
    $this->drupalPostForm(NULL, $edit, 'Next');

    // Choose a simple layout.
    $edit = [
      'layout' => 'layout_onecol',
    ];
    $this->drupalPostForm(NULL, $edit, 'Next');

    // Set the title to a token value that includes an apostrophe.
    $edit = [
      'page_title' => '[user:name]',
    ];
    $this->drupalPostForm(NULL, $edit, 'Finish');

    // View the page and make sure the page title is valid.
    $this->drupalGet('testing');
    // We expect "'" to be escaped only once, which is why we're doing a raw
    // assertion here.
    $this->assertRaw('<h1 class="page-title">My User&#039;s Name</h1>');
  }

}
