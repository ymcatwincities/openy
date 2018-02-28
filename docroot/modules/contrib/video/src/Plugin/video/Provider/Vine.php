<?php

/**
 * @file
 * Contains \Drupal\video\Plugin\video\Provider\Vine.
 */

namespace Drupal\video\Plugin\video\Provider;

use Drupal\video\ProviderPluginBase;

/**
 * @VideoEmbeddableProvider(
 *   id = "vine",
 *   label = @Translation("Vine"),
 *   description = @Translation("Vine Video Provider"),
 *   regular_expressions = {
 *     "@(?<=vine.co/v/)(?<id>[0-9A-Za-z]+)@i",
 *   },
 *   mimetype = "video/vine",
 *   stream_wrapper = "vine"
 * )
 */
class Vine extends ProviderPluginBase {
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
        'src' => sprintf('https://vine.co/v/%s/embed/simple?autoPlay=%d', $data['id'], $settings['autoplay']),
      ],
    ];
  }
  
  /**
   * {@inheritdoc}
   */
  public function getRemoteThumbnailUrl() {
    $data = $this->getVideoMetadata();
    $id = $data['id'];
    $vine = file_get_contents("http://vine.co/v/{$id}");
      preg_match('/property="og:image" content="(.*?)"/', $vine, $matches);
      return ($matches[1]) ? $matches[1] : false;
  }
}