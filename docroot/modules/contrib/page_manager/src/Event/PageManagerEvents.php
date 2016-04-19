<?php

/**
 * @file
 * Contains \Drupal\page_manager\Event\PageManagerEvents.
 */

namespace Drupal\page_manager\Event;

/**
 * Defines events for the Page Manager module.
 */
final class PageManagerEvents {

  /**
   * Name of the event when gathering context for a page.
   *
   * @see \Drupal\page_manager\Entity\Page::getContexts()
   * @see \Drupal\page_manager\Event\PageManagerContextEvent
   */
  const PAGE_CONTEXT = 'page_manager.page_context';

}
