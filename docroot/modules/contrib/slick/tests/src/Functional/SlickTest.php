<?php

namespace Drupal\Tests\slick\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\slick\Entity\Slick;

/**
 * Tests the Slick optionsets, configuration options and permission controls.
 *
 * @group slick
 */
class SlickTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['image', 'blazy', 'slick', 'slick_ui'];

  /**
   * A user with permissions to administer slick.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $slickAdminUser;

  /**
   * A user with permissions to access administration pages.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $otherAdminUser;

  /**
   * Overrides \Drupal\Tests\BrowserTestBase::setUp().
   */
  public function setUp() {
    parent::setUp();

    // Create users.
    $this->slickAdminUser = $this->drupalCreateUser(['administer slick']);
    $this->otherAdminUser = $this->drupalCreateUser(['access administration pages']);
  }

  /**
   * Tests Slick permission.
   */
  public function testAdminAccess() {

    // 1. Test login as a Slick admin user.
    $this->drupalLogin($this->slickAdminUser);

    // Load the Slick admin page.
    $this->verifyPages([
      'admin/config/media/slick',
      'admin/config/media/slick/default',
    ],
    200);

    // Logout as the Slick admin user.
    $this->drupalLogout();

    // 2. Test login as a non-slick admin user.
    $this->drupalLogin($this->otherAdminUser);

    // Attempts to load Slick admin page.
    $this->verifyPages([
      'admin/config/media/slick',
      'admin/config/media/slick/default',
    ],
    403);
  }

  /**
   * Tests Slick optionset CRUD.
   */
  public function testOptionSetCrud() {

    // Login as the Slick admin user.
    $this->drupalLogin($this->slickAdminUser);
    $defaults = ['testset1', 'testset2'];

    // 1. Test creating a new optionset with default settings.
    foreach ($defaults as $name) {
      $optionset = Slick::create(['name' => $name, 'label' => $name]);
      $this->assertTrue($optionset->id() == $name, format_string('Optionset created: @name', ['@name' => $optionset->id()]));
      $this->assertNotEmpty($optionset->getOptions(), 'Create optionset works.');

      // Save the optionset to the database.
      $optionset = $optionset->save();
      $this->assertNotNull($optionset, 'Optionset saved to database.');

      // Read the values from the database.
      $optionset = Slick::load($name);
      $this->assertNotNull($optionset, format_string('Loaded optionset: @name', ['@name' => $optionset->id()]));
      $this->assertSame($name, $optionset->id(), format_string('Loaded name matches: @name', ['@name' => $optionset->id()]));

      // Ensure defaults match the custom saved data when no overrides.
      $default = Slick::create();
      foreach ((array) $default->getSettings() as $key => $value) {
        $new_value = $optionset->getSetting($key);
        $read_value = $this->getPrintedValue($value);
        $read_new_value = $this->getPrintedValue($new_value);

        $message = format_string('@key: default:@value matches saved:@new_value.', [
          '@key' => $key,
          '@value' => $read_value,
          '@new_value' => $read_new_value,
        ]);
        $this->assertSame($value, $new_value, $message);
      }
    }

    // 2. Test the amount of loaded optionsets.
    $optionsets = Slick::loadMultiple();
    $this->assertTrue(is_array($optionsets), 'Available optionsets loaded.');
    $message = format_string('Proper number of optionsets loaded (two created, one default): @count.', ['@count' => count($optionsets)]);
    $this->assertTrue(count($optionsets) == 3, $message);

    // 3. Test optionsets are loaded correctly.
    foreach ($optionsets as $key => $optionset) {
      $this->assertNotEmpty($optionset->id(), 'The loaded optionset has a defined machine.');
      $this->assertNotEmpty($optionset->label(), 'The loaded optionset has a defined human readable name.');
      $this->assertNotEmpty($optionset->getSettings(), 'The loaded optionset has a defined array of settings.');
    }

    // Update the optionset.
    $test_options = $this->getOptionsets();
    $test_options = $test_options['valid'];

    // Load one of the test optionset.
    $test = $defaults[1];
    $optionset = Slick::load($test);

    // 4. Test comparing saved options different from the set2 options.
    foreach ($test_options['set2'] as $key => $value) {
      $saved_value = $optionset->getSetting($key);
      $read_value = $this->getPrintedValue($value);
      $read_saved_value = $this->getPrintedValue($saved_value);

      $message = format_string('@key: saved value:@saved_value can be overriden by set2:@value.', [
        '@key' => $key,
        '@saved_value' => $read_saved_value,
        '@value' => $read_value,
      ]);

      $this->assertNotEquals($value, $saved_value, $message);
    }

    // Union the saved values to use the overrides now.
    $optionset->setSettings((array) $test_options['set2'] + (array) $optionset->getSettings());

    // Save the updated values.
    $saved = $optionset->save();

    $this->assertTrue(SAVED_UPDATED == $saved, 'Saved updates to optionset to database.');

    // Load the values from the database again.
    $optionset = Slick::load($test);

    // 5. Test comparing saved options match the set2 options.
    foreach ($test_options['set2'] as $key => $value) {
      $saved_value = $optionset->getSetting($key);
      $read_value = $this->getPrintedValue($value);
      $read_saved_value = $this->getPrintedValue($saved_value);

      // Asserts that the $saved_value is roughly equivalent to $value.
      $message = format_string('@key: saved value:@saved_value matches set2:@value.', [
        '@key' => $key,
        '@saved_value' => $read_saved_value,
        '@value' => $read_value,
      ]);
      $this->assertSame($saved_value, $value, $message);
    }

    // Delete the optionset.
    $this->assertNotEmpty($optionset->id(), format_string('Optionset @name exists and will be deleted.', ['@name' => $test]));

    $optionset->delete();
  }

  /**
   * Tests Slick optionset form.
   */
  public function testOptionSetForm() {

    // Login as a Slick admin user.
    $this->drupalLogin($this->slickAdminUser);

    // Load the add form.
    $this->verifyPages(['admin/config/media/slick/add'], 200);

    // 1. Test the optionset add form.
    $assert = $this->assertSession();
    $label = 'Testset';
    $id = 'testset';
    $optionset = [
      'label' => $label,
      'name' => $id,
    ];

    // Make a POST request to admin/config/media/slick/add.
    $this->verifySubmitForm('admin/config/media/slick/add', $optionset, t('Save'), 200);
    $assert->pageTextContains(format_string('slick.optionset @label has been added.', ['@label' => $label]));

    // Confirms that optionset is already created, a unique name is required.
    $this->verifySubmitForm('admin/config/media/slick/add', $optionset, t('Save'), 200);
    $assert->pageTextContains(t('The machine-readable name is already in use. It must be unique.'));

    // 2. Test the optionset delete form.
    $testset = Slick::load($id);
    $this->assertNotNull($testset);
    $this->verifyPages(['admin/config/media/slick/' . $id . '/delete'], 200);
    $assert->pageTextContains(format_string('Are you sure you want to delete the Slick optionset @label?', ['@label' => $label]));

    // Delete the optionset.
    $this->verifySubmitForm('admin/config/media/slick/' . $id . '/delete', [], t('Delete'), 200);
    $assert->pageTextContains(format_string('The Slick optionset @label has been deleted', ['@label' => $label]));

    // 3. Test that the deleted optionset no longer exists.
    $testset = Slick::load($id);
    $this->assertNull($testset, 'Make sure the optionset is gone after being deleted.');

    // 4. Test the optionset overrides.
    $options = $this->getOptionsets();

    foreach ($options['valid'] as $set => $values) {
      $edit = [];
      foreach ($values as $key => $value) {
        // @todo: Hidden field not found.
        // Behat\Mink\Exception\ElementNotFoundException: Form field with
        // id|name|label|value "options[settings][cssEaseBezier]" not found.
        if ($key == 'cssEaseBezier') {
          continue;
        }

        $edit["options[settings][{$key}]"] = $value;
      }
      $this->verifySubmitForm('admin/config/media/slick/default', $edit, t('Save'), 200, 'Default optionset overriden.');

      // 5. Test the previous saved/ overriden values loaded into the form.
      // Hence the optionset set2 has different values.
      if ($set == 'set2') {
        $this->verifyPages(['admin/config/media/slick/default'], 200);

        foreach ($values as $key => $value) {
          // @todo: Hidden field not found, and deal with checkboxes.
          // Behat\Mink\Exception\ElementNotFoundException: Form field with
          // id|name|label|value "options[settings][cssEaseBezier]" not found.
          if ($key == 'cssEaseBezier' || is_bool($value)) {
            continue;
          }

          // Assert that the field has an overriden value.
          $assert->fieldValueEquals("options[settings][{$key}]", $value);
        }
      }
    }

    // 6. Assert that a field does not exist in the current page.
    foreach ($options['error'] as $key => $value) {
      $assert->elementNotExists('css', '[name="options[settings][' . $key . ']"]');
    }
  }

  /**
   * Test configuration options.
   *
   * @return array
   *   Returns an array of options to test Slick optionset saving.
   */
  public function getOptionsets() {
    // Valid optionset data.
    $defaults = Slick::defaultSettings();
    $valid = [
      'set1' => $defaults,
      'set2' => [
        'autoplay' => TRUE,
        'initialSlide' => 1,
      ],
    ];

    // Invalid edge cases.
    $error = ['invalidOption' => TRUE];

    return ['valid' => $valid, 'error' => $error];
  }

  /**
   * Gets the human readable values for the UI.
   *
   * @param mixed $value
   *   The given value.
   *
   * @return mixed
   *   Returns printed value.
   */
  public function getPrintedValue($value) {
    $read_value = $value === FALSE ? 'FALSE' : ($value === TRUE ? 'TRUE' : $value);
    $read_value = empty($read_value) ? 'NULL' : $read_value;
    return $read_value;
  }

  /**
   * Verifies the logged in user has access to the various pages.
   *
   * @param array $pages
   *   The array of pages we want to test.
   * @param int $response
   *   (optional) An HTTP response code. Defaults to 200.
   */
  protected function verifyPages(array $pages = [], $response = 200) {
    foreach ($pages as $page) {
      $this->drupalGet($page);
      $this->assertSession()->statusCodeEquals($response);
    }
  }

  /**
   * Wraps the submit form.
   *
   * @param string $page
   *   The page we want to test.
   * @param array $content
   *   The content to submit.
   * @param string $submit
   *   The submit text.
   * @param int $response
   *   (optional) An HTTP response code. Defaults to 200.
   * @param string $message
   *   The message text.
   */
  protected function verifySubmitForm($page = '', array $content = [], $submit = 'Save', $response = 200, $message = '') {
    $this->drupalGet($page);
    $this->submitForm($content, $submit);
    $this->assertResponse($response, $message);
  }

}
