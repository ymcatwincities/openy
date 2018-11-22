<?php

namespace Drupal\acquia_purge\Plugin\Purge\TagsHeader;

use Drupal\purge\Plugin\Purge\TagsHeader\TagsHeaderInterface;
use Drupal\purge\Plugin\Purge\TagsHeader\TagsHeaderBase;
use Drupal\acquia_purge\Hash;

/**
 * Exports the X-Acquia-Purge-Tags header.
 *
 * @PurgeTagsHeader(
 *   id = "acquiapurgetagsheader",
 *   header_name = "X-Acquia-Purge-Tags",
 * )
 */
class AcquiaPurgeTagsHeader extends TagsHeaderBase implements TagsHeaderInterface {

  /**
   * {@inheritdoc}
   */
  public function getValue(array $tags) {
    return implode(' ', Hash::cacheTags($tags));
  }

}
