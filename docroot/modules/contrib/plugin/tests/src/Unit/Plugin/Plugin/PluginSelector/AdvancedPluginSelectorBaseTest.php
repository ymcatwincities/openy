<?php

namespace Drupal\Tests\plugin\Unit\Plugin\Plugin\PluginSelector;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Component\Utility\Html;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheableDependencyInterface;
use Drupal\Core\Form\FormState;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\SubformState;
use Drupal\Core\Form\SubformStateInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\plugin\Plugin\Plugin\PluginSelector\AdvancedPluginSelectorBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @coversDefaultClass \Drupal\plugin\Plugin\Plugin\PluginSelector\AdvancedPluginSelectorBase
 *
 * @group Plugin
 */
class AdvancedPluginSelectorBaseTest extends PluginSelectorBaseTestBase {

  /**
   * The class under test.
   *
   * @var \Drupal\plugin\Plugin\Plugin\PluginSelector\AdvancedPluginSelectorBase|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $sut;

  /**
   * The string translator.
   *
   * @var \Drupal\Core\StringTranslation\TranslationInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $stringTranslation;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->stringTranslation = $this->getStringTranslationStub();

    $this->sut = $this->getMockBuilder(AdvancedPluginSelectorBase::class)
      ->setConstructorArgs(array(
        [],
        $this->pluginId,
        $this->pluginDefinition,
        $this->defaultPluginResolver,
        $this->stringTranslation
      ))
      ->getMockForAbstractClass();
    $this->sut->setSelectablePluginType($this->selectablePluginType);
  }

  /**
   * @covers ::create
   * @covers ::__construct
   */
  function testCreate() {
    $container = $this->getMock(ContainerInterface::class);
    $map = [
      ['plugin.default_plugin_resolver', ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE, $this->defaultPluginResolver],
      ['string_translation', ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE, $this->stringTranslation ],
    ];
    $container->expects($this->any())
      ->method('get')
      ->willReturnMap($map);

    /** @var \Drupal\plugin\Plugin\Plugin\PluginSelector\AdvancedPluginSelectorBase $class */
    $class = get_class($this->sut);
    $plugin = $class::create($container, [], $this->pluginId, $this->pluginDefinition);
    $this->assertInstanceOf(AdvancedPluginSelectorBase::class, $plugin);
  }

  /**
   * @covers ::buildPluginForm
   */
  public function testBuildPluginForm() {
    $plugin_form_state = $this->getMock(SubformStateInterface::class);

    $plugin_form = array(
      '#foo' => $this->randomMachineName(),
    );

    $plugin = $this->getMockForAbstractClass(AdvancedPluginSelectorBaseUnitTestPluginFormPluginInterface::class);
    $plugin->expects($this->once())
      ->method('buildConfigurationForm')
      ->with($this->isType('array'), $this->isInstanceOf(FormStateInterface::class))
      ->willReturn($plugin_form);


    $method = new \ReflectionMethod($this->sut, 'buildPluginForm');
    $method->setAccessible(TRUE);

    $plugin_selector_form = [];
    $build = $method->invoke($this->sut, $plugin_selector_form, $plugin_form_state);
    $this->assertSame('container', $build['#type']);

    $this->sut->setSelectedPlugin($plugin);
    $plugin_selector_form = [];
    $build = $method->invoke($this->sut, $plugin_selector_form, $plugin_form_state);
    $this->assertSame($plugin_form['#foo'], $build['#foo']);
  }

  /**
   * @covers ::buildPluginForm
   */
  public function testBuildPluginFormWithoutPluginForm() {
    $form_state = new FormState();

    $plugin = $this->getMock(PluginInspectionInterface::class);
    $plugin->expects($this->never())
      ->method('buildConfigurationForm');

    $method = new \ReflectionMethod($this->sut, 'buildPluginForm');
    $method->setAccessible(TRUE);

    $plugin_selector_form = [];
    $build = $method->invoke($this->sut, $plugin_selector_form, $form_state);
    $this->assertSame('container', $build['#type']);

    $this->sut->setSelectedPlugin($plugin);
    $plugin_selector_form = [];
    $build = $method->invoke($this->sut, $plugin_selector_form, $form_state);
    $this->assertSame('container', $build['#type']);
  }

