<?php

namespace Drupal\ymca_mindbody\Controller;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\mindbody_cache_proxy\MindbodyCacheProxyInterface;
use Drupal\ymca_mindbody\Form\MindbodyPTForm;
use Drupal\ymca_mindbody\YmcaMindbodyRequestGuard;
use Drupal\ymca_mindbody\YmcaMindbodyTrainingsMapping;
use Symfony\Component\DependencyInjection\ContainerInterface;

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
  public function __construct(MindbodyCacheProxyInterface $cache_proxy, YmcaMindbodyTrainingsMapping $trainings_mapping, YmcaMindbodyRequestGuard $request_guard) {
    $this->proxy = $cache_proxy;
    $this->trainingsMapping = $trainings_mapping;
    $this->requestGuard = $request_guard;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('mindbody_cache_proxy.client'),
      $container->get('ymca_mindbody.trainings_mapping'),
      $container->get('ymca_mindbody.request_guard')
    );
  }

  /**
   * Set page content.
   */
  public function content() {
    $query = \Drupal::request()->query->all();
    $values = array(
      'location' => !empty($query['location']) && is_numeric($query['location']) ? $query['location'] : NULL,
      'program' => !empty($query['program']) && is_numeric($query['program']) ? $query['program'] : NULL,
      'session_type' => !empty($query['session_type']) && is_numeric($query['session_type']) ? $query['session_type'] : NULL,
      'trainer' => !empty($query['trainer']) ? $query['trainer'] : NULL,
      'start_time' => !empty($query['start_time']) ? $query['start_time'] : NULL,
      'end_time' => !empty($query['end_time']) ? $query['end_time'] : NULL,
      'start_date' => !empty($query['start_date']) ? $query['start_date'] : NULL,
      'end_date' => !empty($query['end_date']) ? $query['end_date'] : NULL,
    );

    $form = new MindbodyPTForm($this->proxy, $this->trainingsMapping, $this->requestGuard);
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
