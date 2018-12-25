<?php

namespace Drupal\Tests\purge_queuer_coretags\Unit;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Drupal\purge\Plugin\Purge\Queue\QueueServiceInterface;
use Drupal\purge\Plugin\Purge\Queuers\QueuersServiceInterface;
use Drupal\purge\Plugin\Purge\Queuer\QueuerBase;
use Drupal\purge\Plugin\Purge\Invalidation\InvalidationInterface;
use Drupal\purge\Plugin\Purge\Invalidation\InvalidationsServiceInterface;
use Drupal\purge_queuer_coretags\CacheTagsQueuer;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\purge_queuer_coretags\CacheTagsQueuer
 * @group purge_queuer_coretags
 */
class CacheTagsQueuerTest extends UnitTestCase {

  /**
   * Propagated blacklist.
   *
   * @var array[]
   */
  protected $blacklist = [
    'purge_queuer_coretags.settings' => [
      'blacklist' => [
        'menu',
        'node',
      ],
    ],
  ];

  /**
   * Blacklist without any items.
   *
   * @var array[]
   */
  protected $blacklistEmpty = [
    'purge_queuer_coretags.settings' => ['blacklist' => []],
  ];

  /**
   * The tested cache tags queuer.
   *
   * @var \Drupal\purge_queuer_coretags\CacheTagsQueuer
   */
  protected $cacheTagsQueuer;

  /**
   * The mocked config factory.
   *
   * @var \PHPUnit_Framework_MockObject_MockObject|\Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The container.
   *
   * @var \Symfony\Component\DependencyInjection\ContainerInterface
   */
  protected $container;

  /**
   * The mocked queue service.
   *
   * @var \PHPUnit_Framework_MockObject_MockObject|\Drupal\purge\Plugin\Purge\Queue\QueueServiceInterface
   */
  protected $purgeQueue;

  /**
   * The mocked queuers service.
   *
   * @var \PHPUnit_Framework_MockObject_MockObject|\Drupal\purge\Plugin\Purge\Queuers\QueuersServiceInterface
   */
  protected $purgeQueuers;

  /**
   * The mocked invalidations factory.
   *
   * @var \PHPUnit_Framework_MockObject_MockObject|\Drupal\purge\Plugin\Purge\Invalidation\InvalidationsServiceInterface
   */
  protected $purgeInvalidationFactory;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    $this->purgeQueue = $this->getMockBuilder(QueueServiceInterface::class)->setMethods([])->getMock();
    $this->purgeQueuers = $this->getMockBuilder(QueuersServiceInterface::class)->setMethods(['get'])->getMock();
    $this->purgeInvalidationFactory = $this->getMockForAbstractClass(InvalidationsServiceInterface::class);

    // Create a container with all dependent services in it.
    $this->container = new ContainerBuilder();
    $this->container->set('purge.queue', $this->purgeQueue);
    $this->container->set('purge.queuers', $this->purgeQueuers);
    $this->container->set('purge.invalidation.factory', $this->purgeInvalidationFactory);

    // Initialize the CacheTagsQueuer object and set the container.
    $this->cacheTagsQueuer = new CacheTagsQueuer();
    $this->cacheTagsQueuer->setContainer($this->container);
    $this->assertInstanceOf(ContainerAwareInterface::class, $this->cacheTagsQueuer);
    $this->assertInstanceOf(CacheTagsInvalidatorInterface::class, $this->cacheTagsQueuer);
  }

  /**
   * @covers ::initialize
   */
  public function testInitializeDoesntLoadWhenQueuerDisabled() {
    $this->purgeInvalidationFactory->expects($this->never())->method('get');
    $this->purgeQueue->expects($this->never())->method('add');
    $this->purgeQueuers->expects($this->once())
      ->method('get')
      ->with('coretags')
      ->willReturn(FALSE);
    $this->assertNull($this->cacheTagsQueuer->invalidateTags(["1", "2"]));
  }

  /**
   * @covers ::invalidateTags
   *
   * @dataProvider providerTestInvalidateTags()
   */
  public function testInvalidateTags($config, array $sets, array $adds, $queue_calls) {
    $this->container->set('config.factory', $this->getConfigFactoryStub($config));
    $this->purgeQueuers->expects($this->once())
      ->method('get')
      ->with('coretags')
      ->willReturn($this->getMockBuilder(QueuerBase::class)->disableOriginalConstructor()->getMock());
    $this->purgeInvalidationFactory->expects($this->exactly(array_sum($adds)))
      ->method('get')->with('tag')
      ->willReturn($this->getMock(InvalidationInterface::class));

    // Configure the QueueServiceInterface::add() expectation very accurately.
    $adds = array_filter($adds, function ($v) {return $v !== 0;});
    $this->purgeQueue->expects($this->exactly($queue_calls))
      ->method('add')
      ->with(
        $this->callback(function ($queuer) {
          return $queuer instanceof QueuerBase;
        }),
        $this->callback(function (array $invs) use (&$adds, $sets) {
          if (is_null($expected = each($adds)['value'])) {
            return TRUE;
          }
          return is_array($invs) && (count($invs) == $expected);
        })
      );

    // Perform the provided tag invalidations.
    foreach ($sets as $tags) {
      $this->cacheTagsQueuer->invalidateTags($tags);
    }
  }

  /**
   * Provides test data for testInvalidateTags().
   */
  public function providerTestInvalidateTags() {
    return [
      // Three calls to ::invalidateTags(), 'node:5' should get blacklisted.
      [
        $this->blacklist,
        [['block:1'], ['node:5'], ['extension:views', 'baz']], [1, 0, 2], 2],
      // One call to ::invalidateTags(), with 1 and 3 tags respectively.
      [
        $this->blacklist,
        [['menu:main'], ['NODE:5', 'foo', 'bar', 'bar']], [0, 3], 1],
      // One call to ::invalidateTags() with 4 tags.
      [
        $this->blacklistEmpty,
        [['node:5', 'foo:2', 'foo:3', 'bar:baz']], [4], 1],
      // Five calls to ::invalidateTags() with varying number of tags.
      [
        $this->blacklistEmpty,
        [['a', 'b'], ['c', 'd'], ['e', 'f'], ['g', 'h', 'i'], ['j']],
        [2,2,2,3,1], 5],
    ];
  }

}
