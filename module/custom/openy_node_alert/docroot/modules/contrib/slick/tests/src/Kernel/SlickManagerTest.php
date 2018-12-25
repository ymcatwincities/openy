<?php

namespace Drupal\Tests\slick\Kernel;

use Drupal\Tests\blazy\Kernel\BlazyKernelTestBase;
use Drupal\Tests\slick\Traits\SlickUnitTestTrait;
use Drupal\slick\Entity\Slick;
use Drupal\slick_ui\Form\SlickForm;

/**
 * Tests the Slick manager methods.
 *
 * @coversDefaultClass \Drupal\slick\SlickManager
 *
 * @group slick
 */
class SlickManagerTest extends BlazyKernelTestBase {

  use SlickUnitTestTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'system',
    'user',
    'field',
    'file',
    'filter',
    'image',
    'node',
    'text',
    'blazy',
    'slick',
    'slick_ui',
    'slick_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $bundle = $this->bundle;

    $this->slickAdmin = $this->container->get('slick.admin');
    $this->blazyAdminFormatter = $this->slickAdmin;
    $this->slickFormatter = $this->container->get('slick.formatter');
    $this->slickManager = $this->container->get('slick.manager');

    $this->slickForm = new SlickForm(
      $this->slickAdmin,
      $this->slickManager
    );

    $this->testPluginId  = 'slick_image';
    $this->testFieldName = 'field_slick_image';
    $this->maxItems      = 7;
    $this->maxParagraphs = 2;

    $settings['fields']['field_text_multiple'] = 'text';
    $this->setUpContentTypeTest($bundle, $settings);
    $this->setUpContentWithItems($bundle);
    $this->setUpRealImage();

