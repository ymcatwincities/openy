<?php

namespace Drupal\search_api_solr\Plugin\search_api\data_type;

use Drupal\search_api\Plugin\search_api\data_type\TextDataType;

/**
 * Provides a full text data type based on unmodified tokens.
 *
 * @SearchApiDataType(
 *   id = "solr_text_wstoken",
 *   label = @Translation("Fulltext Tokens"),
 *   description = @Translation("Full text field without any processing like stemming or stop word filters, just unmodified tokens from the text separated by white spaces."),
 *   fallback_type = "text",
 *   prefix = "tw"
 * )
 */
class WhiteSpaceTokensTextDataType extends TextDataType {}
