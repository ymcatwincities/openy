<?php

namespace Drupal\openy_campaign;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\node\Entity\Node;
use Drupal\Component\Utility\Unicode;

/**
 * Class CampaignExtendedRegistrationService.
 *
 * @package Drupal\openy_campaign
 */
class CampaignExtendedRegistrationService {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The node storage.
   *
   * @var \Drupal\node\NodeStorageInterface
   */
  protected $nodeStorage;

  /**
   * The taxonomy storage.
   *
   * @var \Drupal\taxonomy\TermStorageInterface
   */
  protected $taxonomyStorage;

  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
    $this->nodeStorage = $entity_type_manager->getStorage('node');
    $this->taxonomyStorage = $entity_type_manager->getStorage('taxonomy_term');
  }

  /**
   * Helper method to get "Where are you from" field options.
   *
   * @return array List of options.
   */
  public function getWhereAreYouFromOptions() {
    $options = [];
    $tree = $this->taxonomyStorage->loadTree(CAMPAIGN_WHERE_ARE_YOU_FROM_VID, 0, 1);
    foreach ($tree as $term) {
      $options[$term->tid] = $term->name;
    }
    return $options;
  }

  /**
   * Helper method to get "Please specify" options based on selection of "Where are you from".
   *
   * @param mixed $where_are_you_from Where are you from option.
   * @return array List of options.
   */
  public function getWhereAreYouFromSpecifyOptions($where_are_you_from) {
    $options = [];
    if (empty($where_are_you_from)) {
      return $options;
    }
    // Try load tree first.
    $tree = $this->taxonomyStorage->loadTree(CAMPAIGN_WHERE_ARE_YOU_FROM_VID, $where_are_you_from, 1);
    if (!empty($tree)) {
      foreach ($tree as $term) {
        $options["term_{$term->tid}"] = $term->name;
      }
    }
    else {
      // Load tagged Branches / Facilities.
      $nodes = $this->nodeStorage->loadByProperties([
        'field_where_are_you_from_group' => $where_are_you_from,
        'status' => Node::PUBLISHED,
      ]);
      foreach ($nodes as $node) {
        $options["node_{$node->id()}"] = Unicode::truncate($node->getTitle(), 35, TRUE);
      }
    }
    asort($options);
    return $options;
  }

}
