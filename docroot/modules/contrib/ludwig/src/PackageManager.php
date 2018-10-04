<?php

namespace Drupal\ludwig;

use Drupal\Core\Extension\ExtensionDiscovery;

/**
 * Provides information about ludwig-managed packages.
 *
 * Extensions (modules, profiles) can define a ludwig.json which is
 * discovered by this class. This discovery works even without a
 * Drupal installation, and covers non-installed extensions.
 */
class PackageManager implements PackageManagerInterface {

  /**
   * The app root.
   *
   * @var string
   */
  protected $root;

  /**
   * Constructs a new PackageManager object.
   *
   * @param string $root
   *   The app root.
   */
  public function __construct($root) {
    $this->root = $root;
  }

  /**
   * {@inheritdoc}
   */
  public function getPackages() {
    $listing = new ExtensionDiscovery($this->root);
    // Get all profiles, and modules belonging to those profiles.
    $profiles = $listing->scan('profile');
    $profile_directories = array_map(function ($profile) {
      return $profile->getPath();
    }, $profiles);
    $listing->setProfileDirectories($profile_directories);
    $modules = $listing->scan('module');
    /** @var \Drupal\Core\Extension\Extension[] $extensions */
    $extensions = $profiles + $modules;

    $packages = [];
    foreach ($extensions as $extension_name => $extension) {
      $extension_path = $extension->getPath();
      $config = $this->jsonRead($this->root . '/' . $extension_path . '/ludwig.json');
      $config += [
        'require' => [],
      ];

      foreach ($config['require'] as $package_name => $package_data) {
        $namespace = '';
        $src_dir = '';
        $package_path = $extension_path . '/lib/' . str_replace('/', '-', $package_name) . '/' . $package_data['version'];
        $package = $this->jsonRead($this->root . '/' . $package_path . '/composer.json');
        $description = !empty($package['description']) ? $package['description'] : '';
        $homepage = !empty($package['homepage']) ? $package['homepage'] : '';
        $autoload_key = isset($package['autoload']['psr-4']) ? 'psr-4' : 'psr-0';
        if (!empty($package['autoload'][$autoload_key])) {
          $autoload = $package['autoload'][$autoload_key];
          $package_namespaces = array_keys($autoload);
          $namespace = reset($package_namespaces);
          $src_dir = $autoload[$namespace];
          $src_dir = rtrim($src_dir, './');
          // Autoloading fails if the namespace ends with a backslash.
          $namespace = trim($namespace, '\\');
        }
        if ($autoload_key == 'psr-0' && !empty($namespace)) {
          // Core only assumes that LudwigServiceProvider is adding PSR-4
          // paths, each PSR-0 path needs to be converted in order to work.
          if (!empty($src_dir)) {
            $src_dir .= '/';
          }
          $src_dir .= str_replace('\\', '/', $namespace);
        }

        $packages[$package_name] = [
          'name' => $package_name,
          'version' => $package_data['version'],
          'description' => $description,
          'homepage' => $homepage,
          'provider' => $extension_name,
          'download_url' => $package_data['url'],
          'path' => $package_path,
          'provider_path' => $extension_path,
          'namespace' => $namespace,
          'src_dir' => $src_dir,
          'installed' => !empty($namespace),
        ];
      }
    }

    return $packages;
  }

  /**
   * Reads and decodes a json file into an array.
   *
   * @param string $filename
   *   Name of the file to read.
   *
   * @return array
   *   The decoded json data.
   */
  protected function jsonRead($filename) {
    $data = [];
    if (file_exists($filename)) {
      $data = file_get_contents($filename);
      $data = json_decode($data, TRUE);
      if (!$data) {
        $data = [];
      }
    }

    return $data;
  }


}
