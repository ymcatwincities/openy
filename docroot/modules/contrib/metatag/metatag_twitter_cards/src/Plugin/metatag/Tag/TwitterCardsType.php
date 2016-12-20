<?php

namespace Drupal\metatag_twitter_cards\Plugin\metatag\Tag;

use Drupal\metatag\Plugin\metatag\Tag\MetaNameBase;

/**
 * The Twitter Cards Type-tag.
 *
 * @MetatagTag(
 *   id = "twitter_cards_type",
 *   label = @Translation("Twitter card type"),
 *   description = @Translation("Notes: no other fields are required for a Summary card, a Photo card requires the 'image' field, a Media player card requires the 'title', 'description', 'media player URL', 'media player width', 'media player height' and 'image' fields, a Summary Card with Large Image card requires the 'Summary' field and the 'image' field, a Gallery Card requires all the 'Gallery Image' fields, an App Card requires the 'iPhone app ID' field, the 'iPad app ID' field and the 'Google Play app ID' field, a Product Card requires the 'description' field, the 'image' field, the 'Label 1' field, the 'Data 1' field, the 'Label 2' field and the 'Data 2' field."),
 *   name = "twitter:card",
 *   group = "twitter_cards",
 *   weight = 1,
 *   type = "string",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class TwitterCardsType extends MetaNameBase {

  /**
   * {@inheritdoc}
   */
  public function form(array $element = []) {
    $form = [
      '#type' => 'select',
      '#title' => $this->label(),
      '#description' => $this->description(),
      '#options' => [
        'summary' => t('Summary Card'),
        'summary_large_image' => t('Summary Card with large image'),
        'photo' => t('Photo Card'),
        'gallery' => t('Gallery Card'),
        'app' => t('App Card'),
        'player' => t('Player Card'),
        'product' => t('Product Card'),
      ],
      '#empty_option' => t('- None -'),
      '#empty_value' => '',
      '#default_value' => $this->value(),
      '#required' => isset($element['#required']) ? $element['#required'] : FALSE,
      '#element_validate' => [[get_class($this), 'validateTag']],
    ];

    return $form;
  }

}
