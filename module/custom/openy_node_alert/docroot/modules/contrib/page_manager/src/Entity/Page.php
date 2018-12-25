<?php

/**
 * @file
 * Contains \Drupal\page_manager\Entity\Page.
 */

namespace Drupal\page_manager\Entity;

use Drupal\Component\Plugin\Context\ContextInterface;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Plugin\Context\Context;
use Drupal\Core\Plugin\Context\ContextDefinition;
use Drupal\page_manager\Event\PageManagerContextEvent;
use Drupal\page_manager\Event\PageManagerEvents;
use Drupal\page_manager\PageInterface;
use Drupal\Core\Condition\ConditionPluginCollection;
use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\page_manager\PageVariantInterface;

/**
 * Defines a Page entity class.
 *
 * @ConfigEntityType(
 *   id = "page",
 *   label = @Translation("Page"),
 *   handlers = {
 *     "access" = "Drupal\page_manager\Entity\PageAccess",
 *   },
 *   admin_permission = "administer pages",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "status" = "status"
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "description",
 *     "use_admin_theme",
 *     "path",
 *     "access_logic",
 *     "access_conditions",
 *     "parameters",
 *   },
 * )
 */
class Page extends ConfigEntityBase implements PageInterface {

  /**
   * The ID of the page entity.
   *
   * @var string
   */
  protected $id;

  /**
   * The label of the page entity.
   *
   * @var string
   */
  protected $label;

  /**
   * The description of the page entity.
   *
   * @var string
   */
  protected $description;

  /**
   * The path of the page entity.
   *
   * @var string
   */
  protected $path;

  /**
   * The page variant entities.
   *
   * @var \Drupal\page_manager\PageVariantInterface[].
   */
  protected $variants;

  /**
   * An array of collected contexts.
   *
   * @var \Drupal\Component\Plugin\Context\ContextInterface[]
   */
  protected $contexts = [];

  /**
   * The configuration of access conditions.
   *
   * @var array
   */
  protected $access_conditions = [];

  /**
   * Tracks the logic used to compute access, either 'and' or 'or'.
   *
   * @var string
   */
  protected $access_logic = 'and';

  /**
   * The plugin collection that holds the access conditions.
   *
   * @var \Drupal\Component\Plugin\LazyPluginCollection
   */
  protected $accessConditionCollection;

  /**
   * Indicates if this page should be displayed in the admin theme.
   *
   * @var bool
   */
  protected $use_admin_theme;

