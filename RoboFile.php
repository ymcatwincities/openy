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
      ->noInstall(TRUE)
      ->noInteraction()
      ->run();
  }
}
