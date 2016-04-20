<?php

/**
 * @file
 * Contains \Drupal\entity_browser\RouteSubscriber.
 */

namespace Drupal\entity_browser;

use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\Query\QueryFactory;
use Symfony\Component\Routing\RouteCollection;

/**
 * Generates routes for entity browsers.
 */
class RouteSubscriber {

  /**
   * The entity browser storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $browserStorage;

  /**
   * Display plugin manager.
   *
   * @var \Drupal\entity_browser\DisplayManager
   */
  protected $displayManager;

  /**
   * Entity browser query.
   *
   * @var \Drupal\Core\Entity\Query\QueryInterface
   */
  protected $browserQuery;

  /**
   * Constructs a \Drupal\views\EventSubscriber\RouteSubscriber instance.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   */
  public function __construct(EntityManagerInterface $entity_manager, DisplayManager $display_manager, QueryFactory $entity_query) {
    $this->browserStorage = $entity_manager->getStorage('entity_browser');
    $this->displayManager = $display_manager;
    $this->browserQuery = $entity_query->get('entity_browser');
  }

  /**
   * Returns a set of route objects.
   *
   * @return \Symfony\Component\Routing\RouteCollection
   *   A route collection.
   */
  public function routes() {
    $collection = new RouteCollection();
    //return $collection;
    foreach ($this->getBrowserIDsWithRoute() as $id) {
      /** @var $browser \Drupal\entity_browser\EntityBrowserInterface */
      $browser = $this->browserStorage->load($id);
      if ($route = $browser->route()) {
        $collection->add('entity_browser.' . $browser->id(), $route);
      }
    }

    return $collection;
  }

  /**
   * Gets entity browser IDs that use routes.
   *
   * @return array
   *   Array of browser IDs.
   */
  protected function getBrowserIDsWithRoute() {
    // Get all display plugins which provides the type.
    $display_plugins = $this->displayManager->getDefinitions();
    $ids = array();
    foreach ($display_plugins as $id => $definition) {
      if (!empty($definition['uses_route'])) {
        $ids[$id] = $id;
      }
    }

    return $this->browserQuery
      ->condition('status', TRUE)
      ->condition("display", $ids, 'IN')
      ->execute();
  }

}
