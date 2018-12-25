<?php

namespace Drupal\Tests\layout_plugin\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Tests Layout functionality.
 *
 * @group LayoutPlugin
 */
class LayoutTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['system', 'layout_plugin', 'layout_test'];

  /**
   * The layout plugin manager.
   *
   * @var \Drupal\layout_plugin\Plugin\Layout\LayoutPluginManagerInterface
   */
  protected $layoutManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->layoutManager = $this->container->get('plugin.manager.layout_plugin');
  }

  /**
   * Test listing the available layouts.
   */
  public function testLayoutDefinitions() {
    $expected_layouts = [
      'layout_test_1col',
      'layout_test_2col',
      'layout_test_plugin',
    ];
    $this->assertEquals($expected_layouts, array_keys($this->layoutManager->getDefinitions()));
  }

  /**
   * Test rendering a layout.
   *
   * @dataProvider renderLayoutData
   */
  public function testRenderLayout($layout_id, $config, $regions, $html) {
    /** @var \Drupal\layout_plugin\Plugin\Layout\LayoutInterface $layout */
    $layout = $this->layoutManager->createInstance($layout_id, $config);
    $built = $layout->build($regions);
    $this->render($built);
    $this->assertRaw($html);
  }

  /**
   * Data provider for testRenderLayout().
   */
  public function renderLayoutData() {
    $data = [
      'layout_test_1col' => [
        'layout_test_1col',
        [],
        [
          'top' => [
            '#markup' => 'This is the top',
          ],
          'bottom' => [
            '#markup' => 'This is the bottom',
          ],
        ],
      ],

      'layout_test_2col' => [
        'layout_test_2col',
        [],
        [
          'left' => [
            '#markup' => 'This is the left',
          ],
          'right' => [
            '#markup' => 'This is the right',
          ],
        ],
      ],

      'layout_test_plugin' => [
        'layout_test_plugin',
        [
          'setting_1' => 'Config value'
        ],
        [
          'main' => [
            '#markup' => 'Main region',
          ],
        ]
      ],
    ];

    $data['layout_test_1col'][] = <<<'EOD'
<div class="layout-example-1col clearfix">
  <div class="region-top">
    This is the top
  </div>
  <div class="region-bottom">
    This is the bottom
  </div>
</div>
EOD;

    $data['layout_test_2col'][] = <<<'EOD'
<div class="layout-example-2col clearfix">
  <div class="region-left">
    This is the left
  </div>
  <div class="region-right">
    This is the right
  </div>
</div>
EOD;

    $data['layout_test_plugin'][] = <<<'EOD'
<div class="layout-test-plugin clearfix">
  <div>
    <span class="setting-1-label">Blah: </span>
    Config value
  </div>
  <div class="region-main">
    Main region
  </div>
</div>
EOD;

    return $data;
  }

}
