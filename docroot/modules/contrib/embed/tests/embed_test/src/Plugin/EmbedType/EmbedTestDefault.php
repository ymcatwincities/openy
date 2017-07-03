<?php

/**
 * @file
 * Contains \Drupal\embed_test\Plugin\EmbedType\EmbedTestDefault.
 */

namespace Drupal\embed_test\Plugin\EmbedType;

use Drupal\embed\EmbedType\EmbedTypeBase;

/**
 * Default test embed type.
 *
 * @EmbedType(
 *   id = "embed_test_default",
 *   label = @Translation("Default")
 * )
 */
class EmbedTestDefault extends EmbedTypeBase {

  /**
   * {@inheritdoc}
   */
  public function getDefaultIconUrl() {
    return '';
  }

}
