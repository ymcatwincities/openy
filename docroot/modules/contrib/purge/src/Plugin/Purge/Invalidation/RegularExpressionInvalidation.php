<?php

namespace Drupal\purge\Plugin\Purge\Invalidation;

use Drupal\purge\Plugin\Purge\Invalidation\InvalidationInterface;
use Drupal\purge\Plugin\Purge\Invalidation\InvalidationBase;
use Drupal\purge\Plugin\Purge\Invalidation\Exception\InvalidExpressionException;

/**
 * Describes invalidation by regular expression, e.g.: '\.(jpg|jpeg|css|js)$'.
 *
 * @PurgeInvalidation(
 *   id = "regex",
 *   label = @Translation("Regular expression"),
 *   description = @Translation("Invalidates by regular expression."),
 *   examples = {"\.(jpg|jpeg|css|js)$"},
 *   expression_required = TRUE,
 *   expression_can_be_empty = FALSE,
 *   expression_must_be_string = TRUE
 * )
 */
class RegularExpressionInvalidation extends InvalidationBase implements InvalidationInterface {}
