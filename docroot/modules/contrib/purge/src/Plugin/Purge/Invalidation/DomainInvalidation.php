<?php

namespace Drupal\purge\Plugin\Purge\Invalidation;

use Drupal\purge\Plugin\Purge\Invalidation\InvalidationInterface;
use Drupal\purge\Plugin\Purge\Invalidation\InvalidationBase;
use Drupal\purge\Plugin\Purge\Invalidation\Exception\InvalidExpressionException;

/**
 * Describes an entire domain to be invalidated.
 *
 * @PurgeInvalidation(
 *   id = "domain",
 *   label = @Translation("Domain"),
 *   description = @Translation("Invalidates an entire domain name."),
 *   examples = {"www.site.com", "site.com"},
 *   expression_required = TRUE,
 *   expression_can_be_empty = FALSE,
 *   expression_must_be_string = TRUE
 * )
 */
class DomainInvalidation extends InvalidationBase implements InvalidationInterface {}
