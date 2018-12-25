<?php

namespace Drupal\purge\Tests\Invalidation;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\purge\Plugin\Purge\Purger\Exception\BadPluginBehaviorException;
use Drupal\purge\Plugin\Purge\Invalidation\ImmutableInvalidationInterface;
use Drupal\purge\Plugin\Purge\Invalidation\ImmutableInvalidationBase;
use Drupal\purge\Plugin\Purge\Invalidation\InvalidationInterface;
use Drupal\purge\Plugin\Purge\Invalidation\InvalidationBase;
use Drupal\purge\Plugin\Purge\Invalidation\Exception\InvalidPropertyException;
use Drupal\purge\Plugin\Purge\Invalidation\Exception\InvalidExpressionException;
use Drupal\purge\Plugin\Purge\Invalidation\Exception\InvalidStateException;
use Drupal\purge\Plugin\Purge\Invalidation\Exception\MissingExpressionException;
use Drupal\purge\Tests\KernelTestBase;

/**
 * Provides an abstract test class to thoroughly test invalidation types.
 *
 * @see \Drupal\purge\Plugin\Purge\Invalidation\InvalidationInterface
 */
abstract class PluginTestBase extends KernelTestBase {

  /**
   * The plugin ID of the invalidation type being tested.
   *
   * @var string
   */
  protected $plugin_id;

  /**
   * String expressions valid to the invalidation type being tested.
   *
   * @var string[]|null
   */
  protected $expressions = NULL;

  /**
   * String expressions INvalid to the invalidation type being tested.
   *
   * @var string[]|null
   */
  protected $expressionsInvalid;

  /**
   * Set up the test.
   */
  public function setUp() {
    parent::setUp();
    $this->initializeInvalidationFactoryService();
  }

  /**
   * Retrieve a invalidation object provided by the plugin.
   */
  public function getInstance() {
    return $this->purgeInvalidationFactory->get(
      $this->plugin_id,
      $this->expressions[0]
    );
  }

  /**
   * Retrieve a immutable invalidation object, which wraps the plugin.
   */
  public function getImmutableInstance() {
    return $this->purgeInvalidationFactory->getImmutable(
      $this->plugin_id,
      $this->expressions[0]
    );
  }

  /**
   * Tests the code contract strictly enforced on invalidation type plugins.
   */
  public function testCodeContract() {
    $this->assertTrue($this->getInstance() instanceof ImmutableInvalidationInterface);
    $this->assertTrue($this->getInstance() instanceof InvalidationInterface);
    $this->assertTrue($this->getInstance() instanceof ImmutableInvalidationBase);
    $this->assertTrue($this->getInstance() instanceof InvalidationBase);
    $this->assertTrue($this->getImmutableInstance() instanceof ImmutableInvalidationInterface);
    $this->assertFalse($this->getImmutableInstance() instanceof InvalidationInterface);
    $this->assertTrue($this->getImmutableInstance() instanceof ImmutableInvalidationBase);
    $this->assertFalse($this->getImmutableInstance() instanceof InvalidationBase);
  }

  /**
   * Tests \Drupal\purge\Plugin\Purge\Invalidation\ImmutableInvalidation.
   */
  public function testImmutable() {
    $immutable = $this->getImmutableInstance();
    $mutable = $this->getInstance();
    $this->assertEqual($immutable->__toString(), $mutable->__toString());
    $this->assertEqual($immutable->getExpression(), $mutable->getExpression());
    $this->assertEqual($immutable->getState(), $mutable->getState());
    $this->assertEqual($immutable->getStateString(), $mutable->getStateString());
    $this->assertEqual($immutable->getType(), $mutable->getType());
  }

