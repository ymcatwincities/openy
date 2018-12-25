<?php

namespace Drupal\purge_tagsheader_test\Plugin\Purge\TagsHeader;

use Drupal\purge\Plugin\Purge\TagsHeader\TagsHeaderInterface;
use Drupal\purge\Plugin\Purge\TagsHeader\TagsHeaderBase;

/**
 * Test header C.
 *
 * @PurgeTagsHeader(
 *   id = "c",
 *   header_name = "Header-C",
 * )
 */
class CTagsHeader extends TagsHeaderBase implements TagsHeaderInterface {}
