<?php

namespace Drupal\purge\Plugin\Purge\Invalidation;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Url;
use Drupal\purge\Plugin\Purge\Invalidation\InvalidationInterface;
use Drupal\purge\Plugin\Purge\Invalidation\InvalidationBase;
use Drupal\purge\Plugin\Purge\Invalidation\Exception\InvalidExpressionException;

/**
 * Describes URL based invalidation, e.g. "http://site.com/node/1".
 *
 * @PurgeInvalidation(
 *   id = "url",
 *   label = @Translation("Url"),
 *   description = @Translation("Invalidates by URL."),
 *   examples = {"http://site.com/node/1"},
 *   expression_required = TRUE,
 *   expression_can_be_empty = FALSE
 * )
 */
class UrlInvalidation extends InvalidationBase implements InvalidationInterface {

  /**
   * Url object (absolute) or string describing Uri of what needs invalidation.
   *
   * @var \Drupal\Core\Url|string
   */
  protected $expression;

  /**
   * The (absolute) URL object that this invalidation object describes.
   *
   * @var \Drupal\Core\Url
   */
  protected $url;

  /**
   * Get the URL object.
   *
   * @return \Drupal\Core\Url
   */
  public function getUrl() {
    if (!is_null($this->url)) {
      return $this->url;
    }
    if (is_string($this->expression)) {
      try {
        $this->url = Url::fromUri($this->expression, ['absolute' => TRUE]);
      }
      catch (\InvalidArgumentException $e) {
        throw new InvalidExpressionException($e->getMessage());
      }
    }
    elseif ($this->expression instanceof Url) {
      $this->url = $this->expression;
      $this->url->setAbsolute();
    }
    else {
      throw new InvalidExpressionException($this->t('Url invalidations require either a full URL string or a \Drupal\Core\Url object.'));
    }
    return $this->url;
  }

  /**
   * {@inheritdoc}
   */
  public function validateExpression($wildcard_check = TRUE) {
    parent::validateExpression();

    // Set $this->url by calling getUrl and do some more validation.
    $url = $this->getUrl()->toString();
    if ((strpos($url, 'http') === FALSE) && (strpos($url, 'https') === FALSE)) {
      throw new InvalidExpressionException($this->t('Scheme unsupported!'));
    }
    if (!UrlHelper::isValid($url, TRUE)) {
      throw new InvalidExpressionException($this->t('The URL is invalid.'));
    }
    if ($wildcard_check && (strpos($url, '*') !== FALSE)) {
      throw new InvalidExpressionException($this->t('URL invalidations should not contain asterisks!'));
    }
    if (strpos($url, ' ') !== FALSE) {
      throw new InvalidExpressionException($this->t('URL invalidations cannot contain spaces, use %20 instead.'));
    }

    // @see \Drupal\purge\Plugin\Purge\Invalidation\WildcardUrl
    return $url;
  }

}
