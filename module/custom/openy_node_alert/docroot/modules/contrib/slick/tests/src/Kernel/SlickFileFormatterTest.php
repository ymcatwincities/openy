<?php

namespace Drupal\Tests\slick\Kernel;

use Drupal\Core\Form\FormState;
use Drupal\Tests\blazy\Kernel\BlazyKernelTestBase;
use Drupal\Tests\slick\Traits\SlickUnitTestTrait;

/**
 * Tests the Slick field rendering using the text field type.
 *
 * @coversDefaultClass \Drupal\slick\Plugin\Field\FieldFormatter\SlickFileFormatter
 * @group slick
 */
class SlickFileFormatterTest extends BlazyKernelTestBase {

  use SlickUnitTestTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'system',
    'user',
    'field',
    'file',
    'image',
    'filter',
    'node',
    'text',
    'blazy',
    'slick',
    'slick_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installConfig(static::$modules);
    $this->installEntitySchema('slick');

    $this->testFieldName  = 'field_file_multiple';
    $this->testEmptyName  = 'field_file_multiple_empty';
    $this->testFieldType  = 'image';
    $this->testPluginId   = 'slick_file';
    $this->maxItems       = 7;
    $this->maxParagraphs  = 2;
    $this->slickAdmin     = $this->container->get('slick.admin');
    $this->slickManager   = $this->container->get('slick.manager');
    $this->slickFormatter = $this->container->get('slick.formatter');

    // Create contents.
    $bundle = $this->bundle;

    $data = [
      'field_name' => $this->testEmptyName,
      'field_type' => 'image',
    ];

    $this->setUpContentTypeTest($bundle, $data);
    $this->setUpContentWithItems($bundle);

    $this->display = $this->setUpFormatterDisplay($bundle);

    $data['plugin_id'] = $this->testPluginId;
    $this->displayEmpty = $this->setUpFormatterDisplay($bundle, $data);

    $this->formatterInstance = $this->getFormatterInstance();
  }

  /**
   * Tests the Slick formatters.
   */
  public function testSlickFormatter() {
    $entity = $this->entity;

    // Generate the render array to verify if the cache tags are as expected.
    $build = $this->display->build($entity);
    $build_empty = $this->displayEmpty->build($entity);

    $component = $this->display->getComponent($this->testFieldName);
    $this->assertEquals($this->testPluginId, $component['type']);

    $render = $this->slickManager->getRenderer()->renderRoot($build);
    $this->assertNotEmpty($render);

    $render_empty = $this->slickManager->getRenderer()->renderRoot($build_empty[$this->testEmptyName]);
    $this->assertEmpty($render_empty);

    $scopes = $this->formatterInstance->getScopedFormElements();
    $this->assertEquals($this->testPluginId, $scopes['plugin_id']);

    $settings = $this->formatterInstance->buildSettings();
    $this->assertEquals(TRUE, $settings['blazy']);

    $form = [];
    $form_state = new FormState();
    $element = $this->formatterInstance->settingsForm($form, $form_state);
    $this->assertArrayHasKey('optionset', $element);
  }

}
