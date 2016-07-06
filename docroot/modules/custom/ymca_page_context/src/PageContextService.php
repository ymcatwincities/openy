<?php

namespace Drupal\ymca_page_context;

use Drupal\node\Entity\Node;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Controls in what context page header should be rendered.
 */
class PageContextService {
  private $context;

  /**
   * Query Factory.
   *
   * @var \Drupal\Core\Entity\Query\QueryFactory
   */
  protected $entityQuery;

  /**
   * Entity Type Manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The current route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $currentRouteMatch;

  /**
   * Request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * Constructs a new PageContextService.
   */
  public function __construct(QueryFactory $entityQuery, EntityTypeManagerInterface $entityTypeManager, RouteMatchInterface $currentRouteMatch, RequestStack $requestStack) {
    $this->entityQuery = $entityQuery;
    $this->entityTypeManager = $entityTypeManager;
    $this->currentRouteMatch = $currentRouteMatch;
    $this->request = $requestStack;
    $this->context = NULL;
  }

  /**
   * Overrides current context.
   *
   * @param Node $node
   *   Context node entity.
   */
  public function setContext(Node $node) {
    $this->context = $node;
  }

  /**
   * Returns current context.
   *
   * @return mixed
   *   An instance of \Drupal\node\Entity\Node or null.
   */
  public function getContext() {
    $query = $this->request->getCurrentRequest()->query->all();
    $node = $this->currentRouteMatch->getParameter('node');
    if ($this->currentRouteMatch->getRouteName() == 'entity.node.preview') {
      $node = $this->currentRouteMatch->getParameter('node_preview');
    }
    if (isset($node) && is_object($node)) {
      if (in_array($node->bundle(), ['camp', 'location'])) {
        return $node;
      }
      if ($node->hasField('field_related')) {
        if ($value = $node->field_related->getValue()) {
          if ($id = $value[0]['target_id']) {
            return $this->entityTypeManager->getStorage('node')->load($id);
          }
        }
      }
      if ($node->hasField('field_site_section')) {
        if ($value = $node->field_site_section->getValue()) {
          if ($id = $value[0]['target_id']) {
            return $this->entityTypeManager->getStorage('node')->load($id);
          }
        }
      }
    }
    if (isset($query['context']) && ($query['context'] == 'location' || $query['context'] == 'trainer') && isset($query['location']) && is_numeric($query['location'])) {
      $mapping_id = $this->entityQuery
        ->get('mapping')
        ->condition('type', 'location')
        ->condition('field_mindbody_id', $query['location'])
        ->execute();
      $mapping_id = reset($mapping_id);
      if ($mapping = $this->entityTypeManager->getStorage('mapping')->load($mapping_id)) {
        $field_location_ref = $mapping->field_location_ref->getValue();
        $location_id = isset($field_location_ref[0]['target_id']) ? $field_location_ref[0]['target_id'] : FALSE;
        if ($location_node = $this->entityTypeManager->getStorage('node')->load($location_id)) {
          return $location_node;
        }
      }
    }

    return $this->context;
  }

}
