<?php

/**
 * @file
 * Contains \Drupal\globalredirect\EventSubscriber\GlobalredirectSubscriber.
 */

namespace Drupal\globalredirect\EventSubscriber;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Path\AliasManager;
use Drupal\Core\Routing\MatchingRouteNotFoundException;
use Drupal\Core\Url;
use Drupal\globalredirect\RedirectChecker;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\Routing\RequestContext;

/**
 * KernelEvents::REQUEST subscriber for redirecting q=path/to/page requests.
 */
class GlobalredirectSubscriber implements EventSubscriberInterface {

  /**
   * @var \Drupal\Core\Config\Config
   */
  protected $config;

  /**
   * @var \Drupal\Core\Path\AliasManager
   */
  protected $aliasManager;

  /**
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * @var \Drupal\globalredirect\RedirectChecker
   */
  protected $redirectChecker;

  /**
   * @var \Symfony\Component\Routing\RequestContext
   */
  protected $context;

  /**
   * @var \Drupal\Core\Routing\UrlGenerator
   */
  protected $urlGenerator;

  /**
   * Constructs a \Drupal\redirect\EventSubscriber\RedirectRequestSubscriber object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config.
   * @param \Drupal\Core\Path\AliasManager $alias_manager
   *   The alias manager service.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler service.
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager service.
   * @param \Drupal\globalredirect\RedirectChecker $redirect_checker
   *   The redirect checker service.
   * @param \Symfony\Component\Routing\RequestContext
   *   Request context.
   */
  public function __construct(ConfigFactoryInterface $config_factory, AliasManager $alias_manager, LanguageManagerInterface $language_manager, ModuleHandlerInterface $module_handler, EntityManagerInterface $entity_manager, RedirectChecker $redirect_checker, RequestContext $context) {
    $this->config = $config_factory->get('globalredirect.settings');
    $this->aliasManager = $alias_manager;
    $this->languageManager = $language_manager;
    $this->moduleHandler = $module_handler;
    $this->entityManager = $entity_manager;
    $this->redirectChecker = $redirect_checker;
    $this->context = $context;
  }

  /**
   * Detects a q=path/to/page style request and performs a redirect.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
   *   The Event to process.
   */
  public function globalredirectCleanUrls(GetResponseEvent $event) {
    if (!$this->config->get('nonclean_to_clean') || $event->getRequestType() != HttpKernelInterface::MASTER_REQUEST) {
      return;
    }

    $request = $event->getRequest();
    $uri = $request->getUri();
    if (strpos($uri, 'index.php')) {
      $url = str_replace('/index.php', '', $uri);
      $event->setResponse(new RedirectResponse($url, 301));
    }
  }

  /**
   * Detects a url with an ending slash (/) and removes it.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
   */
  public function globalredirectDeslash(GetResponseEvent $event) {
    if (!$this->config->get('deslash') || $event->getRequestType() != HttpKernelInterface::MASTER_REQUEST) {
      return;
    }

    $path_info = ltrim($event->getRequest()->getPathInfo(), '/');
    if (substr($path_info, -1, 1) === '/') {
      $path_info = trim($path_info, '/');
      try {
        $path_info = $this->aliasManager->getPathByAlias($path_info);
        // Need to add the slash back.
        $this->setResponse($event, Url::fromUri('internal:/' . $path_info));
      } catch (MatchingRouteNotFoundException $e) {
        // Do nothing here as it is not our responsibility to handle this.
      }
    }
  }

  /**
   * Redirects any path that is set as front page to the site root.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
   */
  public function globalredirectFrontPage(GetResponseEvent $event) {
    if (!$this->config->get('frontpage_redirect') || $event->getRequestType() != HttpKernelInterface::MASTER_REQUEST) {
      return;
    }

    $request = $event->getRequest();
    $path = trim($request->getPathInfo(), '/');

    // Redirect only if the current path is not the root and this is the front
    // page.
    if ($this->isFrontPage($path)) {
      $this->setResponse($event, Url::fromRoute('<front>'));
    }
  }

