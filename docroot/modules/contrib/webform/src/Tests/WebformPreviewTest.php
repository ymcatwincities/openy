<?php

namespace Drupal\webform\Tests;

use Drupal\webform\Entity\Webform;

/**
 * Tests for webform preview.
 *
 * @group Webform
 */
class WebformPreviewTest extends WebformTestBase {

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_form_preview'];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Exclude Progress tracker so that the default progress bar is displayed.
    // The default progress bar is most likely never going to change.
    \Drupal::configFactory()->getEditable('webform.settings')
      ->set('libraries.excluded_libraries', ['progress-tracker'])
      ->save();
  }

  /**
   * Tests webform preview.
   */
  public function testPreview() {
    $this->drupalLogin($this->rootUser);

    $webform_preview = Webform::load('test_form_preview');

    // Check webform with optional preview.
    $this->drupalGet('webform/test_form_preview');
    $this->assertFieldByName('op', 'Submit');
    $this->assertFieldByName('op', 'Preview');

    // Check default preview.
    $this->drupalPostForm('webform/test_form_preview', ['name' => 'test', 'email' => 'example@example.com'], t('Preview'));

    $this->assertRaw('<h1 class="page-title">Test: Webform: Preview: Preview</h1>');
    $this->assertRaw('<b>Preview</b></li>');
    $this->assertRaw('Please review your submission. Your submission is not complete until you press the "Submit" button!');
    $this->assertFieldByName('op', 'Submit');
    $this->assertFieldByName('op', '< Previous');
    $this->assertRaw('<b>Name</b><br />test<br /><br />');
    $this->assertRaw('<b>Email</b><br /><a href="mailto:example@example.com">example@example.com</a><br /><br />');
    $this->assertRaw('<div class="webform-preview">');

    // Check required preview with custom settings.
    $webform_preview->setSettings([
      'preview' => DRUPAL_REQUIRED,
      'preview_label' => '{Label}',
      'preview_title' => '{Title}',
      'preview_message' => '{Message}',
      'preview_attributes' => ['class' => ['preview-custom']],
      'preview_excluded_elements' => ['email' => 'email'],
    ]);

    // Add 'webform_actions' element.
    $webform_preview->setElementProperties('actions', [
      '#type' => 'webform_actions',
      '#preview_next__label' => '{Preview}',
      '#preview_prev__label' => '{Back}',
    ]);
    $webform_preview->save();

    // Check custom preview.
    $this->drupalPostForm('webform/test_form_preview', ['name' => 'test'], t('{Preview}'));
    $this->assertRaw('<h1 class="page-title">{Title}</h1>');
    $this->assertRaw('<b>{Label}</b></li>');
    $this->assertRaw('{Message}');
    $this->assertFieldByName('op', 'Submit');
    $this->assertFieldByName('op', '{Back}');
    $this->assertRaw('<b>Name</b><br />test<br /><br />');
    $this->assertNoRaw('<b>Email</b><br /><a href="mailto:example@example.com">example@example.com</a><br /><br />');
    $this->assertRaw('<div class="preview-custom webform-preview">');

    $this->drupalGet('webform/test_form_preview');
    $this->assertNoFieldByName('op', 'Submit');
    $this->assertFieldByName('op', '{Preview}');
  }

}
