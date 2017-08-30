<?php

namespace Drupal\metatag\Normalizer;

use Drupal\metatag\Plugin\Field\MetatagEntityFieldItemList;
use Drupal\serialization\Normalizer\NormalizerBase;

/**
 * Normalizes metatag into the viewed entity.
 */
class MetatagNormalizer extends NormalizerBase {

  /**
   * The interface or class that this Normalizer supports.
   *
   * @var string
   */
  protected $supportedInterfaceOrClass = 'Drupal\metatag\Plugin\Field\MetatagEntityFieldItemList';

  /**
   * {@inheritdoc}}
   *
   * @see metatag_get_tags_from_route();
   */
  public function normalize($field_item, $format = NULL, array $context = []) {

    $entity = $field_item->getEntity();

    $tags = metatag_get_tags_from_route($entity);

    foreach ($tags['#attached']['html_head'] as $tag) {
      $normalized['value'][$tag[1]] = $tag[0]['#attributes']['content'];
    }

    if (isset($context['langcode'])) {
      $normalized['lang'] = $context['langcode'];
    }

    return $normalized;
  }

  /**
   * {@inheritdoc}
   */
  public function supportsDenormalization($data, $type, $format = NULL) {
    return FALSE;
  }

}
