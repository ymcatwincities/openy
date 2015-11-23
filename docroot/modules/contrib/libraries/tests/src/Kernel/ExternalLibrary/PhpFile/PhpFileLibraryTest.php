<?php

/**
 * @file
 * Contains \Drupal\Tests\libraries\Kernel\ExternalLibrary\PhpFile\PhpFileLibraryTest.
 */

namespace Drupal\Tests\libraries\Kernel\ExternalLibrary\PhpFile;

use Drupal\Tests\libraries\Kernel\ExternalLibraryKernelTestBase;

/**
 * Tests that the external library manager properly loads PHP file libraries.
 *
 * @group libraries
 */
class PhpFileLibraryTest extends ExternalLibraryKernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['libraries', 'libraries_test'];

  /**
   * The external library manager.
   *
   * @var \Drupal\libraries\ExternalLibrary\ExternalLibraryManagerInterface
   */
  protected $externalLibraryManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->externalLibraryManager = $this->container->get('libraries.manager');

    $this->container->set('stream_wrapper.php_library_files', new TestPhpLibraryFilesStream());
  }

  /**
   * Tests that the external library manager properly loads PHP file libraries.
   *
   * @see \Drupal\libraries\ExternalLibrary\ExternalLibraryManager
   * @see \Drupal\libraries\ExternalLibrary\ExternalLibraryTrait
   * @see \Drupal\libraries\ExternalLibrary\PhpFile\PhpRequireLoader
   */
  public function testPhpFileLibrary() {
    $function_name = '_libraries_test_php_function';
    if (function_exists($function_name)) {
      $this->markTestSkipped('Cannot test file inclusion if the file to be included has already been included prior.');
      return;
    }

    $this->assertFalse(function_exists($function_name));
    $this->externalLibraryManager->load('test_php_file_library');
    $this->assertTrue(function_exists($function_name));
  }

}
