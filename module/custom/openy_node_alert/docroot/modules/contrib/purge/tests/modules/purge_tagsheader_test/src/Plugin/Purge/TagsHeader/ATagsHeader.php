<?php

namespace Drupal\purge_tagsheader_test\Plugin\Purge\TagsHeader;

use Drupal\purge\Plugin\Purge\TagsHeader\TagsHeaderInterface;
use Drupal\purge\Plugin\Purge\TagsHeader\TagsHeaderBase;

/**
 * Test header A.
 *
 * @PurgeTagsHeader(
 *   id = "a",
 *   header_name = "Header-A",
 * )
 */
class ATagsHeader extends TagsHeaderBase implements TagsHeaderInterface {}
