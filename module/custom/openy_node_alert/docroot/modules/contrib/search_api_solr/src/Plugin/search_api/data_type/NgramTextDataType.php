<?php

namespace Drupal\search_api_solr\Plugin\search_api\data_type;

use Drupal\search_api\Plugin\search_api\data_type\TextDataType;

/**
 * Provides a ngram full text data type.
 *
 * @SearchApiDataType(
 *   id = "solr_text_ngram",
 *   label = @Translation("Fulltext Ngram"),
 *   description = @Translation("Full text field with edgeNgramFilter."),
 *   fallback_type = "text",
 *   prefix = "te"
 * )
 */
class NgramTextDataType extends TextDataType {}
