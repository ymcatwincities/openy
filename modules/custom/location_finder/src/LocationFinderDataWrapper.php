<?php

namespace Drupal\location_finder;

use Drupal\openy_socrates\OpenyDataServiceInterface;
use Drupal\openy_socrates\OpenySocratesFacade;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Render\RendererInterface;

/**
 * Class LocationFinderDataWrapper.
 *
 * Provides data for location finder.
 */
class LocationFinderDataWrapper implements OpenyDataServiceInterface {

  /**
   * Openy Socrates Facade.
   *
   * @var OpenySocratesFacade
   */
  protected $socrates;

  /**
   * Query factory.
   *
   * @var \Drupal\Core\Entity\Query\QueryFactory
   */
  protected $queryFactory;

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * DataWrapperBase constructor.
   *
   * @param \Drupal\Core\Entity\Query\QueryFactory $queryFactory
   *   Query factory.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   Renderer.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity type manager.
   * @param \Drupal\openy_socrates\OpenySocratesFacade $socrates
   *   Socrates.
   */
  public function __construct(QueryFactory $queryFactory, RendererInterface $renderer, EntityTypeManagerInterface $entityTypeManager, OpenySocratesFacade $socrates) {
    $this->queryFactory = $queryFactory;
    $this->renderer = $renderer;
    $this->entityTypeManager = $entityTypeManager;
    $this->socrates = $socrates;
  }

  /**
   * {@inheritdoc}
   */
  public function getLocationPins() {
    $branch_pins = $this->socrates->getBranchPins();
    $camp_pins = $this->socrates->getCampPins();
    $pins = array_merge($branch_pins, $camp_pins);
    return $pins;
  }

  /**
   * {@inheritdoc}
   */
  public function getCampPins() {
    $location_ids = $this->queryFactory->get('node')
      ->condition('type', 'camp')
      ->execute();

    if (!$location_ids) {
      return [];
    }

    $storage = $this->entityTypeManager->getStorage('node');
    $builder = $this->entityTypeManager->getViewBuilder('node');
    $locations = $storage->loadMultiple($location_ids);

    $pins = [];
    foreach ($locations as $location) {
      $view = $builder->view($location, 'membership_teaser');
      $coordinates = $location->get('field_location_coordinates')->getValue();
      $tags = [];
      $tags[] = t('Camps');
      $icon = file_create_url(drupal_get_path('module', 'location_finder') . '/img/map_icon_green.png');
      $pins[] = [
        'icon' => $icon,
        'tags' => $tags,
        'lat' => round($coordinates[0]['lat'], 5),
        'lng' => round($coordinates[0]['lng'], 5),
        'name' => $location->label(),
        'markup' => $this->renderer->renderRoot($view),
      ];
    }

    return $pins;
  }

  /**
   * {@inheritdoc}
   */
  public function addDataServices($services) {
    return [
      'getLocationPins',
      'getCampPins',
      // @todo consider to extend Socrates with service_name:method instead of just method or to make methods more longer in names.
    ];
  }

}
