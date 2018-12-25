<?php

namespace Drupal\search_api_solr\Plugin\search_api\data_type;

use Drupal\search_api\Plugin\search_api\data_type\TextDataType;

/**
 * Provides a phonetic full text data type.
 *
 * @SearchApiDataType(
 *   id = "solr_text_phonetic",
 *   label = @Translation("Fulltext Phonetic"),
 *   description = @Translation("Full text field with phonetic matching."),
 *   fallback_type = "text",
 *   prefix = "phon"
 * )
 */
class PhoneticTextDataType extends TextDataType {}
