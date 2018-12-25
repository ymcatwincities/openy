<?php

namespace Drupal\purge_purger_test\Tests;

use Drupal\purge_ui\Tests\PurgerConfigFormTestBase;

/**
 * Tests \Drupal\purge_purger_test\Form\PurgerConfigForm.
 *
 * @group purge_purger_test
 */
class PurgerConfigFormTest extends PurgerConfigFormTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['purge_purger_test'];

  /**
   * The plugin ID for which the form tested is rendered for.
   *
   * @var string
   */
  protected $plugin = 'withform';

  /**
   * The full class of the form being tested.
   *
   * @var string
   */
  protected $formClass = 'Drupal\purge_purger_test\Form\PurgerConfigForm';

  /**
   * Verify that the form contains all fields we require.
   */
  public function testFieldExistence() {
    $this->drupalLogin($this->admin_user);
    $this->drupalGet($this->route);
    $this->assertField('edit-textfield');
    $this->assertText("Test");
  }

  /**
   * Test validating the data.
   */
  public function testFormValidation() {
    // Assert that no validation errors occur in the testing form.
    $form_state = $this->getFormStateInstance();
    $form_state->addBuildInfo('args', [$this->formArgs]);
    $form_state->setValues([
      'textfield' => "The moose in the noose ate the goose who was loose.",
    ]);
    $form = $this->getFormInstance();
    $this->formBuilder->submitForm($form, $form_state);
    $errors = $form_state->getErrors();
    $this->assertEqual(0, count($errors));
  }

  /**
   * Test posting data to the form.
   */
  public function testFormSubmit() {
    $this->drupalLogin($this->admin_user);
    $edit = [
      'textfield' => "The moose in the noose ate the goose who was loose.",
    ];
    $this->drupalPostForm($this->route, $edit, t('Save configuration'));
  }

}
