<?php

namespace Drupal\ymca_mindbody\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\mindbody\MindbodyException;
use Drupal\ymca_mindbody\YmcaMindbodyResultsSearcherInterface;
use Drupal\ymca_mindbody\YmcaMindbodyRequestGuard;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Controller for "Mindbody results" page.
 */
class MindbodyResultsController extends ControllerBase {

  /**
   * The results searcher.
   *
   * @var YmcaMindbodyResultsSearcherInterface
   */
  protected $resultsSearcher;

  /**
   * Logger.
   *
   * @var LoggerChannelInterface
   */
  protected $logger;

  /**
   * The request stack.
   *
   * @var RequestStack
   */
  protected $requestStack;

  /**
   * MindbodyResultsController constructor.
   *
   * @param YmcaMindbodyResultsSearcherInterface $results_searcher
   *   Results searcher.
   * @param YmcaMindbodyRequestGuard $request_guard
   *   Request guard.
   * @param LoggerChannelFactoryInterface $logger_factory
   *   Logger factory.
   * @param RequestStack $request_stack
   *   Request stack.
   */
  public function __construct(
    YmcaMindbodyResultsSearcherInterface $results_searcher,
    YmcaMindbodyRequestGuard $request_guard,
    LoggerChannelFactoryInterface $logger_factory,
    RequestStack $request_stack
  ) {
    $this->requestGuard = $request_guard;
    $this->resultsSearcher = $results_searcher;
    $this->logger = $logger_factory->get('ymca_mindbody');
    $this->requestStack = $request_stack;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('ymca_mindbody.results_searcher'),
      $container->get('ymca_mindbody.request_guard'),
      $container->get('logger.factory'),
      $container->get('request_stack')
    );
  }

  /**
   * Set page content.
   */
  public function content() {
    $query = $this->requestStack->getCurrentRequest()->query->all();
    $values = array(
      'location' => !empty($query['location']) && is_numeric($query['location']) ? $query['location'] : NULL,
      'program' => !empty($query['program']) && is_numeric($query['program']) ? $query['program'] : NULL,
      'session_type' => !empty($query['session_type']) && is_numeric($query['session_type']) ? $query['session_type'] : NULL,
      'trainer' => !empty($query['trainer']) ? $query['trainer'] : NULL,
      'start_time' => !empty($query['start_time']) ? $query['start_time'] : NULL,
      'end_time' => !empty($query['end_time']) ? $query['end_time'] : NULL,
      'date_range' => !empty($query['date_range']) ? $query['date_range'] : NULL,
    );
    if (isset($query['context'])) {
      $values['context'] = $query['context'];
    }

    $node = $this->requestStack->getCurrentRequest()->get('node');
    try {
      $search_results = $this->resultsSearcher->getSearchResults($values, $node);
    }
    catch (MindbodyException $e) {
      $this->logger->error('Failed to get the results: %msg', ['%msg' => $e->getMessage()]);

      return [
        '#prefix' => '<div class="row mindbody-search-results-content">
          <div class="container">
            <div class="day col-sm-12">',
        'markup' => $this->resultsSearcher->getDisabledMarkup(),
        '#suffix' => '</div></div></div>',
      ];
    }

    return [
      'search_results' => $search_results,
      '#cache' => [
        'max-age' => 0,
      ],
    ];
  }

  /**
   * Set Title.
   */
  public function setTitle() {
    return t('Personal Trainer Schedules');
  }

}
