<?php

namespace Drupal\ymca_mindbody\Controller;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\mindbody_cache_proxy\MindbodyCacheProxyInterface;
use Drupal\ymca_mindbody\Form\MindbodyPTForm;
use Drupal\ymca_mindbody\YmcaMindbodyRequestGuard;
use Drupal\ymca_mindbody\YmcaMindbodyTrainingsMapping;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\Query\QueryFactory;

/**
 * Controller for "Mindbody results" page.
 */
class MindbodyResultsController implements ContainerInjectionInterface {

  /**
   * Mindbody Proxy.
   *
   * @var MindbodyCacheProxyInterface
   */
  protected $proxy;

  /**
   * MindbodyResultsController constructor.
   *
   * @param MindbodyCacheProxyInterface $cache_proxy
   *   Mindbody cache proxy.
   */
  public function __construct(MindbodyCacheProxyInterface $cache_proxy, YmcaMindbodyTrainingsMapping $trainings_mapping, YmcaMindbodyRequestGuard $request_guard, QueryFactory $entityQuery) {
    $this->proxy = $cache_proxy;
    $this->trainingsMapping = $trainings_mapping;
    $this->requestGuard = $request_guard;
    $this->entityQuery = $entityQuery;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('mindbody_cache_proxy.client'),
      $container->get('ymca_mindbody.trainings_mapping'),
      $container->get('ymca_mindbody.request_guard'),
      $container->get('entity.query')
    );
  }

  /**
   * Set page content.
   */
  public function content() {
    $query = \Drupal::request()->query->all();
    $values = array(
      'location' => is_numeric($query['location']) ? $query['location'] : '',
      'program' => is_numeric($query['program']) ? $query['program'] : '',
      'session_type' => is_numeric($query['session_type']) ? $query['session_type'] : '',
      'trainer' => isset($query['trainer']) ? $query['trainer'] : '',
      'start_time' => isset($query['start_time']) ? $query['start_time'] : '',
      'end_time' => isset($query['end_time']) ? $query['end_time'] : '',
      'start_date' => isset($query['start_date']) ? $query['start_date'] : '',
      'end_date' => isset($query['end_date']) ? $query['end_date'] : '',
    );

    $form = new MindbodyPTForm($this->proxy, $this->trainingsMapping, $this->requestGuard, $this->entityQuery);
    $search_results = $form->getSearchResults($values);

    return [
      '#markup' => render($search_results),
      '#cache' => [
        'max-age' => 0,
      ],
    ];
  }

  /**
   * Set Title.
   */
  public function setTitle() {
    return t('Personal Training Schedules');
  }

}
