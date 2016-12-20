<?php

/**
 * This base plugin allows "property"-style meta tags, e.g. Open Graph tags, to
 * be further customized.
 */

namespace Drupal\metatag\Plugin\metatag\Tag;

abstract class MetaPropertyBase extends MetaNameBase {
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

      // If tag must be secure, convert all http:// to https://.
      if ($this->secure() && strpos($value, 'http://') !== FALSE) {
        $value = str_replace('http://', 'https://', $value);
      }

      $element = [
        '#tag' => 'meta',
        '#attributes' => [
          'property' => $this->name,
          'content' => $value,
        ]
      ];
    }

    return $element;
  }
}
