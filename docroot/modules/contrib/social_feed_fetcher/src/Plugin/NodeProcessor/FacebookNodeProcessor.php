<?php

namespace Drupal\social_feed_fetcher\Plugin\NodeProcessor;

use Drupal\node\Entity\Node;
use Drupal\social_feed_fetcher\PluginNodeProcessorPluginBase;

/**
 * Class FacebookNodeProcessor
 *
 * @package Drupal\social_feed_fetcher\Plugin\NodeProcessor
 *
 * @PluginNodeProcessor(
 *   id = "facebook_processor",
 *   label = @Translation("Facebook node processor")
 * )
 */
class FacebookNodeProcessor extends PluginNodeProcessorPluginBase {

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function processItem($source, $data_item) {
    if (!$this->isPostIdExist($data_item['id'])) {
      $node = $this->entityStorage->create([
        'type' => 'social_post',
        'title' => 'Post ID: ' . $data_item['id'],
        'field_platform' => ucwords($source),
        'field_id' => $data_item['id'],
        'field_post' => [
          'value' => social_feed_fetcher_linkify(html_entity_decode($data_item['message'])),
          'format' => $this->config->get('formats.post_format'),
        ],
        'field_link' => [
          'uri' => $data_item['link'],
          'title' => '',
          'options' => [],
        ],
        'field_sp_image' => [
          'target_id' => social_feed_fetcher_save_file($data_item['image'], 'public://facebook/'),
        ],
        'field_posted' => [
          'value' => $this->setPostTime($data_item['created_time']),
        ],
      ]);
      return $node->save();
    }
    return FALSE;
  }

}
