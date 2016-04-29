<?php

/**
 * @file
 * Contains \Drupal\redirect\EventSubscriber\RedirectRequestSubscriber.
 */

namespace Drupal\redirect\EventSubscriber;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Logger\RfcLogLevel;
use Drupal\Core\Path\AliasManager;
use Drupal\Core\PathProcessor\InboundPathProcessorInterface;
use Drupal\Core\Routing\MatchingRouteNotFoundException;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\Core\Url;
use Drupal\redirect\Exception\RedirectLoopException;
use Drupal\redirect\RedirectChecker;
use Drupal\redirect\RedirectRepository;
use Psr\Log\InvalidArgumentException;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Routing\RequestContext;

/**
 * Redirect subscriber for controller requests.
 */
class RedirectRequestSubscriber implements EventSubscriberInterface {

  /** @var  \Drupal\redirect\RedirectRepository */
  protected $redirectRepository;

  /**
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * @var \Drupal\Core\Config\Config
   */
  protected $config;

  /**
   * @var \Drupal\Core\Path\AliasManager
   */
  protected $aliasManager;

  /**
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * @var \Drupal\redirect\RedirectChecker
   */
  protected $checker;

  /**
   * @var \Symfony\Component\Routing\RequestContext
   */
  protected $context;

  /**
   * A path processor manager for resolving the system path.
   *
   * @var \Drupal\Core\PathProcessor\InboundPathProcessorInterface
   */
  protected $pathProcessor;

  /**
   * Constructs a \Drupal\redirect\EventSubscriber\RedirectRequestSubscriber object.
   *
   * @param \Drupal\redirect\RedirectRepository $redirect_repository
   *   The redirect entity repository.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config
   *   The config.
   * @param \Drupal\Core\Path\AliasManager $alias_manager
   *   The alias manager service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler service.
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager service.
   * @param \Drupal\redirect\RedirectChecker $checker
   *   The redirect checker service.
   * @param \Symfony\Component\Routing\RequestContext
   *   Request context.
   */
  public function __construct(RedirectRepository $redirect_repository, LanguageManagerInterface $language_manager, ConfigFactoryInterface $config, AliasManager $alias_manager, ModuleHandlerInterface $module_handler, EntityManagerInterface $entity_manager, RedirectChecker $checker, RequestContext $context, InboundPathProcessorInterface $path_processor) {
    $this->redirectRepository = $redirect_repository;
    $this->languageManager = $language_manager;
    $this->config = $config->get('redirect.settings');
    $this->aliasManager = $alias_manager;
    $this->moduleHandler = $module_handler;
    $this->entityManager = $entity_manager;
    $this->checker = $checker;
    $this->context = $context;
    $this->pathProcessor = $path_processor;
  }

  /**
   * Handles the redirect if any found.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
   *   The event to process.
   */
  public function onKernelRequestCheckRedirect(GetResponseEvent $event) {
    // Get a clone of the request. During inbound processing the request
    // can be altered. Allowing this here can lead to unexpected behavior.
    // For example the path_processor.files inbound processor provided by
    // the system module alters both the path and the request; only the
    // changes to the request will be propagated, while the change to the
    // path will be lost.
    $request = clone $event->getRequest();

    if (!$this->checker->canRedirect($request)) {
      return;
    }

    // Get URL info and process it to be used for hash generation.
    parse_str($request->getQueryString(), $request_query);

    // Do the inbound processing so that for example language prefixes are
    // removed.
    $path = $this->pathProcessor->processInbound($request->getPathInfo(), $request);
    $path = ltrim($path, '/');

    $this->context->fromRequest($request);

    try {
      $redirect = $this->redirectRepository->findMatchingRedirect($path, $request_query, $this->languageManager->getCurrentLanguage()->getId());
    }
    catch (RedirectLoopException $e) {
      \Drupal::logger('redirect')->warning($e->getMessage());
      $response = new Response();
      $response->setStatusCode(503);
      $response->setContent('Service unavailable');
      $event->setResponse($response);
      return;
    }

    if (!empty($redirect)) {

      // Handle internal path.
      $url = $redirect->getRedirectUrl();
      if ($this->config->get('passthrough_querystring')) {
        $url->setOption('query', (array) $url->getOption('query') + $request_query);
      }
      $headers = [
        'X-Redirect-ID' => $redirect->id(),
      ];
      $response = new TrustedRedirectResponse($url->setAbsolute()->toString(), $redirect->getStatusCode(), $headers);
      $response->addCacheableDependency($redirect);
      $event->setResponse($response);
    }
  }

