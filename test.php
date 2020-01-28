<?php

/**
 * @file
 * The PHP page that serves all page requests on a Drupal installation.
 *
 * All Drupal code is released under the GNU General Public License.
 * See COPYRIGHT.txt and LICENSE.txt files in the "core" directory.
 */

use Drupal\Core\DrupalKernel;
use Symfony\Component\HttpFoundation\Request;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\paragraphs\Entity\Paragraph;

$autoloader = require_once 'autoload.php';

//$kernel = new DrupalKernel('prod', $autoloader);
$request = Request::createFromGlobals();
$kernel = DrupalKernel::createFromRequest($request, $autoloader, 'prod');
$kernel->boot();

echo "<pre>";

function getLastChangedNode() {
  $conn = \Drupal::getContainer()->get('database');
  $statement = $conn->select('node_field_data')
    ->fields('node_field_data', ['type', 'langcode', 'status', 'changed'])
    ->orderBy('changed', 'DESC')
    ->range(0, 1);
  return $statement->execute()->fetchAssoc();
}

function getServerInfo() {
  $conn = \Drupal::getContainer()->get('database');
  $sql_version = $conn->query('select version();')->fetchField();
  $sql_detailed_version = $conn->query("SHOW VARIABLES LIKE '%version%';")
    ->fetchAllKeyed();
  $server_software = $_SERVER['SERVER_SOFTWARE'];
  $php_version = $_SERVER['PHP_VERSION'];
  $conn_options = $conn->getConnectionOptions();
  return [
    'server_software' => $server_software,
    'php_version' => $php_version,
    'sql_version' => $sql_version,
    'sql_detailed_version' => $sql_detailed_version,
    'sql_driver' => $conn_options['driver'],
  ];
}

function getThemeInfo() {
  $default_theme = \Drupal::config('system.theme')->get('default');
  $base_theme = \Drupal::service('theme_handler')
    ->listInfo()[$default_theme]->base_theme;

  return [
    'default_theme' => $default_theme,
    'base_theme' => $base_theme,
  ];
}

function getSimpleData() {
  return [
    'last_changed_node' => getLastChangedNode(),
    'server_info' => getServerInfo(),
    'theme_info' => getThemeInfo(),
  ];
}

//$simpleData = getSimpleData();
//var_dump($simpleData);

function getModules() {
  $all_modules = \Drupal::getContainer()
    ->get('extension.list.module')
    ->getList();
  //var_dump($all_modules);

  $modules = [
    'openy' => [],
    'custom' => [],
    'contrib' => [],
  ];

  function contains($needle, $haystack) {
    return strpos($haystack, $needle) !== FALSE;
  }

  foreach ($all_modules as $module) {
    if ($module->status != 1) {
      continue;
    }

    $module_name = $module->info['name'];
    $module_ver = $module->info['version'];
    if (contains('profiles/contrib/openy', $module->getPathname())) {
      $module_type = 'openy';
    }
    elseif (contains('modules/contrib', $module->getPathname())) {
      $module_type = 'contrib';
    }
    else {
      $module_type = 'custom';
    }

    $modules[$module_type][] = [
      'name' => $module_name,
      'version' => $module_ver,
    ];
  }

  return $modules;
}

//$modules = getModules();
//var_dump([
//  'openy' => count($modules['openy']),
//  'contrib' => count($modules['contrib']),
//  'custom' => count($modules['custom'])
//]);
//var_dump($modules);

function getFrontpageParagraphs() {
  $front_page = \Drupal::config('system.site')->get('page.front');
  $front_page = explode('/', $front_page);
  $nid = end($front_page);

  $node = \Drupal::getContainer()->get('entity_type.manager')
    ->getStorage('node')
    ->load($nid);

  $field_ids = [];
  // Get all the entity reference revisions fields.
  $map = \Drupal::service('entity_field.manager')
    ->getFieldMapByFieldType('entity_reference_revisions');

  // Get all fields of the node with paragraphs.
  foreach ($map['node'] as $name => $data) {
    $target_type = FieldStorageConfig::loadByName('node', $name)
      ->getSetting('target_type');

    if ($target_type == 'paragraph' && $node->hasField($name)) {
      $field_ids[] = $name;
    }
  }

  $found_paragraphs = [];
  foreach ($field_ids as $field_id) {
    if (!$node->hasField($field_id)) {
      continue;
    }
    $field = $node->get($field_id)->getValue();
    foreach ($field as $field_paragraph) {
      /** @var \Drupal\paragraphs\Entity\Paragraph $paragraph */
      $paragraph = Paragraph::load($field_paragraph['target_id']);

      $found_paragraphs[] = $paragraph->bundle();
    }
  }

  return $found_paragraphs;
}

//$found_paragraphs = getFrontpageParagraphs();
//var_dump($found_paragraphs);

function getParagraphsUsage() {
  $conn = \Drupal::getContainer()->get('database');
  $statement = $conn->select('paragraphs_item')
    ->fields('paragraphs_item', ['type'])
    ->groupBy('type')
    ->orderBy('count', 'DESC');
  $statement->addExpression('COUNT(type)', 'count');
  $paragraphs_counted = $statement->execute()->fetchAllKeyed();

  return $paragraphs_counted;
}

//$paragraphs_counted = getParagraphsUsage();
//var_dump($paragraphs_counted);

function getBundleUsage() {
  $conn = \Drupal::getContainer()->get('database');
  $statement = $conn->select('node_field_data')
    ->fields('node_field_data', ['type'])
    ->where('status=1')
    ->groupBy('type')
    ->orderBy('count', 'DESC');
  $statement->addExpression('COUNT(type)', 'count');
  $bundles_counted = $statement->execute()->fetchAllKeyed();

  return $bundles_counted;
}

//$bundles_counted = getBundleUsage();
//var_dump($bundles_counted);
