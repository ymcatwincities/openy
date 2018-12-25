<?php

/**
 * @file
 * Contains \Drupal\video\Plugin\video\Provider\YouTube.
 */

namespace Drupal\video\Plugin\video\Provider;

use Drupal\video\ProviderPluginBase;
use GuzzleHttp\Exception\ClientException;

/**
 * @VideoEmbeddableProvider(
 *   id = "youtube",
 *   label = @Translation("YouTube"),
 *   description = @Translation("YouTube Video Provider"),
 *   regular_expressions = {
 *     "@(?:(?<protocol>http|https):)?//(?:www\.)?youtube(?<cookie>-nocookie)?\.com/embed/(?<id>[a-z0-9_-]+)@i",
 *     "@(?:(?<protocol>http|https):)?//(?:www\.)?youtube(?<cookie>-nocookie)?\.com/v/(?<id>[a-z0-9_-]+)@i",
 *     "@(?:(?<protocol>http|https):)?//(?:www\.)?youtube(?<cookie>-nocookie)?\.com/watch(\?|\?.*\&)v=(?<id>[a-z0-9_-]+)@i",
 *     "@(?:(?<protocol>http|https):)?//youtu(?<cookie>-nocookie)?\.be/(?<id>[a-z0-9_-]+)@i"
 *   },
 *   mimetype = "video/youtube",
 *   stream_wrapper = "youtube"
 * )
 */
class YouTube extends ProviderPluginBase {

  /**
   * {@inheritdoc}
   */
  public function renderEmbedCode($settings) {
    $file = $this->getVideoFile();
    $data = $this->getVideoMetadata();
    return [
      '#type' => 'html_tag',
      '#tag' => 'iframe',
      '#attributes' => [
        'width' => $settings['width'],
        'height' => $settings['height'],
        'frameborder' => '0',
        'allowfullscreen' => 'allowfullscreen',
        'src' => sprintf('https://www.youtube.com/embed/%s?autoplay=%d&start=%d', $data['id'], $settings['autoplay'], NULL),
      ],
    ];
  }
  
  /**
   * {@inheritdoc}
   */
  public function getRemoteThumbnailUrl() {
    $data = $this->getVideoMetadata();
    $url = '';

    // Sometimes the video has not every version of thumbnails. Guzzle throws
    // exception at that time. Now catch it, and try download another size of
    // thumbnail.
    $img_urls = [
      'http://img.youtube.com/vi/' . $data['id'] . "/maxresdefault.jpg",
      'http://img.youtube.com/vi/' . $data['id'] . "/hqdefault.jpg",
      'http://img.youtube.com/vi/' . $data['id'] . "/default.jpg",
    ];

    foreach ($img_urls as $url) {
      try {
        $this->httpClient->request('GET', $url);
      }
      catch (ClientException $e) {
        $e->getResponse();
        if ($e->getCode() != 404) {
          throw $e;
        }
      }
    }

    return $url;
  }
}
