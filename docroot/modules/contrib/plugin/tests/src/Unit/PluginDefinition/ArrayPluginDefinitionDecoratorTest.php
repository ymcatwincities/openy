<?php

namespace Drupal\Tests\plugin\Unit\PluginDefinition;

use Drupal\Component\Plugin\Derivative\DeriverInterface;
use Drupal\Core\Plugin\Context\ContextDefinitionInterface;
use Drupal\plugin\PluginDefinition\ArrayPluginDefinitionDecorator;
use Drupal\plugin\PluginDefinition\PluginDefinitionInterface;
use Drupal\plugin\PluginOperationsProviderInterface;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\plugin\PluginDefinition\ArrayPluginDefinitionDecorator
 *
 * @group Plugin
 */
class ArrayPluginDefinitionDecoratorTest extends UnitTestCase {

  /**
   * The array definition.
   *
   * @var mixed[]
   */
  protected $arrayDefinition = [];

  /**
   * The class under test.
   *
   * @var \Drupal\plugin\PluginDefinition\ArrayPluginDefinitionDecorator
   */
  protected $sut;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->arrayDefinition = [
      'id' => $this->randomMachineName(),
      'label' => $this->randomMachineName(),
      'description' => $this->randomMachineName(),
      'class' => $this->getMockClass(DeriverInterface::class),
      'category' => $this->randomMachineName(),
      'provider' => $this->randomMachineName(),
      'deriver' => $this->getMockClass(DeriverInterface::class),
      'operations_provider' => $this->getMockClass(PluginOperationsProviderInterface::class),
      'context' => [
        $this->randomMachineName() => $this->getMock(ContextDefinitionInterface::class),
      ],
      'config_dependencies' => [
        'module' => [$this->randomMachineName()],
      ],
      'parent_id' => $this->randomMachineName(),
    ];

