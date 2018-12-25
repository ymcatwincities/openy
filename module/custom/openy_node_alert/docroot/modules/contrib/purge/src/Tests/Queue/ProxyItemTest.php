<?php

namespace Drupal\purge\Tests\Queue;

use Drupal\purge\Tests\KernelTestBase;
use Drupal\purge\Plugin\Purge\Invalidation\InvalidationInterface;
use Drupal\purge\Plugin\Purge\Queue\Exception\InvalidPropertyException;
use Drupal\purge\Plugin\Purge\Queue\ProxyItem;
use Drupal\purge\Plugin\Purge\Queue\TxBuffer;

/**
 * Tests \Drupal\purge\Tests\Queue\ProxyItem.
 *
 * @group purge
 */
class ProxyItemTest extends KernelTestBase {

  /**
   * The TxBuffer object in which state and properties get stored.
   *
   * @var \Drupal\purge\Plugin\Purge\Queue\TxBuffer
   */
  protected $buffer;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->buffer = new TxBuffer();
  }

  /**
   * Tests \Drupal\purge\Plugin\Purge\Queue\ProxyItem::__get
   */
  public function testGet() {
    $i = $this->getInvalidations(1);
    $i->setStateContext('a');
    $i->setState(InvalidationInterface::PROCESSING);
    $i->setProperty('foo', 'bar');
    $i->setStateContext(NULL);
    $p = new ProxyItem($i, $this->buffer);
    $this->buffer->set($i, TxBuffer::CLAIMED);

    // Test the 'item_id' property and changing it directly on the buffer.
    $this->assertNull($p->item_id);
    $this->buffer->setProperty($i, 'item_id', '5');
    $this->assertEqual('5', $p->item_id);
    $this->buffer->delete($i);
    $this->assertNull($p->item_id);
    $this->buffer->set($i, TxBuffer::CLAIMED);
    $this->assertNull($p->item_id);

    // Test the 'data' array property and its peculiar format.
    $this->assertTrue(is_array($p->data));
    $this->assertEqual($i->getPluginId(), $p->data[ProxyItem::DATA_INDEX_TYPE]);
    $this->assertEqual($i->getType(), $p->data[ProxyItem::DATA_INDEX_TYPE]);
    $this->assertTrue(is_array($p->data[ProxyItem::DATA_INDEX_STATES]));
    $this->assertEqual(1, count($p->data[ProxyItem::DATA_INDEX_STATES]));
    $this->assertTrue(isset($p->data[ProxyItem::DATA_INDEX_STATES]['a']));
    $this->assertEqual(InvalidationInterface::PROCESSING, $p->data[ProxyItem::DATA_INDEX_STATES]['a']);
    $this->assertEqual($i->getExpression(), $p->data[ProxyItem::DATA_INDEX_EXPRESSION]);
    $this->assertTrue(isset($p->data[ProxyItem::DATA_INDEX_PROPERTIES]['a']['foo']));
    $this->assertEqual('bar', $p->data[ProxyItem::DATA_INDEX_PROPERTIES]['a']['foo']);

    // Test the 'created' property and changing it directly on the buffer.
    $this->assertNull($p->created);
    $this->buffer->setProperty($i, 'created', 123456789);
    $this->assertEqual(123456789, $p->created);

    // Test that bad properties throw a InvalidPropertyException as expected.
    foreach (['properties', 'buffer', 'test'] as $property) {
      $thrown = FALSE;
      try {
        $p->$property;
      }
      catch (InvalidPropertyException $e) {
        $thrown = TRUE;
      }
      $this->assertTrue($thrown);
    }
  }

  /**
   * Tests \Drupal\purge\Plugin\Purge\Queue\ProxyItem::__set
   */
  public function testSet() {
    $i = $this->getInvalidations(1);
    $p = new ProxyItem($i, $this->buffer);
    $this->buffer->set($i, TxBuffer::CLAIMED);

    // Test setting the 'item_id' and 'created' properties.
    $this->assertNull($this->buffer->getProperty($i, 'item_id'));
    $p->item_id = 5;
    $this->assertEqual(5, $this->buffer->getProperty($i, 'item_id'));
    $p->item_id = 'FOOBAR';
    $this->assertEqual('FOOBAR', $this->buffer->getProperty($i, 'item_id'));
    $this->assertNull($this->buffer->getProperty($i, 'created'));
    $p->created = FALSE;
    $this->assertFalse($this->buffer->getProperty($i, 'created'));
    $p->created = 0.7;
    $this->assertEqual(0.7, $this->buffer->getProperty($i, 'created'));

    // Test setting 'data' (RO) and other non-existing properties.
    foreach (['data', 'foo', 'bar'] as $property) {
      $thrown = FALSE;
      try {
        $p->$property = time();
      }
      catch (InvalidPropertyException $e) {
        $thrown = TRUE;
      }
      $this->assertTrue($thrown);
    }
  }

}
