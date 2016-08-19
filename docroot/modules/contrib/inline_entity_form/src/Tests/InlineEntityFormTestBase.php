<?php

namespace Drupal\inline_entity_form\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Base Class for Inline Entity Form Tests.
 */
abstract class InlineEntityFormTestBase extends WebTestBase {

  /**
   * User with permissions to create content.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $user;

  /**
   * Node storage.
   *
   * @var \Drupal\Core\Entity\ContentEntityStorageInterface;
   */
  protected $nodeStorage;

  /**
   * Field config storage.
   *
   * @var \Drupal\Core\Config\Entity\ConfigEntityStorage
   */
  protected $fieldStorageConfigStorage;

  /**
   * Field config storage.
   *
   * @var \Drupal\Core\Config\Entity\ConfigEntityStorageInterface
   */
  protected $fieldConfigStorage;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->nodeStorage = $this->container->get('entity_type.manager')->getStorage('node');
    $this->fieldStorageConfigStorage = $this->container->get('entity_type.manager')->getStorage('field_storage_config');
    $this->fieldConfigStorage = $this->container->get('entity_type.manager')->getStorage('field_config');
  }


  /**
   * Gets IEF button name.
   *
   * @param array $xpath
   *   Xpath of the button.
   *
   * @return string
   *   The name of the button.
   */
  protected function getButtonName($xpath) {
    $retval = '';
    /** @var \SimpleXMLElement[] $elements */
    if ($elements = $this->xpath($xpath)) {
      foreach ($elements[0]->attributes() as $name => $value) {
        if ($name == 'name') {
          $retval = $value;
          break;
        }
      }
    }
    return $retval;
  }

  /**
   * Passes if no node is found for the title.
   *
   * @param $title
   *   Node title to check.
   * @param $message
   *   Message to display.
   */
  protected function assertNoNodeByTitle($title, $message = '') {
    if (!$message) {
      $message = "No node with title: $title";
    }
    $node = $this->getNodeByTitle($title);

    $this->assertTrue(empty($node), $message);
  }

  /**
   * Passes if node is found for the title.
   *
   * @param $title
   *   Node title to check.
   * @param $message
   *   Message to display.
   */
  protected function assertNodeByTitle($title, $bundle = NULL, $message = '') {
    if (!$message) {
      $message = "Node with title found: $title";
    }
    $node = $this->getNodeByTitle($title);
    if ($this->assertTrue(!empty($node), $message)) {
      if ($bundle) {
        $this->assertEqual($node->bundle(), $bundle, "Node is correct bundle: $bundle");
      }
    }
  }

  /**
   * Checks for check correct fields on form displays based on exported config
   * in inline_entity_form_test module.
   *
   * @param $form_display
   *  The form display to check.
   */
  protected function checkFormDisplayFields($form_display, $prefix) {
    $form_display_fields = [
      'node.ief_test_custom.default' => [
        'expected' => [
          '[title][0][value]',
          '[uid][0][target_id]',
          '[created][0][value][date]',
          '[created][0][value][time]',
          '[promote][value]',
          '[sticky][value]',
          '[positive_int][0][value]',
        ],
        'unexpected' => [],
      ],
      'node.ief_test_custom.inline' => [
        'expected' => [
          '[title][0][value]',
          '[positive_int][0][value]',
        ],
        'unexpected' => [
          '[uid][0][target_id]',
          '[created][0][value][date]',
          '[created][0][value][time]',
          '[promote][value]',
          '[sticky][value]',
        ],
      ],
    ];
    if ($fields = $form_display_fields[$form_display]) {
      $this->assert('debug', 'Checking form dispaly: '. $form_display);
      foreach ($fields['expected'] as $expected_field) {
        $this->assertFieldByName($prefix . $expected_field);
      }
      foreach ($fields['unexpected'] as $unexpected_field) {
        $this->assertNoFieldByName($prefix . $unexpected_field, NULL);
      }
    }
    else {
      // Test calling unexported form display if we are here.
      throw new \Exception('Form display not found: ' . $form_display);
    }
  }

}
