<?php

namespace Drupal\lazyloader;

use Drupal\Core\Condition\ConditionAccessResolverTrait;
use Drupal\Core\Condition\ConditionManager;
use Drupal\Core\Condition\ConditionPluginCollection;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

class LazyLoaderVisibilityChecker {

  use ConditionAccessResolverTrait;

  /** @var  \Drupal\Core\Entity\EntityStorageInterface */
  protected $imageStyleStorage;

  /** @var \Drupal\Core\Condition\ConditionManager */
  protected $conditionManager;

  /** @var \Drupal\Core\Config\ConfigFactoryInterface  */
  protected $configFactory;

  /**
   * Creates a new LazyLoaderVisibilityChecker instance.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Condition\ConditionManager $condition_manager
   *   The condition manager.
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $entity_type_manager, ConditionManager $condition_manager) {
    $this->configFactory = $config_factory;
    $this->imageStyleStorage = $entity_type_manager->getStorage('image_style');
    $this->conditionManager = $condition_manager;
  }

  /**
   * @return \Drupal\Core\Condition\ConditionPluginCollection
   */
  protected function getConditionList() {
    return new ConditionPluginCollection($this->conditionManager, $this->configFactory->get('lazyloader.exclude')->get('visibility'));
  }

  /**
   * @return bool
   */
  public function isEnabled() {
    $enabled =  $this->configFactory->get('lazyloader.configuration')->get('enabled');

    $conditions_apply = $this->resolveConditions(iterator_to_array($this->getConditionList()->getIterator()), 'and');
    return $enabled && $conditions_apply;
  }

  public function isValidFilename($uri) {
    $excluded_files = $this->configFactory->get('lazyloader.exclude')->get('filenames');
    $parts = explode('/', $uri);
    $parts = explode('?', array_pop($parts));
    $filename = array_shift($parts);
    return !(bool) preg_match('/^' . $filename . '$/m', $excluded_files);
  }

  public function isValidImageStyle($uri) {
    $excluded_styles = $this->filterSelectedValues($this->configFactory->get('lazyloader.exclude')->get('image_styles'));
    // If no image styles are selected we have nothing to exclude.
    if (empty($excluded_styles)) {
      return TRUE;
    }

    $styles = implode('|', array_keys($this->imageStyleStorage->loadMultiple()));
    // Make sure the image is actually a derived image.
    if (!preg_match('/styles\/[' . $styles . ']/', $uri)) {
      // Not a derived image, nothing to do here.
      return TRUE;
    }
    
    $excluded_styles = implode('|', $excluded_styles);
    return !preg_match('/styles\/[' . $excluded_styles . ']/', $uri);
  }

  /**
   * Filters an array of selected checkbox values.
   *
   * @todo Ideally we would trust the stored data in config.
   */
  protected function filterSelectedValues($values) {
    // Filter out deselected values.
    foreach ((array) $values as $key => $value) {
      if (!$value) {
        unset($values[$key]);
      }
    }

    return $values;
  }

}
