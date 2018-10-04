<?php

namespace Drupal\ludwig;

interface PackageManagerInterface {

  /**
   * Gets the ludwig-managed packages.
   *
   * @return array
   *   The packages, keyed by package name.
   */
  public function getPackages();

}