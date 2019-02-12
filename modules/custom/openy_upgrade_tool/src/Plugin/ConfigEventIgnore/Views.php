<?php

namespace Drupal\openy_upgrade_tool\Plugin\ConfigEventIgnore;

use Drupal\openy_upgrade_tool\ConfigEventIgnoreBase;

/**
 * Provides config event ignore rules for views config type.
 *
 * @ConfigEventIgnore(
 *   id="views_ignore",
 *   label = @Translation("Views"),
 *   type="view",
 *   weight=0
 * )
 */
class Views extends ConfigEventIgnoreBase {

  /**
   * {@inheritdoc}
   */
  public function getRules() {
    return [
      // Ignore any cache_metadata changes.
      // Path to cache_metadata key can be different, so we need to use
      // regexp to describe the rule.
      // Example - 'display.page_1.cache_metadata.contexts'.
      // Example - 'display.default.cache_metadata.tags'.
      [
        'value' => '/^display\..+\.cache_metadata.*/',
        'operator' => self::REGEXP_OPERATOR,
      ],
    ];
  }

}
