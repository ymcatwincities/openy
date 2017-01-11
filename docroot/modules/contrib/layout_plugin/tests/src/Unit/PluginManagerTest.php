<?php

namespace Drupal\Tests\layout_plugin\Unit;

use Drupal\layout_plugin\Plugin\Layout\LayoutPluginManager;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the LayoutPluginManager.
 *
 * @coversDefaultClass \Drupal\layout_plugin\Plugin\Layout\LayoutPluginManager
 *
 * @group LayoutPlugin
 */
class PluginManagerTest extends UnitTestCase {

  /**
   * Test processDefinition.
   *
   * @covers ::processDefinition
   */
  public function testProcessDefinition() {
    $namespaces = new \ArrayObject();
    $namespaces['Drupal\layout_plugin_test'] = $this->root . '/modules/layout_plugin_test/src';

    $cache_backend = $this->getMock('Drupal\Core\Cache\CacheBackendInterface');

    $module_handler = $this->getMock('Drupal\Core\Extension\ModuleHandlerInterface');
    $module_handler->method('getModuleDirectories')->willReturn(array());
    $module_handler->method('moduleExists')->willReturn(TRUE);
    $extension = $this->getMockBuilder('Drupal\Core\Extension\Extension')
      ->disableOriginalConstructor()
      ->getMock();
    $extension->method('getPath')->willReturn('modules/layout_plugin_test');
    $module_handler->method('getModule')->willReturn($extension);

    $theme_handler = $this->getMock('Drupal\Core\Extension\ThemeHandlerInterface');
    $theme_handler->method('getThemeDirectories')->willReturn(array());

    $plugin_manager = new LayoutPluginManager($namespaces, $cache_backend, $module_handler, $theme_handler);

    // A simple definition with only the required keys.
    $definition = [
      'label' => 'Simple layout',
      'category' => 'Test layouts',
      'theme' => 'simple_layout',
      'provider' => 'layout_plugin_test',
      'regions' => [
        'first' => ['label' => 'First region'],
        'second' => ['label' => 'Second region'],
      ],
    ];
    $plugin_manager->processDefinition($definition, 'simple_layout');
    $this->assertEquals('modules/layout_plugin_test', $definition['path']);
    $this->assertEquals([
      'first' => 'First region',
      'second' => 'Second region'
    ], $definition['region_names']);

    // A more complex definition.
    $definition = [
      'label' => 'Complex layout',
      'category' => 'Test layouts',
      'template' => 'complex-layout',
      'library' => 'library_module/library_name',
      'provider' => 'layout_plugin_test',
      'path' => 'layout/complex',
      'icon' => 'complex-layout.png',
      'regions' => [
        'first' => ['label' => 'First region'],
        'second' => ['label' => 'Second region'],
      ],
    ];
    $plugin_manager->processDefinition($definition, 'complex_layout');
    $this->assertEquals('modules/layout_plugin_test/layout/complex', $definition['path']);
    $this->assertEquals('modules/layout_plugin_test/layout/complex', $definition['template_path']);
    $this->assertEquals('modules/layout_plugin_test/layout/complex/complex-layout.png', $definition['icon']);
    $this->assertEquals('complex_layout', $definition['theme']);
    $this->assertEquals(['module' => ['library_module']], $definition['dependencies']);

    // A layout with a template path.
    $definition = [
      'label' => 'Split layout',
      'category' => 'Test layouts',
      'template' => 'templates/split-layout',
      'provider' => 'layout_plugin_test',
      'path' => 'layouts',
      'icon' => 'images/split-layout.png',
      'regions' => [
        'first' => ['label' => 'First region'],
        'second' => ['label' => 'Second region'],
      ],
    ];
    $plugin_manager->processDefinition($definition, 'split_layout');
    $this->assertEquals('modules/layout_plugin_test/layouts', $definition['path']);
    $this->assertEquals('modules/layout_plugin_test/layouts/templates', $definition['template_path']);
    $this->assertEquals('modules/layout_plugin_test/layouts/images/split-layout.png', $definition['icon']);
    $this->assertEquals('split_layout', $definition['theme']);

    // A layout with an auto-registered library.
    $definition = [
      'label' => 'Auto library',
      'category' => 'Test layouts',
      'theme' => 'auto_library',
      'provider' => 'layout_plugin_test',
      'path' => 'layouts/auto_library',
      'css' => 'css/auto-library.css',
      'regions' => [
        'first' => ['label' => 'First region'],
        'second' => ['label' => 'Second region'],
      ],
    ];
    $plugin_manager->processDefinition($definition, 'auto_library');
    $this->assertEquals('modules/layout_plugin_test/layouts/auto_library/css/auto-library.css', $definition['css']);
    $this->assertEquals('layout_plugin/auto_library', $definition['library']);
  }

