<?php

namespace Drupal\webform\Tests\Element;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\webform\Entity\Webform;
use Drupal\webform\Tests\WebformTestBase;
use Drupal\webform\Utility\WebformElementHelper;

/**
 * Tests for webform elements.
 *
 * @group Webform
 */
class WebformElementTest extends WebformTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['filter', 'webform'];

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = [
    'test_element_allowed_tags',
    'test_element_ignored_properties',
    'test_element_invalid',
    'test_element_text_format',
    'test_form_properties',
  ];

  /**
   * Test element settings.
   */
  public function testElements() {
    global $base_path;

    /**************************************************************************/
    // Allowed tags
    /**************************************************************************/

    // Check <b> tags is allowed.
    $this->drupalGet('webform/test_element_allowed_tags');
    $this->assertRaw('Hello <b>...Goodbye</b>');

    // Check custom <ignored> <tag> is allowed and <b> tag removed.
    \Drupal::configFactory()->getEditable('webform.settings')
      ->set('element.allowed_tags', 'ignored tag')
      ->save();
    $this->drupalGet('webform/test_element_allowed_tags');
    $this->assertRaw('Hello <ignored></tag>...Goodbye');

    // Restore admin tags.
    \Drupal::configFactory()->getEditable('webform.settings')
      ->set('element.allowed_tags', 'admin')
      ->save();

    /**************************************************************************/
    // Test invalid elements.
    /**************************************************************************/

    // Check invalid elements .
    $this->drupalGet('webform/test_element_invalid');
    $this->assertRaw('Unable to display this webform. Please contact the site administrator.');

    /**************************************************************************/
    // Test ignored properties.
    /**************************************************************************/

    // Check ignored properties.
    $webform_ignored_properties = Webform::load('test_element_ignored_properties');
    $elements = $webform_ignored_properties->getElementsInitialized();
    foreach (WebformElementHelper::$ignoredProperties as $ignored_property) {
      $this->assert(!isset($elements['test'][$ignored_property]), new FormattableMarkup('@property ignored.', ['@property' => $ignored_property]));
    }

    /**************************************************************************/
    // Test text format element.
    /**************************************************************************/

    $webform_text_format = Webform::load('test_element_text_format');

    // Check 'text_format' values.
    $this->drupalGet('webform/test_element_text_format');
    $this->assertFieldByName('text_format[value]', 'The quick brown fox jumped over the lazy dog.');
    $this->assertRaw('No HTML tags allowed.');

    $text_format = [
      'value' => 'Custom value',
      'format' => 'custom_format',
    ];
    $form = $webform_text_format->getSubmissionForm(['data' => ['text_format' => $text_format]]);
    $this->assertEqual($form['elements']['text_format']['#default_value'], $text_format['value']);
    $this->assertEqual($form['elements']['text_format']['#format'], $text_format['format']);

    /**************************************************************************/
    // Test webform properties.
    /**************************************************************************/

    // Check element's root properties moved to the webform's properties.
    $this->drupalGet('webform/test_form_properties');
    $this->assertPattern('/Form prefix<form /');
    $this->assertPattern('/<\/form>\s+Form suffix/');
    $this->assertRaw('<form class="webform-submission-test-form-properties-form webform-submission-form test-form-properties js-webform-details-toggle webform-details-toggle" invalid="invalid" style="border: 10px solid red; padding: 1em;" data-drupal-selector="webform-submission-test-form-properties-form" action="https://www.google.com/search" method="get" id="webform-submission-test-form-properties-form" accept-charset="UTF-8">');

    // Check editing webform settings style attributes and custom properties
    // updates the element's root properties.
    $this->drupalLogin($this->rootUser);
    $edit = [
      'attributes[class][select][]' => ['form--inline clearfix', '_other_'],
      'attributes[class][other]' => 'test-form-properties',
      'attributes[style]' => 'border: 10px solid green; padding: 1em;',
      'attributes[attributes]' => '',
      'method' => '',
      'action' => '',
      'custom' => "'suffix': 'Form suffix TEST'
'prefix': 'Form prefix TEST'",
    ];
    $this->drupalPostForm('/admin/structure/webform/manage/test_form_properties/settings', $edit, t('Save'));
    $this->drupalGet('webform/test_form_properties');
    $this->assertPattern('/Form prefix TEST<form /');
    $this->assertPattern('/<\/form>\s+Form suffix TEST/');
    $this->assertRaw('<form class="webform-submission-test-form-properties-form webform-submission-form form--inline clearfix test-form-properties js-webform-details-toggle webform-details-toggle" style="border: 10px solid green; padding: 1em;" data-drupal-selector="webform-submission-test-form-properties-form" action="' . $base_path . 'webform/test_form_properties" method="post" id="webform-submission-test-form-properties-form" accept-charset="UTF-8">');
  }

}