    $this->display = $this->setUpFormatterDisplay($bundle);
    $this->formatterInstance = $this->getFormatterInstance();
  }

  /**
   * Tests cases for various methods.
   *
   * @covers ::attach
   * @covers ::attachSkin
   * @covers ::getSkins
   * @covers ::getConstantSkins
   * @covers ::getSkinsByGroup
   * @covers ::libraryInfoBuild
   */
  public function testSlickManagerMethods() {
    $manager = $this->slickManager;
    $settings = [
      'media_switch'     => 'media',
      'lazy'             => 'ondemand',
      'mousewheel'       => TRUE,
      'skin'             => 'classic',
      'down_arrow'       => TRUE,
      'thumbnail_effect' => 'hover',
      'slick_css'        => TRUE,
      'module_css'       => TRUE,
    ] + $this->getFormatterSettings();

    $attachments = $manager->attach($settings);
    $this->assertArrayHasKey('slick', $attachments['drupalSettings']);

    // Tests for skins.
    $skins = $manager->getSkins();
    $this->assertArrayHasKey('skins', $skins);
    $this->assertArrayHasKey('arrows', $skins);
    $this->assertArrayHasKey('dots', $skins);

    // Verify we have cached skins.
    $cid = 'slick:skins';
    $cached_skins = $manager->getCache()->get($cid);
    $this->assertEquals($cid, $cached_skins->cid);
    $this->assertEquals($skins, $cached_skins->data);

    // Verify skins has thumbnail constant.
    $defined_skins = $manager::getConstantSkins();
    $this->assertTrue(in_array('thumbnail', $defined_skins));

    // Verify libraries.
    $libraries = $manager->libraryInfoBuild();
    $this->assertArrayHasKey('slick.main.default', $libraries);

    $skins = $manager->getSkinsByGroup('dots');
    $this->assertArrayHasKey('dots', $skins);
  }

  /**
   * Tests for Slick build.
   *
   * @param bool $items
   *   Whether to provide items, or not.
   * @param array $settings
   *   The settings being tested.
   * @param array $options
   *   The options being tested.
   * @param mixed|bool|string $expected
   *   The expected output.
   *
   * @covers ::slick
   * @covers ::preRenderSlick
   * @covers ::buildGrid
   * @covers ::build
   * @covers ::preRenderSlickWrapper
   * @dataProvider providerTestSlickBuild
   */
  public function testBuild($items, array $settings, array $options, $expected) {
    $manager  = $this->slickManager;
    $defaults = $this->getFormatterSettings() + Slick::htmlSettings();
    $settings = array_merge($defaults, $settings);

    $settings['optionset'] = 'test';

    $build = $this->display->build($this->entity);

    $items = !$items ? [] : $build[$this->testFieldName]['#build']['items'];
    $build = [
      'items'     => $items,
      'settings'  => $settings,
      'options'   => $options,
      'optionset' => Slick::load($settings['optionset']),
    ];

    $slick = $manager::slick($build);
    $this->assertEquals($expected, !empty($slick));

    $slick['#build']['settings'] = $settings;
    $slick['#build']['items'] = $items;

    $element = $manager::preRenderSlick($slick);
    $this->assertEquals($expected, !empty($element));

    if (!empty($settings['optionset_thumbnail'])) {
      $build['thumb'] = [
        'items'    => $items,
        'settings' => $settings,
        'options'  => $options,
      ];
    }

    $slicks = $manager->build($build);
    $this->assertEquals($expected, !empty($slicks));

    $slicks['#build']['items'] = $items;
    $slicks['#build']['settings'] = $settings;

    if (!empty($settings['optionset_thumbnail'])) {
      $slicks['#build']['thumb']['items'] = $build['thumb']['items'];
      $slicks['#build']['thumb']['settings'] = $build['thumb']['settings'];
    }

    $elements = $manager->preRenderSlickWrapper($slicks);
    $this->assertEquals($expected, !empty($elements));
  }

  /**
   * Provide test cases for ::testBuild().
   *
   * @return array
   *   An array of tested data.
   */
  public function providerTestSlickBuild() {
    $data[] = [
      FALSE,
      [],
      [],
      FALSE,
    ];
    $data[] = [
      TRUE,
      [
        'grid' => 3,
        'visible_items' => 6,
        'override' => TRUE,
        'overridables' => ['arrows' => FALSE, 'dots' => TRUE],
        'skin_dots' => 'dots',
        'cache' => -1,
        'cache_tags' => ['url.site'],
      ],
      ['dots' => TRUE],
      TRUE,
    ];
    $data[] = [
      TRUE,
      [
        'grid' => 3,
        'visible_items' => 6,
        'unslick' => TRUE,
      ],
      [],
      TRUE,
    ];
    $data[] = [
      TRUE,
      [
        'skin' => 'test',
        'nav' => TRUE,
        'optionset_thumbnail' => 'test_nav',
        'thumbnail_position' => 'top',
        'thumbnail_style' => 'thumbnail',
        'thumbnail_effect' => 'hover',

      ],
      [],
      TRUE,
    ];

    return $data;
  }

  /**
   * Tests for \Drupal\slick_ui\Form\SlickForm.
   *
   * @covers \Drupal\slick_ui\Form\SlickForm::getFormElements
   * @covers \Drupal\slick_ui\Form\SlickForm::cleanFormElements
   * @covers \Drupal\slick_ui\Form\SlickForm::getResponsiveFormElements
   * @covers \Drupal\slick_ui\Form\SlickForm::getLazyloadOptions
   * @covers \Drupal\slick_ui\Form\SlickForm::typecastOptionset
   * @covers \Drupal\slick_ui\Form\SlickForm::getJsEasingOptions
   * @covers \Drupal\slick_ui\Form\SlickForm::getCssEasingOptions
   * @covers \Drupal\slick_ui\Form\SlickForm::getOptionsRequiredByTemplate
   * @covers \Drupal\slick_ui\Form\SlickForm::getBezier
   */
  public function testSlickForm() {
    $elements = $this->slickForm->getFormElements();
    $this->assertArrayHasKey('mobileFirst', $elements);

    $elements = $this->slickForm->cleanFormElements();
    $this->assertArrayNotHasKey('appendArrows', $elements);

    $elements = $this->slickForm->getResponsiveFormElements(2);
    $this->assertArrayHasKey('breakpoint', $elements[0]);

    $options = $this->slickForm->getLazyloadOptions();
    $this->assertArrayHasKey('ondemand', $options);

    $settings = [];
    $this->slickForm->typecastOptionset($settings);
    $this->assertEmpty($settings);

    $settings['mobileFirst'] = 1;
    $settings['edgeFriction'] = 0.27;
    $this->slickForm->typecastOptionset($settings);
    $this->assertEquals(TRUE, $settings['mobileFirst']);

    $options = $this->slickForm->getJsEasingOptions();
    $this->assertArrayHasKey('easeInQuad', $options);

    $options = $this->slickForm->getCssEasingOptions();
    $this->assertArrayHasKey('easeInQuad', $options);

    $options = $this->slickForm->getOptionsRequiredByTemplate();
    $this->assertArrayHasKey('lazyLoad', $options);

    $bezier = $this->slickForm->getBezier('easeInQuad');
    $this->assertEquals('cubic-bezier(0.550, 0.085, 0.680, 0.530)', $bezier);
  }

}
