<?php

namespace Drupal\search_api_solr\Plugin\search_api\data_type;

use Drupal\search_api\Plugin\search_api\data_type\TextDataType;

/**
 * Provides a not stemmed full text data type.
 *
 * @SearchApiDataType(
 *   id = "solr_text_unstemmed",
 *   label = @Translation("Fulltext Unstemmed"),
 *   description = @Translation("Full text field without stemming."),
 *   fallback_type = "text",
 *   prefix = "tu"
 * )
 */
class UnstemmedTextDataType extends TextDataType {}
