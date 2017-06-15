<?php

/**
 * @file
 * Contains \Drupal\embed\Tests\EmbedButtonAdminTest.
 */

namespace Drupal\embed\Tests;

/**
 * Tests the administrative UI.
 *
 * @group embed
 */
class EmbedButtonAdminTest extends EmbedTestBase {

  /**
   * Tests the embed_button administration functionality.
   */
  public function testEmbedButtonAdmin() {
    // Ensure proper access to the Embed settings page.
    $this->drupalGet('admin/config/content/embed');
    $this->assertResponse(403);

    $this->drupalLogin($this->adminUser);
    $this->drupalGet('admin/config/content/embed');
    $this->assertResponse(200);

    // Add embed button.
    $this->clickLink('Add embed button');
    $button_id = strtolower($this->randomMachineName());
    $button_label = $this->randomMachineName();
    $edit = array(
      'id' => $button_id,
      'label' => $button_label,
      'type_id' => 'embed_test_default',
    );
    $this->drupalPostForm(NULL, $edit, 'Save');
    // Ensure that the newly created button is listed.
    $this->drupalGet('admin/config/content/embed');
    $this->assertText($button_label, 'Test embed_button appears on the list page');

    // Edit embed button.
    $this->drupalGet('admin/config/content/embed/button/manage/' . $button_id);
    $new_button_label = $this->randomMachineName();
    $edit = array(
      'label' => $new_button_label,
    );
    $this->drupalPostForm(NULL, $edit, 'Save');
    // Ensure that name and label has been changed.
    $this->drupalGet('admin/config/content/embed');
    $this->assertText($new_button_label, 'New label appears on the list page');
    $this->assertNoText($button_label, 'Old label does not appears on the list page');

    // Delete embed button.
    $this->drupalGet('admin/config/content/embed/button/manage/' . $button_id . '/delete');
    $this->drupalPostForm(NULL, array(), 'Delete');
    // Ensure that the deleted embed button no longer exists.
    $this->drupalGet('admin/config/content/embed/button/manage/' . $button_id);
    $this->assertResponse(404, 'Deleted embed button no longer exists.');
    // Ensure that the deleted button is no longer listed.
    $this->drupalGet('admin/config/content/embed');
    $this->assertNoText($button_label, 'Test embed_button does not appears on the list page');
  }

  public function testButtonValidation() {
    $this->drupalLogin($this->adminUser);
    $button_id = strtolower($this->randomMachineName());
    $edit = array(
      'id' => $button_id,
      'label' => $this->randomMachineName(),
      'type_id' => 'embed_test_aircraft',
    );
    $this->drupalPostAjaxForm('admin/config/content/embed/button/add', $edit, 'type_id');
    $this->assertFieldByName('type_settings[aircraft_type]', 'fixed-wing');

    $edit['type_settings[aircraft_type]'] = 'invalid';
    $this->drupalPostForm(NULL, $edit, 'Save');
    $this->assertText('Cannot select invalid aircraft type.');

    $edit['type_settings[aircraft_type]'] = 'helicopters';
    $this->drupalPostForm(NULL, $edit, 'Save');
    $this->assertText('Helicopters are just rotorcraft.');

    $this->drupalGet('admin/config/content/embed/button/manage/' . $button_id);
    $this->assertFieldByName('type_settings[aircraft_type]', 'rotorcraft');
  }

}
