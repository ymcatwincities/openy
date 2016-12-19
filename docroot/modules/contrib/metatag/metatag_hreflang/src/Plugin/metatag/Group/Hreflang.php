<?php

namespace Drupal\metatag_hreflang\Plugin\metatag\Group;

use \Drupal\metatag\Plugin\metatag\Group\GroupBase;

/**
 * Provides a plugin for the 'Alternative language links (hreflang)' meta tag group.
 *
 * @MetatagGroup(
 *   id = "hreflang",
 *   label = @Translation("Alternative language links (hreflang)"),
 *   description = @Translation("These meta tags are designed to point visitors to versions of the current page in other languages. It is recommended to use the default ""[node:url-LANGCODE]"" tokens for the hreflang values, they will only be output if a translation exists for that locale. Also, it is suggested to use the ""[node:source:url]"" token for the default locale."),
 *   weight = 60,
 * )
 */
class Hreflang extends GroupBase {
  // Nothing here yet. Just a placeholder class for a plugin.
}