  /**
   * Test getting layout options.
   *
   * @covers ::getLayoutOptions
   */
  public function testGetLayoutOptions() {
    /** @var LayoutPluginManager|\PHPUnit_Framework_MockObject_MockBuilder $layout_manager */
    $layout_manager = $this->getMockBuilder('Drupal\layout_plugin\Plugin\Layout\LayoutPluginManager')
      ->disableOriginalConstructor()
      ->setMethods(['getDefinitions'])
      ->getMock();

    $layout_manager->method('getDefinitions')
      ->willReturn([
        'simple_layout' => [
          'label' => 'Simple layout',
          'category' => 'Test layouts',
        ],
        'complex_layout' => [
          'label' => 'Complex layout',
          'category' => 'Test layouts',
        ],
      ]);

    $options = $layout_manager->getLayoutOptions();
    $this->assertEquals([
      'simple_layout' => 'Simple layout',
      'complex_layout' => 'Complex layout',
    ], $options);

    $options = $layout_manager->getLayoutOptions(array('group_by_category' => TRUE));
    $this->assertEquals([
      'Test layouts' => [
        'simple_layout' => 'Simple layout',
        'complex_layout' => 'Complex layout',
      ],
    ], $options);
  }

  /**
   * Tests layout theme implementations.
   *
   * @covers ::getThemeImplementations
   */
  public function testGetThemeImplementations() {
    /** @var LayoutPluginManager|\PHPUnit_Framework_MockObject_MockBuilder $layout_manager */
    $layout_manager = $this->getMockBuilder('Drupal\layout_plugin\Plugin\Layout\LayoutPluginManager')
      ->disableOriginalConstructor()
      ->setMethods(['getDefinitions'])
      ->getMock();

    $layout_manager->method('getDefinitions')
      ->willReturn([
        // Should get template registered automatically.
        'simple_layout' => [
          'path' => 'modules/layout_plugin_test',
          'template_path' => 'modules/layout_plugin_test/templates',
          'template' => 'simple-layout',
          'theme' => 'simple_layout',
        ],
        // Shouldn't get registered automatically.
        'complex_layout' => [
          'path' => 'modules/layout_plugin_test',
          'theme' => 'complex_layout',
        ],
      ]);

    $theme_registry = $layout_manager->getThemeImplementations();
    $this->assertEquals([
      'simple_layout' => [
        'render element' => 'content',
        'template' => 'simple-layout',
        'path' => 'modules/layout_plugin_test/templates',
      ],
    ], $theme_registry);
  }

