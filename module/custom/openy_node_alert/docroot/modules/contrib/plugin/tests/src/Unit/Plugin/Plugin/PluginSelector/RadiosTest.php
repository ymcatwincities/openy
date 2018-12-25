<?php

namespace Drupal\Tests\plugin\Unit\Plugin\Plugin\PluginSelector;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\SubformStateInterface;
use Drupal\Core\PageCache\ResponsePolicy\KillSwitch;
use Drupal\plugin\Plugin\Plugin\PluginSelector\AdvancedPluginSelectorBase;
use Drupal\plugin\Plugin\Plugin\PluginSelector\Radios;
use Drupal\plugin\PluginDefinition\PluginLabelDefinitionInterface;

/**
 * @coversDefaultClass \Drupal\plugin\Plugin\Plugin\PluginSelector\Radios
 *
 * @group Plugin
 */
class RadiosTest extends PluginSelectorBaseTestBase {

  /**
   * The class under test.
   *
   * @var \Drupal\plugin\Plugin\Plugin\PluginSelector\Radios
   */
  protected $sut;

  /**
   * The response policy.
   *
   * @var \Drupal\Core\PageCache\ResponsePolicyInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $responsePolicy;

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

    $this->responsePolicy = $this->getMockBuilder(KillSwitch::class)
      ->disableOriginalConstructor()
      ->getMock();

    $this->stringTranslation = $this->getStringTranslationStub();

    $this->sut = new Radios([], $this->pluginId, $this->pluginDefinition, $this->defaultPluginResolver, $this->stringTranslation, $this->responsePolicy);
    $this->sut->setSelectablePluginType($this->selectablePluginType);
  }

  /**
   * @covers ::buildSelectorForm
   */
  public function testBuildSelectorFormWithoutAvailablePlugins() {
    $plugin_selector_form = [];
    $plugin_selector_form_state = $this->getMock(SubformStateInterface::class);

    $this->selectablePluginManager->expects($this->any())
      ->method('getDefinitions')
      ->willReturn([]);

    $build = $this->sut->buildSelectorForm($plugin_selector_form, $plugin_selector_form_state);

    $this->assertArrayHasKey('clear', $build);
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
    $plugin_definition = $this->getMock(PluginLabelDefinitionInterface::class);
    $plugin_definition->expects($this->atLeastOnce())
      ->method('getLabel')
      ->willReturn($plugin_label);
    $plugin = $this->getMock(PluginInspectionInterface::class);
    $plugin->expects($this->atLeastOnce())
      ->method('getPluginDefinition')
      ->willReturn($plugin_definition);
    $plugin->expects($this->atLeastOnce())
      ->method('getPluginId')
      ->willReturn($plugin_id);

    $this->selectablePluginType->expects($this->atLeastOnce())
      ->method('ensureTypedPluginDefinition')
      ->willReturnArgument(0);

    $this->sut->setSelectedPlugin($plugin);
    $selector_title = $this->randomMachineName();
    $this->sut->setLabel($selector_title);
    $selector_description = $this->randomMachineName();
    $this->sut->setDescription($selector_description);

    $element = array(
      '#parents' => array('foo', 'bar'),
      '#title' => $selector_title,
    );
    $form_state = $this->getMock(FormStateInterface::class);
    $available_plugins = array($plugin);

    $expected_build_plugin_id = array(
      '#ajax' => array(
        'callback' => array(Radios::class, 'ajaxRebuildForm'),
        'effect' => 'fade',
        'event' => 'change',
        'progress' => 'none',
        'trigger_as' => array(
          'name' => 'foo__bar__select__container__change',
        ),
      ),
      '#attached' => [
        'library' => ['plugin/plugin_selector.plugin_radios'],
      ],
      '#default_value' => $plugin_id,
      '#empty_value' => 'select',
      '#options' => array(
        $plugin_id => $plugin_label,
      ) ,
      '#required' => FALSE,
      '#title' => $selector_title,
      '#description' => $selector_description,
      '#type' => 'radios',
    );
    $expected_build_change = array(
      '#ajax' => array(
        'callback' => array(AdvancedPluginSelectorBase::class, 'ajaxRebuildForm'),
      ),
      '#attributes' => array(
        'class' => array('js-hide')
      ),
      '#limit_validation_errors' => array(array('foo', 'bar', 'select', 'plugin_id')),
      '#name' => 'foo__bar__select__container__change',
      '#submit' => [[AdvancedPluginSelectorBase::class, 'rebuildForm']],
      '#type' => 'submit',
      '#value' => 'Choose',
    );
    $build = $method->invokeArgs($this->sut, array($element, $form_state, $available_plugins));
    $this->assertEquals($expected_build_plugin_id, $build['container']['plugin_id']);
    $this->assertEquals($expected_build_change, $build['container']['change']);
    $this->assertSame('container', $build['container']['#type']);
  }

}
