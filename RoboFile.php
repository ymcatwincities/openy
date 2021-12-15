<?php
/**
 * Open Y Robo commands. 
 * Here we are able to create an any version of Open Y for CI builds.
 *
 * @see http://robo.li/
 */
class RoboFile extends \Robo\Tasks {
  /**
   * Create Open Y project https://github.com/ymcatwincities/openy-project without installation.
   *
   * @param string $path
   *   Installation path that will be used to create "openy-project" folder.
   */
  function OpenyCreateProject($path) {
    $this->taskComposerCreateProject()
      ->source('ymcatwincities/openy-project:9.2.x-init-dev')
      ->target($path . '/openy-project')
      ->ansi(TRUE)
      ->dev()
      ->noInstall(TRUE)
      ->noInteraction()
      ->run();
  }

  /**
   * Add fork as repository to composer.json.
   *
   * @param string $path
   *   Installation path where repository should be added.
   *
   * @param string $repository
   *   Local path of the repository.
   */
  function OpenyAddFork($path, $repository) {
    $this->taskComposerConfig()
      ->dir($path . '/openy-project')
      ->repository(99, $repository, 'path')
      ->ansi(TRUE)
      ->run();
  }


  /**
   * Set target branch of the fork.
   *
   * @param string $path
   *   Installation path where "openy-project" is placed.
   *
   * @param string $branch
   *   Branch name.
   */
  function OpenySetBranch($path, $branch) {
    $this->taskComposerRequire()
      ->dir($path . '/openy-project')
      ->dependency('ymcatwincities/openy', $branch)
      ->ansi(TRUE)
      ->run();
  }

  /**
   * Installs Open Y from fork as dependency.
   *
   * @param string $path
   *   Installation path where "openy-project" is placed.
   */
  function OpenyInstall($path) {
    $this->taskComposerInstall()
      ->dir($path . '/openy-project')
      ->noInteraction()
      ->ansi(TRUE)
      ->run();
  }

  /**
   * Creates symlink to mirror build folder into web accessible dir.
   *
   * @param string $docroot_path
   *   Path where website folder should be created.
   *
   * @param string $build_path
   *   Path where source code for build is placed.
   */
  function OpenyBuildFolder($docroot_path, $build_path) {
    $this->taskFilesystemStack()
      ->symlink($docroot_path, $build_path)
      ->chgrp('www-data', 'jenkins')
      ->run();
  }
}
