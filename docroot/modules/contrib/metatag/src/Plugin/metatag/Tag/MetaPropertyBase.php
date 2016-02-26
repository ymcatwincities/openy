<?php
/**
 * @file
 * Contains \Drupal\metatag\Plugin\metatag\Tag\MetaPropertyBase.
 */

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

      $element = array(
        '#tag' => 'meta',
        '#attributes' => array(
          'property' => $this->name,
          'content' => $value,
        )
      );
    }

    return $element;
  }
}
