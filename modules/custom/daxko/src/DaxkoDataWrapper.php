<?php

namespace Drupal\daxko;

use Drupal\openy_socrates\OpenyDataServiceInterface;

/**
 * Class DaxkoDataWrapper.
 *
 * Provides example of membership matrix.
 */
class DaxkoDataWrapper extends DataWrapperBase implements OpenyDataServiceInterface {

  /**
   * The owner of mapping entities.
   */
  const MAPPING_OWNER_ID = 1;

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
   * Calculate membership type name.
   *
   * @param \stdClass $membership_type
   *   Membership type.
   *
   * @return string
   *   Membership type name.
   *
   * @throws \Exception
   */
  private function getMembershipTypeName(\stdClass $membership_type) {
    $map = [
      [
        'needle' => '*Program Member',
        'replace' => 'Program Member',
      ],
      [
        'needle' => '1 Adult (30-64)',
        'replace' => '1 Adult',
      ],
      [
        'needle' => '1 Adult with Kids',
        'replace' => '1 Adult with Kids',
      ],
      [
        'needle' => '2 Adults (30-64)',
        'replace' => '2 Adults',
      ],
      [
        'needle' => '2 Adults with Kids',
        'replace' => '2 Adults with Kids',
      ],
      [
        'needle' => 'College Student',
        'replace' => 'College Student',
      ],
      [
        'needle' => 'Complimentary',
        'replace' => 'Complimentary',
      ],
      [
        'needle' => 'Military Adult',
        'replace' => 'Military Adult',
      ],
      [
        'needle' => 'Military Family',
        'replace' => 'Military Family',
      ],
      [
        'needle' => 'Senior 1 Adult',
        'replace' => '1 Senior',
      ],
      [
        'needle' => 'Senior 2 Adults',
        'replace' => '2 Seniors',
      ],
      [
        'needle' => 'Student',
        'replace' => 'Student',
      ],
      [
        'needle' => 'Young Adult',
        'replace' => 'Young Adult',
      ],
    ];

    foreach ($map as $item) {
      if (strpos($membership_type->name, $item['needle']) !== FALSE) {
        return $item['replace'];
      }
    }

    throw new \Exception(sprintf('Failed to get membership type name for type "%s"', $membership_type->name));
  }

  /**
   * Remove all membership type mappings.
   */
  public function deleteMembershipTypeMappings() {
    $this->mappingRepo->deleteAllMappingsByType('membership_type');
    $this->loggerChannel->info('All membership type mappings have been deleted.');
  }

