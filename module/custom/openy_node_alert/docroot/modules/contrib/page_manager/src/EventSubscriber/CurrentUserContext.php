<?php

/**
 * @file
 * Contains \Drupal\page_manager\EventSubscriber\CurrentUserContext.
 */

namespace Drupal\page_manager\EventSubscriber;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\Context\ContextDefinition;
use Drupal\Core\Session\AccountInterface;
use Drupal\page_manager\Event\PageManagerContextEvent;
use Drupal\Core\Plugin\Context\Context;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\page_manager\Event\PageManagerEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Sets the current user as a context.
 */
class CurrentUserContext implements EventSubscriberInterface {

  use StringTranslationTrait;

  /**
   * The account proxy.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $account;

  /**
   * The user storage.
   *
   * @var \Drupal\user\UserStorageInterface
   */
  protected $userStorage;

  /**
   * Constructs a new CurrentUserContext.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The current account.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(AccountInterface $account, EntityTypeManagerInterface $entity_type_manager) {
    $this->account = $account;
    $this->userStorage = $entity_type_manager->getStorage('user');
  }

  /**
   * Adds in the current user as a context.
   *
   * @param \Drupal\page_manager\Event\PageManagerContextEvent $event
   *   The page entity context event.
   */
  public function onPageContext(PageManagerContextEvent $event) {
    $id = $this->account->id();
    $current_user = $this->userStorage->load($id);

    $context = new Context(new ContextDefinition('entity:user', $this->t('Current user')), $current_user);
    $cacheability = new CacheableMetadata();
    $cacheability->setCacheContexts(['user']);
    $context->addCacheableDependency($cacheability);
    $event->getPage()->addContext('current_user', $context);
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[PageManagerEvents::PAGE_CONTEXT][] = 'onPageContext';
    return $events;
  }

}
