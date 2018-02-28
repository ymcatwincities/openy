<?php

/**
 * @file
 * Contains Drupal\video\ProviderPluginBase
 */

namespace Drupal\video;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Component\Render\PlainTextOutput;
use GuzzleHttp\ClientInterface;
use Drupal\image\Entity\ImageStyle;

/**
 * A base for the provider plugins.
 */
abstract class ProviderPluginBase implements ProviderPluginInterface, ContainerFactoryPluginInterface {

  /**
   * File object to handle
   *
   * @var Drupal\file\Entity\File $file
   */
  protected $file;

  /**
   * Additional metadata for the embedded video object
   *
   * @var array
   */
  protected $metadata = array();

  /**
   * Additional settings for the video widget
   *
   * @var array
   */
  protected $settings = array();
  
  /**
   * An http client.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * Create a plugin with the given input.
   *
   * @param string $configuration
   *   The configuration of the plugin.
   * @param \GuzzleHttp\ClientInterface $http_client
   *    An HTTP client.
   *
   * @throws \Exception
   */
  public function __construct($configuration, ClientInterface $http_client) {
    $this->file = $configuration['file'];
    $this->metadata = $configuration['metadata'];
    $this->settings = $configuration['settings'];
    $this->httpClient = $http_client;
  }

  /**
   * Get the ID of the video.
   *
   * @return string
   *   The video ID.
   */
  protected function getVideoFile() {
    return $this->file;
  }

  /**
   * Get the input which caused this plugin to be selected.
   *
   * @return string
   *   The raw input from the user.
   */
  protected function getVideoMetadata() {
    return $this->metadata;
  }

  /**
   * Get the input which caused this plugin to be selected.
   *
   * @return string
   *   The raw input from the user.
   */
  protected function getVideoSettings() {
    return $this->settings;
  }
  
  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($configuration, $container->get('http_client'));
  }

  /**
   * {@inheritdoc}
   */
  public function renderThumbnail($image_style, $link_url) {
    $this->downloadThumbnail();
    $output = [
      '#theme' => 'image',
      '#uri' => !empty($image_style) ? ImageStyle::load($image_style)->buildUrl($this->getLocalThumbnailUri()) : $this->getLocalThumbnailUri(),
    ];
    if ($link_url) {
      $output = [
        '#type' => 'link',
        '#title' => $output,
        '#url' => $link_url,
      ];
    }
    return $output;
  }

  /**
   * Download the remote thumbnail to the local file system.
   */
  protected function downloadThumbnail() {
    $local_uri = $this->getLocalThumbnailUri();
    if (!file_exists($local_uri)) {
      $thumb_dir = $this->getUploadLocation();
      file_prepare_directory($thumb_dir, FILE_CREATE_DIRECTORY);
      $thumbnail = $this->httpClient->request('GET', $this->getRemoteThumbnailUrl());
      file_unmanaged_save_data((string) $thumbnail->getBody(), $local_uri);
    }
  }
  
  /**
   * Get the URL to the local thumbnail.
   *
   * @return string
   *   The URI for the local thumbnail.
   */
  public function getLocalThumbnailUri() {
    $data = $this->getVideoMetadata();
    return $this->getUploadLocation() . '/' . $data['id'] . '.png';
  }
  
  /**
   * Determines the URI for a video field.
   *
   * @param array $settings
   *   The array of field settings.
   * @param array $data
   *   An array of token objects to pass to token_replace().
   *
   * @return string
   *   An unsanitized file directory URI with tokens replaced. The result of
   *   the token replacement is then converted to plain text and returned.
   */
  protected function getUploadLocation($data = []) {
    $settings = $this->getVideoSettings();
    $destination = trim($settings['file_directory'], '/');
    $destination = PlainTextOutput::renderFromHtml(\Drupal::token()->replace($destination, $data));
    return $settings['uri_scheme'] . '://' . $destination;
  }
}