  /**
   * Populate membership_type mappings.
   */
  public function populateDaxkoMembershipTypes() {
    $membership_data = $this->getMembershipData();
    $storage = $this->entityTypeManager->getStorage('mapping');

    $memberships = [];
    foreach ($membership_data as $daxko_branch_id => $data) {

      $branch = $this->locationRepo->getBranchByDaxkoBranchId($daxko_branch_id);
      foreach ($data as $membership_type) {
        // Skip membership types if they are hidden online.
        if ($membership_type->showOnline === FALSE) {
          continue;
        }

        try {
          $name = $this->getMembershipTypeName($membership_type);
          $membership_type->humanName = $name;
          $memberships[$branch->id()][$name][$membership_type->id] = $membership_type;
        }
        catch (\Exception $e) {
          $this->loggerChannel->error('Found unsupported membership type name "%name".', ['%name' => $membership_type->name]);
        }
      }
    }

    foreach ($memberships as $branch_id => $membership_items) {
      foreach ($membership_items as $membership_item_name => $membership_item) {
        // Try to update existing entity.
        $existing = $this->membershipTypeRepo->getMappingByNameAndBranch($membership_item_name, $branch_id);
        if (!empty($existing)) {
          // Update only IDs.
          $ids = [];
          foreach ($membership_item as $variation) {
            $ids[] = $variation->id;
          }
          $existing->set('field_daxko_membership_ids', $ids);
          $existing->save();
          continue;
        }

        $values = [
          'type' => 'membership_type',
          'langcode' => 'en',
          'field_branch_ct_reference' => $branch_id,
        ];

        foreach ($membership_item as $variation) {
          $values['field_daxko_membership_ids'][] = $variation->id;
        }

        $entity = $storage->create($values);
        $entity->setName($membership_item_name);
        $entity->setOwnerId(self::MAPPING_OWNER_ID);
        $entity->save();
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getMembershipPriceMatrix() {
    $matrix = [
      [
        'id' => 'youth',
        'title' => 'Youth',
        'description' => 'Youth membership',
        'locations' => [
          [
            'title' => 'Location #1',
            'id' => 1,
            'price' => 10,
          ],
          [
            'title' => 'Location #2',
            'id' => 2,
            'price' => 20,
          ],
          [
            'title' => 'Location #3',
            'id' => 3,
            'price' => 30,
          ],
        ],
      ],
      [
        'id' => 'adult',
        'title' => 'Adult',
        'description' => 'Adult membership',
        'locations' => [
          [
            'title' => 'Location #1',
            'id' => 1,
            'price' => 100,
          ],
          [
            'title' => 'Location #2',
            'id' => 2,
            'price' => 200,
          ],
          [
            'title' => 'Location #3',
            'id' => 3,
            'price' => 300,
          ],
        ],
      ],
      [
        'id' => 'family',
        'title' => 'Family',
        'description' => 'Family membership',
        'locations' => [
          [
            'title' => 'Location #1',
            'id' => 1,
            'price' => 1000,
          ],
          [
            'title' => 'Location #2',
            'id' => 2,
            'price' => 2000,
          ],
          [
            'title' => 'Location #3',
            'id' => 3,
            'price' => 3000,
          ],
        ],
      ],
    ];

    return $matrix;
  }

  /**
   * {@inheritdoc}
   */
  public function getBranchPins() {
    $location_ids = $this->entityTypeManager->getStorage('node')
      ->getQuery()
      ->condition('type', 'branch')
      ->execute();

    if (!$location_ids) {
      return [];
    }

    $storage = $this->entityTypeManager->getStorage('node');
    $builder = $this->entityTypeManager->getViewBuilder('node');
    $locations = $storage->loadMultiple($location_ids);

    // Get labels and icons for every bundle from Open Y Map config.
    $typeIcons = $this->configFactory->get('openy_map.settings')->get('type_icons');
    $typeLabels = $this->configFactory->get('openy_map.settings')->get('type_labels');
    $tag = $typeLabels['branch'];
    $pins = [];
    foreach ($locations as $location) {
      $view = $builder->view($location, 'membership_teaser');
      $coordinates = $location->get('field_location_coordinates')->getValue();
      if (!$coordinates) {
        continue;
      }
      $uri = !empty($typeIcons[$location->bundle()]) ? $typeIcons[$location->bundle()] :
        '/' . drupal_get_path('module', 'openy_map') . "/img/map_icon_green.png";
      $pins[] = [
        'icon' => $uri,
        'tags' => [$tag],
        'lat' => round($coordinates[0]['lat'], 5),
        'lng' => round($coordinates[0]['lng'], 5),
        'name' => $location->label(),
        'markup' => $this->renderer->renderRoot($view),
      ];
    }

    return $pins;
  }

  /**
   * Get Summary.
   *
   * @param int $location_id
   *   Location ID.
   * @param string $membership_id
   *   Membership type ID.
   *
   * @return string
   *   Price.
   */
  public function getSummary($location_id, $membership_id) {
    $result['location'] = NULL;
    $result['membership'] = NULL;

    return $result;
  }

  /**
   * Get Redirect Link.
   *
   * @param int $location_id
   *   Location ID.
   * @param string $membership_id
   *   Membership type ID.
   *
   * @return \Drupal\Core\Url
   *   Redirect url.
   */
  public function getRedirectUrl($location_id, $membership_id) {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function addDataServices(array $services) {
    return [
      'getBranchPins',
      'getMembershipPriceMatrix',
      'getMembershipTypes',
      'getLocations',
      // @todo consider to extend Socrates with service_name:method instead of just method or to make methods more longer in names.
      'getSummary',
      'getRedirectUrl',
    ];
  }

}
