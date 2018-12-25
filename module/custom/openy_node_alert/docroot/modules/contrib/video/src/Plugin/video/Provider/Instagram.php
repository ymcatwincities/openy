<?php

/**
 * @file
 * Contains \Drupal\video\Plugin\video\Provider\Instagram.
 */

namespace Drupal\video\Plugin\video\Provider;

use Drupal\video\ProviderPluginBase;

/**
 * @VideoEmbeddableProvider(
 *   id = "instagram",
 *   label = @Translation("Instagram"),
 *   description = @Translation("Instagram Video Provider"),
 *   regular_expressions = {
 *     "@^.*?instagram\.com\/p\/(?<id>(.*?))[\/]?$@i",
 *   },
 *   mimetype = "video/instagram",
 *   stream_wrapper = "instagram"
 * )
 */
class Instagram extends ProviderPluginBase {
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
        'height' => '100%',
        'frameborder' => '0',
        'allowfullscreen' => 'allowfullscreen',
        'src' => sprintf('//instagram.com/p/%s/embed/?autoplay=%d', $data['id'], $settings['autoplay']),
      ],
      '0' => array(
        '#type' => 'html_tag',
        '#tag' => 'script',
        '#attributes' => array(
             'type' => 'text/javascript',
             'src' => '//platform.instagram.com/en_US/embeds.js',
             'async',
             'defer'
        ),
        '#value' => '',
      ),
    ];
  }
  
  /**
   * {@inheritdoc}
   */
  public function getRemoteThumbnailUrl() {
    $data = $this->getVideoMetadata();
    return 'http://instagr.am/p/' . $data['id'] . '/media/?size=l';
  }
}