<?php

namespace Drupal\Tests\scheduler\Functional;

use Drupal\node\Entity\NodeType;

/**
 * Tests the display of the date entry fields (vertical tab, fieldset).
 *
 * @group scheduler
 */
class SchedulerFieldsDisplayTest extends SchedulerBrowserTestBase {

  /**
   * Additional module field_ui is required for the 'manage form display' test.
   *
   * @var array
   */
  public static $modules = ['field_ui'];

  /**
   * Tests date input is displayed as vertical tab or an expandable fieldset.
   */
  public function testFieldsDisplay() {
    $this->drupalLogin($this->adminUser);

    // Check that the dates are shown in a vertical tab by default.
    $this->drupalGet('node/add/page');
    $this->assertTrue($this->xpath('//div[contains(@class, "form-type-vertical-tabs")]//details[@id = "edit-scheduler-settings"]'), 'By default the scheduler dates are shown in a vertical tab.');

    // Check that the dates are shown as a fieldset when configured to do so.
    $this->nodetype->setThirdPartySetting('scheduler', 'fields_display_mode', 'fieldset')->save();
    $this->drupalGet('node/add/page');
    $this->assertFalse($this->xpath('//div[contains(@class, "form-type-vertical-tabs")]//details[@id = "edit-scheduler-settings"]'), 'The scheduler dates are not shown in a vertical tab when they are configured to show as a fieldset.');
    $this->assertTrue($this->xpath('//details[@id = "edit-scheduler-settings"]'), 'The scheduler dates are shown in a fieldset when they are configured to show as a fieldset.');
    $this->assertTrue($this->xpath('//details[@id = "edit-scheduler-settings" and not(@open = "open")]'), 'The scheduler dates fieldset is collapsed by default.');

    // Check that the fieldset is expanded if either of the scheduling dates
    // are required.
    $this->nodetype->setThirdPartySetting('scheduler', 'publish_required', TRUE)->save();
    $this->drupalGet('node/add/page');
    $this->assertTrue($this->xpath('//details[@id = "edit-scheduler-settings" and @open = "open"]'), 'The scheduler dates are shown in an expanded fieldset when the publish-on date is required.');

    $this->nodetype->setThirdPartySetting('scheduler', 'publish_required', FALSE)
      ->setThirdPartySetting('scheduler', 'unpublish_required', TRUE)->save();
    $this->drupalGet('node/add/page');
    $this->assertTrue($this->xpath('//details[@id = "edit-scheduler-settings" and @open = "open"]'), 'The scheduler dates are shown in an expanded fieldset when the unpublish-on date is required.');

    // Check that the fieldset is expanded if the 'always' option is set.
    $this->nodetype->setThirdPartySetting('scheduler', 'publish_required', FALSE)
      ->setThirdPartySetting('scheduler', 'unpublish_required', FALSE)
      ->setThirdPartySetting('scheduler', 'expand_fieldset', 'always')->save();
    $this->drupalGet('node/add/page');
    $this->assertTrue($this->xpath('//details[@id = "edit-scheduler-settings" and @open = "open"]'), 'The scheduler dates are shown in an expanded fieldset when the option to always expand is turned on.');

    // Check that the fieldset is expanded if the node already has a publish-on
    // date. This requires editing an existing scheduled node.
    $this->nodetype->setThirdPartySetting('scheduler', 'expand_fieldset', 'when_required')->save();
    $options = [
      'title' => 'Contains Publish-on date ' . $this->randomMachineName(10),
      'type' => $this->type,
      'publish_on' => strtotime('+1 day'),
    ];
    $node = $this->drupalCreateNode($options);
    $this->drupalGet('node/' . $node->id() . '/edit');
    $this->assertTrue($this->xpath('//details[@id = "edit-scheduler-settings" and @open = "open"]'), 'The scheduler dates are shown in an expanded fieldset when a publish-on date already exists.');

    // Check that the fieldset is expanded if the node has an unpublish-on date.
    $options = [
      'title' => 'Contains Unpublish-on date ' . $this->randomMachineName(10),
      'type' => $this->type,
      'unpublish_on' => strtotime('+1 day'),
    ];
    $node = $this->drupalCreateNode($options);
    $this->drupalGet('node/' . $node->id() . '/edit');
    $this->assertTrue($this->xpath('//details[@id = "edit-scheduler-settings" and @open = "open"]'), 'The scheduler dates are shown in an expanded fieldset when an unpublish-on date already exists.');
  }

  /**
   * Tests the settings entry in the content type form display.
   *
   * This test covers scheduler_entity_extra_field_info().
   */
  public function testManageFormDisplay() {

    // Create a custom administrator user with permissions to use the field_ui
    // module 'node form display' tab.
    $this->adminUser = $this->drupalCreateUser([
      'administer content types',
      'administer node form display',
    ]);
    $this->drupalLogin($this->adminUser);

    // Check that the weight input field is displayed when the content type is
    // enabled for scheduling. This field still exists even with tabledrag on.
    $this->drupalGet('admin/structure/types/manage/' . $this->type . '/form-display');
    $this->assertFieldById('edit-fields-scheduler-settings-weight', NULL, 'The scheduler settings row is shown when the content type is enabled for scheduling.');

    // Check that the weight input field is not displayed when the content type
    // is not enabled for scheduling.
    $this->nodetype->setThirdPartySetting('scheduler', 'publish_enable', FALSE)
      ->setThirdPartySetting('scheduler', 'unpublish_enable', FALSE)->save();
    $this->drupalGet('admin/structure/types/manage/' . $this->type . '/form-display');
    $this->assertNoFieldById('edit-fields-scheduler-settings-weight', NULL, 'The scheduler settings row is not shown when the content type is not enabled for scheduling.');
  }

}
