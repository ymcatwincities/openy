<?php

namespace Drupal\ygh_content\Plugin\migrate\process;

use Drupal\Core\Entity\Entity;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Process parent ID to get parent identifier in format menu_link_content:UUID.
 *
 * @MigrateProcessPlugin(
 *   id = "ygh_content_menu_link_content_parent"
 * )
 */
class YghContentMenuLinkContentParent extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    /** @var Entity $item */
    if ($item = \Drupal::getContainer()->get('entity.manager')->getStorage('menu_link_content')->load($value)) {
      return sprintf('menu_link_content:%s', $item->uuid());
    }
    return 0;
  }

}
