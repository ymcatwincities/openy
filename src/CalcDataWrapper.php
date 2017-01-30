<?php

namespace Drupal\openy_calc;

use Drupal\openy_socrates\OpenyDataServiceInterface;

/**
 * Class CalcDataWrapper.
 *
 * Provides example of membership matrix.
 */
class CalcDataWrapper extends DataWrapperBase implements OpenyDataServiceInterface {

  /**
   * Get membership data from Daxko.
   *
   * This method creates a cache bin with the data.
   * Please, use this method with cron to populate the data.
   *
   * @return array
   *   Data.
   */
  public function getMembershipData() {
    // @todo fix cache life time.
    $cid = __METHOD__;
    if ($cache = $this->cacheBackend->get($cid)) {
      $data = $cache->data;
    }
    else {
      $data = [];

      $daxko_branch_ids = $this->locationRepo->getAllDaxkoBranchIds();
      foreach ($daxko_branch_ids as $branch_id) {
        $data[$branch_id] = $this->daxkoClient->getMembershipTypes(['branch_id' => $branch_id]);
      }

      $this->cacheBackend->set($cid, $data);
    }

    return $data;
  }

  /**
   * {@inheritdoc}
   */
  public function getMembershipPriceMatrix() {
    $matrix = [];
    $data = $this->getMembershipData();

    // Get generic data for membership types.
    foreach ($data as $branch_id => $branch) {
      $node = $this->locationRepo->getBranchByDaxkoBranchId($branch_id);

      foreach ($branch as $membership_type) {
        $matrix[$membership_type->name]['id'] = $membership_type->name;
        $matrix[$membership_type->name]['title'] = $membership_type->name;
        $matrix[$membership_type->name]['description'] = '';

        // Get "JOIN" fee.
        $price = NULL;
        foreach ($membership_type->fees as $fee) {
          if ($fee->type == 'JOIN') {
            $price = $fee->amount;
          }
        }

        $matrix[$membership_type->name]['locations'][$branch_id] = [
          'title' => $node->label(),
          'id' => $node->id(),
          'price' => $price,
        ];
      }
    }

    return $matrix;
  }

  /**
   * {@inheritdoc}
   */
  public function getBranchPins() {
    $location_ids = $this->queryFactory->get('node')
      ->condition('type', 'branch')
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
      switch ($location->getType()) {
        case 'branch':
          $tags[] = t('YMCA');
          $icon = file_create_url(drupal_get_path('module', 'location_finder') . '/img/map_icon_blue.png');
          break;

        case 'camp':
          $tags[] = t('Camps');
          $icon = file_create_url(drupal_get_path('module', 'location_finder') . '/img/map_icon_green.png');
          break;
      }
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
      'getBranchPins',
      'getMembershipPriceMatrix',
      'getMembershipTypes',
      'getLocations',
      // @todo consider to extend Socrates with service_name:method instead of just method or to make methods more longer in names.
      'getPrice',
    ];
  }

}
