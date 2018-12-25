<?php

namespace Drupal\embed_test\Plugin\Filter;

use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;
use Drupal\node\Entity\Node;

/**
 * Renders a full node view from an embed code like node:NID.
 *
 * @Filter(
 *   id = "embed_test_node",
 *   title = @Translation("Test Node"),
 *   description = @Translation("Embeds nodes using node:NID embed codes."),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_MARKUP_LANGUAGE,
 * )
 */
class EntityEmbedByID extends FilterBase {

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {
    $result = new FilterProcessResult($text);

    $matches = [];
    preg_match_all('/node:([0-9]+)/', $text, $matches);

    foreach ($matches[0] as $i => $search) {
      $node = Node::load($matches[1][$i]);
      $build = \Drupal::entityTypeManager()->getViewBuilder('node')->view($node);
      $replace = \Drupal::service('renderer')->render($build);
      $text = str_replace($search, $replace, $text);
    }

    $result->setProcessedText($text);
    return $result;
  }

}
