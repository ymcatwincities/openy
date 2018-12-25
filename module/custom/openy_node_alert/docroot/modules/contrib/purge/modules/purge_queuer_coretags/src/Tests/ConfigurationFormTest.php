<?php

namespace Drupal\purge_queuer_coretags\Tests;

use Drupal\purge_ui\Tests\QueuerConfigFormTestBase;

/**
 * Tests \Drupal\purge_queuer_coretags\Form\ConfigurationForm.
 *
 * @group purge
 */
class ConfigurationFormTest extends QueuerConfigFormTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['purge_queuer_coretags', 'purge_coretags_removed_test'];

  /**
   * The plugin ID for which the form tested is rendered for.
   *
   * @var string
   */
  protected $plugin = 'coretags';

  /**
   * The full class of the form being tested.
   *
   * @var string
   */
  protected $formClass = 'Drupal\purge_queuer_coretags\Form\ConfigurationForm';

  /**
   * Test the blacklist section.
   */
  public function testFieldExistence() {
    $this->drupalLogin($this->admin_user);
    $this->drupalGet($this->route);
    // Assert its standard fields and values.
    $this->assertField('edit-blacklist-0');
    $this->assertRaw('config:');
    $this->assertField('edit-blacklist-1');
    $this->assertRaw('4xx-response');
    $this->assertField('edit-blacklist-3');
    $this->assertNoField('edit-blacklist-4');
    $this->assertText('Add prefix');
    $this->assertText('if you know what you are doing');
    // Test that direct configuration changes are reflected properly.
    $this->config('purge_queuer_coretags.settings')
      ->set('blacklist', ['a', 'b', 'c', 'd'])
      ->save();
    $this->drupalGet($this->route);
    $this->assertField('edit-blacklist-0');
    $this->assertField('edit-blacklist-1');
    $this->assertField('edit-blacklist-2');
    $this->assertField('edit-blacklist-3');
    $this->assertNoField('edit-blacklist-4');
    // Submit 1 valid and three empty values, test the re-rendered form.
    $form = $this->getFormInstance();
    $form_state = $this->getFormStateInstance();
    $form_state->addBuildInfo('args', [$this->formArgs]);
    $form_state->setValue('blacklist', ['testvalue', '', '', '']);
    $this->formBuilder->submitForm($form, $form_state);
    $this->assertEqual(0, count($form_state->getErrors()));
    $this->drupalGet($this->route);
    $this->assertRaw('testvalue');
    $this->assertField('edit-blacklist-0');
    $this->assertNoField('edit-blacklist-1');
  }

}
