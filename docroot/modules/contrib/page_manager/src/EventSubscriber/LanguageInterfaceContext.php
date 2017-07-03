<?php

namespace Drupal\page_manager\EventSubscriber;

use Drupal\Core\Plugin\Context\ContextRepositoryInterface;
use Drupal\page_manager\Event\PageManagerContextEvent;
use Drupal\page_manager\Event\PageManagerEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Sets the current language_interface as a context.
 */
class LanguageInterfaceContext implements EventSubscriberInterface {

  /**
   * The context repository service.
   *
   * @var \Drupal\Core\Plugin\Context\ContextRepositoryInterface
   */
  protected $contextRepository;

  /**
   * Creates LanguageInterfaceContext object.
   *
   * @param \Drupal\Core\Plugin\Context\ContextRepositoryInterface $context_repository
   *   The context repository service.
   */
  public function __construct(ContextRepositoryInterface $context_repository) {
    $this->contextRepository = $context_repository;
  }

  /**
   * Add the language_interface context onPageContext event.
   *
   * @param \Drupal\page_manager\Event\PageManagerContextEvent $event
   *   The page entity context event.
   */
  public function onPageContext(PageManagerContextEvent $event) {
    $contexts = $this->contextRepository->getRuntimeContexts(array('@language.current_language_context:language_interface'));
    $context = reset($contexts);
    $event->getPage()->addContext('language_interface', $context);
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[PageManagerEvents::PAGE_CONTEXT][] = 'onPageContext';

    return $events;
  }

}
