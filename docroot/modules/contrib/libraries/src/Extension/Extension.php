<?php

/**
 * @file
 * Contains \Drupal\libraries\Extension\Extension.
 */

namespace Drupal\libraries\Extension;

use Drupal\Core\Extension\Extension as CoreExtension;

/**
 * @todo
 */
class Extension extends CoreExtension implements ExtensionInterface {

  /**
   * {@inheritdoc}
   *
   * @todo Determine whether this needs to be cached.
   */
  public function getLibraryDependencies() {
    // @todo Make this unit-testable.
    $info = system_get_info($this->getType(), $this->getName());
    assert('!empty($info)');
    return isset($info['library_dependencies']) ? $info['library_dependencies'] : [];
  }

}
