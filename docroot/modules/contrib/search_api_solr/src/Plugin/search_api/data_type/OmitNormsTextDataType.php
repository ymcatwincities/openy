<?php

namespace Drupal\search_api_solr\Plugin\search_api\data_type;

use Drupal\search_api\Plugin\search_api\data_type\TextDataType;

/**
 * Provides a full text data type which omit norms.
 *
 * @SearchApiDataType(
 *   id = "solr_text_omit_norms",
 *   label = @Translation("Fulltext Omit norms"),
 *   description = @Translation("Full text field which omits norms."),
 *   fallback_type = "text",
 *   prefix = "to"
 * )
 */
class OmitNormsTextDataType extends TextDataType {}
