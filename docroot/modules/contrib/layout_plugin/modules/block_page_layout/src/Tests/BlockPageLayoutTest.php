<?php

/**
 * @file
 * Contains \Drupal\block_page_layout\Tests\BlockPageLayoutTest.
 */

namespace Drupal\block_page_layout\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Tests using BlockPageLayoutVariant with page_manager.
 *
 * @group layout_plugin
 */
class BlockPageLayoutTest extends WebTestBase {

  /**
   * {@inheritdoc}
   */
  protected $strictConfigSchema = FALSE;

  /**
   * {@inheritdoc}
   */
  public static $modules = ['block', 'page_manager', 'block_page_layout', 'layout_plugin_example'];

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
    ];
    $this->drupalPostForm(NULL, $edit, 'Save');

    // Add variant with a layout that has settings.
    $this->clickLink('Add new variant');
    $this->clickLink('Block page (with Layout plugin integration)');
    $edit = [
      'id' => 'block_page_layout_1',
      'label' => 'Default',
      'variant_settings[layout]' => 'layout_example_test',
    ];
    $this->drupalPostForm(NULL, $edit, 'Save');

    // Add a block.
    $this->clickLink('Add new block');
    $this->clickLink('Powered by Drupal');
    $edit = [
      'region' => 'top',
    ];
    $this->drupalPostForm(NULL, $edit, 'Add block');

    // Check the default value and change a layout setting.
    $this->assertText('Blah');
    $this->assertFieldByName("variant_settings[layout_settings][setting_1]", "Default");
    $edit = [
      'variant_settings[layout_settings][setting_1]' => 'Abracadabra',
    ];
    $this->drupalPostForm(NULL, $edit, 'Save');

    // Go back to the variant edit form and see that the setting stuck.
    $this->drupalGet('admin/structure/page_manager/manage/foo/variant/block_page_layout_1');
    $this->assertFieldByName("variant_settings[layout_settings][setting_1]", "Abracadabra");

    // View the page and make sure the setting is present.
    $this->drupalGet('testing');
    $this->assertText('Blah:');
    $this->assertText('Abracadabra');
    $this->assertText('Powered by Drupal');
  }

}
