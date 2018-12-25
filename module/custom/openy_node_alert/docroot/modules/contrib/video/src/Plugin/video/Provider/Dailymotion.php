<?php

/**
 * @file
 * Contains \Drupal\video\Plugin\video\Provider\Dailymotion.
 */

namespace Drupal\video\Plugin\video\Provider;

use Drupal\video\ProviderPluginBase;

/**
 * @VideoEmbeddableProvider(
 *   id = "dailymotion",
 *   label = @Translation("Dailymotion"),
 *   description = @Translation("Dailymotion Video Provider"),
 *   regular_expressions = {
 *     "@dailymotion\.com/video/(?<id>[^/_]+)_@i",
 *   },
 *   mimetype = "video/dailymotion",
 *   stream_wrapper = "dailymotion"
 * )
 */
class Dailymotion extends ProviderPluginBase {
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
        'src' => sprintf('//www.dailymotion.com/embed/video/%s?autoPlay=$d', $data['id'], $settings['autoplay']),
      ],
    ];
  }
  
  /**
   * {@inheritdoc}
   */
  public function getRemoteThumbnailUrl() {
    $data = $this->getVideoMetadata();
    return 'http://www.dailymotion.com/thumbnail/video/' . $data['id'];
  }
}