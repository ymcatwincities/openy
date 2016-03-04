<?php

/**
 * @file
 * Contains \Drupal\Tests\purge_queuer_coretags\Unit\CacheTagsQueuerTest.
 */

namespace Drupal\Tests\purge\Unit;

use Drupal\purge_queuer_coretags\CacheTagsQueuer;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\purge_queuer_coretags\CacheTagsQueuer
 * @group purge_queuer_coretags
 */
class CacheTagsQueuerTest extends UnitTestCase {

  /**
   * The tested cache tags queuer.
   *
   * @var \Drupal\purge_queuer_coretags\CacheTagsQueuer
   */
  protected $cacheTagsQueuer;

  /**
   * The mocked queue.
   *
   * @var \PHPUnit_Framework_MockObject_MockObject|\Drupal\purge\Plugin\Purge\Queue\QueueServiceInterface
   */
  protected $queue;

  /**
   * The mocked invalidation object factory.
   *
   * @var \PHPUnit_Framework_MockObject_MockObject|\Drupal\purge\Plugin\Purge\Invalidation\InvalidationsServiceInterface
   */
  protected $invalidationFactory;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    $this->queue = $this->getMock('\Drupal\purge\Plugin\Purge\Queue\QueueServiceInterface');
    $this->invalidationFactory = $this->getMock('\Drupal\purge\Plugin\Purge\Invalidation\InvalidationsServiceInterface');
    // $this->cacheTagsQueuer = new CacheTagsQueuer($this->queue, $this->invalidationFactory);
  }

  /**
   * @covers ::invalidateTags
   *
   * @dataProvider providerTestInvalidateTags()
   */
  public function testInvalidateTags(array $tag_invalidations, $invalidation_instantiations, array $queue_additions) {
    // $tag_invalidation = $this->getMockBuilder('\Drupal\purge\Plugin\Purge\Invalidation\Tag')
    //   ->disableOriginalConstructor();
    // $this->invalidationFactory->expects($this->exactly($invalidation_instantiations))
    //   ->method('get')
    //   ->with('tag')
    //   ->willReturn($tag_invalidation);
    //
    // $this->queue->expects($this->exactly(count($tag_invalidations)))
    //   ->method('add');
    // for ($i = 0; $i < count($tag_invalidations); $i++) {
    //   $this->queue->expects($this->at($i))
    //     ->method('add')
    //     ->with($this->callback(function($invalidations) use ($queue_additions, $i) {
    //       // Ensure we have an array of invalidations of the right size.
    //       return is_array($invalidations) && count($invalidations) == $queue_additions[$i];
    //     }));
    // }
    //
    // // Perform the provided tag invalidations.
    // foreach ($tag_invalidations as $tags) {
    //   $this->cacheTagsQueuer->invalidateTags($tags);
    // }
  }

  /**
   * Provides test data for testInvalidateTags().
   */
  public function providerTestInvalidateTags() {
    // $tags = [
    //   'menu:main',
    //   'node:5',
    //   'extension:views',
    //   'extension',
    //   'node_list',
    // ];
    //
    // // Each of these test cases simulate the cache tag invalidations within one
    // // request.
    // return [
    //   // Many invalidations with one tag each.
    //   [
    //     [[$tags[0]], [$tags[1]], [$tags[2]], [$tags[3]], [$tags[4]]],
    //     5,
    //     [1, 1, 1, 1, 1],
    //   ],
    //   // One invalidation with many tags.
    //   [
    //     [[$tags[0], $tags[1], $tags[2], $tags[3], $tags[4]]],
    //     5,
    //     [5],
    //   ],
    //   // Two invalidations, with 2 and 3 tags respectively.
    //   [
    //     [[$tags[0], $tags[1]], [$tags[2], $tags[3], $tags[4]]],
    //     5,
    //     [2, 3],
    //   ]
    // ];
  }

}
