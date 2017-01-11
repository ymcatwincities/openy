<?php

namespace Drupal\purge\Plugin\Purge\TagsHeader;

use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\purge\Plugin\Purge\TagsHeader\TagsHeadersServiceInterface;
use Drupal\purge\IteratingServiceBaseTrait;
use Drupal\purge\ServiceBase;

/**
 * Provides a service that provides access to available tags headers.
 */
class TagsHeadersService extends ServiceBase implements TagsHeadersServiceInterface {
  use IteratingServiceBaseTrait;

  /**
   * The plugin manager for tagsheaders.
   *
   * @var \Drupal\purge\Plugin\Purge\TagsHeader\PluginManager
   */
  protected $pluginManager;

  /**
   * Construct \Drupal\purge\Plugin\Purge\Processor\ProcessorsService.
   *
   * @param \Drupal\Component\Plugin\PluginManagerInterface $pluginManager
   *   The plugin manager for this service.
   */
  public function __construct(PluginManagerInterface $pluginManager) {
    $this->pluginManager = $pluginManager;
  }

  /**
   * {@inheritdoc}
   * @ingroup countable
   */
  public function count() {
    $this->initializePluginInstances();
    return count($this->instances);
  }

  /**
   * {@inheritdoc}
   */
  public function reload() {
    parent::reload();
    $this->reloadIterator();
  }

}
