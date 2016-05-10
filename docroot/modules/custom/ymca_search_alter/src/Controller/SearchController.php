<?php

namespace Drupal\ymca_search_alter\Controller;

use Drupal\Core\Cache\CacheableDependencyInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\search\SearchPageInterface;
use Drupal\search\SearchPageRepositoryInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Drupal\search\Controller\SearchController as DrupalSearchController;

/**
 * Route controller for search.
 */
class SearchController extends DrupalSearchController {

  /**
   * {@inheritdoc}
   */
  public function __construct(SearchPageRepositoryInterface $search_page_repository, RendererInterface $renderer) {
    parent::__construct($search_page_repository, $renderer);
  }

  /**
   * Creates a render array for the search page.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   * @param \Drupal\search\SearchPageInterface $entity
   *   The search page entity.
   *
   * @return array
   *   The search form and search results build array.
   */
  public function view(Request $request, SearchPageInterface $entity) {
    // Disable this page caching - https://www.drupal.org/node/2323571.
    \Drupal::service('page_cache_kill_switch')->trigger();

    $build = array();
    $plugin = $entity->getPlugin();

    // Build the form first, because it may redirect during the submit,
    // and we don't want to build the results based on last time's request.
    $build['#cache']['contexts'][] = 'url.query_args:keys';
    if ($request->query->has('q')) {
      $keys = trim($request->get('q'));
      $plugin->setSearch($keys, $request->query->all(), $request->attributes->all());
    }

    $build['#title'] = $plugin->suggestedTitle();
    $build['#search_form'] = $this->entityFormBuilder()->getForm($entity, 'search');
    $build['#search_form']['#attributes']['style'] = 'display:none;';

    // Build search results, if keywords or other search parameters are in the
    // GET parameters. Note that we need to try the search if 'keys' is in
    // there at all, vs. being empty, due to advanced search.
    $results = array();
    if ($request->query->has('q')) {
      if ($plugin->isSearchExecutable()) {
        // Log the search.
        if ($this->config('search.settings')->get('logging')) {
          $this->logger->notice('Searched %type for %keys.', array('%keys' => $keys, '%type' => $entity->label()));
        }

        // Collect the search results.
        $results = $plugin->buildResults();
      }
      else {
        // The search not being executable means that no keywords or other
        // conditions were entered.
        drupal_set_message($this->t('Please enter some keywords.'), 'error');
      }
    }

    $build['#search_results'] = array(
      '#theme' => array('item_list__search_results__' . $plugin->getPluginId(), 'item_list__search_results'),
      '#items' => $results,
      '#empty' => array(
        '#markup' => '<h3>' . $this->t('Your search yielded no results.') . '</h3>',
      ),
      '#list_type' => 'ol',
      '#context' => array(
        'plugin' => $plugin->getPluginId(),
      ),
    );

    $this->renderer->addCacheableDependency($build, $entity);
    if ($plugin instanceof CacheableDependencyInterface) {
      $this->renderer->addCacheableDependency($build, $plugin);
    }

    // If this plugin uses a search index, then also add the cache tag tracking
    // that search index, so that cached search result pages are invalidated
    // when necessary.
    if ($plugin->getType()) {
      $build['#search_results']['#cache']['tags'][] = 'search_index';
      $build['#search_results']['#cache']['tags'][] = 'search_index:' . $plugin->getType();
    }

    $build['#pager'] = array(
      '#type' => 'pager',
    );
    $build['#theme'] = ['search_results_page'];

    return $build;
  }

}
