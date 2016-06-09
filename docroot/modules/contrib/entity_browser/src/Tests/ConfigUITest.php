<?php

/**
 * @file
 * Contains \Drupal\entity_browser\Tests\ConfigUITest.
 */

namespace Drupal\entity_browser\Tests;
use Drupal\entity_browser\Plugin\EntityBrowser\Display\IFrame;
use Drupal\entity_browser\Plugin\EntityBrowser\SelectionDisplay\NoDisplay;
use Drupal\entity_browser\Plugin\EntityBrowser\WidgetSelector\Tabs;
use Drupal\simpletest\WebTestBase;

/**
 * Tests the entity browser config UI.
 *
 * @group entity_browser
 */
class ConfigUITest extends WebTestBase {

  /**
   * The test user.
   *
   * @var \Drupal\User\UserInterface
   */
  protected $adminUser;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['entity_browser', 'ctools', 'block'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->drupalPlaceBlock('local_actions_block');
    $this->adminUser = $this->drupalCreateUser([
      'administer entity browsers',
    ]);
  }

  /**
   * Tests the entity browser config UI.
   */
  public function testConfigUI() {
    $this->drupalGet('/admin/config/content/entity_browser');
    $this->assertResponse(403, "Anonymous user can't access entity browser listing page.");
    $this->drupalGet('/admin/config/content/entity_browser/add');
    $this->assertResponse(403, "Anonymous user can't access entity browser add form.");

    // Listing is empty.
    $this->drupalLogin($this->adminUser);
    $this->drupalGet('/admin/config/content/entity_browser');
    $this->assertResponse(200, 'Admin user is able to navigate to the entity browser listing page.');
    $this->assertText('There is no Entity browser yet.', 'Entity browsers table is empty.');

    // Add page.
    $this->clickLink('Add Entity browser');
    $this->assertUrl('/admin/config/content/entity_browser/add');
    $edit = [
      'label' => 'Test entity browser',
      'id' => 'test_entity_browser',
      'display' => 'iframe',
      'widget_selector' => 'tabs',
      'selection_display' => 'no_display',
    ];
    $this->drupalPostForm(NULL, $edit, 'Next');

    // Display configuration step.
    $this->assertUrl('/admin/config/content/entity_browser/test_entity_browser/display', ['query' => ['js' => 'nojs']]);
    $edit = [
      'width' => 100,
      'height' => 100,
      'link_text' => 'All animals are created equal',
      'auto_open' => TRUE,
    ];
    $this->drupalPostForm(NULL, $edit, 'Next');

    // Widget selector step.
    $this->assertUrl('/admin/config/content/entity_browser/test_entity_browser/widget_selector', ['query' => ['js' => 'nojs']]);
    $this->assertText('This plugin has no configuration options.');
    $this->drupalPostForm(NULL, [], 'Next');

    // Selection display step.
    $this->assertUrl('/admin/config/content/entity_browser/test_entity_browser/selection_display', ['query' => ['js' => 'nojs']]);
    $this->assertText('This plugin has no configuration options.');
    $this->drupalPostForm(NULL, [], 'Next');

    // Widgets step.
    $this->assertUrl('/admin/config/content/entity_browser/test_entity_browser/widgets', ['query' => ['js' => 'nojs']]);
    $this->drupalPostAjaxForm(NULL, ['widget' => 'upload'], 'widget');
    $this->drupalPostForm(NULL, [], 'Finish');

    // Back on listing page.
    $this->assertUrl('/admin/config/content/entity_browser');
    $this->assertText('Test entity browser', 'Entity browser label found on the listing page');
    $this->assertText('test_entity_browser', 'Entity browser ID found on the listing page.');

    // Check structure of entity browser object.
    /** @var \Drupal\entity_browser\EntityBrowserInterface $loaded_entity_browser */
    $loaded_entity_browser = $this->container->get('entity_type.manager')
      ->getStorage('entity_browser')
      ->load('test_entity_browser');
    $this->assertEqual('test_entity_browser', $loaded_entity_browser->id(), 'Entity browser ID was correctly saved.');
    $this->assertEqual('Test entity browser', $loaded_entity_browser->label(), 'Entity browser label was correctly saved.');
    $this->assertTrue($loaded_entity_browser->getDisplay() instanceof IFrame, 'Entity browser display was correctly saved.');
    $expected = [
      'width' => '100',
      'height' => '100',
      'link_text' => 'All animals are created equal',
      'auto_open' => TRUE,
    ];
    $this->assertEqual($expected, $loaded_entity_browser->getDisplay()->getConfiguration(), 'Entity browser display configuration was correctly saved.');
    $this->assertTrue($loaded_entity_browser->getSelectionDisplay() instanceof NoDisplay, 'Entity browser selection display was correctly saved.');
    $this->assertEqual([], $loaded_entity_browser->getSelectionDisplay()->getConfiguration(), 'Entity browser selection display configuration was correctly saved.');
    $this->assertEqual($loaded_entity_browser->getWidgetSelector() instanceof Tabs, 'Entity browser widget selector was correctly saved.');
    $this->assertEqual([], $loaded_entity_browser->getWidgetSelector()->getConfiguration(), 'Entity browser widget selector configuration was correctly saved.');

    $widgets = $loaded_entity_browser->getWidgets();
    $uuid = current($widgets->getInstanceIds());
    /** @var \Drupal\entity_browser\WidgetInterface $widget */
    $widget = $widgets->get($uuid);
    $this->assertEqual('upload', $widget->id(), 'Entity browser widget was correctly saved.');
    $this->assertEqual($uuid, $widget->uuid(), 'Entity browser widget uuid was correctly saved.');
    $configuration = $widget->getConfiguration()['settings'];
    $this->assertEqual(['upload_location' => 'public://'], $configuration, 'Entity browser widget configuration was correctly saved.');
    $this->assertEqual(1, $widget->getWeight(), 'Entity browser widget weight was correctly saved.');

    // Navigate to edit.
    $this->clickLink('Edit');
    $this->assertUrl('/admin/config/content/entity_browser/test_entity_browser');
    $this->assertFieldById('edit-label', 'Test entity browser', 'Correct label found.');
    $this->assertText('test_entity_browser', 'Correct id found.');
    $this->assertOptionSelected('edit-display', 'iframe', 'Correct display selected.');
    $this->assertOptionSelected('edit-widget-selector', 'tabs', 'Correct widget selector selected.');
    $this->assertOptionSelected('edit-selection-display', 'no_display', 'Correct selection display selected.');

    $this->drupalPostForm(NULL,[], 'Next');
    $this->assertUrl('/admin/config/content/entity_browser/test_entity_browser/display', ['query' => ['js' => 'nojs']]);
    $this->assertFieldById('edit-width', '100', 'Correct value for width found.');
    $this->assertFieldById('edit-height', '100', 'Correct value for height found.');
    $this->assertFieldById('edit-link-text', 'All animals are created equal', 'Correct value for link text found.');
    $this->assertFieldChecked('edit-auto-open', 'Auto open is enabled.');

    $this->drupalPostForm(NULL,[], 'Next');
    $this->assertUrl('/admin/config/content/entity_browser/test_entity_browser/widget_selector', ['query' => ['js' => 'nojs']]);

    $this->drupalPostForm(NULL,[], 'Next');
    $this->assertUrl('/admin/config/content/entity_browser/test_entity_browser/selection_display', ['query' => ['js' => 'nojs']]);

    $this->drupalPostForm(NULL,[], 'Next');
    $this->assertFieldById('edit-table-' . $uuid . '-label', 'upload', 'Correct value for widget label found.');
    $this->assertFieldById('edit-table-' . $uuid . '-form-upload-location', 'public://', 'Correct value for upload location found.');

    $this->drupalPostForm(NULL,[], 'Finish');

    $this->drupalLogout();
    $this->drupalGet('/admin/config/content/entity_browser/test_entity_browser');
    $this->assertResponse(403, "Anonymous user can't access entity browser edit form.");

    $this->drupalLogin($this->adminUser);
    $this->drupalGet('/admin/config/content/entity_browser');
    $this->clickLink('Delete');
    $this->assertText('This action cannot be undone.', 'Delete question found.');
    $this->drupalPostForm(NULL, [], 'Delete Entity Browser');

    $this->assertText('Entity browser Test entity browser was deleted.', 'Confirmation message found.');
    $this->assertText('There is no Entity browser yet.', 'Entity browsers table is empty.');
    $this->drupalLogout();
  }

}