  /**
   * Normalizes the path aliases.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
   */
  public function globalredirectNormalizeAliases(GetResponseEvent $event) {
    if ($event->getRequestType() != HttpKernelInterface::MASTER_REQUEST || !$this->config->get('normalize_aliases') || !$path = trim($event->getRequest()
        ->getPathInfo(), '/')
    ) {
      return;
    }

    $system_path = $this->aliasManager->getPathByAlias($path);
    $alias = $this->aliasManager->getAliasByPath($system_path, $this->languageManager->getCurrentLanguage()
      ->getId());
    // If the alias defined in the system is not the same as the one via which
    // the page has been accessed do a redirect to the one defined in the
    // system.
    if ($alias != $path) {
      if ($url = \Drupal::pathValidator()->getUrlIfValid($alias)) {
        $this->setResponse($event, $url);
      }
    }
  }

  /**
   * Redirects forum taxonomy terms to correct forum path.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
   */
  public function globalredirectForum(GetResponseEvent $event) {
    $request = $event->getRequest();
    if ($event->getRequestType() != HttpKernelInterface::MASTER_REQUEST || !$this->config->get('term_path_handler') || !$this->moduleHandler->moduleExists('forum') || !preg_match('/taxonomy\/term\/([0-9]+)$/', $request->getUri(), $matches)) {
      return;
    }

    $term = $this->entityManager->getStorage('taxonomy_term')
      ->load($matches[1]);
    if (!empty($term) && $term->url() != $request->getPathInfo()) {
      $this->setResponse($event, Url::fromUri('entity:taxonomy_term/' . $term->id()));
    }
  }

  /**
   * Prior to set the response it check if we can redirect.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
   *   The event object.
   * @param \Drupal\Core\Url $url
   *   The Url where we want to redirect.
   */
  protected function setResponse(GetResponseEvent $event, Url $url) {
    $request = $event->getRequest();
    $this->context->fromRequest($request);

    parse_str($request->getQueryString(), $query);
    $url->setOption('query', $query);
    $url->setAbsolute(TRUE);

    // We can only check access for routed URLs.
    if (!$url->isRouted() || $this->redirectChecker->canRedirect($url->getRouteName(), $request)) {
      // Add the 'rendered' cache tag, so that we can invalidate all responses
      // when settings are changed.
      $headers = [
        'X-Drupal-Cache-Tags' => 'rendered',
      ];
      $event->setResponse(new RedirectResponse($url->toString(), 301, $headers));
    }
  }


  /**
   * {@inheritdoc}
   */
  static function getSubscribedEvents() {
    // This needs to run before RouterListener::onKernelRequest(), which has
    // a priority of 32. Otherwise, that aborts the request if no matching
    // route is found.
    $events[KernelEvents::REQUEST][] = array('globalredirectCleanUrls', 33);
    $events[KernelEvents::REQUEST][] = array('globalredirectDeslash', 34);
    $events[KernelEvents::REQUEST][] = array('globalredirectFrontPage', 35);
    $events[KernelEvents::REQUEST][] = array(
      'globalredirectNormalizeAliases',
      36
    );
    $events[KernelEvents::REQUEST][] = array('globalredirectForum', 37);
    return $events;
  }

  /**
   * Determine if the given path is the site's front page.
   *
   * @param string $path
   *   The path to check.
   *
   * @return bool
   *   Returns TRUE if the path is the site's front page.
   */
  protected function isFrontPage($path) {
    // @todo PathMatcher::isFrontPage() doesn't work here for some reason.
    $front = \Drupal::config('system.site')->get('page.front');

    // This might be an alias.
    $alias_path = \Drupal::service('path.alias_manager')->getPathByAlias($path);

    return !empty($path)
    // Path matches front or alias to front.
    && (($path == $front) || ($alias_path == $front));
  }

}