  /**
   * @covers ::buildSelectorForm
   * @covers ::setPluginSelector
   */
  public function testBuildSelectorFormWithoutAvailablePlugins() {
    $plugin_selector_form = [];
    $plugin_selector_form_state = $this->getMock(SubformStateInterface::class);

    $this->selectablePluginManager->expects($this->any())
      ->method('getDefinitions')
      ->willReturn([]);

    $build = $this->sut->buildSelectorForm($plugin_selector_form, $plugin_selector_form_state);
    unset($build['container']['#plugin_selector_form_state_key']);

    $expected_build = array(
      '#tree' => TRUE,
      '#cache' => [
        'contexts' => [],
        'tags' => [],
        'max-age' => Cache::PERMANENT,
      ],
      'container' => array(
        '#attributes' => array(
          'class' => array('plugin-selector-' . Html::getId($this->pluginId)),
        ),
        '#available_plugins' => [],
        '#process' => [
          [
            AdvancedPluginSelectorBase::class,
            'processBuildSelectorForm'
          ]
        ],
        '#type' => 'container',
      ),
    );
    $this->assertSame($expected_build, $build);
  }

  /**
   * @covers ::buildSelectorForm
   * @covers ::setPluginSelector
   */
  public function testBuildSelectorFormWithOneAvailablePlugin() {
    $plugin_selector_form = [];
    $plugin_selector_form_state = $this->getMock(SubformStateInterface::class);

    $plugin_id = $this->randomMachineName();
    $plugin = $this->getMock(PluginInspectionInterface::class);

    $plugin_definitions = [
      $plugin_id => [
        'id' => $plugin_id,
      ],
    ];

    $this->selectablePluginManager->expects($this->any())
      ->method('createInstance')
      ->with($plugin_id)
      ->willReturn($plugin);
    $this->selectablePluginManager->expects($this->any())
      ->method('getDefinitions')
      ->willReturn($plugin_definitions);

    $build = $this->sut->buildSelectorForm($plugin_selector_form, $plugin_selector_form_state);
    unset($build['container']['#plugin_selector_form_state_key']);

    $expected_build = array(
      '#tree' => TRUE,
      '#cache' => [
        'contexts' => [],
        'tags' => [],
        'max-age' => 0,
      ],
      'container' => array(
        '#attributes' => array(
          'class' => array('plugin-selector-' . Html::getId($this->pluginId)),
        ),
        '#available_plugins' => [$plugin],
        '#process' => [
          [
            AdvancedPluginSelectorBase::class,
            'processBuildSelectorForm'
          ]
        ],
        '#type' => 'container',
      ),
    );
    $this->assertSame($expected_build, $build);
  }

  /**
   * Configures a CacheableDependencyInterface mock.
   *
   * @param \PHPUnit_Framework_MockObject_MockObject $mock
   *   The mock to configure. It must also implement
   *   \Drupal\Core\Cache\CacheableDependencyInterface.
   * @param string[] $contexts
   * @param int $max_age
   * @Param string[] $tags
   */
  protected function configureMockCacheableDependency(\PHPUnit_Framework_MockObject_MockObject $mock, array $contexts, $max_age, array $tags) {
    $mock->expects($this->atLeastOnce())
      ->method('getCacheContexts')
      ->willReturn($contexts);
    $mock->expects($this->atLeastOnce())
      ->method('getCacheMaxAge')
      ->willReturn($max_age);
    $mock->expects($this->atLeastOnce())
      ->method('getCacheTags')
      ->willReturn($tags);
  }

  /**
   * @covers ::buildSelectorForm
   * @covers ::setPluginSelector
   */
  public function testBuildSelectorFormWithMultipleAvailablePlugins() {
    $plugin_selector_form = [];
    $plugin_selector_form_state = $this->getMock(SubformStateInterface::class);
    $cache_contexts = [$this->randomMachineName()];
    $cache_tags = [$this->randomMachineName()];

    $plugin_id_a = $this->randomMachineName();
    $plugin_a = $this->getMock(PluginInspectionInterface::class);
    $plugin_id_b = $this->randomMachineName();
    $plugin_b = $this->getMock(AdvancedPluginSelectorBaseUnitTestPluginFormPluginInterface::class);
    $this->configureMockCacheableDependency($plugin_b, $cache_contexts, mt_rand(), $cache_tags);

    $plugin_definitions = [
      $plugin_id_a => [
        'id' => $plugin_id_a,
      ],
      $plugin_id_b => [
        'id' => $plugin_id_b,
      ],
    ];

    $map = [
      [$plugin_id_a, [], $plugin_a],
      [$plugin_id_b, [], $plugin_b],
    ];
    $this->selectablePluginManager->expects($this->atLeastOnce())
      ->method('createInstance')
      ->willReturnMap($map);
    $this->selectablePluginManager->expects($this->any())
      ->method('getDefinitions')
      ->willReturn($plugin_definitions);

    $build = $this->sut->buildSelectorForm($plugin_selector_form, $plugin_selector_form_state);
    unset($build['container']['#plugin_selector_form_state_key']);

    $expected_build = array(
      '#tree' => TRUE,
      '#cache' => [
        'contexts' => $cache_contexts,
        'tags' => $cache_tags,
        'max-age' => 0,
      ],
      'container' => array(
        '#attributes' => array(
          'class' => array('plugin-selector-' . Html::getId($this->pluginId)),
        ),
        '#available_plugins' => array($plugin_a, $plugin_b),
        '#process' => [
          [
            AdvancedPluginSelectorBase::class,
            'processBuildSelectorForm'
          ]
        ],
        '#type' => 'container',
      ),
    );
    $this->assertSame($expected_build, $build);
  }