  /**
   * Test if setting and getting the object state goes well.
   *
   * @see \Drupal\purge\Plugin\Purge\Invalidation\ImmutableInvalidationInterface::getProperties
   * @see \Drupal\purge\Plugin\Purge\Invalidation\ImmutableInvalidationInterface::getProperty
   * @see \Drupal\purge\Plugin\Purge\Invalidation\InvalidationInterface::deleteProperty
   * @see \Drupal\purge\Plugin\Purge\Invalidation\InvalidationInterface::setProperty
   */
  public function testProperties() {
    $i = $this->getInstance();
    // Verify that getProperties() has the right initial state (of emptyness).
    $this->assertEqual($i->getProperties(), []);
    // Verify that exceptions are thrown when they should.
    $this->assertException('\LogicException', [$i, 'getProperty'], ['key1']);
    $this->assertException('\LogicException', [$i, 'setProperty'], ['key1', 'foobar']);
    $this->assertNoException('\LogicException', [$i, 'getProperties']);
    $i->setStateContext('purger1');
    $this->assertException('\LogicException', [$i, 'getProperties']);
    $this->assertNoException('\LogicException', [$i, 'getProperty'], ['key1']);
    $this->assertNoException('\LogicException', [$i, 'setProperty'], ['key1', 'foobar']);
    // Verify retrieving and setting properties.
    $this->assertNull($i->getProperty('doesntexist'));
    $this->assertEqual($i->getProperty('key1'), 'foobar');
    $this->assertNull($i->deleteProperty('key1'));
    $this->assertNull($i->getProperty('key1'));
    $this->assertNull($i->setProperty('key1', 'foobar2'));
    $this->assertEqual($i->getProperty('key1'), 'foobar2');
    // Switch state to add some more properties.
    $i->setState(InvalidationInterface::FAILED);
    $i->setStateContext('purger2');
    $i->setProperty('key2', 'baz');
    $i->setState(InvalidationInterface::FAILED);
    $i->setStateContext(NULL);
    // Verify that getProperties() works as it should.
    $p = $i->getProperties();
    $this->assertEqual(count($p), 2);
    $this->assertTrue(isset($p['purger1']['key1']));
    $this->assertEqual($p['purger1']['key1'], 'foobar2');
    $this->assertTrue(isset($p['purger2']['key2']));
    $this->assertEqual($p['purger2']['key2'], 'baz');
  }

  /**
   * Test if setting and getting the object state goes well.
   *
   * @see \Drupal\purge\Plugin\Purge\Invalidation\InvalidationInterface::setState
   * @see \Drupal\purge\Plugin\Purge\Invalidation\InvalidationInterface::getState
   * @see \Drupal\purge\Plugin\Purge\Invalidation\InvalidationInterface::getStateString
   */
  public function testState() {
    $i = $this->getInstance();
    // Test the initial state of the invalidation object. Then verify that a
    // BadPluginBehaviorException is thrown when left as FRESH.
    $this->assertEqual($i->getState(), InvalidationInterface::FRESH);
    $this->assertEqual($i->getStateString(), 'FRESH');
    $i->setStateContext('test');
    $this->assertException('\Drupal\purge\Plugin\Purge\Purger\Exception\BadPluginBehaviorException', [$i, 'setStateContext'], [NULL]);
    $i->setState(InvalidationInterface::FAILED);
    $i->setStateContext(NULL);
    // Verify that setting state in general context throws exceptions.
    $this->assertException('\LogicException', [$i, 'setState'], [InvalidationInterface::FAILED]);
    // Test \Drupal\purge\Plugin\Purge\Invalidation\InvalidationInterface::setState catches bad input.
    foreach (['2', 'FRESH', -1, 5, 100] as $badstate) {
      $this->assertException('\Drupal\purge\Plugin\Purge\Invalidation\Exception\InvalidStateException', [$i, 'setState'], [$badstate]);
    }
    // Test setting normal states results in the same return state.
    $test_states = [
      InvalidationInterface::PROCESSING    => 'PROCESSING',
      InvalidationInterface::SUCCEEDED     => 'SUCCEEDED',
      InvalidationInterface::FAILED        => 'FAILED',
      InvalidationInterface::NOT_SUPPORTED => 'NOT_SUPPORTED',
    ];
    $context = 0;
    $i->setStateContext((string) $context);
    foreach ($test_states as $state => $string) {
      $this->assertNull($i->setStateContext((string) ($context++)));
      $this->assertNull($i->setState($state));
      $this->assertEqual($i->getState(), $state);
      $this->assertEqual($i->getStateString(), $string);
    }
    $i->setStateContext(NULL);
  }