  /**
   * Tests layout theme implementations.
   *
   * @covers ::alterThemeImplementations
   */
  public function testAlterThemeImplementations() {
    /** @var LayoutPluginManager|\PHPUnit_Framework_MockObject_MockBuilder $layout_manager */
    $layout_manager = $this->getMockBuilder('Drupal\layout_plugin\Plugin\Layout\LayoutPluginManager')
      ->disableOriginalConstructor()
      ->setMethods(['getDefinitions'])
      ->getMock();

    $layout_manager->method('getDefinitions')
      ->willReturn([
        'simple_layout' => [
          'template' => 'simple-layout',
          'theme' => 'simple_layout',
        ],
        'no_template_preprocess' => [
          'template' => 'no-template-preprocess',
          'theme' => 'no_template_preprocess',
        ],
        'only_template_preprocess' => [
          'template' => 'only-template-preprocess',
          'theme' => 'only_template_preprocess',
        ],
        // If the user registered the theme hook themselves, then we don't
        // want to add our preprocess function (because we're not totally sure
        // how it'll work).
        'complex_layout' => [
          'theme' => 'complex_layout',
        ],
      ]);

    $theme_registry = [
      'other_theme_hook' => [
        'preprocess functions' => [
          'template_preprocess_other_theme_hook'
        ],
      ],
      'simple_layout' => [
        'preprocess functions' => [
          'template_preprocess',
          'template_preprocess_simple_layout'
        ],
      ],
      'simple_layout__suggestion_template' => [
        'base hook' => 'simple_layout',
        'preprocess functions' => [
          'template_preprocess',
          'template_preprocess_simple_layout'
        ],
      ],
      // Make sure our alter still works if there is no 'template_preprocess'.
      'no_template_preprocess' => [
        'preprocess functions' => [
          'template_preprocess_no_template_preprocess'
        ],
      ],
      // Make sure our alter still works if there's only 'template_preprocess'.
      'only_template_preprocess' => [
        'preprocess functions' => [
          'template_preprocess',
        ],
      ],
      'complex_layout' => [
        'preprocess functions' => [
          'template_preprocess_complex_layout',
        ],
      ],
    ];

    $layout_manager->alterThemeImplementations($theme_registry);
    $this->assertEquals([
      'other_theme_hook' => [
        'preprocess functions' => [
          'template_preprocess_other_theme_hook'
        ],
      ],
      'simple_layout' => [
        'preprocess functions' => [
          'template_preprocess',
          '_layout_plugin_preprocess_layout',
          'template_preprocess_simple_layout'
        ],
      ],
      'simple_layout__suggestion_template' => [
        'base hook' => 'simple_layout',
        'preprocess functions' => [
          'template_preprocess',
          '_layout_plugin_preprocess_layout',
          'template_preprocess_simple_layout'
        ],
      ],
      'no_template_preprocess' => [
        'preprocess functions' => [
          '_layout_plugin_preprocess_layout',
          'template_preprocess_no_template_preprocess'
        ],
      ],
      'only_template_preprocess' => [
        'preprocess functions' => [
          'template_preprocess',
          '_layout_plugin_preprocess_layout',
        ],
      ],
      'complex_layout' => [
        'preprocess functions' => [
          'template_preprocess_complex_layout',
        ],
      ],
    ], $theme_registry);
  }

  /**
   * Tests layout plugin library info.
   *
   * @covers ::getLibraryInfo
   */
  public function testGetLibraryInfo() {
    /** @var \Drupal\layout_plugin\Plugin\Layout\LayoutPluginManager|\PHPUnit_Framework_MockObject_MockObject $layout_manager */
    $layout_manager = $this->getMockBuilder('Drupal\layout_plugin\Plugin\Layout\LayoutPluginManager')
      ->disableOriginalConstructor()
      ->setMethods(['getDefinitions', 'getProviderVersion'])
      ->getMock();

    $layout_manager->method('getDefinitions')
      ->willReturn([
        // Should get template registered automatically.
        'simple_layout' => [
          'css' => 'modules/layout_plugin_test/layouts/simple_layout/simple-layout.css',
          'library' => 'layout_plugin/simple_layout',
          'provider_type' => 'module',
          'provider' => 'layout_plugin_test',
        ],
        'theme_layout' => [
          'css' => 'themes/theme_with_layout/layouts/theme_layout/theme-layout.css',
          'library' => 'layout_plugin/theme_layout',
          'provider_type' => 'theme',
          'provider' => 'theme_with_layout',
        ],
        'complex_layout' => [
          'library' => 'layout_plugin_test/complex_layout',
        ],
      ]);

    $layout_manager->method('getProviderVersion')
      ->willReturnMap([
        ['module', 'layout_plugin_test', '1.2.3'],
        ['theme', 'theme_with_layout', '2.3.4'],
      ]);

    $library_info = $layout_manager->getLibraryInfo();
    $this->assertEquals([
      'simple_layout' => [
        'version' => '1.2.3',
        'css' => [
          'theme' => [
            '/modules/layout_plugin_test/layouts/simple_layout/simple-layout.css' => [],
          ],
        ],
      ],
      'theme_layout' => [
        'version' => '2.3.4',
        'css' => [
          'theme' => [
            '/themes/theme_with_layout/layouts/theme_layout/theme-layout.css' => [],
          ],
        ],
      ],
    ], $library_info);
  }

}
