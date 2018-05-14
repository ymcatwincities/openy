<?php

/**
 * @file
 * Contains \Drupal\video\Plugin\video\Provider\Facebook.
 */

namespace Drupal\video\Plugin\video\Provider;

use Drupal\video\ProviderPluginBase;

/**
 * @VideoEmbeddableProvider(
 *   id = "facebook",
 *   label = @Translation("Facebook"),
 *   description = @Translation("Facebook Video Provider"),
 *   regular_expressions = {
 *     "@^https?://www\.facebook\.com/.*(/videos/(?<id>\d+))@i",
 *     "@^https?://www\.facebook\.com/.*(video\.php\?v=(?<id>\d+))@i"
 *   },
 *   mimetype = "video/facebook",
 *   stream_wrapper = "facebook"
 * )
 */

class Facebook extends ProviderPluginBase {
  /**
   * {@inheritdoc}
   */
  public function renderEmbedCode($settings) {
    $file = $this->getVideoFile();
    $data = $this->getVideoMetadata();
    // @see https://developers.facebook.com/docs/plugins/embedded-video-player
    return [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '0' => array(
        '#type' => 'html_tag',
        '#tag' => 'script',
        '#value' => 'window.fbAsyncInit = function() {
	FB.init({
		xfbml      : true,
		version    : \'v2.3\'
	});
	}; (function(d, s, id){
		var js, fjs = d.getElementsByTagName(s)[0];
		if (d.getElementById(id)) {return;}
		js = d.createElement(s); js.id = id;
		js.src = "//connect.facebook.net/en_US/sdk.js";
		fjs.parentNode.insertBefore(js, fjs);
	}(document, \'script\', \'facebook-jssdk\'));',
      ),
      '1' => array(
        '#type' => 'html_tag',
        '#tag' => 'div',
        '#attributes' => array(
          'class' => 'fb-video',
          'data-href' => sprintf('https://www.facebook.com/video.php?v=%s', $data['id']),
          'data-width' => $settings['width'],
          'data-autoplay' => $settings['autoplay'] ? 1 : 0
        ),
      ),
    ];
  }
  
  /**
   * {@inheritdoc}
   */
  public function getRemoteThumbnailUrl() {
    $data = $this->getVideoMetadata();
    return 'https://graph.facebook.com/' . $data['id'] . '/picture';
  }
}