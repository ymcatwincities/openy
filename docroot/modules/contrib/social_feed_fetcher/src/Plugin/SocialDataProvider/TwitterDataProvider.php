<?php

namespace Drupal\social_feed_fetcher\Plugin\SocialDataProvider;


use Abraham\TwitterOAuth\TwitterOAuth;
use Drupal\social_feed_fetcher\SocialDataProviderPluginBase;

/**
 * Class TwitterDataProvider
 *
 * @package Drupal\social_feed_fetcher\Plugin\SocialDataProvider
 *
 * @SocialDataProvider(
 *   id = "twitter",
 *   label = @Translation("Twitter data provider")
 * )
 */
class TwitterDataProvider extends SocialDataProviderPluginBase {

  /**
   * Twitter OAuth client.
   *
   * @var \Abraham\TwitterOAuth\TwitterOAuth
   */
  protected $twitter;

  /**
   * Retrieve Tweets from the given accounts home page.
   *
   * @param int $count
   *   The number of posts to return.
   *
   * @return array
   *   An array of posts.
   */
  public function getPosts($count) {
    return $this->twitter->get('statuses/home_timeline', ['count' => $count]);
  }

  /**
   * Set the Twitter client.
   */
  public function setClient() {
    if (NULL === $this->twitter) {
      $this->twitter = new TwitterOAuth(
        $this->config->get('tw_consumer_key'),
        $this->config->get('tw_consumer_secret'),
        $this->config->get('tw_access_token'),
        $this->config->get('tw_access_token_secret')
      );
    }
  }
}