  /**
   * @covers ::submitSelectorForm
   */
  public function testSubmitSelectorForm() {
    $plugin_selector_form = array(
      'container' => array(
        'plugin_form' => array(
          $this->randomMachineName() => [],
        ),
      ),
    );
    $plugin_selector_form_state = $this->getMock(SubformStateInterface::class);

    $plugin = $this->getMockForAbstractClass(AdvancedPluginSelectorBaseUnitTestPluginFormPluginInterface::class);
    $plugin->expects($this->once())
      ->method('submitConfigurationForm')
      ->with($plugin_selector_form['container']['plugin_form'], $this->isInstanceOf(FormStateInterface::class));

    $this->sut->submitSelectorForm($plugin_selector_form, $plugin_selector_form_state);
    $this->sut->setSelectedPlugin($plugin);
    $this->sut->submitSelectorForm($plugin_selector_form, $plugin_selector_form_state);
  }

  /**
   * @covers ::validateSelectorForm
   */
  public function testValidateSelectorForm() {
    $plugin_id_a = $this->randomMachineName();
    $plugin_id_b = $this->randomMachineName();

    $selector_form = array(
      '#parents' => [],
      'container' => array(
        '#parents' => array('foo', 'bar', 'container'),
        'plugin_form' => array(
          $this->randomMachineName() => [],
        ),
      ),
    );

    $plugin_a = $this->getMockForAbstractClass(AdvancedPluginSelectorBaseUnitTestPluginFormPluginInterface::class);
    $plugin_a->expects($this->any())
      ->method('getPluginId')
      ->willReturn($plugin_id_a);
    $plugin_b = $this->getMockForAbstractClass(AdvancedPluginSelectorBaseUnitTestPluginFormPluginInterface::class);
    $plugin_b->expects($this->never())
      ->method('validateConfigurationForm');
    $plugin_b->expects($this->any())
      ->method('getPluginId')
      ->willReturn($plugin_id_b);

    $map = array(
      array($plugin_id_a, [], $plugin_a),
      array($plugin_id_b, [], $plugin_b),
    );
    $this->selectablePluginManager->expects($this->exactly(2))
      ->method('createInstance')
      ->willReturnMap($map);

    // The plugin is set for the first time. The plugin form must not be
    // validated, as there is no input for it yet.
    $form_state = new FormState();
    $form_state->setValues([
      'container' => [
        'select' => [
          'container' => [
            'plugin_id' => $plugin_id_a,
          ],
        ],
      ],
    ]);
    $selector_form_state = SubformState::createForSubform($selector_form, $selector_form, $form_state);
    $this->sut->validateSelectorForm($selector_form, $selector_form_state);
    $this->assertTrue($selector_form_state->isRebuilding());
    $this->assertSame($plugin_a, $this->sut->getSelectedPlugin());

    // The form is validated, but the plugin remains unchanged, and as such
    // should validate its own form as well.
    $form_state = new FormState();
    $form_state->setValues([
      'container' => [
        'select' => [
          'container' => [
            'plugin_id' => $plugin_id_a,
          ],
        ],
      ],
    ]);
    $selector_form_state = SubformState::createForSubform($selector_form, $selector_form, $form_state);
    $plugin_a->expects($this->once())
      ->method('validateConfigurationForm')
      ->with($selector_form['container']['plugin_form'], $this->isInstanceOf(FormStateInterface::class));
    $this->sut->validateSelectorForm($selector_form, $selector_form_state);
    $this->assertFalse($selector_form_state->isRebuilding());
    $this->assertSame($plugin_a, $this->sut->getSelectedPlugin());

    // The plugin has changed. The plugin form must not be validated, as there
    // is no input for it yet.
    $form_state = new FormState();
    $form_state->setValues([
      'container' => [
        'select' => [
          'container' => [
            'plugin_id' => $plugin_id_b,
          ],
        ],
      ],
    ]);
    $selector_form_state = SubformState::createForSubform($selector_form, $selector_form, $form_state);
    $this->sut->validateSelectorForm($selector_form, $selector_form_state);
    $this->assertTrue($selector_form_state->isRebuilding());
    $this->assertSame($plugin_b, $this->sut->getSelectedPlugin());

    // Change the plugin ID back to the original. No new plugin may be
    // instantiated, nor must the plugin form be validated.
    $form_state = new FormState();
    $form_state->setValues([
      'container' => [
        'select' => [
          'container' => [
            'plugin_id' => $plugin_id_a,
          ],
        ],
      ],
    ]);
    $selector_form_state = SubformState::createForSubform($selector_form, $selector_form, $form_state);
    $this->sut->validateSelectorForm($selector_form, $selector_form_state);
    $this->assertTrue($selector_form_state->isRebuilding());
    $this->assertSame($plugin_a, $this->sut->getSelectedPlugin());
  }

