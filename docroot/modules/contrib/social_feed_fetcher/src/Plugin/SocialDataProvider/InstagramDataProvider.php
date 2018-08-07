<?php

namespace Drupal\social_feed_fetcher\Plugin\SocialDataProvider;

use Drupal\social_feed_fetcher\SocialDataProviderPluginBase;
use MetzWeb\Instagram\Instagram;

/**
 * Class InstagramDataProvider
 *
 * @package Drupal\social_feed_fetcher\Plugin\SocialDataProvider
 *
 * @SocialDataProvider(
 *   id = "instagram",
 *   label = @Translation("Instagram data provider")
 * )
 */
class InstagramDataProvider extends SocialDataProviderPluginBase {

  /**
   * Instagram client.
   *
   * @var \MetzWeb\Instagram\Instagram
   */
  protected $instagram;


  /**
   * Set the Instagram client.
   *
   * @throws \Exception
   */
  public function setClient() {
    if (NULL === $this->instagram) {
      $this->instagram = new Instagram($this->config->get('in_client_id'));
      $this->instagram->setAccessToken($this->config->get('in_access_token'));
    }
  }

  /**
   * Retrieve user's posts.
   *
   * @param int $numPosts
   *   Number of posts to get.
   *
   * @return array
   *   An array of stdClass posts.
   */
  public function getPosts($numPosts) {
    $resolution = $this->config->get('in_picture_resolution');
    $posts    = [];
    $response = $this->instagram->getUserMedia('self', $numPosts);
    if (isset($response->data)) {
      $posts = array_map(function ($post) use ($resolution) {
        $type = $this->getMediaArrayKey($post->type);
        return [
          'raw'       => $post,
          'media_url' => isset($post->{$type}->{$resolution}) ? $post->{$type}->{$resolution}->url : '',
          'type'      => $post->type,
        ];
      }, $response->data);
    }
    return $posts;
  }

  /**
   * Retrieve the array key to fetch the post media url.
   *
   * @param string $type
   *   The post type.
   *
   * @return string
   *   The array key to fetch post media url.
   */
  protected function getMediaArrayKey($type) {
    $mediaType = 'images';
    if ($type === 'video') {
      $mediaType = 'videos';
    }
    return $mediaType;
  }

}