  /**
   * Detects a q=path/to/page style request and performs a redirect.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
   *   The Event to process.
   */
  public function redirectCleanUrls(GetResponseEvent $event) {
    if (!$this->config->get('nonclean_to_clean') || $event->getRequestType() != HttpKernelInterface::MASTER_REQUEST) {
      return;
    }

    $request = $event->getRequest();
    $uri = $request->getUri();
    if (strpos($uri, 'index.php')) {
      $url = str_replace('/index.php', '', $uri);
      $response = new TrustedRedirectResponse($url, 301);
      $response->addCacheableDependency(CacheableMetadata::createFromRenderArray([])->addCacheTags(['rendered']));
      $event->setResponse($response);
    }
  }

  /**
   * Detects a url with an ending slash (/) and removes it.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
   */
  public function redirectDeslash(GetResponseEvent $event) {
    if (!$this->config->get('deslash') || $event->getRequestType() != HttpKernelInterface::MASTER_REQUEST) {
      return;
    }

    $path_info = $event->getRequest()->getPathInfo();
    if (($path_info !== '/') && (substr($path_info, -1, 1) === '/')) {
      $path_info = rtrim($path_info, '/');
      try {
        $path_info = $this->aliasManager->getPathByAlias($path_info);
        $this->setResponse($event, Url::fromUri('internal:' . $path_info));
      } catch (\Exception $e) {
        watchdog_exception('redirect', $e, $e->getMessage(), [], RfcLogLevel::WARNING);
      }
    }
  }

  /**
   * Redirects any path that is set as front page to the site root.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
   */
  public function redirectFrontPage(GetResponseEvent $event) {
    if (!$this->config->get('frontpage_redirect') || $event->getRequestType() != HttpKernelInterface::MASTER_REQUEST) {
      return;
    }

    $request = $event->getRequest();
    $path = $request->getPathInfo();

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
  public function redirectNormalizeAliases(GetResponseEvent $event) {
    if ($event->getRequestType() != HttpKernelInterface::MASTER_REQUEST || !$this->config->get('normalize_aliases') || !$path = $event->getRequest()->getPathInfo()) {
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
  public function redirectForum(GetResponseEvent $event) {
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
    if (!$url->isRouted() || $this->checker->canRedirect($request, $url->getRouteName())) {
      // Add the 'rendered' cache tag, so that we can invalidate all responses
      // when settings are changed.
      $response = new TrustedRedirectResponse($url->toString(), 301);
      $response->addCacheableDependency(CacheableMetadata::createFromRenderArray([])->addCacheTags(['rendered']));
      $event->setResponse($response);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    // This needs to run before RouterListener::onKernelRequest(), which has
    // a priority of 32. Otherwise, that aborts the request if no matching
    // route is found.
    $events[KernelEvents::REQUEST][] = array('onKernelRequestCheckRedirect', 33);
    $events[KernelEvents::REQUEST][] = array('redirectCleanUrls', 34);
    $events[KernelEvents::REQUEST][] = array('redirectDeslash', 35);
    $events[KernelEvents::REQUEST][] = array('redirectFrontPage', 36);
    $events[KernelEvents::REQUEST][] = array(
      'redirectNormalizeAliases',
      37,
    );
    $events[KernelEvents::REQUEST][] = array('redirectForum', 38);
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

    // Since deslash runs after the front page redirect, check and deslash here
    // if enabled.
    if ($this->config->get('deslash')) {
      $path = rtrim($path, '/');
    }

    // This might be an alias.
    $alias_path = \Drupal::service('path.alias_manager')->getPathByAlias($path);

    return !empty($path)
    // Path matches front or alias to front.
    && (($path == $front) || ($alias_path == $front));
  }

}
