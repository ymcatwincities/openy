<?php
/**
 * @file
 * Contains \Drupal\metatag\Generator\MetatagTagGenerator.
 */

namespace Drupal\metatag\Generator;

use Drupal\Console\Generator\Generator;

class MetatagTagGenerator extends Generator {

  /**
   * Generator plugin.
   *
   * @param string $module
   * @param string $name
   * @param string $label
   * @param string $description
   * @param string $plugin_id
   * @param string $class_name
   * @param string $group
   * @param string $weight
   */
  public function generate($module, $name, $label, $description, $plugin_id, $class_name, $group, $weight) {
    $parameters = [
      'module' => $module,
      'name' => $name,
      'label' => $label,
      'description' => $description,
      'plugin_id' => $plugin_id,
      'class_name' => $class_name,
      'group' => $group,
      'weight' => $weight,
    ];

    $this->renderFile(
      'tag.php.twig',
      $this->getSite()->getPluginPath($module, 'metatag/Tag') . '/' . $class_name . '.php',
      $parameters
    );
  }

}
