<?php

/**
 * This base plugin allows "http-equiv"-style meta tags, e.g. the content
 * language meta tag, to be further customized.
 */

namespace Drupal\metatag\Plugin\metatag\Tag;

abstract class MetaHttpEquivBase extends MetaNameBase {
  /**
   * Display the meta tag.
   */
  public function output() {
    if (empty($this->value)) {
      // If there is no value, we don't want a tag output.
      $element = '';
    }
    else {
      $element = [
        '#tag' => 'meta',
        '#attributes' => [
          'http-equiv' => $this->name,
          'content' => $this->value(),
        ]
      ];
    }

    return $element;
  }
}
