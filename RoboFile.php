<?php
/**
 * This is project's console commands configuration for Robo task runner.
 *
 * @see http://robo.li/
 */
class RoboFile extends \Robo\Tasks {
  /**
   * This method creates OpenY project without installation.
   */
  function OpenyCreateProject($path) {
    $this->taskComposerCreateProject()
      ->source('ymcatwincities/openy-project:8.1.x-development-dev')
      ->target($path . '/openy-project')
      ->ansi(TRUE)
      ->noInstall(TRUE)
      ->noInteraction()
      ->run();
  }

  /**
   * This method adds fork as repository to composer.json.
   *
   * Example: 'https://github.com/Sanchiz/openy'.
   */
  function OpenyAddFork($path, $repository) {
    $this->taskComposerConfig()
      ->dir($path . '/openy-project')
      ->repository(99, $repository, 'vcs')
      ->ansi(TRUE)
      ->run();
  }


  /**
   * This method adds branch for OpenY distro in composer.json.
   *
   * Example: 'dev-feature/drupal-8.4'.
   */
  function OpenySetBranch($path, $branch) {
    $this->taskComposerRequire()
      ->dir($path . '/openy-project')
      ->dependency('ymcatwincities/openy', $branch)
      ->ansi(TRUE)
      ->run();
  }

  /**
   * This method installs OpenY.
   */
  function OpenyInstall($path) {
    $this->taskComposerInstall()
      ->dir($path . '/openy-project')
      ->noInteraction()
      ->ansi(TRUE)
      ->run();
  }

  /**
   * This method create symlink for web accessible build folder.
   */
  function OpenyBuildFolder($docroot_path, $build_path) {
    $this->taskFilesystemStack()
      ->symlink($docroot_path, $build_path)
      ->chgrp('www-data', 'jenkins')
      ->run();
  }
}