  /**
   * @covers ::rebuildForm
   */
  public function testRebuildForm() {
    $form = [];
    $form_state = $this->getMock(FormStateInterface::class);
    $form_state->expects($this->once())
      ->method('setRebuild')
      ->with(TRUE);

    $this->sut->rebuildForm($form, $form_state);
  }

  /**
   * @covers ::buildNoAvailablePlugins
   */
  public function testBuildNoAvailablePlugins() {
    $element = [];
    $form_state = $this->getMock(FormStateInterface::class);
    $form = [];

    $label = $this->randomMachineName();

    $this->sut->setLabel($label);

    $expected_build = $element + array(
        'select' => array(
          'message' => array(
            '#markup' => 'There are no available options.',
            '#title' => $label,
            '#type' => 'item',
          ),
          'container' => array(
            '#type' => 'container',
            'plugin_id' => array(
              '#type' => 'value',
              '#value' => NULL,
            ),
          ),
        ),
      );
    $this->assertEquals($expected_build, $this->sut->buildNoAvailablePlugins($element, $form_state, $form));
  }

  /**
   * @covers ::buildOneAvailablePlugin
   */
  public function testBuildOneAvailablePlugin() {
    $plugin_id = 'foo_bar:plugin_id';

    $plugin_form = array(
      '#type' => 'plugin_form',
    );

    $plugin = $this->getMockForAbstractClass(AdvancedPluginSelectorBaseUnitTestPluginFormPluginInterface::class);
    $plugin->expects($this->atLeastOnce())
      ->method('getPluginId')
      ->willReturn($plugin_id);
    $plugin->expects($this->once())
      ->method('buildConfigurationForm')
      ->willReturn($plugin_form);

    $element = array(
      '#available_plugins' => array($plugin),
    );
    $form_state = $this->getMock(FormStateInterface::class);
    $form = [];

    $label = 'Foo Bar';

    $this->sut->setLabel($label);
    $build = $this->sut->buildOneAvailablePlugin($element, $form_state, $form);
    unset($build['plugin_form']['#id']);
    $this->assertSame($build['#available_plugins'], [$plugin]);
    $this->assertSame($build['plugin_form'], $plugin_form);
  }

  /**
   * @covers ::buildMultipleAvailablePlugins
   */
  public function testbuildMultipleAvailablePlugins() {
    $plugin = $this->getMock(PluginInspectionInterface::class);

    $element = array(
      '#available_plugins' => array($plugin),
    );
    $form_state = $this->getMock(FormStateInterface::class);
    $form = [];

    $plugin_form = array(
      '#type' => $this->randomMachineName(),
    );

    $selector = array(
      '#type' => $this->randomMachineName(),
    );

    /** @var \Drupal\plugin\Plugin\Plugin\PluginSelector\AdvancedPluginSelectorBase|\PHPUnit_Framework_MockObject_MockObject $plugin_selector */
    $plugin_selector = $this->getMockBuilder(AdvancedPluginSelectorBase::class)
      ->setMethods(array('buildPluginForm', 'buildSelector'))
      ->setConstructorArgs(array(
        [],
        $this->pluginId,
        $this->pluginDefinition,
        $this->defaultPluginResolver,
        $this->stringTranslation
      ))
      ->getMockForAbstractClass();
    $plugin_selector->setSelectablePluginType($this->selectablePluginType);
    $plugin_selector->expects($this->once())
      ->method('buildPluginForm')
      ->with($this->isType('array'), $form_state)
      ->willReturn($plugin_form);
    $plugin_selector->expects($this->once())
      ->method('buildSelector')
      ->with($element, $form_state, array($plugin))
      ->willReturn($selector);
    $plugin_selector->setSelectedPlugin($plugin);

    $expected_build = array(
      '#available_plugins' => array($plugin),
      'select' => $selector,
      'plugin_form' => $plugin_form,
    );

    $method = new \ReflectionMethod(get_class($plugin_selector), 'buildMultipleAvailablePlugins');
    $method->setAccessible(TRUE);
    $this->assertEquals($expected_build, $method->invoke($plugin_selector, $element, $form_state, $form));
  }

