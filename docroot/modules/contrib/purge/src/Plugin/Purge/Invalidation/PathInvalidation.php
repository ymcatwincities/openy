<?php

namespace Drupal\purge\Plugin\Purge\Invalidation;

use Drupal\purge\Plugin\Purge\Invalidation\InvalidationInterface;
use Drupal\purge\Plugin\Purge\Invalidation\InvalidationBase;
use Drupal\purge\Plugin\Purge\Invalidation\Exception\InvalidExpressionException;

/**
 * Describes path based invalidation, e.g. "news/article-1".
 *
 * @PurgeInvalidation(
 *   id = "path",
 *   label = @Translation("Path"),
 *   description = @Translation("Invalidates by path."),
 *   examples = {"news/article-1"},
 *   expression_required = TRUE,
 *   expression_can_be_empty = TRUE,
 *   expression_must_be_string = TRUE
 * )
 */
class PathInvalidation extends InvalidationBase implements InvalidationInterface {

  /**
   * {@inheritdoc}
   */
  public function validateExpression($wildcard_check = TRUE) {
    parent::validateExpression();
    if ($wildcard_check && (strpos($this->expression, '*') !== FALSE)) {
      throw new InvalidExpressionException($this->t('Path invalidations should not contain asterisks.'));
    }
    if ($wildcard_check && $this->expression === '*') {
      throw new InvalidExpressionException($this->t('Path invalidations cannot be "*".'));
    }
    if (strpos($this->expression, ' ') !== FALSE) {
      throw new InvalidExpressionException($this->t('Path invalidations cannot contain spaces, use %20 instead.'));
    }
    if (strpos($this->expression, '/') === 0) {
      throw new InvalidExpressionException($this->t('Path invalidations cannot start with slashes.'));
    }
  }

}
