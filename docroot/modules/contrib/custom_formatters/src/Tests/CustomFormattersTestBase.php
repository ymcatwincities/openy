<?php

namespace Drupal\custom_formatters\Tests;

use Drupal\Component\Utility\Unicode;
use Drupal\field_ui\Tests\FieldUiTestTrait;
use Drupal\simpletest\WebTestBase;

/**
 * Class CustomFormattersTestBase.
 *
 * @package Drupal\custom_formatters\Tests
 */
abstract class CustomFormattersTestBase extends WebTestBase {

  use FieldUiTestTrait;

  /**
   * Admin user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $adminUser = NULL;

  /**
   * The custom formatter.
   *
   * @var string
   */
  protected $formatter = '';

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'block',
    'custom_formatters_test',
    'field_ui',
    'image',
    'node',
    'text',
  ];

  /**
   * A test node.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $node;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Create an admin user.
    $this->adminUser = $this->drupalCreateUser([
      'access administration pages',
      'administer content types',
      'administer custom formatters',
      'administer node display',
    ]);

    // Ensure relevant configuration present if profile isn't 'standard'.
    if ($this->profile !== 'standard') {
      // Blocks.
      $this->drupalPlaceBlock('local_actions_block');
      $this->drupalPlaceBlock('local_tasks_block');

      // Content types.
      $this->createContentType([
        'type' => 'article',
      ]);
    }

    // Create a test node.
    $this->node = $this->drupalCreateNode(['type' => 'article']);

    // Login as admin user.
    $this->drupalLogin($this->adminUser);
  }

  /**
   * Pass if the Custom Formatter is found.
   *
   * @param string $name
   *   The name of the formatter to check.
   * @param string $message
   *   Message to display.
   * @param string $group
   *   The group this message belong to, default to 'Other'.
   *
   * @return bool
   *   TRUE on pass, FALSE on fail.
   */
  public function assertCustomFormatterExists($name, $message = '', $group = 'Other') {
    $formatter = \Drupal::entityTypeManager()
      ->getStorage('formatter')
      ->load($name);
    $message = !empty($message) ? $message : t('Custom Formatter %name found.', ['%name' => $name]);

    return $this->assert(!is_null($formatter), $message, $group);
  }

  /**
   * Create a Custom Formatter.
   *
   * @param array $values
   *   The values to set for the Custom Formatter.
   *
   * @return \Drupal\custom_formatters\FormatterInterface
   *   The Custom Formatter object.
   */
  protected function createCustomFormatter($values = []) {
    // Prepare the default values.
    $name = $this->randomMachineName();
    $defaults = [
      'label'       => $name,
      'id'          => Unicode::strtolower($name),
      'field_types' => ['text_with_summary'],
    ];
    $values += $defaults;

    // Create the Custom Formatter.
    $formatter = \Drupal::entityTypeManager()
      ->getStorage('formatter')
      ->create($values);
    $formatter->save();

    // Clear cached formatters.
    \Drupal::service('plugin.manager.field.formatter')
      ->clearCachedDefinitions();

    return $formatter;
  }

  /**
   * Set a Custom Formatter to be used by a specified field/bundle/view mode.
   *
   * @param string $formatter_name
   *   A Custom Formatter name.
   * @param string $field_name
   *   A Field name.
   * @param string $bundle_name
   *   A Node content type.
   * @param string $view_mode
   *   A Node view mode.
   */
  protected function setCustomFormatter($formatter_name, $field_name, $bundle_name, $view_mode = 'default') {
    $this->drupalPostForm("admin/structure/types/manage/{$bundle_name}/display/{$view_mode}", ["fields[{$field_name}][type]" => "custom_formatters:{$formatter_name}"], t('Save'));
    $this->assertText(t('Your settings have been saved.'));
  }

}
