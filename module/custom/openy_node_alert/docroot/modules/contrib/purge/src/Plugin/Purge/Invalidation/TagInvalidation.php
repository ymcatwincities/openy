<?php

namespace Drupal\purge\Plugin\Purge\Invalidation;

use Drupal\purge\Plugin\Purge\Invalidation\InvalidationInterface;
use Drupal\purge\Plugin\Purge\Invalidation\InvalidationBase;
use Drupal\purge\Plugin\Purge\Invalidation\Exception\InvalidExpressionException;

/**
 * Describes invalidation by Drupal cache tag, e.g.: 'user:1', 'menu:footer'.
 *
 * @PurgeInvalidation(
 *   id = "tag",
 *   label = @Translation("Tag"),
 *   description = @Translation("Invalidates by Drupal cache tag."),
 *   examples = {"node:1", "menu:footer"},
 *   expression_required = TRUE,
 *   expression_can_be_empty = FALSE,
 *   expression_must_be_string = TRUE,
 * )
 */
class TagInvalidation extends InvalidationBase implements InvalidationInterface {

  /**
   * {@inheritdoc}
   */
  public function validateExpression() {
    parent::validateExpression();
    if (strpos($this->expression, '*') !== FALSE) {
      throw new InvalidExpressionException($this->t('Tag invalidations cannot contain asterisks.'));
    }
  }

}
