<?php

namespace Drupal\Tests\plugin\Unit\Plugin\Plugin\PluginSelector;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\PageCache\ResponsePolicy\KillSwitch;
use Drupal\plugin\Plugin\Plugin\PluginSelector\AdvancedPluginSelectorBase;
use Drupal\plugin\Plugin\Plugin\PluginSelector\SelectList;
use Drupal\plugin\PluginDefinition\PluginDefinitionInterface;
use Drupal\plugin\PluginDefinition\PluginLabelDefinitionInterface;

/**
 * @coversDefaultClass \Drupal\plugin\Plugin\Plugin\PluginSelector\SelectList
 *
 * @group Plugin
 */
class SelectListTest extends PluginSelectorBaseTestBase {

  /**
   * The class under test.
   *
   * @var \Drupal\plugin\Plugin\Plugin\PluginSelector\SelectList
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

    $this->selectablePluginType->expects($this->any())
      ->method('ensureTypedPluginDefinition')
      ->willReturnArgument(0);

    $this->sut = new SelectList([], $this->pluginId, $this->pluginDefinition, $this->defaultPluginResolver, $this->stringTranslation, $this->responsePolicy);
    $this->sut->setSelectablePluginType($this->selectablePluginType);
  }

  /**
   * @covers ::buildSelector
   * @covers ::buildOptionsLevel
   */
  public function testBuildSelector() {
    $this->stringTranslation->expects($this->any())
      ->method('translate')
      ->willReturnArgument(0);

    $method = new \ReflectionMethod($this->sut, 'buildSelector');
    $method->setAccessible(TRUE);

    $plugin_id_a = $this->randomMachineName();
    $plugin_label_a = $this->randomMachineName();
    $plugin_definition_a = $this->getMock(PluginLabelDefinitionInterface::class);
    $plugin_definition_a->expects($this->atLeastOnce())
      ->method('getLabel')
      ->willReturn($plugin_label_a);
    $plugin_a = $this->getMock(PluginInspectionInterface::class);
    $plugin_a->expects($this->atLeastOnce())
      ->method('getPluginId')
      ->willReturn($plugin_id_a);
    $plugin_id_b = $this->randomMachineName();
    $plugin_definition_b = $this->getMock(PluginDefinitionInterface::class);
    $plugin_definition_b->expects($this->atLeastOnce())
      ->method('getId')
      ->willReturn($plugin_id_b);
    $plugin_b = $this->getMock(PluginInspectionInterface::class);

    $this->sut->setSelectedPlugin($plugin_a);
    $selector_title = $this->randomMachineName();
    $this->sut->setLabel($selector_title);
    $selector_description = $this->randomMachineName();
    $this->sut->setDescription($selector_description);

    $element = array(
      '#parents' => array('foo', 'bar'),
    );
    $form_state = $this->getMock(FormStateInterface::class);
    $available_plugins = [$plugin_a, $plugin_b];

    $this->selectablePluginManager->expects($this->atLeastOnce())
      ->method('getDefinitions')
      ->willReturn([
        $plugin_id_a => $plugin_definition_a,
        $plugin_id_b => $plugin_definition_b,
      ]);

    $expected_build_plugin_id = array(
      '#ajax' => array(
        'callback' => array(SelectList::class, 'ajaxRebuildForm'),
        'effect' => 'fade',
        'event' => 'change',
        'trigger_as' => array(
          'name' => 'foo__bar__select__container__change',
        ),
      ),
      '#default_value' => $plugin_id_a,
      '#empty_value' => '',
      '#options' => array(
        $plugin_id_a => $plugin_label_a,
        $plugin_id_b => $plugin_id_b,
      ) ,
      '#required' => FALSE,
      '#title' => $selector_title,
      '#description' => $selector_description,
      '#type' => 'select',
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
