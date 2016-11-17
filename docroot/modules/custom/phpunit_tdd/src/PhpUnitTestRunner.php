<?php

namespace Drupal\phpunit_tdd;

/**
 * Class PhpUnitTestRunner.
 *
 * @package Drupal\phpunit_tdd
 */
class PhpUnitTestRunner {

  /**
   * Run PHPUnit test class.
   *
   * @param string $name
   *   Class name with namespace.
   *   Example: 'Drupal\\Tests\\personify_mindbody_sync\\Unit\\PusherTest'.
   * @param string $path
   *   Full path to the class.
   *   Example: DRUPAL_ROOT . '/modules/custom/personify_mindbody_sync/tests/src/Unit/PusherTest.php'.
   */
  public function run($name, $path) {
    include DRUPAL_ROOT . '/vendor/phpunit/php-text-template/src/Template.php';
    include DRUPAL_ROOT . '/vendor/phpunit/phpunit/src/Framework/TestCase.php';
    include DRUPAL_ROOT . '/vendor/phpunit/phpunit/src/Framework/TestSuite.php';
    include DRUPAL_ROOT . '/vendor/phpunit/phpunit/src/TextUI/TestRunner.php';
    include DRUPAL_ROOT . '/core/tests/bootstrap.php';

    drupal_phpunit_populate_class_loader();

    $loader = new \PHPUnit_Runner_StandardTestSuiteLoader();
    $reflection = $loader->load($name, $path);
    $suite = new \PHPUnit_Framework_TestSuite($reflection);
    $suite->addTestSuite($name);
    $suite->setName($name);

    $path = DRUPAL_ROOT . '/core/phpunit.xml.dist';
    \PHPUnit_TextUI_TestRunner::run($suite, ['configuration' => $path]);
  }

}
