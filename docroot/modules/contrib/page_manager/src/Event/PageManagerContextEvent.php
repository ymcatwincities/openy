<?php

/**
 * @file
 * Contains \Drupal\page_manager\Event\PageManagerContextEvent.
 */

namespace Drupal\page_manager\Event;

use Drupal\page_manager\PageInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Wraps a page entity for event subscribers.
 *
 * @see \Drupal\page_manager\Event\PageManagerEvents::PAGE_CONTEXT
 */
class PageManagerContextEvent extends Event {

  /**
   * The page entity the context is gathered for.
   *
   * @var \Drupal\page_manager\PageInterface
   */
  protected $page;

  /**
   * Creates a new PageManagerContextEvent.
   *
   * @param \Drupal\page_manager\PageInterface $page
   *   The page entity.
   */
  public function __construct(PageInterface $page) {
    $this->page = $page;
  }

  /**
   * Returns the page entity for this event.
   *
   * @return \Drupal\page_manager\PageInterface
   *   The page entity.
   */
  public function getPage() {
    return $this->page;
  }

}
