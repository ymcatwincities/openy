<?php

namespace Drupal\ludwig;

use Drupal\Core\Archiver\ArchiverManager;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\FileTransfer\Local;
use Drupal\Core\Site\Settings;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;

/**
 * Download packages defined in ludwig.json files.
 */
class PackageDownloader implements PackageDownloaderInterface {

  /**
   * The archiver manager.
   *
   * @var \Drupal\Core\Archiver\ArchiverManager
   */
  protected $archiverManager;

  /**
   * The file system.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * The HTTP client.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * The app root.
   *
   * @var string
   */
  protected $root;

  /**
   * The archive extraction path.
   *
   * @var string
   */
  protected $extractionDir;

  /**
   * The archive download cache path.
   *
   * @var string
   */
  protected $cacheDir;

  /**
   * Constructs a new PackageDownloader object.
   *
   * @param \Drupal\Core\Archiver\ArchiverManager $archiver_manager
   *   The archiver manager.
   * @param \Drupal\Core\File\FileSystemInterface $file_system
   *   The file system.
   * @param \GuzzleHttp\ClientInterface $http_client
   *   The HTTP client.
   * @param string $root
   *   The app root.
   */
  public function __construct(ArchiverManager $archiver_manager, FileSystemInterface $file_system, ClientInterface $http_client, $root) {
    $this->archiverManager = $archiver_manager;
    $this->fileSystem = $file_system;
    $this->httpClient = $http_client;
    $this->root = $root;
  }

  /**
   * {@inheritdoc}
   */
  public function download(array $package) {
    $provider_path = $this->root . '/' . $package['provider_path'];
    if (!is_writable($provider_path)) {
      throw new \Exception(sprintf('The extension directory %s is not writable.', $provider_path));
    }
    $archive_path = $this->downloadArchive($package['download_url']);
    if (!$archive_path) {
      throw new \Exception(sprintf('Unable to retrieve %s from %s.', $package['name'], $package['download_url']));
    }
    $archiver = $this->extractArchive($archive_path);
    $files = $archiver->listContents();
    if (!$files) {
      throw new \Exception(sprintf('The archive downloaded from %s contains no files.', $package['download_url']));
    }

    // The real path the first directory in the extracted archive.
    // @todo Will this work for non-GitHub archives?
    $source_location = $this->fileSystem->realpath($this->getExtractionDirectory() . '/' . $files[0]);
    $package_destination = $this->root . '/' . $package['path'];
    $file_transfer = new Local($this->root);
    $file_transfer->copyDirectory($source_location, $package_destination);
    $new_perms = substr(sprintf('%o', fileperms($package_destination)), -4, -1) . "5";
    $file_transfer->chmod($package_destination, intval($new_perms, 8), TRUE);
  }

  /**
   * Downloads an archive from the given URL to the temporary directory.
   *
   * Returns the local path if the file has already been downloaded.
   *
   * @param string $url
   *   The archive URL.
   *
   * @return string
   *   The path to the local file.
   *
   * @throws \Exception
   */
  protected function downloadArchive($url) {
    $parsed_url = parse_url($url);
    $cache_directory = $this->getCacheDirectory();
    $local = $cache_directory . '/' . $this->fileSystem->basename($parsed_url['path']);

    if (!file_exists($local)) {
      $destination = $local;
      try {
        $data = $this->httpClient->request('get', $url)->getBody()->getContents();
        $local = file_unmanaged_save_data($data, $destination, FILE_EXISTS_REPLACE);
      }
      catch (RequestException $exception) {
        throw new \Exception(sprintf('Failed to fetch file due to error "%s"', $exception->getMessage()));
      }
      if (!$local) {
        throw new \Exception(sprintf('%s could not be saved to %s', $url, $destination));
      }

      return $local;
    }
    else {
      return $local;
    }
  }

  /**
   * Extracts a downloaded archive file.
   *
   * @param string $file
   *   The filename of the archive.
   *
   * @return \Drupal\Core\Archiver\ArchiverInterface
   *   The used archiver.
   *
   * @throws \Exception
   */
  protected function extractArchive($file) {
    /** @var \Drupal\Core\Archiver\ArchiverInterface $archiver */
    $archiver = $this->archiverManager->getInstance([
      'filepath' => $this->fileSystem->realpath($file),
    ]);
    if (!$archiver) {
      throw new \Exception(sprintf('Cannot extract %file, not a valid archive.', ['%file' => $file]));
    }

    // Unfortunately, we can only use the directory name to determine the package
    // name. Some archivers list the first file as the directory (i.e., MODULE/)
    // and others list an actual file (i.e., MODULE/README.TXT).
    $files = $archiver->listContents();
    $package = strtok($files[0], '/\\');

    // Remove the directory if it exists, otherwise it might contain a mixture of
    // old files mixed with the new files (e.g. in cases where files were removed
    // from a later release).
    $extract_location = $this->getExtractionDirectory() . '/' . $package;
    if (file_exists($extract_location)) {
      file_unmanaged_delete_recursive($extract_location);
    }

    return $archiver->extract($this->getExtractionDirectory());
  }

  /**
   * Gets the directory where the archive files should be extracted.
   *
   * @return string
   *   The full path to the extraction directory.
   */
  protected function getExtractionDirectory() {
    if (!$this->extractionDir) {
      $directory = 'temporary://luwdig-extraction-' . substr(hash('sha256', Settings::getHashSalt()), 0, 8);
      if (!file_exists($directory)) {
        mkdir($directory);
      }
      $this->extractionDir = $directory;
    }
    return $this->extractionDir;
  }

  /**
   * Gets the directory where the archive files should be cached.
   *
   * @return string
   *   The full path to the cache directory.
   */
  protected function getCacheDirectory() {
    if (!$this->cacheDir) {
      $directory = 'temporary://ludwig-cache-' . substr(hash('sha256', Settings::getHashSalt()), 0, 8);
      if (!file_exists($directory)) {
        mkdir($directory);
      }
      $this->cacheDir = $directory;
    }
    return $this->cacheDir;
  }

}