  /**
   * Parameter context configuration.
   *
   * An associative array keyed by parameter name, which contains associative
   * arrays with the following keys:
   * - machine_name: Machine-readable context name.
   * - label: Human-readable context name.
   * - type: Context type.
   *
   * @var array[]
   */
  protected $parameters = [];

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->description;
  }

  /**
   * {@inheritdoc}
   */
  public function getPath() {
    return $this->path;
  }

  /**
   * {@inheritdoc}
   */
  public function usesAdminTheme() {
    return isset($this->use_admin_theme) ? $this->use_admin_theme : strpos($this->getPath(), '/admin/') === 0;
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageInterface $storage, $update = TRUE) {
    parent::postSave($storage, $update);
    static::routeBuilder()->setRebuildNeeded();
  }

  /**
   * {@inheritdoc}
   */
  public static function postDelete(EntityStorageInterface $storage, array $entities) {
    parent::postDelete($storage, $entities);
    static::routeBuilder()->setRebuildNeeded();
  }

  /**
   * Wraps the route builder.
   *
   * @return \Drupal\Core\Routing\RouteBuilderInterface
   *   An object for state storage.
   */
  protected static function routeBuilder() {
    return \Drupal::service('router.builder');
  }

  /**
   * Wraps the entity storage for page variants.
   *
   * @return \Drupal\Core\Entity\EntityStorageInterface
   */
  protected function variantStorage() {
    return \Drupal::service('entity_type.manager')->getStorage('page_variant');
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginCollections() {
    return [
      'access_conditions' => $this->getAccessConditions(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getAccessConditions() {
    if (!$this->accessConditionCollection) {
      $this->accessConditionCollection = new ConditionPluginCollection(\Drupal::service('plugin.manager.condition'), $this->get('access_conditions'));
    }
    return $this->accessConditionCollection;
  }

  /**
   * {@inheritdoc}
   */
  public function addAccessCondition(array $configuration) {
    $configuration['uuid'] = $this->uuidGenerator()->generate();
    $this->getAccessConditions()->addInstanceId($configuration['uuid'], $configuration);
    return $configuration['uuid'];
  }

  /**
   * {@inheritdoc}
   */
  public function getAccessCondition($condition_id) {
    return $this->getAccessConditions()->get($condition_id);
  }

  /**
   * {@inheritdoc}
   */
  public function removeAccessCondition($condition_id) {
    $this->getAccessConditions()->removeInstanceId($condition_id);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getAccessLogic() {
    return $this->access_logic;
  }

  /**
   * {@inheritdoc}
   */
  public function getParameters() {
    $names = $this->getParameterNames();
    if ($names) {
      return array_intersect_key($this->parameters, array_flip($names));
    }
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getParameter($name) {
    if ($this->hasParameter($name)) {
      return $this->parameters[$name];
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function hasParameter($name) {
    return isset($this->parameters[$name]);
  }

  /**
   * {@inheritdoc}
   */
  public function setParameter($name, $type, $label = '') {
    $this->parameters[$name] = [
      'machine_name' => $name,
      'type' => $type,
      'label' => $label,
    ];
    // Reset contexts when a parameter is added or changed.
    $this->contexts = [];
    // Reset the contexts of every variant.
    foreach ($this->getVariants() as $page_variant) {
      $page_variant->resetCollectedContexts();
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function removeParameter($name) {
    unset($this->parameters[$name]);
    // Reset contexts when a parameter is removed.
    $this->contexts = [];
    // Reset the contexts of every variant.
    foreach ($this->getVariants() as $page_variant) {
      $page_variant->resetCollectedContexts();
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getParameterNames() {
    if (preg_match_all('|\{(\w+)\}|', $this->getPath(), $matches)) {
      return $matches[1];
    }
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    parent::preSave($storage);

    $this->filterParameters();
  }

  /**
   * Filters the parameters to remove any without a valid type.
   *
   * @return $this
   */
  protected function filterParameters() {
    $names = $this->getParameterNames();
    foreach ($this->get('parameters') as $name => $parameter) {
      // Remove parameters without any type, or which are no longer valid.
      if (empty($parameter['type']) || !in_array($name, $names)) {
        $this->removeParameter($name);
      }
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function addContext($name, ContextInterface $value) {
    $this->contexts[$name] = $value;
  }

  /**
   * {@inheritdoc}
   */
  public function getContexts() {
    // @todo add the other global contexts here as they are added
    // @todo maybe come up with a non-hardcoded way of doing this?
    $global_contexts = [
      'current_user'
    ];
    if (!$this->contexts) {
      $this->eventDispatcher()->dispatch(PageManagerEvents::PAGE_CONTEXT, new PageManagerContextEvent($this));
      foreach ($this->getParameters() as $machine_name => $configuration) {
        // Parameters can be updated in the UI, so unless it's a global context
        // we'll need to rely on the current settings in the tempstore instead
        // of the ones cached in the router.
        if (!isset($global_contexts[$machine_name])) {
          // First time through, parameters will not be defined by the route.
          if (!isset($this->contexts[$machine_name])) {
            $cacheability = new CacheableMetadata();
            $cacheability->setCacheContexts(['route']);

            $context_definition = new ContextDefinition($configuration['type'], $configuration['label']);
            $context = new Context($context_definition);
            $context->addCacheableDependency($cacheability);
            $this->contexts[$machine_name] = $context;
          }
          else {
            $this->contexts[$machine_name]->getContextDefinition()->setDataType($configuration['type']);
            if (!empty($configuration['label'])) {
              $this->contexts[$machine_name]->getContextDefinition()->setLabel($configuration['label']);
            }
          }
        }
      }
    }
    return $this->contexts;
  }

  /**
   * {@inheritdoc}
   */
  public function addVariant(PageVariantInterface $variant) {
    // If variants hasn't been initialized, we initialize it before adding the
    // new variant.
    if ($this->variants === NULL) {
      $this->getVariants();
    }
    $this->variants[$variant->id()] = $variant;
    $this->sortVariants();
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getVariant($variant_id) {
    $variants = $this->getVariants();
    if (!isset($variants[$variant_id])) {
      throw new \UnexpectedValueException('The requested variant does not exist or is not associated with this page');
    }
    return $variants[$variant_id];
  }

  /**
   * {@inheritdoc}
   */
  public function removeVariant($variant_id) {
    $this->getVariant($variant_id)->delete();
    unset($this->variants[$variant_id]);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getVariants() {
    if (!isset($this->variants)) {
      $this->variants = [];
      /** @var \Drupal\page_manager\PageVariantInterface $variant */
      foreach ($this->variantStorage()->loadByProperties(['page' => $this->id()]) as $variant) {
        $this->variants[$variant->id()] = $variant;
      }
      $this->sortVariants();
    }
    return $this->variants;
  }

  /**
   * Sort variants.
   */
  protected function sortVariants() {
    if (isset($this->variants)) {
      // Suppress errors because of https://bugs.php.net/bug.php?id=50688.
      @uasort($this->variants, [$this, 'variantSortHelper']);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function variantSortHelper($a, $b) {
    $a_weight = $a->getWeight();
    $b_weight = $b->getWeight();
    if ($a_weight == $b_weight) {
      return 0;
    }

    return ($a_weight < $b_weight) ? -1 : 1;
  }

  /**
   * Wraps the event dispatcher.
   *
   * @return \Symfony\Component\EventDispatcher\EventDispatcherInterface
   *   The event dispatcher.
   */
  protected function eventDispatcher() {
    return \Drupal::service('event_dispatcher');
  }

  /**
   * {@inheritdoc}
   */
  public function __sleep() {
    $vars = parent::__sleep();

    // Gathered contexts objects should not be serialized.
    if (($key = array_search('contexts', $vars)) !== FALSE) {
      unset($vars[$key]);
    }

    return $vars;
  }

  /**
   * {@inheritdoc}
   *
   * @todo: Remove this as part of https://www.drupal.org/node/2696683.
   */
  protected function urlRouteParameters($rel) {
    if ($rel == 'edit-form') {
      $uri_route_parameters = [];
      $uri_route_parameters['machine_name'] = $this->id();
      $uri_route_parameters['step'] = 'general';
      return $uri_route_parameters;
    }

    return parent::urlRouteParameters($rel);
  }

}
