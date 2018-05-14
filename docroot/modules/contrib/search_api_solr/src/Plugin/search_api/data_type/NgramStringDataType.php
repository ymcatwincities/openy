<?php

namespace Drupal\search_api_solr\Plugin\search_api\data_type;

use Drupal\search_api\Plugin\search_api\data_type\StringDataType;

/**
 * Provides a ngram string data type.
 *
 * @SearchApiDataType(
 *   id = "solr_string_ngram",
 *   label = @Translation("String Ngram"),
 *   description = @Translation("String fields are used for short, keyword-like character strings where you only want to find complete field values, not individual words. The Ngram derivate allows to search for strings that 'start with'."),
 *   fallback_type = "string",
 *   prefix = "se"
 * )
 */
class NgramStringDataType extends StringDataType {}