  /**
   * @covers ::setSelectedPlugin
   * @covers ::getSelectedPlugin
   */
  public function testGetPlugin() {
    $plugin = $this->getMock(PluginInspectionInterface::class);
    $this->assertSame($this->sut, $this->sut->setSelectedPlugin($plugin));
    $this->assertSame($plugin, $this->sut->getSelectedPlugin());
  }

  /**
   * @covers ::buildSelector
   */
  public function testBuildSelector() {
    $this->stringTranslation->expects($this->any())
      ->method('translate')
      ->willReturnArgument(0);

    $method = new \ReflectionMethod($this->sut, 'buildSelector');
    $method->setAccessible(TRUE);

    $plugin_id = $this->randomMachineName();
    $plugin_label = $this->randomMachineName();
    $plugin = $this->getMock(PluginInspectionInterface::class);
    $plugin->expects($this->any())
      ->method('getPluginId')
      ->willReturn($plugin_id);
    $plugin->expects($this->any())
      ->method('getPluginLabel')
      ->willReturn($plugin_label);

    $this->sut->setSelectedPlugin($plugin);

    $element = array(
      '#parents' => array('foo', 'bar'),
    );
    $form_state = $this->getMock(FormStateInterface::class);
    $available_plugins = array($plugin);

    $expected_build_change = array(
      '#ajax' => array(
        'callback' => array(
          AdvancedPluginSelectorBase::class,
          'ajaxRebuildForm'
        ),
      ),
      '#attributes' => array(
        'class' => array('js-hide')
      ),
      '#limit_validation_errors' => array(
        array(
          'foo',
          'bar',
          'select',
          'plugin_id'
        )
      ),
      '#name' => 'foo__bar__select__container__change',
      '#submit' => [[AdvancedPluginSelectorBase::class, 'rebuildForm']],
      '#type' => 'submit',
      '#value' => 'Choose',
    );
    $build = $method->invokeArgs($this->sut, array(
      $element,
      $form_state,
      $available_plugins
    ));
    $this->assertArrayHasKey('plugin_id', $build['container']);
    $this->assertEquals($expected_build_change, $build['container']['change']);
    $this->assertSame('container', $build['container']['#type']);
  }

  /**
   * @covers ::defaultConfiguration
   */
  public function testDefaultConfiguration() {
    $this->assertInternalType('array', $this->sut->defaultConfiguration());
  }

  /**
   * @covers ::buildConfigurationForm
   */
  public function testBuildConfigurationForm() {
    $form = [
      'plugin' => [],
    ];
    $form_state = new FormState();
    $configuration_form = $form['plugin'];
    $configuration_form_state = SubformState::createForSubform($configuration_form, $form, $form_state);
    $this->assertInternalType('array', $this->sut->buildConfigurationForm($configuration_form, $configuration_form_state));
  }

  /**
   * @covers ::getSelectorVisibilityForSingleAvailability
   * @covers ::setSelectorVisibilityForSingleAvailability
   */
  public function testGetSelectorVisibilityForSingleAvailability() {
    $this->assertFalse($this->sut->getSelectorVisibilityForSingleAvailability());
    $this->sut->setSelectorVisibilityForSingleAvailability(TRUE);
    $this->assertTrue($this->sut->getSelectorVisibilityForSingleAvailability());
  }

}


/**
 * Provides a plugin that provides a form and cacheability metadata.
 */
interface AdvancedPluginSelectorBaseUnitTestPluginFormPluginInterface extends PluginInspectionInterface, PluginFormInterface, CacheableDependencyInterface {
}
