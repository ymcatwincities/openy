<?php

namespace Drupal\purge_processor_lateruntime\EventSubscriber;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\FinishRequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Drupal\purge\Plugin\Purge\Purger\Exception\CapacityException;
use Drupal\purge\Plugin\Purge\Purger\Exception\DiagnosticsException;
use Drupal\purge\Plugin\Purge\Purger\Exception\LockException;

/**
 * Processes queue items at the end of every request.
 */
class LateRuntimeProcessor implements EventSubscriberInterface, ContainerAwareInterface {
  use ContainerAwareTrait;

  /**
   * The processor plugin or FALSE when disabled.
   *
   * @var false|\Drupal\purge_processor_lateruntime\Plugin\Purge\Processor\LateRuntimeProcessor
   */
  protected $processor;

  /**
   * The purge executive service, which wipes content from external caches.
   *
   * @var \Drupal\purge\Plugin\Purge\Purger\PurgersServiceInterface
   */
  protected $purgePurgers;

  /**
   * The queue in which to store, claim and release invalidation objects from.
   *
   * @var \Drupal\purge\Plugin\Purge\Queue\QueueServiceInterface
   */
  protected $purgeQueue;

  /**
   * {@inheritdoc}
   */
  static function getSubscribedEvents() {
    $events[KernelEvents::FINISH_REQUEST][] = 'onKernelFinishRequest';
    return $events;
  }

  /**
   * Initialize the services.
   *
   * @return bool
   *   TRUE when everything is available, FALSE when our plugin is disabled.
   */
  protected function initialize() {
    if (is_null($this->processor)) {
      // If the lateruntime processor plugin doesn't load, this object is not
      // allowed to operate and thus loads the least possible dependencies.
      $this->processor = $this->container->get('purge.processors')->get('lateruntime');
      if ($this->processor !== FALSE) {
        $this->purgePurgers = $this->container->get('purge.purgers');
        $this->purgeQueue = $this->container->get('purge.queue');
      }
    }
    return $this->processor !== FALSE;
  }

  /**
   * Invoked by the FINISH_REQUEST kernel event.
   *
   * @param \Symfony\Component\HttpKernel\Event\FinishRequestEvent $event
   *   The event object.
   *
   * @return void
   */
  public function onKernelFinishRequest(FinishRequestEvent $event) {

    // Immediately stop if our plugin is disabled.
    if (!$this->initialize()) {
      return;
    }

    // Claim a chunk of invalidations, process and let the queue handle results.
    $claims = $this->purgeQueue->claim();
    try {
      $this->purgePurgers->invalidate($this->processor, $claims);
    }
    catch (DiagnosticsException $e) {
      // Diagnostic exceptions happen when the system cannot purge.
    }
    catch (CapacityException $e) {
      // Capacity exceptions happen when too much was purged this request.
    }
    catch (LockException $e) {
      // Lock exceptions happen when another code path is currently processing.
    }
    finally {
      $this->purgeQueue->handleResults($claims);
    }
  }

}
