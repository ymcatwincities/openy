<?php

namespace Drupal\social_feed_fetcher\Plugin\NodeProcessor;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\social_feed_fetcher\PluginNodeProcessorPluginBase;

/**
 * Class TwitterNodeProcessor
 *
 * @package Drupal\social_feed_fetcher\Plugin\NodeProcessor
 *
 * @PluginNodeProcessor(
 *   id = "twitter_processor",
 *   label = @Translation("Twitter node processor")
 * )
 */
class TwitterNodeProcessor extends PluginNodeProcessorPluginBase {

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function processItem($source, $data_item) {
    if (!$this->isPostIdExist($data_item->id)) {
      $node = $this->entityStorage->create([
        'type' => 'social_post',
        'title' => 'Post ID: ' . $data_item->id,
        'field_platform' => ucwords($source),
        'field_id' => $data_item->id,
        'field_post' => [
          'value' => social_feed_fetcher_linkify(html_entity_decode($data_item->text)),
          'format' => $this->config->get('formats.post_format'),
        ],
        'field_link' => [
          'uri' => $data_item->entities->urls[0]->url,
          'title' => '',
          'options' => [],
        ],
        'field_sp_image' => [
          'target_id' => social_feed_fetcher_save_file($data_item->entities->media[0]->media_url_https, 'public://twitter/'),
        ],
        'field_posted' => $this->setPostTime($data_item->created_at),
      ]);
      return $node->save();
    }
    return FALSE;
  }

}