<?php

namespace Drupal\social_feed_fetcher\Plugin\SocialDataProvider;


use Drupal\social_feed_fetcher\Annotation\SocialDataProvider;
use Drupal\social_feed_fetcher\SocialDataProviderPluginBase;
use Facebook\Facebook;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class FacebookDataProvider
 *
 * @package Drupal\social_feed_fetcher\Plugin\SocialDataProvider
 *
 * @SocialDataProvider(
 *   id = "facebook",
 *   label = @Translation("Facebook data provider")
 * )
 */
class FacebookDataProvider extends SocialDataProviderPluginBase {

  /**
   * Field names to retrieve from Facebook.
   *
   * @var array
   */
  protected $fields = [
    'link',
    'message',
    'created_time',
    'permalink_url',
    'picture{url}',
    'type',
    'attachments'
  ];


  /**
   * Facebook Client.
   *
   * @var \Facebook\Facebook
   */
  private $facebook;

  /**
   * {@inheritdoc}
   * @throws \Facebook\Exceptions\FacebookSDKException
   */
  public function setClient() {
    if (NULL === $this->facebook) {
      $this->facebook = new Facebook([
        'app_id'                => $this->config->get('fb_app_id'),
        'app_secret'            => $this->config->get('fb_secret_key'),
        'default_graph_version' => 'v2.10',
        'default_access_token'  => $this->defaultAccessToken(),
      ]);
    }
  }


  /**
   * Fetch Facebook posts from a given feed.
   *
   * @param int $num_posts
   *   The number of posts to return.
   *
   * @return array
   *   An array of Facebook posts.
   *
   * @throws \Facebook\Exceptions\FacebookSDKException
   */
  public function getPosts($num_posts = 10) {
    $page_name = $this->config->get('fb_page_name');
    $post_types = $this->config->get('fb_post_type');
    $posts      = [];
    $post_count = 0;
    $url        = $page_name . $this->getFacebookFeedUrl($num_posts);
    do {
      $response = $this->facebook->get($url);
      // Ensure not caught in an infinite loop if there's no next page.
      $url = NULL;
      if ($response->getHttpStatusCode() == Response::HTTP_OK) {
        $data       = json_decode($response->getBody(), TRUE);
        $posts      = array_merge($this->extractFacebookFeedData($post_types, $data['data']), $posts);
        $post_count = count($posts);
        if ($post_count < $num_posts && isset($data['paging']['next'])) {
          $url = $data['paging']['next'];
        }
      }
    } while ($post_count < $num_posts || NULL != $url);
    return array_slice($posts, 0, $num_posts);
  }

  /**
   * Extract information from the Facebook feed.
   *
   * @param string $post_types
   *   The type of posts to extract.
   * @param array $data
   *   An array of data to extract information from.
   *
   * @return array
   *   An array of posts.
   */
  protected function extractFacebookFeedData($post_types, array $data) {
    $posts = array_map(function ($post) {
      $image = NULL;
      if (!empty($post['attachments']['data'][0]['media']['image']['src'])) {
        $image = $post['attachments']['data'][0]['media']['image']['src'];
      }

      if (!empty($post['attachments']['data'][0]['subattachments']['data'][0]['media']['image']['src'])) {
        $image = $post['attachments']['data'][0]['subattachments']['data'][0]['media']['image']['src'];
      }

      $post += [
        'image' => $image
      ];

      return $post;
    }, $data);

    // Filtering needed.
    if (TRUE == is_string($post_types)) {
      $posts = array_filter($posts, function ($post) use ($post_types) {
        return $post['type'] === $post_types;
      });
      return $posts;
    }
    return $posts;
  }

  /**
   * Generate the Facebook access token.
   *
   * @return string
   *   The access token.
   */
  protected function defaultAccessToken() {
    return $this->config->get('fb_app_id') . '|' . $this->config->get('fb_secret_key');
  }

  /**
   * Create the Facebook feed URL.
   *
   * @param int $num_posts
   *   The number of posts to return.
   *
   * @return string
   *   The feed URL with the appended fields to retrieve.
   */
  protected function getFacebookFeedUrl($num_posts) {
    return '/feed?fields=' . implode(',', $this->fields) . '&limit=' . $num_posts;
  }
}