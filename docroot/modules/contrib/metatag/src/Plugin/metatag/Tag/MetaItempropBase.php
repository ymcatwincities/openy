<?php

/**
 * This base plugin allows "itemprop"-style meta tags, e.g. Google plus tags, to
 * be further customized.
 */

namespace Drupal\metatag\Plugin\metatag\Tag;

abstract class MetaItempropBase extends MetaNameBase {
  /**
   * Display the meta tag.
   */
  public function output() {
    if (empty($this->value)) {
      // If there is no value, we don't want a tag output.
      $element = '';
    }
    else {
      // Parse out the image URL, if needed.
      $value = $this->parseImageURL();

      $element = [
        '#tag' => 'meta',
        '#attributes' => [
          'itemprop' => $this->name,
          'content' => $value,
        ]
      ];
    }

    return $element;
  }
}