    $this->sut = new ArrayPluginDefinitionDecorator($this->arrayDefinition);
  }

  /**
   * @covers ::__construct
   */
  public function testConstruct() {
    $this->sut = new ArrayPluginDefinitionDecorator($this->arrayDefinition);
    $this->assertInstanceOf(ArrayPluginDefinitionDecorator::class, $this->sut);
  }

  /**
   * @covers ::getArrayDefinition
   */
  public function testGetArrayDefiniton() {
    $this->assertSame($this->arrayDefinition, $this->sut->getArrayDefinition());
  }

  /**
   * @covers ::setId
   * @covers ::getId
   * @covers ::offsetExists
   * @covers ::offsetSet
   * @covers ::offsetGet
   * @covers ::offsetUnset
   */
  public function testGetId() {
    // Test the injected value.
    $this->assertSame($this->arrayDefinition['id'], $this->sut->getId());
    $this->assertSame($this->arrayDefinition['id'], $this->sut->getArrayDefinition()['id']);
    $this->assertSame($this->arrayDefinition['id'], $this->sut['id']);

    // Test changing the value through the setter.
    $value = $this->randomMachineName();
    $this->assertSame($this->sut, $this->sut->setId($value));
    $this->assertSame($value, $this->sut->getId());
    $this->assertSame($value, $this->sut->getArrayDefinition()['id']);
    $this->assertSame($value, $this->sut['id']);

    // Test changing the value through array access.
    $value = $this->randomMachineName();
    $this->sut['id'] = $value;
    $this->assertSame($value, $this->sut->getId());
    $this->assertSame($value, $this->sut->getArrayDefinition()['id']);
    $this->assertSame($value, $this->sut['id']);

    // Test unsetting the value.
    unset($this->sut['id']);
    $this->assertFalse(isset($this->sut['id']));
    $this->assertNull($this->sut->getId());
    $this->assertFalse(isset($this->sut->getArrayDefinition()['id']));
  }

  /**
   * @covers ::setLabel
   * @covers ::getLabel
   * @covers ::offsetExists
   * @covers ::offsetSet
   * @covers ::offsetGet
   * @covers ::offsetUnset
   */
  public function testGetLabel() {
    // Test the injected value.
    $this->assertSame($this->arrayDefinition['label'], $this->sut->getLabel());
    $this->assertSame($this->arrayDefinition['label'], $this->sut->getArrayDefinition()['label']);
    $this->assertSame($this->arrayDefinition['label'], $this->sut['label']);

    // Test changing the value through the setter.
    $value = $this->randomMachineName();
    $this->assertSame($this->sut, $this->sut->setLabel($value));
    $this->assertSame($value, $this->sut->getLabel());
    $this->assertSame($value, $this->sut->getArrayDefinition()['label']);
    $this->assertSame($value, $this->sut['label']);

    // Test changing the value through array access.
    $value = $this->randomMachineName();
    $this->sut['label'] = $value;
    $this->assertSame($value, $this->sut->getLabel());
    $this->assertSame($value, $this->sut->getArrayDefinition()['label']);
    $this->assertSame($value, $this->sut['label']);

    // Test unsetting the value.
    unset($this->sut['label']);
    $this->assertFalse(isset($this->sut['label']));
    $this->assertNull($this->sut->getLabel());
    $this->assertFalse(isset($this->sut->getArrayDefinition()['label']));
  }

  /**
   * @covers ::setDescription
   * @covers ::getDescription
   * @covers ::offsetExists
   * @covers ::offsetSet
   * @covers ::offsetGet
   * @covers ::offsetUnset
   */
  public function testGetDescription() {
    // Test the injected value.
    $this->assertSame($this->arrayDefinition['description'], $this->sut->getDescription());
    $this->assertSame($this->arrayDefinition['description'], $this->sut->getArrayDefinition()['description']);
    $this->assertSame($this->arrayDefinition['description'], $this->sut['description']);

    // Test changing the value through the setter.
    $value = $this->randomMachineName();
    $this->assertSame($this->sut, $this->sut->setDescription($value));
    $this->assertSame($value, $this->sut->getDescription());
    $this->assertSame($value, $this->sut->getArrayDefinition()['description']);
    $this->assertSame($value, $this->sut['description']);

    // Test changing the value through array access.
    $value = $this->randomMachineName();
    $this->sut['description'] = $value;
    $this->assertSame($value, $this->sut->getDescription());
    $this->assertSame($value, $this->sut->getArrayDefinition()['description']);
    $this->assertSame($value, $this->sut['description']);

    // Test unsetting the value.
    unset($this->sut['description']);
    $this->assertFalse(isset($this->sut['description']));
    $this->assertNull($this->sut->getDescription());
    $this->assertFalse(isset($this->sut->getArrayDefinition()['description']));
  }

  /**
   * @covers ::setClass
   * @covers ::getClass
   * @covers ::offsetExists
   * @covers ::offsetSet
   * @covers ::offsetGet
   * @covers ::offsetUnset
   */
  public function testGetClass() {
    // Test the injected value.
    $this->assertSame($this->arrayDefinition['class'], $this->sut->getClass());
    $this->assertSame($this->arrayDefinition['class'], $this->sut->getArrayDefinition()['class']);
    $this->assertSame($this->arrayDefinition['class'], $this->sut['class']);

    // Test changing the value through the setter.
    $value = '\stdClass';
    $this->assertSame($this->sut, $this->sut->setClass($value));
    $this->assertSame($value, $this->sut->getClass());
    $this->assertSame($value, $this->sut->getArrayDefinition()['class']);
    $this->assertSame($value, $this->sut['class']);

    // Test changing the value through array access.
    $value = '\stdClass';
    $this->sut['class'] = $value;
    $this->assertSame($value, $this->sut->getClass());
    $this->assertSame($value, $this->sut->getArrayDefinition()['class']);
    $this->assertSame($value, $this->sut['class']);

    // Test unsetting the value.
    unset($this->sut['class']);
    $this->assertFalse(isset($this->sut['class']));
    $this->assertNull($this->sut->getClass());
    $this->assertFalse(isset($this->sut->getArrayDefinition()['class']));
  }

  /**
   * @covers ::setDeriverClass
   * @covers ::getDeriverClass
   * @covers ::offsetExists
   * @covers ::offsetSet
   * @covers ::offsetGet
   * @covers ::offsetUnset
   */
  public function testGetDeriverClass() {
    // Test the injected value.
    $this->assertSame($this->arrayDefinition['deriver'], $this->sut->getDeriverClass());
    $this->assertSame($this->arrayDefinition['deriver'], $this->sut->getArrayDefinition()['deriver']);
    $this->assertSame($this->arrayDefinition['deriver'], $this->sut['deriver']);

    // Test changing the value through the setter.
    $value = $this->getMockClass(DeriverInterface::class);
    $this->assertSame($this->sut, $this->sut->setDeriverClass($value));
    $this->assertSame($value, $this->sut->getDeriverClass());
    $this->assertSame($value, $this->sut->getArrayDefinition()['deriver']);
    $this->assertSame($value, $this->sut['deriver']);

    // Test changing the value through array access.
    $value = $this->getMockClass(DeriverInterface::class);
    $this->sut['deriver'] = $value;
    $this->assertSame($value, $this->sut->getDeriverClass());
    $this->assertSame($value, $this->sut->getArrayDefinition()['deriver']);
    $this->assertSame($value, $this->sut['deriver']);

    // Test unsetting the value.
    unset($this->sut['deriver']);
    $this->assertFalse(isset($this->sut['deriver']));
    $this->assertNull($this->sut->getDeriverClass());
    $this->assertFalse(isset($this->sut->getArrayDefinition()['deriver']));
  }

  /**
   * @covers ::setContextDefinitions
   * @covers ::getContextDefinitions
   * @covers ::offsetExists
   * @covers ::offsetSet
   * @covers ::offsetGet
   * @covers ::offsetUnset
   */
  public function testGetContextDefinitions() {
    // Test the injected value.
    $this->assertSame($this->arrayDefinition['context'], $this->sut->getContextDefinitions());
    $this->assertSame($this->arrayDefinition['context'], $this->sut->getArrayDefinition()['context']);
    $this->assertSame($this->arrayDefinition['context'], $this->sut['context']);

    // Test changing the value through the setter.
    $context_definition_name_a = $this->randomMachineName();
    $context_definition_a = $this->getMock(ContextDefinitionInterface::class);
    $context_definition_name_b = $this->randomMachineName();
    $context_definition_b = $this->getMock(ContextDefinitionInterface::class);
    $value = [
      $context_definition_name_a => $context_definition_a,
      $context_definition_name_b => $context_definition_b,
    ];
    $this->assertSame($this->sut, $this->sut->setContextDefinitions($value));
    $this->assertSame($value, $this->sut->getContextDefinitions());
    $this->assertSame($value, $this->sut->getArrayDefinition()['context']);
    $this->assertSame($value, $this->sut['context']);

    // Test changing the value through array access.
    $context_definition_name_a = $this->randomMachineName();
    $context_definition_a = $this->getMock(ContextDefinitionInterface::class);
    $context_definition_name_b = $this->randomMachineName();
    $context_definition_b = $this->getMock(ContextDefinitionInterface::class);
    $value = [
      $context_definition_name_a => $context_definition_a,
      $context_definition_name_b => $context_definition_b,
    ];
    $this->sut['context'] = $value;
    $this->assertSame($value, $this->sut->getContextDefinitions());
    $this->assertSame($value, $this->sut->getArrayDefinition()['context']);
    $this->assertSame($value, $this->sut['context']);

    // Test unsetting the value.
    unset($this->sut['context']);
    $this->assertFalse(isset($this->sut['context']));
    $this->assertSame([], $this->sut->getContextDefinitions());
    $this->assertFalse(isset($this->sut->getArrayDefinition()['context']));
  }

  /**
   * @covers ::setContextDefinitions
   * @covers ::offsetSet
   *
   * @depends testGetContextDefinitions
   *
   * @expectedException \InvalidArgumentException
   */
  public function testSetContextDefinitionsWithInvalidDefinition() {
    $context_definitions = [
      $this->randomMachineName() => new \stdClass(),
    ];

    $this->sut['context'] = $context_definitions;
  }

  /**
   * @covers ::setContextDefinition
   * @covers ::getContextDefinition
   * @covers ::hasContextDefinition
   */
  public function testGetContextDefinition() {
    $name = $this->randomMachineName();
    $context_definition = $this->getMock(ContextDefinitionInterface::class);

    $this->assertSame($this->sut, $this->sut->setContextDefinition($name, $context_definition));
    $this->assertSame($context_definition, $this->sut->getContextDefinition($name));
    $this->assertTrue($this->sut->hasContextDefinition($name));
  }

  /**
   * @covers ::getContextDefinition
   * @covers ::hasContextDefinition
   *
   * @depends testGetContextDefinition
   *
   * @expectedException \InvalidArgumentException
   */
  public function testGetContextDefinitionWithNonExistentDefinition() {
    $name = $this->randomMachineName();

    $this->assertFalse($this->sut->hasContextDefinition($name));
    $this->sut->getContextDefinition($name);
  }

  /**
   * @covers ::mergeDefaultArrayDefinition
   */
  public function testMergeDefaultArrayDefinition() {
    $other_definition = [
      'foo' => $this->randomMachineName(),
      'label' => $this->randomMachineName(),
    ];

    $this->assertSame($this->sut, $this->sut->mergeDefaultArrayDefinition($other_definition));
    $this->assertSame($other_definition['foo'], $this->sut->getArrayDefinition()['foo']);
    $this->assertNotSame($other_definition['label'], $this->sut->getArrayDefinition()['label']);
  }

  /**
   * @covers ::mergeDefaultDefinition
   * @covers ::doMergeDefaultDefinition
   * @covers ::validateMergeDefinition
   * @covers ::isDefinitionCompatible
   *
   * @depends testMergeDefaultArrayDefinition
   */
  public function testMergeDefaultDefinition() {
    $other_definition = new ArrayPluginDefinitionDecorator();
    $other_definition['foo'] = $this->randomMachineName();
    $other_definition['label'] = $this->randomMachineName();

    $this->assertSame($this->sut, $this->sut->mergeDefaultDefinition($other_definition));
    $this->assertSame($other_definition['foo'], $this->sut->getArrayDefinition()['foo']);
    $this->assertNotSame($other_definition['label'], $this->sut->getArrayDefinition()['label']);
  }

  /**
   * @covers ::mergeDefaultDefinition
   * @covers ::validateMergeDefinition
   * @covers ::isDefinitionCompatible
   *
   * @depends testMergeDefaultDefinition
   *
   * @expectedException \InvalidArgumentException
   */
  public function testMergeDefaultDefinitionWithInvalidOtherDefinition() {
    $other_definition = $this->getMock(PluginDefinitionInterface::class);

    $this->sut->mergeDefaultDefinition($other_definition);
  }

  /**
   * @covers ::mergeOverrideArrayDefinition
   */
  public function testMergeOverrideArrayDefinition() {
    $other_definition = [
      'foo' => $this->randomMachineName(),
      'label' => $this->randomMachineName(),
    ];

    $this->assertSame($this->sut, $this->sut->mergeOverrideArrayDefinition($other_definition));
    $this->assertSame($other_definition['foo'], $this->sut->getArrayDefinition()['foo']);
    $this->assertSame($other_definition['label'], $this->sut->getArrayDefinition()['label']);
  }

  /**
   * @covers ::mergeOverrideDefinition
   * @covers ::doMergeOverrideDefinition
   * @covers ::validateMergeDefinition
   * @covers ::isDefinitionCompatible
   *
   * @depends testMergeOverrideArrayDefinition
   */
  public function testMergeOverrideDefinition() {
    $other_definition = new ArrayPluginDefinitionDecorator();
    $other_definition['foo'] = $this->randomMachineName();
    $other_definition['label'] = $this->randomMachineName();

    $this->assertSame($this->sut, $this->sut->mergeOverrideDefinition($other_definition));
    $this->assertSame($other_definition['foo'], $this->sut->getArrayDefinition()['foo']);
    $this->assertSame($other_definition['label'], $this->sut->getArrayDefinition()['label']);
  }

  /**
   * @covers ::mergeOverrideDefinition
   * @covers ::validateMergeDefinition
   * @covers ::isDefinitionCompatible
   *
   * @depends testMergeOverrideDefinition
   *
   * @expectedException \InvalidArgumentException
   */
  public function testMergeOverrideDefinitionWithInvalidOtherDefinition() {
    $other_definition = $this->getMock(PluginDefinitionInterface::class);

    $this->sut->mergeOverrideDefinition($other_definition);
  }

  /**
   * @covers ::setCategory
   * @covers ::getCategory
   * @covers ::offsetExists
   * @covers ::offsetSet
   * @covers ::offsetGet
   * @covers ::offsetUnset
   */
  public function testGetCategory() {
    // Test the injected value.
    $this->assertSame($this->arrayDefinition['category'], $this->sut->getCategory());
    $this->assertSame($this->arrayDefinition['category'], $this->sut->getArrayDefinition()['category']);
    $this->assertSame($this->arrayDefinition['category'], $this->sut['category']);

    // Test changing the value through the setter.
    $value = $this->randomMachineName();
    $this->assertSame($this->sut, $this->sut->setCategory($value));
    $this->assertSame($value, $this->sut->getCategory());
    $this->assertSame($value, $this->sut->getArrayDefinition()['category']);
    $this->assertSame($value, $this->sut['category']);

    // Test changing the value through array access.
    $value = $this->randomMachineName();
    $this->sut['category'] = $value;
    $this->assertSame($value, $this->sut->getCategory());
    $this->assertSame($value, $this->sut->getArrayDefinition()['category']);
    $this->assertSame($value, $this->sut['category']);

    // Test unsetting the value.
    unset($this->sut['category']);
    $this->assertFalse(isset($this->sut['category']));
    $this->assertNull($this->sut->getCategory());
    $this->assertFalse(isset($this->sut->getArrayDefinition()['category']));
  }

  /**
   * @covers ::setProvider
   * @covers ::getProvider
   * @covers ::offsetExists
   * @covers ::offsetSet
   * @covers ::offsetGet
   * @covers ::offsetUnset
   */
  public function testGetProvider() {
    // Test the injected value.
    $this->assertSame($this->arrayDefinition['provider'], $this->sut->getProvider());
    $this->assertSame($this->arrayDefinition['provider'], $this->sut->getArrayDefinition()['provider']);
    $this->assertSame($this->arrayDefinition['provider'], $this->sut['provider']);

    // Test changing the value through the setter.
    $value = $this->randomMachineName();
    $this->assertSame($this->sut, $this->sut->setProvider($value));
    $this->assertSame($value, $this->sut->getProvider());
    $this->assertSame($value, $this->sut->getArrayDefinition()['provider']);
    $this->assertSame($value, $this->sut['provider']);

    // Test changing the value through array access.
    $value = $this->randomMachineName();
    $this->sut['provider'] = $value;
    $this->assertSame($value, $this->sut->getProvider());
    $this->assertSame($value, $this->sut->getArrayDefinition()['provider']);
    $this->assertSame($value, $this->sut['provider']);

    // Test unsetting the value.
    unset($this->sut['provider']);
    $this->assertFalse(isset($this->sut['provider']));
    $this->assertNull($this->sut->getProvider());
    $this->assertFalse(isset($this->sut->getArrayDefinition()['provider']));
  }

  /**
   * @covers ::setParentId
   * @covers ::getParentId
   * @covers ::offsetExists
   * @covers ::offsetSet
   * @covers ::offsetGet
   * @covers ::offsetUnset
   */
  public function testGetParentId() {
    // Test the injected value.
    $this->assertSame($this->arrayDefinition['parent_id'], $this->sut->getParentId());
    $this->assertSame($this->arrayDefinition['parent_id'], $this->sut->getArrayDefinition()['parent_id']);
    $this->assertSame($this->arrayDefinition['parent_id'], $this->sut['parent_id']);

    // Test changing the value through the setter.
    $value = $this->randomMachineName();
    $this->assertSame($this->sut, $this->sut->setParentId($value));
    $this->assertSame($value, $this->sut->getParentId());
    $this->assertSame($value, $this->sut->getArrayDefinition()['parent_id']);
    $this->assertSame($value, $this->sut['parent_id']);

    // Test changing the value through array access.
    $value = $this->randomMachineName();
    $this->sut['parent_id'] = $value;
    $this->assertSame($value, $this->sut->getParentId());
    $this->assertSame($value, $this->sut->getArrayDefinition()['parent_id']);
    $this->assertSame($value, $this->sut['parent_id']);

    // Test unsetting the value.
    unset($this->sut['parent_id']);
    $this->assertFalse(isset($this->sut['parent_id']));
    $this->assertNull($this->sut->getParentId());
    $this->assertFalse(isset($this->sut->getArrayDefinition()['parent_id']));
  }

  /**
   * @covers ::setConfigDependencies
   * @covers ::getConfigDependencies
   * @covers ::offsetExists
   * @covers ::offsetSet
   * @covers ::offsetGet
   * @covers ::offsetUnset
   */
  public function testGetConfigDependencies() {
    // Test the injected value.
    $this->assertSame($this->arrayDefinition['config_dependencies'], $this->sut->getConfigDependencies());
    $this->assertSame($this->arrayDefinition['config_dependencies'], $this->sut->getArrayDefinition()['config_dependencies']);
    $this->assertSame($this->arrayDefinition['config_dependencies'], $this->sut['config_dependencies']);

    // Test changing the value through the setter.
    $value = [
      'module' => [$this->randomMachineName()],
    ];
    $this->assertSame($this->sut, $this->sut->setConfigDependencies($value));
    $this->assertSame($value, $this->sut->getConfigDependencies());
    $this->assertSame($value, $this->sut->getArrayDefinition()['config_dependencies']);
    $this->assertSame($value, $this->sut['config_dependencies']);

    // Test changing the value through array access.
    $value = [
      'module' => [$this->randomMachineName()],
    ];
    $this->sut['config_dependencies'] = $value;
    $this->assertSame($value, $this->sut->getConfigDependencies());
    $this->assertSame($value, $this->sut->getArrayDefinition()['config_dependencies']);
    $this->assertSame($value, $this->sut['config_dependencies']);

    // Test unsetting the value.
    unset($this->sut['config_dependencies']);
    $this->assertFalse(isset($this->sut['config_dependencies']));
    $this->assertSame([], $this->sut->getConfigDependencies());
    $this->assertFalse(isset($this->sut->getArrayDefinition()['config_dependencies']));
  }

  /**
   * @covers ::count
   */
  public function testCount() {
    $this->assertSame(count($this->arrayDefinition), count($this->sut));
  }

  /**
   * @covers ::getIterator
   */
  public function testGetIterator() {
    foreach ($this->sut as $key => $value) {
      $this->assertSame($this->arrayDefinition[$key], $value);
    }
  }

  /**
   * @covers ::createFromDecoratedDefinition
   */
  public function testCreateFromDecoratedDefinition() {
    $this->assertInstanceOf(ArrayPluginDefinitionDecorator::class, ArrayPluginDefinitionDecorator::createFromDecoratedDefinition($this->arrayDefinition));
  }

  /**
   * @covers ::createFromDecoratedDefinition
   *
   * @dataProvider providerCreateFromDecoratedDefinitionWithInvalidDecoratedDefinition
   *
   * @expectedException \InvalidArgumentException
   */
  public function testCreateFromDecoratedDefinitionWithInvalidDecoratedDefinition($decorated_definition) {
    $this->assertInstanceOf(ArrayPluginDefinitionDecorator::class, ArrayPluginDefinitionDecorator::createFromDecoratedDefinition($decorated_definition));
  }

  /**
   * Provides data to self::testCreateFromDecoratedDefinitionWithInvalidDecoratedDefinition().
   */
  public function providerCreateFromDecoratedDefinitionWithInvalidDecoratedDefinition() {
    return [
      [$this->randomMachineName()],
      [new \stdClass()],
      [new ArrayPluginDefinitionDecorator()],
    ];
  }

  /**
   * @covers ::setOperationsProviderClass
   * @covers ::getOperationsProviderClass
   * @covers ::offsetExists
   * @covers ::offsetSet
   * @covers ::offsetGet
   * @covers ::offsetUnset
   */
  public function testGetOperationsProviderClass() {
    // Test the injected value.
    $this->assertSame($this->arrayDefinition['operations_provider'], $this->sut->getOperationsProviderClass());
    $this->assertSame($this->arrayDefinition['operations_provider'], $this->sut->getArrayDefinition()['operations_provider']);
    $this->assertSame($this->arrayDefinition['operations_provider'], $this->sut['operations_provider']);

    // Test changing the value through the setter.
    $value = $this->getMockClass(PluginOperationsProviderInterface::class);
    $this->assertSame($this->sut, $this->sut->setOperationsProviderClass($value));
    $this->assertSame($value, $this->sut->getOperationsProviderClass());
    $this->assertSame($value, $this->sut->getArrayDefinition()['operations_provider']);
    $this->assertSame($value, $this->sut['operations_provider']);

    // Test changing the value through array access.
    $value = $this->getMockClass(PluginOperationsProviderInterface::class);
    $this->sut['operations_provider'] = $value;
    $this->assertSame($value, $this->sut->getOperationsProviderClass());
    $this->assertSame($value, $this->sut->getArrayDefinition()['operations_provider']);
    $this->assertSame($value, $this->sut['operations_provider']);

    // Test unsetting the value.
    unset($this->sut['operations_provider']);
    $this->assertFalse(isset($this->sut['operations_provider']));
    $this->assertNull($this->sut->getOperationsProviderClass());
    $this->assertFalse(isset($this->sut->getArrayDefinition()['operations_provider']));
  }

}
