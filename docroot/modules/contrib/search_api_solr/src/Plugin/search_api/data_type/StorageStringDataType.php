<?php

namespace Drupal\search_api_solr\Plugin\search_api\data_type;

use Drupal\search_api\Plugin\search_api\data_type\StringDataType;

/**
 * Provides a storage-only string data type.
 *
 * @SearchApiDataType(
 *   id = "solr_string_storage",
 *   label = @Translation("Storage-only"),
 *   description = @Translation("A storage-only field. You can store any string and retrieve it from the index but you can't search through it."),
 *   fallback_type = "string",
 *   prefix = "z"
 * )
 */
class StorageStringDataType extends StringDataType {}
