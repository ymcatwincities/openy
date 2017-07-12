<?php

namespace Drupal\default_content;

/**
 * A scanner to find YAML files in a given folder.
 */
class Scanner implements ScannerInterface {

  /**
   * {@inheritdoc}
   */
  public function scan($directory) {
    // Use Unix paths regardless of platform, skip dot directories, follow
    // symlinks (to allow extensions to be linked from elsewhere), and return
    // the RecursiveDirectoryIterator instance to have access to getSubPath(),
    // since SplFileInfo does not support relative paths.
    $flags = \FilesystemIterator::UNIX_PATHS;
    $flags |= \FilesystemIterator::SKIP_DOTS;
    $flags |= \FilesystemIterator::CURRENT_AS_SELF;
    $directory_iterator = new \RecursiveDirectoryIterator($directory, $flags);
    $iterator = new \RecursiveIteratorIterator($directory_iterator);

    $files = [];
    foreach ($iterator as $fileinfo) {
      /* @var \SplFileInfo $fileinfo */

      // Skip directories and non-json files.
      if ($fileinfo->isDir() || $fileinfo->getExtension() != 'json') {
        continue;
      }

      // @todo Use a typed class?
      $file = new \stdClass();
      $file->name = $fileinfo->getFilename();
      $file->uri = $fileinfo->getPathname();
      $files[$file->uri] = $file;
    }

    return $files;
  }

}
