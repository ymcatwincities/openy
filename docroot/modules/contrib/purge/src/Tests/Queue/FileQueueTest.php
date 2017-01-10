<?php

namespace Drupal\purge\Tests\Queue;

use Drupal\Core\DestructableInterface;
use Drupal\Core\StreamWrapper\PublicStream;
use Drupal\purge\Tests\Queue\PluginTestBase;

/**
 * Tests \Drupal\purge\Plugin\Purge\Queue\FileQueue.
 *
 * @group purge
 * @see \Drupal\purge\Plugin\Purge\Queue\QueueInterface
 */
class FileQueueTest extends PluginTestBase {
  protected $plugin_id = 'file';

  /**
   * The file under public:// to which the queue buffer gets written to.
   *
   * @see \Drupal\purge\Plugin\Purge\Queue\File::$file
   */
  protected $file = 'purge-file.queue';

  /**
   * Set up the test.
   */
  public function setUp() {
    parent::setUp();
    $this->file = DRUPAL_ROOT . '/' . PublicStream::basePath() . '/' . $this->file;
  }

  /**
   * Tests if the buffer gets written to disk properly.
   *
   * @see \Drupal\purge\Plugin\Purge\Queue\File::bufferInitialize
   * @see \Drupal\purge\Plugin\Purge\Queue\File::bufferCommit
   * @see \Drupal\purge\Plugin\Purge\Queue\File::deleteQueue
   * @see \Drupal\purge\Plugin\Purge\Queue\File::destruct
   */
  public function testBufferReadingAndWriting() {
    $this->assertTrue($this->queue instanceof DestructableInterface);
    $this->assertFalse(file_exists($this->file));

    // Two assertions within this test, check the raw payload written to
    // disk by the file queue. However, because of its dependence on time(),
    // this test is exposed to the hosts performance. This anonymous function
    // creates a range of payloads to make this test more resilient.
    $payloads = function ($base, $time) {
      return [
        $base . $time-2 . "\n",
        $base . $time-1 . "\n",
        $base . $time   . "\n",
        $base . $time+1 . "\n",
        $base . $time+2 . "\n",
      ];
    };

    // Create one item without claiming it, and test the written output.
    $this->queue->createItem('s1');
    $this->assertFalse(file_exists($this->file));
    $this->queue->destruct();
    $this->assertTrue(file_exists($this->file));
    $this->assertTrue(in_array(file_get_contents($this->file), $payloads('1|s:2:"s1";|0|', time())));

    // Delete the queue and assure the file is gone.
    $this->queue->deleteQueue();
    $this->assertFalse(file_exists($this->file));

    // Create one item and claim it, test the output written to disk.
    $this->queue->createItem('s2');
    $i = $this->queue->claimItem();
    $this->queue->destruct();
    $this->assertTrue(file_exists($this->file));
    $this->assertTrue(in_array(file_get_contents($this->file), $payloads('1|s:2:"s2";|' . $i->expire . '|', $i->created)));

    // Delete the queue file, write our own file to disk and reload the queue.
    $this->queue->deleteQueue();
    $this->queue = NULL;
    file_put_contents($this->file, '1|s:6:"qwerty";|0|12345' . "\n");
    $this->assertTrue(file_exists($this->file));
    $this->setUpQueuePlugin();
    $claim = $this->queue->claimItem(1);
    $this->assertTrue(is_object($claim));
    $this->assertEqual(1, $claim->item_id);
    $this->assertEqual('qwerty', $claim->data);
    $this->assertEqual(12345, $claim->created);

    $this->queue->deleteQueue();
  }

}
