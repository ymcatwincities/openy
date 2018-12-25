<?php

namespace Drupal\purge\Plugin\Purge\Invalidation;

use Drupal\purge\Plugin\Purge\Invalidation\PathInvalidation;
use Drupal\purge\Plugin\Purge\Invalidation\InvalidationInterface;
use Drupal\purge\Plugin\Purge\Invalidation\Exception\InvalidExpressionException;

/**
 * Describes wildcardpath based invalidation, e.g. "news/*".
 *
 * @PurgeInvalidation(
 *   id = "wildcardpath",
 *   label = @Translation("Path wildcard"),
 *   description = @Translation("Invalidates by path."),
 *   examples = {"news/*"},
 *   expression_required = TRUE,
 *   expression_can_be_empty = FALSE,
 *   expression_must_be_string = TRUE
 * )
 */
class WildcardPathInvalidation extends PathInvalidation implements InvalidationInterface {

  /**
   * {@inheritdoc}
   */
  public function validateExpression() {
    parent::validateExpression(FALSE);
    if (strpos($this->expression, '*') === FALSE) {
      throw new InvalidExpressionException($this->t('Wildcard invalidations should contain an asterisk.'));
    }
  }

}
