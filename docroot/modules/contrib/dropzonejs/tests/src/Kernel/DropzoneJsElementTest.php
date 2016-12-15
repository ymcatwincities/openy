<?php

namespace Drupal\Tests\dropzonejs\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\user\Entity\Role;
use Drupal\user\RoleInterface;

/**
 * Tests related to the dropzoneJs element.
 *
 * @group DropzoneJs
 */
class DropzoneJsElementTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['system', 'file', 'user', 'dropzonejs', 'dropzonejs_test'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installSchema('system', 'router');
    $this->installEntitySchema('user');

    /** @var \Drupal\user\RoleInterface $role */
    $role = Role::create(['id' => RoleInterface::ANONYMOUS_ID]);
    $role->grantPermission('dropzone upload files');
    $role->save();
  }

  /**
   * Tests that the dropzonejs element appears.
   */
  public function testDropzoneJsElement() {
    $this->container->get('router.builder')->rebuild();
    $form = \Drupal::formBuilder()->getForm('\Drupal\dropzonejs_test\Form\DropzoneJsTestForm');
    $this->render($form);

    $xpath_base = "//div[contains(@class, 'form-item-dropzonejs')]";
    // Label.
    $this->assertFalse($this->xpath("$xpath_base/label[text()='Not DropzoneJs element']"));
    $this->assertTrue($this->xpath("$xpath_base/label[text()='DropzoneJs element']"));
    // Element where dropzonejs is attached to.
    $this->assertTrue($this->xpath("$xpath_base/div[contains(@class, 'dropzone-enable')]"));
    // Uploaded files input.
    $this->assertTrue($this->xpath("$xpath_base/input[contains(@data-drupal-selector, 'edit-dropzonejs-uploaded-files')]"));
    // Upload files path.
    $this->assertTrue($this->xpath("$xpath_base/input[contains(@data-upload-path, '/dropzonejs/upload?token=')]"));
    // Js is attached.
    $this->assertTrue($this->xpath("/html/body/script[contains(@src, 'libraries/dropzone/dist/min/dropzone.min.js')]"));
    $this->assertTrue($this->xpath("/html/body/script[contains(@src, 'modules/dropzonejs/js/dropzone.integration.js')]"));
  }
}
