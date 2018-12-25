<?php

namespace Drupal\search_api_solr\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a connector plugin annotation object.
 *
 * Condition plugins provide generalized conditions for use in other
 * operations, such as conditional block placement.
 *
 * Plugin Namespace: Plugin\SolrConnector
 *
 * @see \Drupal\search_api_solr\SolrConnector\SolrConnectorManager
 * @see \Drupal\search_api_solr\SolrConnector\SolrConnectorInterface
 * @see \Drupal\search_api_solr\SolrConnector\SolrConnectorPluginBase
 *
 * @ingroup plugin_api
 *
 * @Annotation
 */
class SolrConnector extends Plugin {

  /**
   * The Solr connector plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable name of the Solr connector.
   *
   * @ingroup plugin_translatable
   *
   * @var \Drupal\Core\Annotation\Translation
   */
  public $label;

  /**
   * The backend description.
   *
   * @ingroup plugin_translatable
   *
   * @var \Drupal\Core\Annotation\Translation
   */
  public $description;

}
