<?php

namespace Drupal\purge_tagsheader_test\Plugin\Purge\TagsHeader;

use Drupal\purge\Plugin\Purge\TagsHeader\TagsHeaderInterface;
use Drupal\purge\Plugin\Purge\TagsHeader\TagsHeaderBase;

/**
 * Test header B.
 *
 * @PurgeTagsHeader(
 *   id = "b",
 *   header_name = "Header-B",
 * )
 */
class BTagsHeader extends TagsHeaderBase implements TagsHeaderInterface {}
