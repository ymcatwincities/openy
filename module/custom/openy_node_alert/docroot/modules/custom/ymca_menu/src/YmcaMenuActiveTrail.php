<?php

namespace Drupal\ymca_menu;

use Drupal\Core\Menu\MenuActiveTrail;
use Drupal\Core\Menu\MenuTreeStorage;
use Drupal\node\NodeInterface;
use Drupal\Core\Routing\RouteMatch;
use Drupal\Core\ParamConverter\ParamNotConvertedException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Exception\MethodNotAllowedException;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;

define('TERM_NEWS_TID', 6);

/**
 * Extend the MenuActiveTrail class.
 */
class YmcaMenuActiveTrail extends MenuActiveTrail {

  /**
   * Load link by properties.
   *
   * @param array $properties
   *   Associative array of properties.
   *
   * @return mixed
   *   Link plugin instance.
   */
  protected function loadLinkByProperties(array $properties) {
    // @todo Inject services via constructor.
    $link_manager = \Drupal::service('plugin.manager.menu.link');
    $connection = \Drupal::service('database');
    $cache_backend_interface = \Drupal::service('cache.menu');
    $cache_tags_invalidator = \Drupal::service('cache_tags.invalidator');

    $storage = new MenuTreeStorage($connection, $cache_backend_interface, $cache_tags_invalidator, 'menu_tree');
    $links = $storage->loadByProperties($properties);
    if (empty($links)) {
      return NULL;
    }

    $keys = array_keys($links);
    $plugin_id = $keys[0];
    $instance = $link_manager->createInstance($plugin_id);

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getActiveLink($menu_name = NULL) {
    // Call the parent method to implement the default behavior.
    $found = parent::getActiveLink($menu_name);
    // Lookup for a Active Link of preview node.
    $route_name = $this->routeMatch->getRouteName();
    if (is_null($found) && $route_name == 'entity.node.preview') {
      $node = \Drupal::routeMatch()->getParameter('node_preview');
      $route_parameters = array('node' => $node->id());
      // Load links matching this route.
      $links = $this->menuLinkManager->loadLinksByRoute('entity.node.canonical', $route_parameters, $menu_name);
      // Select the first matching link.
      if ($links) {
        $found = reset($links);
      }
    }

    // Path-based active trail detection.
    if (!$found) {
      $context = \Drupal::service('router.request_context');
      $path = trim($context->getPathInfo(), '/');
      $url = "base:$path";
      $link = $this->loadLinkByProperties(['url' => $url]);
      return $link;
    }

    // Only override active link detection for Top menu.
    if ($menu_name !== 'top-menu') {
      return $found;
    }

    $route_name = \Drupal::routeMatch()->getRouteName();
    if ($route_name == 'ymca_groupex.all_schedules_search_results') {
      $route_name_matched = 'ymca_groupex.all_schedules_search';
    }

    // If a node is displayed, load the default parent menu item
    // from the node type's menu settings and return it instead
    // of the default one.
    $node = \Drupal::routeMatch()->getParameter('node');
    if ($this->routeMatch->getRouteName() == 'entity.node.preview') {
      $node = \Drupal::routeMatch()->getParameter('node_preview');
    }

    if (isset($node) && $node instanceof NodeInterface) {
      $bundle = $node->bundle();
      switch ($bundle) {
        case 'article':
          if ($field_related_value = $node->field_related->getValue()) {
            if (!empty($field_related_value[0]['target_id'])) {
              if ($related = \Drupal::entityTypeManager()->getStorage('node')->load($field_related_value[0]['target_id'])) {
                if ($related->bundle() == 'location') {
                  $route_name_matched = 'ymca_frontend.locations';
                }
              }
            }
          }
          break;

        case 'landing_page':
          if ($field_related_value = $node->field_ygtc_related->getValue()) {
            if (!empty($field_related_value[0]['target_id'])) {
              if ($related = \Drupal::entityTypeManager()->getStorage('node')->load($field_related_value[0]['target_id'])) {
                if ($related->bundle() == 'location') {
                  $route_name_matched = 'ymca_frontend.locations';
                }
              }
            }
          }
          break;

        case 'location':
          $route_name_matched = 'ymca_frontend.locations';
          break;

        case 'blog':
          $route_name_matched = 'view.ymca_twin_cities_blog.blog_page';
          if ($field_tags_value = $node->field_tags->getValue()) {
            foreach ($field_tags_value as $id) {
              if ($id['target_id'] == TERM_NEWS_TID) {
                $route_name_matched = 'view.ymca_news.page_news';
              }
            }
          }
          break;
      }
    }

    if (isset($route_name_matched)) {
      $links = \Drupal::service('plugin.manager.menu.link')
        ->loadLinksByRoute($route_name_matched, [], $menu_name);
      if ($links) {
        $found = reset($links);
      }
    }

    // Pathbased active trail detection.
    if (!$found) {
      // Consequently contract path by removing it's last parts.
      $context = \Drupal::service('router.request_context');
      $path = trim($context->getPathInfo(), '/');
      $path_elements = explode('/', $path);
      while (count($path_elements) > 1) {
        array_pop($path_elements);
        $path = '/' . implode('/', $path_elements);
        // Retrieve request for the page.
        $route_request = $this->getRequestForPath($path);
        if ($route_request) {
          $route_match = RouteMatch::createFromRequest($route_request);
          $route_parameters = $route_match->getRawParameters()->all();
          $links = \Drupal::service('plugin.manager.menu.link')
            ->loadLinksByRoute($route_match->getRouteName(), $route_parameters, $menu_name);
          if ($links) {
            $found = reset($links);
            break;
          }
        }
      }
    }

    return $found;
  }

  /**
   * Matches a path in the router.
   *
   * @param string $path
   *   The request path with a leading slash.
   *
   * @return \Symfony\Component\HttpFoundation\Request
   *   A populated request object or NULL if the path couldn't be matched.
   */
  protected function getRequestForPath($path) {
    // @todo Use the RequestHelper once https://www.drupal.org/node/2090293 is
    //   fixed.
    $request = Request::create($path);
    // Performance optimization: set a short accept header to reduce overhead in
    // AcceptHeaderMatcher when matching the request.
    $request->headers->set('Accept', 'text/html');
    // Find the system path by resolving aliases, language prefix, etc.
    $processed = \Drupal::service('path_processor_manager')->processInbound($path, $request);
    if (empty($processed) || !empty($exclude[$processed])) {
      // This resolves to the front page, which we already add.
      return NULL;
    }
    \Drupal::service('path.current')->setPath($processed, $request);
    // Attempt to match this path to provide a fully built request.
    try {
      $request->attributes->add(\Drupal::service('router')->matchRequest($request));
      return $request;
    }
    catch (ParamNotConvertedException $e) {
      return NULL;
    }
    catch (ResourceNotFoundException $e) {
      return NULL;
    }
    catch (MethodNotAllowedException $e) {
      return NULL;
    }
    catch (AccessDeniedHttpException $e) {
      return NULL;
    }
    catch (\Exception $e) {
      watchdog_exception('ymca_menu', $e);
      return NULL;
    }
  }

}
