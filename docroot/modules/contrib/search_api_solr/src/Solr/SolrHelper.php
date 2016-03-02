<?php

/**
 * @file
 * Contains \Drupal\search_api_solr\Solr\SolrHelper.
 */

namespace Drupal\search_api_solr\Solr;

use Drupal\Core\Url;
use Drupal\search_api\Query\QueryInterface;
use Solarium\Client;
use Drupal\search_api_solr\Utility\Utility as SearchApiSolrUtility;
use Solarium\Core\Query\Helper as SolariumHelper;
use Solarium\QueryType\Select\Query\Query;
use Drupal\search_api\Utility as SearchApiUtility;

class SolrHelper {

  /**
   * A connection to the Solr server.
   *
   * @var \Solarium\Client
   */
  protected $solr;

  /**
   * A connection to the Solr server.
   *
   * @var array
   */
  protected $configuration;

  /**
   * Stores Solr system information.
   *
   * @var array
   */
  protected $systemInfo;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration) {
    $this->configuration = $configuration;
  }

  /**
   * Sets the solr connection.
   *
   * @param \Solarium\Client $solr
   *   The solarium connection object.
   */
  public function setSolr(Client $solr) {
    $this->solr = $solr;
  }

  /**
   * Returns a link to the Solr server, if the necessary options are set.
   */
  public function getServerLink() {
    if (!$this->configuration) {
      return '';
    }
    $host = $this->configuration['host'];
    if ($host == 'localhost' && !empty($_SERVER['SERVER_NAME'])) {
      $host = $_SERVER['SERVER_NAME'];
    }
    $url_path = $this->configuration['scheme'] . '://' . $host . ':' . $this->configuration['port'] . $this->configuration['path'];
    $url = Url::fromUri($url_path);

    return \Drupal::l($url_path, $url);
  }

  /**
   * Extract and format highlighting information for a specific item from a Solr response.
   *
   * Will also use highlighted fields to replace retrieved field data, if the
   * corresponding option is set.
   */
  public function getExcerpt($response, $solr_id, array $fields, array $field_mapping) {
    if (!isset($response->highlighting->$solr_id)) {
      return FALSE;
    }
    $output = '';

    if (!empty($this->configuration['excerpt']) && !empty($response->highlighting->$solr_id->spell)) {
      foreach ($response->highlighting->$solr_id->spell as $snippet) {
        $snippet = strip_tags($snippet);
        $snippet = preg_replace('/^.*>|<.*$/', '', $snippet);
        $snippet = SearchApiSolrUtility::formatHighlighting($snippet);
        // The created fragments sometimes have leading or trailing punctuation.
        // We remove that here for all common cases, but take care not to remove
        // < or > (so HTML tags stay valid).
        $snippet = trim($snippet, "\00..\x2F:;=\x3F..\x40\x5B..\x60");
        $output .= $snippet . ' … ';
      }
    }
    if (!empty($this->configuration['highlight_data'])) {
      foreach ($field_mapping as $search_api_property => $solr_property) {
        if (substr($solr_property, 0, 3) == 'tm_' && !empty($response->highlighting->$solr_id->$solr_property)) {
          // Contrary to above, we here want to preserve HTML, so we just
          // replace the [HIGHLIGHT] tags with the appropriate format.
          $fields[$search_api_property] = SearchApiSolrUtility::formatHighlighting($response->highlighting->$solr_id->$solr_property);
        }
      }
    }

    return $output;
  }

  /**
   * Flatten a keys array into a single search string.
   *
   * @param array $keys
   *   The keys array to flatten, formatted as specified by
   *   \Drupal\search_api\Query\QueryInterface::getKeys().
   * @param bool $is_nested
   *   (optional) Whether the function is called for a nested condition.
   *   Defaults to FALSE.
   *
   * @return string
   *   A Solr query string representing the same keys.
   */
  public function flattenKeys(array $keys, $is_nested = FALSE) {
    $k = array();
    $or = $keys['#conjunction'] == 'OR';
    $neg = !empty($keys['#negation']);
    foreach ($keys as $key_nr => $key) {
      // We cannot use \Drupal\Core\Render\Element::children() anymore because
      // $keys is not a valid render array.
      if ($key_nr[0] === '#' || !$key) {
        continue;
      }
      if (is_array($key)) {
        $subkeys = $this->flattenKeys($key, TRUE);
        if ($subkeys) {
          $nested_expressions = TRUE;
          // If this is a negated OR expression, we can't just use nested keys
          // as-is, but have to put them into parantheses.
          if ($or && $neg) {
            $subkeys = "($subkeys)";
          }
          $k[] = $subkeys;
        }
      }
      else {
        $solariumHelper = new SolariumHelper();
        $key = $solariumHelper->escapePhrase(trim($key));
        $k[] = $key;
      }
    }
    if (!$k) {
      return '';
    }

    // Formatting the keys into a Solr query can be a bit complex. The following
    // code will produce filters that look like this:
    //
    // #conjunction | #negation | return value
    // ----------------------------------------------------------------
    // AND          | FALSE     | A B C
    // AND          | TRUE      | -(A AND B AND C)
    // OR           | FALSE     | ((A) OR (B) OR (C))
    // OR           | TRUE      | -A -B -C

    // If there was just a single, unnested key, we can ignore all this.
    if (count($k) == 1 && empty($nested_expressions)) {
      $k = reset($k);
      return $neg ? "*:* AND -$k" : $k;
    }

    if ($or) {
      if ($neg) {
        return '*:* AND -' . implode(' AND -', $k);
      }
      return '((' . implode(') OR (', $k) . '))';
    }
    $k = implode($neg || $is_nested ? ' AND ' : ' ', $k);
    return $neg ? "*:* AND -($k)" : $k;
  }

  /**
   * Gets the current Solr version.
   *
   * @return int
   *   1, 3 or 4. Does not give a more detailed version, for that you need to
   *   use getSystemInfo().
   */
  public function getSolrVersion() {
    // Allow for overrides by the user.
    if (!empty($this->configuration['solr_version'])) {
      return $this->configuration['solr_version'];
    }

    $system_info = $this->getSystemInfo();
    // Get our solr version number
    if (isset($system_info['lucene']['solr-spec-version'])) {
      return $system_info['lucene']['solr-spec-version'];
    }
    return 0;
  }

  /**
   * Gets information about the Solr Core.
   *
   * @return object
   *   A response object with system information.
   */
  public function getSystemInfo() {
    // @todo Add back persistent cache?
    if (!isset($this->systemInfo)) {
      // @todo Finish https://github.com/basdenooijer/solarium/pull/155 and stop
      // abusing the ping query for this.
      $query = $this->solr->createPing(array('handler' => 'admin/system'));
      $this->systemInfo = $this->solr->ping($query)->getData();
    }

    return $this->systemInfo;
  }

  /**
   * Gets meta-data about the index.
   *
   * @return object
   *   A response object filled with data from Solr's Luke.
   */
  public function getLuke() {
    // @todo Write a patch for Solarium to have a separate Luke query and stop
    // abusing the ping query for this.
    $query = $this->solr->createPing(array('handler' => 'admin/luke'));
    return $this->solr->ping($query)->getData();
  }

  /**
   * Gets summary information about the Solr Core.
   *
   * @return array
   */
  public function getStatsSummary() {
    $summary = array(
      '@pending_docs' => '',
      '@autocommit_time_seconds' => '',
      '@autocommit_time' => '',
      '@deletes_by_id' => '',
      '@deletes_by_query' => '',
      '@deletes_total' => '',
      '@schema_version' => '',
      '@core_name' => '',
      '@index_size' => '',
    );

    $solr_version = $this->getSolrVersion();
    $query = $this->solr->createPing();
    $query->setResponseWriter(Query::WT_PHPS);
    if (version_compare($solr_version, '4', '>=')) {
      $query->setHandler('admin/mbeans?stats=true');
    }
    else {
      $query->setHandler('admin/stats.jsp');
    }
    $stats = $this->solr->ping($query)->getData();
    if (!empty($stats)) {
      if (version_compare($solr_version, '3', '<=')) {
        // @todo Needs to be updated by someone who has a Solr 3.x setup.
        /*
        $docs_pending_xpath = $stats->xpath('//stat[@name="docsPending"]');
        $summary['@pending_docs'] = (int) trim(current($docs_pending_xpath));
        $max_time_xpath = $stats->xpath('//stat[@name="autocommit maxTime"]');
        $max_time = (int) trim(current($max_time_xpath));
        // Convert to seconds.
        $summary['@autocommit_time_seconds'] = $max_time / 1000;
        $summary['@autocommit_time'] = \Drupal::service('date')->formatInterval($max_time / 1000);
        $deletes_id_xpath = $stats->xpath('//stat[@name="deletesById"]');
        $summary['@deletes_by_id'] = (int) trim(current($deletes_id_xpath));
        $deletes_query_xpath = $stats->xpath('//stat[@name="deletesByQuery"]');
        $summary['@deletes_by_query'] = (int) trim(current($deletes_query_xpath));
        $summary['@deletes_total'] = $summary['@deletes_by_id'] + $summary['@deletes_by_query'];
        $schema = $stats->xpath('/solr/schema[1]');
        $summary['@schema_version'] = trim($schema[0]);
        $core = $stats->xpath('/solr/core[1]');
        $summary['@core_name'] = trim($core[0]);
        $size_xpath = $stats->xpath('//stat[@name="indexSize"]');
        $summary['@index_size'] = trim(current($size_xpath));
        */
      }
      else {
        $update_handler_stats = $stats['solr-mbeans']['UPDATEHANDLER']['updateHandler']['stats'];
        $summary['@pending_docs'] = (int) $update_handler_stats['docsPending'];
        $max_time = (int) $update_handler_stats['autocommit maxTime'];
        // Convert to seconds.
        $summary['@autocommit_time_seconds'] = $max_time / 1000;
        $summary['@autocommit_time'] = \Drupal::service('date.formatter')->formatInterval($max_time / 1000);
        $summary['@deletes_by_id'] = (int) $update_handler_stats['deletesById'];
        $summary['@deletes_by_query'] = (int) $update_handler_stats['deletesByQuery'];
        $summary['@deletes_total'] = $summary['@deletes_by_id'] + $summary['@deletes_by_query'];
        $summary['@schema_version'] = $this->getSystemInfo()['core']['schema'];
        $summary['@core_name'] = $stats['solr-mbeans']['CORE']['core']['stats']['coreName'];
        $summary['@index_size'] = $stats['solr-mbeans']['QUERYHANDLER']['/replication']['stats']['indexSize'];
      }
    }
    return $summary;
  }

  /**
   * Sets the highlighting parameters.
   *
   * (The $query parameter currently isn't used and only here for the potential
   * sake of subclasses.)
   *
   * @param \Drupal\search_api\Query\QueryInterface $query
   *   The query object.
   * @param \Solarium\QueryType\Select\Query\Query $solarium_query
   *   The Solarium select query object.
   */
  public function setHighlighting(Query $solarium_query, QueryInterface $query, $highlight = true, $excerpt = true) {
    if ($excerpt || $highlight) {
      $hl = $solarium_query->getHighlighting();
      $hl->setFields('spell');
      $hl->setSimplePrefix('[HIGHLIGHT]');
      $hl->setSimplePostfix('[/HIGHLIGHT]');
      $hl->setSnippets(3);
      $hl->setFragSize(70);
      $hl->setMergeContiguous(TRUE);
    }

    if ($highlight) {
      $hl = $solarium_query->getHighlighting();
      $hl->setFields('tm_*');
      $hl->setSnippets(1);
      $hl->setFragSize(0);
      if (!empty($this->configuration['excerpt'])) {
        // If we also generate a "normal" excerpt, set the settings for the
        // "spell" field (which we use to generate the excerpt) back to the
        // above values.
        $hl->getField('spell')->setSnippets(3);
        $hl->getField('spell')->setFragSize(70);
        // It regrettably doesn't seem to be possible to set hl.fl to several
        // values, if one contains wild cards (i.e., "t_*,spell" wouldn't work).
        $hl->setFields('*');
      }
    }
  }

  public function setMoreLikeThis(Query &$solarium_query, QueryInterface $query, $mlt_options = array(), $field_options = array(), $fields) {
    $solarium_query = $this->solr->createMoreLikeThis(array('handler' => 'select'));
    // The fields to look for similarities in.
    if (empty($mlt_options['fields'])) {
      return;
    }

    $mlt_fl = array();
    foreach ($mlt_options['fields'] as $mlt_field) {
      // Solr 4 has a bug which results in numeric fields not being supported
      // in MLT queries.
      // Date fields don't seem to be supported at all.
      $version = $this->getSolrVersion();
      if ($fields[$mlt_field][0] === 'd' || ($version == 4 && in_array($fields[$mlt_field][0], array('i', 'f')))) {
        continue;
      }

      $mlt_fl[] = $fields[$mlt_field];
      // For non-text fields, set minimum word length to 0.
      if (isset($field_options[$mlt_field]['type']) && !SearchApiUtility::isTextType($field_options[$mlt_field]['type'])) {
        $solarium_query->addParam('f.' . $fields[$mlt_field] . '.mlt.minwl', 0);
      }
    }

    //$solarium_query->setHandler('mlt');
    $solarium_query->setMltFields($mlt_fl);
    $customizer = $this->solr->getPlugin('customizerequest');
    $customizer->createCustomization('id')
      ->setType('param')
      ->setName('qt')
      ->setValue('mlt');
    // @todo Make sure these configurations are correct
    $solarium_query->setMinimumDocumentFrequency(1);
    $solarium_query->setMinimumTermFrequency(1);
  }

  public function setSpatial(Query $solarium_query, QueryInterface $query, $spatial_options = array(), $field_names) {
    foreach ($spatial_options as $i => $spatial) {
      // reset radius for each option
      unset($radius);

      if (empty($spatial['field']) || empty($spatial['lat']) || empty($spatial['lon'])) {
        continue;
      }

      $field = $field_names[$spatial['field']];
      $escaped_field = SearchApiSolrUtility::escapeFieldName($field);
      $point = ((float) $spatial['lat']) . ',' . ((float) $spatial['lon']);

      // Prepare the filter settings.
      if (isset($spatial['radius'])) {
        $radius = (float) $spatial['radius'];
      }

      $spatial_method = 'geofilt';
      if (isset($spatial['method']) && in_array($spatial['method'], array('geofilt', 'bbox'))) {
        $spatial_method = $spatial['method'];
      }

      $filter_queries = $solarium_query->getFilterQueries();
      // Change the fq facet ranges to the correct fq.
      foreach ($filter_queries as $key => $filter_query) {
        // If the fq consists only of a filter on this field, replace it with
        // a range.
        $preg_field = preg_quote($escaped_field, '/');
        if (preg_match('/^' . $preg_field . ':\["?(\*|\d+(?:\.\d+)?)"? TO "?(\*|\d+(?:\.\d+)?)"?\]$/', $filter_query, $matches)) {
          unset($filter_queries[$key]);
          if ($matches[1] && is_numeric($matches[1])) {
            $min_radius = isset($min_radius) ? max($min_radius, $matches[1]) : $matches[1];
          }
          if (is_numeric($matches[2])) {
            // Make the radius tighter accordingly.
            $radius = isset($radius) ? min($radius, $matches[2]) : $matches[2];
          }
        }
      }

      // If either a radius was given in the option, or a filter was
      // encountered, set a filter for the lowest value. If a lower boundary
      // was set (too), we can only set a filter for that if the field name
      // doesn't contains any colons.
      if (isset($min_radius) && strpos($field, ':') === FALSE) {
        $upper = isset($radius) ? " u=$radius" : '';
        $solarium_query->createFilterQuery()->setQuery("{!frange l=$min_radius$upper}geodist($field,$point)");
      }
      elseif (isset($radius)) {
        $solarium_query->createFilterQuery()->setQuery("{!$spatial_method pt=$point sfield=$field d=$radius}");
      }

      // @todo: Check if this object returns the correct value
      $sorts = $solarium_query->getSorts();
      // Change sort on the field, if set (and not already changed).
      if (isset($sorts[$spatial['field']]) && substr($sorts[$spatial['field']], 0, strlen($field)) === $field) {
        if (strpos($field, ':') === FALSE) {
          $sorts[$spatial['field']] = str_replace($field, "geodist($field,$point)", $sorts[$spatial['field']]);
        }
        else {
          $link = \Drupal::l(t('edit server'), Url::fromRoute('entity.search_api_server.edit_form', array('search_api_server' => $this->server->id())));
          \Drupal::logger('search_api_solr')->warning('Location sort on field @field had to be ignored because unclean field identifiers are used.', array('@field' => $spatial['field'], 'link' => $link));
        }
      }

      // Change the facet parameters for spatial fields to return distance
      // facets.
      $facets = $solarium_query->getFacetSet();
      // @todo: Fix this so it takes it from the solarium query
      if (!empty($facets)) {
        if (!empty($facet_params['facet.field'])) {
          $facet_params['facet.field'] = array_diff($facet_params['facet.field'], array($field));
        }
        foreach ($facets as $delta => $facet) {
          if ($facet['field'] != $spatial['field']) {
            continue;
          }
          $steps = $facet['limit'] > 0 ? $facet['limit'] : 5;
          $step = (isset($radius) ? $radius : 100) / $steps;
          for ($k = $steps - 1; $k > 0; --$k) {
            $distance = $step * $k;
            $key = "spatial-$delta-$distance";
            $facet_params['facet.query'][] = "{!$spatial_method pt=$point sfield=$field d=$distance key=$key}";
          }
          foreach (array('limit', 'mincount', 'missing') as $setting) {
            unset($facet_params["f.$field.facet.$setting"]);
          }
        }
      }
    }

    // Normal sorting on location fields isn't possible.
    foreach (array_keys($solarium_query->getSorts()) as $sort) {
      if (substr($sort, 0, 3) === 'loc') {
        $solarium_query->removeSort($sort);
      }
    }
  }

  public function setSorts(Query $solarium_query, QueryInterface $query, $field_names_single_value = array()) {
    foreach ($query->getSorts() as $field => $order) {
      $f = $field_names_single_value[$field];
      if (substr($f, 0, 3) == 'ss_') {
        $f = 'sort_' . substr($f, 3);
      }
      $solarium_query->addSort($f, strtolower($order));
    }
  }

  public function setGrouping(Query $solarium_query, QueryInterface $query, $grouping_options = array(), $index_fields = array(), $field_names = array()) {
    $group_params['group'] = 'true';
    // We always want the number of groups returned so that we get pagers done
    // right.
    $group_params['group.ngroups'] = 'true';
    if (!empty($grouping_options['truncate'])) {
      $group_params['group.truncate'] = 'true';
    }
    if (!empty($grouping_options['group_facet'])) {
      $group_params['group.facet'] = 'true';
    }
    foreach ($grouping_options['fields'] as $collapse_field) {
      $type = $index_fields[$collapse_field]['type'];
      // Only single-valued fields are supported.
      if ($this->getSolrVersion() < 4) {
        // For Solr 3.x, only string and boolean fields are supported.
        if (!SearchApiUtility::isTextType($type, array('string', 'boolean', 'uri'))) {
          $warnings[] = $this->t('Grouping is not supported for field @field. ' .
            'Only single-valued fields of type "String", "Boolean" or "URI" are supported.',
            array('@field' => $index_fields[$collapse_field]['name']));
          continue;
        }
      }
      else {
        if (SearchApiUtility::isTextType($type)) {
          $warnings[] = $this->t('Grouping is not supported for field @field. ' .
            'Only single-valued fields not indexed as "Fulltext" are supported.',
            array('@field' => $index_fields[$collapse_field]['name']));
          continue;
        }
      }
      $group_params['group.field'][] = $field_names[$collapse_field];
    }
    if (empty($group_params['group.field'])) {
      unset($group_params);
    }
    else {
      if (!empty($grouping_options['group_sort'])) {
        foreach ($grouping_options['group_sort'] as $group_sort_field => $order) {
          if (isset($fields[$group_sort_field])) {
            $f = $fields[$group_sort_field];
            if (substr($f, 0, 3) == 'ss_') {
              $f = 'sort_' . substr($f, 3);
            }
            $order = strtolower($order);
            $group_params['group.sort'][] = $f . ' ' . $order;
          }
        }
        if (!empty($group_params['group.sort'])) {
          $group_params['group.sort'] = implode(', ', $group_params['group.sort']);
        }
      }
      if (!empty($grouping_options['group_limit']) && ($grouping_options['group_limit'] != 1)) {
        $group_params['group.limit'] = $grouping_options['group_limit'];
      }
    }
    foreach ($group_params as $param_id => $param_value) {
      $solarium_query->addParam($param_id, $param_value);
    }
  }

}
