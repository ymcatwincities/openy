<?php

namespace Drupal\webform\Twig;

use Drupal\Component\Utility\Html;
use Drupal\Core\Entity\EntityInterface;
use Drupal\webform\Utility\WebformHtmlHelper;
use Drupal\webform\WebformTokenManagerInterface;

/**
 * Twig extension with some useful functions and filters.
 */
class TwigExtension extends \Twig_Extension {

  /**
   * The webform token manager.
   *
   * @var \Drupal\webform\WebformTokenManagerInterface
   */
  protected $tokenManager;

  /**
   * Constructs a TwigExtension object.
   *
   * @param \Drupal\webform\WebformTokenManagerInterface $token_manager
   *   The webform token manager.
   */
  public function __construct(WebformTokenManagerInterface $token_manager) {
    $this->tokenManager = $token_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function getFunctions() {
    return [
      new \Twig_SimpleFunction('webform_token', [$this, 'webformToken']),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return 'webform';
  }

  /**
   * Replace tokens in text.
   *
   * @param string|array $token
   *   A string of text that may contain tokens.
   * @param \Drupal\Core\Entity\EntityInterface|null $entity
   *   A Webform or Webform submission entity.
   * @param array $data
   *   (optional) An array of keyed objects.
   * @param array $options
   *   (optional) A keyed array of settings and flags to control the token
   *   replacement process.
   *
   * @return string|array
   *   Text or array with tokens replaced.
   *
   * @see \Drupal\Core\Utility\Token::replace
   */
  public function webformToken($token, EntityInterface $entity = NULL, array $data = [], array $options = []) {
    // Allow the webform_token function to be tested during validation without
    // a valid entity.
    if (!$entity) {
      return $token;
    }

    $value = $this->tokenManager->replace($token, $entity, $data, $options);

    // Must decode HTML entities which are going to re-encoded.
    $value = Html::decodeEntities($value);

    return (WebformHtmlHelper::containsHtml($value)) ? ['#markup' => $value] : $value;
  }

}
