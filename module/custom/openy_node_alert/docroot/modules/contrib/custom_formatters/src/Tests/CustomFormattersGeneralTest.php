<?php

namespace Drupal\custom_formatters\Tests;

/**
 * Test general functionality.
 *
 * @group Custom Formatters
 */
class CustomFormattersGeneralTest extends CustomFormattersTestBase {

  /**
   * Test General UI related functionality.
   */
  public function testCustomFormattersUi() {
    // Ensure the Formatters administration is linked in the structure section.
    $this->drupalGet('admin/structure');
    $this->assertLinkByHref('admin/structure/formatters');
    $this->assertText('Administer Formatters.');

    $this->drupalGet('admin/structure/formatters');

    // Ensure the Formatters overview page is present.
    $expected_title = t(':title | :sitename', [
      ':title'    => 'Formatters',
      ':sitename' => \Drupal::config('system.site')->get('name'),
    ]);
    $this->assertTitle($expected_title);

    // Ensure the Settings link is present and correct.
    $this->assertLink(t('Settings'));
    $this->assertLinkByHref('admin/structure/formatters/settings');

    // Ensure our pre-prepared test formatter is present.
    $this->assertText('Test Formatter');
    $this->assertLinkByHref('admin/structure/formatters/manage/test_formatter');
    $this->assertCustomFormatterExists('test_formatter');

    // Ensure our pre-prepared test formatter is present on the Manage display
    // page.
    $this->drupalGet('admin/structure/types/manage/article/display');
    $this->assertRaw('custom_formatters:test_formatter');
    $this->assertRaw('Custom: Test Formatter');

    // Change the Label prefix.
    $edit = ['label_prefix_value' => $this->randomMachineName()];
    $this->drupalPostForm('admin/structure/formatters/settings', $edit, t('Save configuration'));
    $this->assertText(t('Custom Formatters settings have been updated.'));

    // Ensure our pre-prepared test formatter is present on the Manage display
    // page with the altered label prefix.
    $this->drupalGet('admin/structure/types/manage/article/display');
    $this->assertRaw(t('@prefix: Test Formatter', ['@prefix' => $edit['label_prefix_value']]));

    // Remove the Label prefix.
    $edit = ['label_prefix' => FALSE];
    $this->drupalPostForm('admin/structure/formatters/settings', $edit, t('Save configuration'));
    $this->assertText(t('Custom Formatters settings have been updated.'));

    // Ensure our pre-prepared test formatter is present on the Manage display
    // page without a label prefix.
    $this->drupalGet('admin/structure/types/manage/article/display');
    $this->assertRaw('Test Formatter');
  }

  /**
   * Test the Formatter preset Engine.
   *
   * @TODO - Add manual creation test.
   */
  public function testFormatterTypeFormatterPreset() {
    // Create a Custom formatter.
    $this->formatter = $this->createCustomFormatter([
      'type' => 'formatter_preset',
      'data' => [
        'formatter' => 'text_trimmed',
        'settings'  => [
          'trim_length' => 10,
        ],
      ],
    ]);

    // Set the formatter active on the Body field.
    $this->setCustomFormatter($this->formatter->id(), 'body', 'article');

    // Ensure Formatter rendered correctly.
    $this->drupalGet($this->node->toUrl());
    // We substring to a length of 7 characters instead of 10 characters as the
    // formatter will include the starting HTML paragraph tag in the character
    // count.
    $this->assert(!strstr($this->content, $this->node->get('body')[0]->value) && strstr($this->content, substr($this->node->get('body')[0]->value, 0, 7)), t('Custom formatter output found.'));
  }

  /**
   * Test the PHP Engine.
   *
   * @TODO - Add manual creation test.
   */
  public function testCustomFormatterTypePhp() {
    // Create a Custom formatter.
    $text = $this->randomMachineName();
    $this->formatter = $this->createCustomFormatter([
      'type' => 'php',
      'data' => "return '{$text}';",
    ]);

    // Set the formatter active on the Body field.
    $this->setCustomFormatter($this->formatter->id(), 'body', 'article');

    // Ensure Formatter rendered correctly.
    $this->drupalGet($this->node->toUrl());
    $this->assertText($text, t('Custom formatter output found.'));
  }

  /**
   * Test the Twig engine.
   *
   * @TODO - Add manual creation test.
   */
  public function testCustomFormatterTypeTwig() {
    // Create a Custom formatter.
    $text = $this->randomMachineName();
    $this->formatter = $this->createCustomFormatter([
      'type' => 'twig',
      'data' => $text,
    ]);

    // Set the formatter active on the Body field.
    $this->setCustomFormatter($this->formatter->id(), 'body', 'article');

    // Ensure Formatter rendered correctly.
    $this->drupalGet($this->node->toUrl());
    $this->assertText($text, t('Custom formatter output found.'));
  }

  /**
   * Test the HTML + Token engine.
   *
   * @TODO - Add manual creation test.
   */
  public function testCustomFormatterTypeHtmlToken() {
    // Create a Custom formatter.
    $text = $this->randomMachineName();
    $this->formatter = $this->createCustomFormatter([
      'type' => 'html_token',
      'data' => $text,
    ]);

    // Set the formatter active on the Body field.
    $this->setCustomFormatter($this->formatter->id(), 'body', 'article');

    // Ensure Formatter rendered correctly.
    $this->drupalGet($this->node->toUrl());
    $this->assertText($text, t('Custom formatter output found.'));
  }

}
