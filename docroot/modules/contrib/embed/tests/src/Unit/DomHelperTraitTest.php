<?php

namespace Drupal\Tests\embed\Unit;

use Drupal\Component\Utility\Html;
use Drupal\Component\Serialization\Json;
use Drupal\Tests\UnitTestCase;
use Drupal\embed\DomHelperTrait;

/**
 * Tests \Drupal\embed\DomHelperTrait
 *
 * @group embed
 */
class DomHelperTraitTest extends UnitTestCase {
  use DomHelperTrait;

  /**
   * The DOM Document used for testing.
   *
   * @var \DOMDocument
   */
  protected $document;

  /**
   * The DOM Node used for testing.
   *
   * @var \DOMElement
   */
  protected $node;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    $this->document = Html::load('<outer><test foo="bar" namespace:foo="bar"><test bar="foo"></test></test></outer>');
    $this->node = $this->document->getElementsByTagName('body')->item(0)->firstChild->firstChild;
  }

  /**
   * Tests DomHelperTrait::changeNodeName().
   */
  public function testChangeNodeName() {
    $this->changeNodeName($this->node, 'tested');
    $this->assertEquals($this->node->tagName, 'tested');
    $this->assertEquals(Html::serialize($this->document), '<outer><tested foo="bar" namespace:foo="bar"><test bar="foo"></test></tested></outer>');
  }

  /**
   * Tests DomHelperTrait::setNodeContent().
   */
  public function testSetNodeContent() {
    $this->setNodeContent($this->node, '<div><replacement replaced="true" /></div>');
    $this->assertEquals(Html::serialize($this->document), '<outer><test foo="bar" namespace:foo="bar"><div><replacement replaced="true"></replacement></div></test></outer>');
    // Test replacing with an empty value.
    $this->setNodeContent($this->node, '');
    $this->assertEquals(Html::serialize($this->document), '<outer><test foo="bar" namespace:foo="bar"></test></outer>');
    // Test replacing again with a non-empty value.
    $this->setNodeContent($this->node, '<div></div>');
    $this->assertEquals(Html::serialize($this->document), '<outer><test foo="bar" namespace:foo="bar"><div></div></test></outer>');
  }

  /**
   * Test DomHelperTrait::replaceNodeContent().
   */
  public function testReplaceNodeContent() {
    $this->replaceNodeContent($this->node, '<div><replacement replaced="true" /></div>');
    $this->assertEquals(Html::serialize($this->document), '<outer><div><replacement replaced="true"></replacement></div></outer>');
    // Test replacing with an empty value.
    $this->replaceNodeContent($this->node, '');
    $this->assertEquals(Html::serialize($this->document), '<outer></outer>');
    // Test replacing again with a non-empty value.
    $this->replaceNodeContent($this->node, '<div></div>');
    $this->assertEquals(Html::serialize($this->document), '<outer><div></div></outer>');
  }

  /**
   * Test DomHelperTrait::getNodeAttributesAsArray().
   */
  public function testGetNodeAttributesAsArray() {
    $attributes = $this->getNodeAttributesAsArray($this->node);
    $this->assertArrayEquals(['foo' => 'bar', 'namespace:foo' => 'bar'], $attributes);

    // Test more complex attributes with special characters.
    $string = "TEST: A <complex> 'encoded' \"JSON\" string";
    $object = array('nested' => array('array' => true), 'string' => $string);
    $html = '<test data-json-string=\'' . Json::encode($string) . '\' data-json-object=\'' . Json::encode($object) . '\'></test>';
    $document = Html::load($html);
    $node = $document->getElementsByTagName('body')->item(0)->firstChild;
    $attributes = $this->getNodeAttributesAsArray($node);
    $this->assertArrayEquals(['data-json-string' => $string, 'data-json-object' => $object], $attributes);
  }
}
