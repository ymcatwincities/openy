<?php

namespace Drupal\Tests\slick\Kernel\Form;

use Drupal\Core\Form\FormInterface;
use Drupal\Core\Form\FormState;
use Drupal\KernelTests\KernelTestBase;
use Drupal\slick_ui\Form\SlickSettingsForm;

/**
 * Tests the Slick UI settings form.
 *
 * @coversDefaultClass \Drupal\slick_ui\Form\SlickSettingsForm
 *
 * @group slick
 */
class SlickSettingsFormTest extends KernelTestBase {

  /**
   * The slick settings form object under test.
   *
   * @var \Drupal\slick_ui\Form\SlickSettingsForm
   */
  protected $slickSettingsForm;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'system',
    'file',
    'image',
    'blazy',
    'slick',
    'slick_ui',
  ];

  /**
   * {@inheritdoc}
   *
   * @covers ::__construct
   */
  protected function setUp() {
    parent::setUp();

    $this->installConfig(static::$modules);

    $this->blazyManager = $this->container->get('blazy.manager');

    $this->slickSettingsForm = new SlickSettingsForm(
      $this->blazyManager->getConfigFactory()
    );
  }

  /**
   * Tests for \Drupal\slick_ui\Form\SlickSettingsForm.
   *
   * @covers ::getFormId
   * @covers ::getEditableConfigNames
   * @covers ::buildForm
   * @covers ::submitForm
   */
  public function testSlickSettingsForm() {
    // Emulate a form state of a submitted form.
    $form_state = (new FormState())->setValues([
      'slick_css'  => TRUE,
      'module_css' => TRUE,
    ]);

    $this->assertInstanceOf(FormInterface::class, $this->slickSettingsForm);
    $this->assertTrue($this->blazyManager->getConfigFactory()->get('slick.settings')->get('slick_css'));

    $id = $this->slickSettingsForm->getFormId();
    $this->assertEquals('slick_settings_form', $id);

    $method = new \ReflectionMethod(SlickSettingsForm::class, 'getEditableConfigNames');
    $method->setAccessible(TRUE);

    $name = $method->invoke($this->slickSettingsForm);
    $this->assertEquals(['slick.settings'], $name);

    $form = $this->slickSettingsForm->buildForm([], $form_state);
    $this->slickSettingsForm->submitForm($form, $form_state);
  }

}
