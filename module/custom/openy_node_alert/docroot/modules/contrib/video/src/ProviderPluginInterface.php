<?php

/**
 * @file
 * Contains Drupal\video\ProviderInterface.
 */

namespace Drupal\video;

/**
 * Providers an interface for embed providers.
 */
interface ProviderPluginInterface {
  
  /**
   * Render embed code.
   *
   * @param string $settings
   *   The settings of the video player.
   *
   * @return mixed
   *   A renderable array of the embed code.
   */
  public function renderEmbedCode($settings);
  
  /**
   * Get the URL of the remote thumbnail.
   *
   * This is used to download the remote thumbnail and place it on the local
   * file system so that it can be rendered with image styles. This is only
   * called if no existing file is found for the thumbnail and should not be
   * called unnecessarily, as it might query APIs for video thumbnail
   * information.
   *
   * @return string
   *   The URL to the remote thumbnail file.
   */
  public function getRemoteThumbnailUrl();
  
}