  /**
   * Test if typecasting invalidation objects to strings gets us a string.
   *
   * @see \Drupal\purge\Plugin\Purge\Invalidation\InvalidationInterface::__toString
   */
  public function testStringExpression() {
    $this->assertEqual( (string) $this->getInstance(), $this->expressions[0],
      'The __toString method returns $expression.');
  }

  /**
   * Test if all valid string expressions properly instantiate the object.
   *
   * @see \Drupal\purge\Plugin\Purge\Invalidation\InvalidationInterface::__construct
   */
  public function testValidExpressions() {
    if (is_null($this->expressions)) {
      $this->purgeInvalidationFactory->get($this->plugin_id);
    }
    else {
      foreach ($this->expressions as $e) {
        $this->purgeInvalidationFactory->get($this->plugin_id, $e);
      }
    }
  }

  /**
   * Test if all invalid string expressions fail to instantiate the object.
   *
   * @see \Drupal\purge\Plugin\Purge\Invalidation\InvalidationInterface::__construct
   */
  public function testInvalidExpressions($expressions = NULL) {
    foreach ($this->expressionsInvalid as $exp) {
      $thrown = FALSE;
      try {
        $this->purgeInvalidationFactory->get($this->plugin_id, $exp);
      }
      catch (\Exception $e) {
        $thrown = $e;
      }
      if (is_null($exp)) {
        $this->assertTrue($thrown instanceof MissingExpressionException, var_export($exp, TRUE));
      }
      else {
        $this->assertTrue($thrown instanceof InvalidExpressionException, var_export($exp, TRUE));
      }
    }
  }

  /**
   * Test retrieving the plugin ID and definition.
   *
   * @see \Drupal\purge\Plugin\Purge\Invalidation\InvalidationInterface::getPluginId
   * @see \Drupal\purge\Plugin\Purge\Invalidation\InvalidationInterface::getType
   * @see \Drupal\purge\Plugin\Purge\Invalidation\InvalidationInterface::getPluginDefinition
   */
  public function testPluginIdAndDefinition() {
    // Test mutable objects.
    $mutable = $this->getInstance();
    $this->assertEqual($this->plugin_id, $mutable->getPluginId());
    $this->assertEqual($this->plugin_id, $mutable->getType());
    $d = $mutable->getPluginDefinition();
    $this->assertTrue(is_array($d));
    $this->assertTrue(is_array($d['examples']));
    $this->assertTrue($d['label'] instanceof TranslatableMarkup);
    $this->assertFalse(empty((string) $d['label']));
    $this->assertTrue($d['description'] instanceof TranslatableMarkup);
    $this->assertFalse(empty((string) $d['description']));
    $this->assertTrue(isset($d['expression_required']));
    $this->assertTrue(isset($d['expression_can_be_empty']));
    $this->assertTrue(isset($d['expression_must_be_string']));
    if (!$d["expression_required"]) {
      $this->assertFalse($d["expression_can_be_empty"]);
    }
    // Test the immutable objects.
    $immutable = $this->getImmutableInstance();
    $this->assertEqual($this->plugin_id, $immutable->getPluginId());
    $this->assertEqual($this->plugin_id, $immutable->getType());
    $d = $immutable->getPluginDefinition();
    $this->assertTrue(is_array($d));
    $this->assertTrue(is_array($d['examples']));
    $this->assertTrue($d['label'] instanceof TranslatableMarkup);
    $this->assertFalse(empty((string) $d['label']));
    $this->assertTrue($d['description'] instanceof TranslatableMarkup);
    $this->assertFalse(empty((string) $d['description']));
    $this->assertTrue(isset($d['expression_required']));
    $this->assertTrue(isset($d['expression_can_be_empty']));
    $this->assertTrue(isset($d['expression_must_be_string']));
    if (!$d["expression_required"]) {
      $this->assertFalse($d["expression_can_be_empty"]);
    }
  }

}
