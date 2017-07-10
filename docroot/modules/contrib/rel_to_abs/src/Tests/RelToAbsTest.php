<?php

namespace Drupal\rel_to_abs\Tests;

use Drupal\rel_to_abs\Plugin\Filter\RelToAbs;
use Drupal\Core\Language\LanguageInterface;
use Drupal\simpletest\WebTestBase;

/**
 * Ensure that the rel_to_abs filter provided functions properly.
 *
 * Functional test cases are far slower to execute than unit test cases because
 * they require a complete Drupal install to be done for each test.
 *
 * @see Drupal\simpletest\WebTestBase
 *
 * @ingroup rel_to_abs
 *
 * SimpleTest uses group annotations to help you organize your tests.
 *
 * @group rel_to_abs
 * @group filters
 */
class RelToAbsTest extends WebTestBase {

  static public $modules = array('rel_to_abs');

  /**
   * Unit test to check relative to absolute url conversion.
   */
  public function testRelToAbsFilterUnitTestCase() {
    $language = \Drupal::getContainer()
      ->get('language_manager')
      ->getLanguage(LanguageInterface::LANGCODE_NOT_SPECIFIED);

    $front = \Drupal::url('<front>', array(), array(
      'absolute' => TRUE,
      'language' => $language,
    ));

    $filter = new RelToAbs(array(), 'rel_to_abs', array('provider' => 'rel_to_abs'));

    $markup = '<div><a href="/node/1">link</a><img src="/files/test.jpg"/><span background="/files/test.jpg">test</span><a href="mailto:test@test.test"></a><a href="#anchor"></a></div>';
    $check = '<div><a href="' . $front . '/node/1">link</a><img src="' . $front . '/files/test.jpg"/><span background="' . $front . '/files/test.jpg">test</span><a href="mailto:test@test.test"></a><a href="#anchor"></a></div>';

    $result = $filter->process($markup, NULL);

    $this->assertEqual($check, $result);
  }

}
