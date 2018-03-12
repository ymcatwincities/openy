<?php

use Drupal\Core\DrupalKernel;
use Drupal\Core\Site\Settings;
use Symfony\Component\HttpFoundation\Request;


/**
 * Implementation of hook_drush_command().
 */
function cibox_drush_command() {
  $items = [];
  $items['cibox-cache-rebuild'] = [
    'description' => 'Rebuild a Drupal 8 site and clear all its caches.',
    'options' => [],
    'arguments' => [],
    // Bootstrap to DRUSH_BOOTSTAP_DRUPAL_SITE to pick the correct site.
    // Further bootstrap is done by the rebuild script.
    'bootstrap' => DRUSH_BOOTSTRAP_DRUPAL_SITE,
    'core' => ['8+'],
    'aliases' => ['ciboxcr'],
  ];
  $items['get-unused-content-blocks'] = [
    'description' => 'Rebuild a Drupal 8 site and clear all its caches.',
    'options' => [],
    'arguments' => [],
    // Bootstrap to DRUSH_BOOTSTAP_DRUPAL_SITE to pick the correct site.
    // Further bootstrap is done by the rebuild script.
    'bootstrap' => DRUSH_BOOTSTRAP_DRUPAL_FULL,
    'core' => ['8+'],
    'aliases' => ['gucb'],
  ];
  return $items;
}

/**
 * Rebuild a Drupal 8 site in a quick way.
 */
function drush_cibox_cache_rebuild() {

  chdir(DRUPAL_ROOT);
  // Clear the APC cache to ensure APC class loader is reset.
  if (function_exists('apc_fetch')) {
    apc_clear_cache('user');
  }
  $autoloader = drush_drupal_load_autoloader(DRUPAL_ROOT);
  require_once DRUSH_DRUPAL_CORE . '/includes/utility.inc';
  $request = Request::createFromGlobals();
  // Manually resemble early bootstrap of DrupalKernel::boot().
  require_once DRUSH_DRUPAL_CORE . '/includes/bootstrap.inc';
  require_once DRUSH_DRUPAL_CORE . '/includes/database.inc';
  DrupalKernel::bootEnvironment();

  // Avoid 'Only variables should be passed by reference'
  $root = DRUPAL_ROOT;
  $site_path = DrupalKernel::findSitePath($request);
  Settings::initialize($root, $site_path, $autoloader);
  $t = db_query('SHOW TABLES')->fetchAllKeyed(0, 0);
  $cached = array_filter(
    array_keys($t),
    function ($v) {
      return substr($v, 0, 6) === 'cache_';
    }
  );
  foreach (array_values($cached) as $tname) {
    db_query('TRUNCATE TABLE ' . $tname)->execute();
  }
  // Use our error handler since _drupal_log_error() depends on an unavailable theme system (ugh).
  set_error_handler('drush_error_handler');
  // drupal_rebuild() calls drupal_flush_all_caches() itself, so we don't do it manually.
  // drupal_rebuild($autoloader, $request);
  drush_log(
    dt(
      'Fast Cache rebuild complete. Cleared %t tables',
      ['%t' => count($cached)]
    )
  );

}

function drush_cibox_get_unused_content_blocks() {

  chdir(DRUPAL_ROOT);
  // Clear the APC cache to ensure APC class loader is reset.
  if (function_exists('apc_fetch')) {
    apc_clear_cache('user');
  }
  $autoloader = drush_drupal_load_autoloader(DRUPAL_ROOT);
  require_once DRUSH_DRUPAL_CORE . '/includes/utility.inc';
  $request = Request::createFromGlobals();
  // Manually resemble early bootstrap of DrupalKernel::boot().
  require_once DRUSH_DRUPAL_CORE . '/includes/bootstrap.inc';
  require_once DRUSH_DRUPAL_CORE . '/includes/database.inc';
  DrupalKernel::bootEnvironment();
  // Avoid 'Only variables should be passed by reference'
  $root = DRUPAL_ROOT;
  $site_path = DrupalKernel::findSitePath($request);
  Settings::initialize($root, $site_path, $autoloader);

  /** @var \Drupal\Core\Entity\EntityFieldManager $entityManager */
  $entityManager = \Drupal::service('entity_field.manager');

  // Array of entity->field->bundle for entity_reference.
  $fieldsArray = \Drupal::service('entity_field.manager')
    ->getFieldMapByFieldType('entity_reference');

  foreach ($fieldsArray as $entity => $fieldsData) {
    foreach ($fieldsData as $fieldName => $fieldData) {
      if (!isset($fieldData['bundles'])) {
        throw new Exception(
          'Entity: ' . $entity . ' has no bundles: ' . print_r(
            $fieldsArray[$entity]
          )
        );
      }
      else {
        foreach ($fieldData['bundles'] as $bundle_mn => $bundle_n) {
          $fieldsDefinitions = $entityManager->getFieldDefinitions(
            $entity,
            $bundle_mn
          );
          /**
           * @var string $fieldName
           * @var \Drupal\field\Entity\FieldConfig|\Drupal\Core\Field\BaseFieldDefinition $fieldDefinition
           */
          foreach ($fieldsDefinitions as $fieldName => $fieldDefinition) {
            if ($fieldDefinition instanceof \Drupal\Core\Field\BaseFieldDefinition) {

              /** @var \Drupal\Core\Field\BaseFieldDefinition $fieldName */
              /** @var \Drupal\Core\TypedData\DataDefinitionInterface $itemDefinition */
              if ($fieldDefinition->getType() !== 'entity_reference') {
                continue;
              }
              $itemDefinition = $fieldDefinition->getItemDefinition();
              $fieldSettings = $itemDefinition->getSettings();
              if (isset($fieldSettings['target_type']) && $fieldSettings['target_type'] == 'block_content') {
                print_r("\n\n Entity: $entity ");
                print_r("Bundle: $bundle_mn ");
                print_r("Base!!!! Field Name: $fieldName \n\n");
                // todo work on basefields.
                throw new Exception(
                  'FIXME: Add handling of references in Base Fields'
                );
              }
            }
            if ($fieldDefinition instanceof \Drupal\field\Entity\FieldConfig) {
              if ($fieldDefinition->getType() !== 'entity_reference') {
                continue;
              }
              $fieldSettings = $fieldDefinition->get('settings');
              // TODO: Pass 'default:block_content' as an argument to drush command.
              if (isset($fieldSettings['handler']) && $fieldSettings['handler'] == 'default:block_content') {
                print_r("Entity: $entity ");
                print_r("Bundle: $bundle_mn ");
                print_r("Field Name: $fieldName \n");
                $ids = Drupal::entityQuery($entity)->condition(
                  $fieldName,
                  '',
                  '!='
                )->execute();

                $entities = Drupal::entityTypeManager()
                  ->getStorage($entity)
                  ->loadMultiple(array_values($ids));

                /** @var \Drupal\Core\Entity\Entity $entity */
                foreach ($entities as $eid => $eentity) {
                  $values = $eentity->get($fieldName)->getValue();
                  foreach ($values as $id => $data) {
                    $used[] = $data['target_id'];
                  }
                }

              }
            }
          }
        }

      }
    }
  }
  $used_in_fields = array_unique(array_values($used));

  print_r("\n\n\nFound blocks in fields: " . count($used_in_fields) . "\n\n\n");

  // All block ids.
  $cbs = db_query(
    'SELECT bc.id as id, bc.uuid as uuid, bc.type as type, bcfd.Info as title FROM block_content bc LEFT JOIN block_content_field_data bcfd ON bc.id = bcfd.id ORDER BY id DESC'
  )->fetchAllAssoc('id');

  $t = db_query('SHOW TABLES')->fetchAllKeyed(0, 0);

  // To skip.
  $cnames = [
    'bundle',
    'langcode',
    'type',
    'id',
    'created',
    'date',
    'timestamp',
    'uid',
    'entity_id',
    'tid',
    'wid',
    'entity_type',
    'revision_id',
    'webform_id',
    'uuid',
    'hash',
  ];

  $ctypes = ['int' => 3, 'double' => 6, 'float' => 5];

  $tnames = [
    'block_content',
    'watchdog',
    'ban_ip',
    'users',
    'user_roles',
    'batch',
    'captcha_sessions',
    'crop',
    'flood',
    'history',
    'logger_entity',
    'purge_queue',
    'queue',
    'menu_tree',
  ];

  $wheres = [];
  foreach ($t as $name => $data) {
    if (substr($name, 0, 15) === 'ymca_retention_' || substr(
        $name,
        0,
        10
      ) === 'personify_' || substr($name, 0, 5) === 'file_' || substr(
        $name,
        0,
        18
      ) === 'groupex_form_cache' || substr(
        $name,
        0,
        20
      ) === 'groupex_google_cache' || substr(
        $name,
        0,
        5
      ) === 'cache' || in_array($name, $tnames) || substr(
        $name,
        0,
        8
      ) === 'migrate_') {
      continue;
    }
    $c = db_query('SHOW COLUMNS FROM ' . $name)->fetchAllKeyed(0, 0);

    foreach ($c as $cname => $ctype) {
      $clean_ctype = explode('(', $ctype);
      if (!in_array($cname, $cnames) && !in_array($clean_ctype[0], $ctypes)) {
        $wheres[$name][] = $cname;
      }
      if (endsWith($cname, '_target_id')) {
        $exact_wheres[$name][] = $cname;
        if (count($exact_wheres[$name]) >= 2) {
          print_r($exact_wheres[$name]);
        }
      }
    }

  }
  //  print_r($wheres);
  $csv = [];
  foreach ($cbs as $cbid => $cdata) {
    // Skip block search if it was used in fields by entity_reference.
    if (in_array($cbid, $used_in_fields)) {
      continue;
    }
    $cbuuid = $cdata->uuid;
    $title = $cdata->title;
    $type = $cdata->type;
    $csv[$cbid]['id'] = $cbid;
    $csv[$cbid]['uuid'] = $cbuuid;
    $csv[$cbid]['title'] = $title;
    $csv[$cbid]['type'] = $type;

    print_r(
      "\nAnalyzing Block ID: " . $cbid . " Type: " . $type . " Title: " . $title
    );

    // Search by uuid.
    foreach ($wheres as $name => $data) {
      if (!isset($csv[$cbid])) {
        continue;
      }

      // Make SQL query and get results for uuid.
      if (count($data) == 0) {
        continue;
      }
      elseif (count($data) == 1) {
        $f = db_query(
          "SELECT COUNT(*) as count FROM " . $name . " WHERE (CONVERT(" . $data[0] . " USING utf8) LIKE '%" . $cbuuid . "%')"
        )->fetchAllKeyed(0, 0);
        reset($f);

        if (key($f) != NULL) {
          // TODO: Check is found block is in latest revision before deciding it is still used.
          unset($csv[$cbid]);
          $speedname = $name;
          $speedvalue = $data;
          $topspeed = [$speedname => $speedvalue];
          unset($wheres[$speedname]);
          // Put table name, where block was found, as a 0 element of array to speedup a loop.
          $wheres = $topspeed + $wheres;

          print_r(
            "\nTable with block found by uuid: $name  : $cbuuid : Used" . key(
              $f
            ) . " times. Skipping...\n"
          );
        }
      }
      else {
        $expr = '';
        foreach ($data as $key => $searchable_cname) {
          if ($key == 0) {
            $expr = "SELECT COUNT(*) as count FROM " . $name . " WHERE (CONVERT(" . $searchable_cname . " USING utf8) LIKE '%" . $cbuuid . "%'";
          }
          else {
            $expr .= " OR CONVERT(" . $searchable_cname . " USING utf8) LIKE '%" . $cbuuid . "%'";
          }
        }
        $expr .= " )";
        $f = db_query($expr)->fetchAllKeyed(0, 0);
        reset($f);

        if (key($f) != NULL) {
          unset($csv[$cbid]);
          $speedname = $name;
          $speedvalue = $data;
          $topspeed = [$speedname => $speedvalue];
          unset($wheres[$speedname]);
          $wheres = $topspeed + $wheres;

          print_r(
            "\nTable with block found by uuid: $name  : $cbuuid : Used " . key(
              $f
            ) . " times. Skipping...\n"
          );
        }
      }
    }


  }
  $fp = fopen('results.csv', 'w');
  foreach ($csv as $fields) {
    fputcsv($fp, $fields);
  }
  fclose($fp);
  print_r(
    "Find results.csv file in current directory with a list of potentially not used blocks.\n
    Consider to remove them from database to speedup a whole system."
  );

}

function startsWith($haystack, $needle) {
  $length = strlen($needle);
  return (substr($haystack, 0, $length) === $needle);
}

function endsWith($haystack, $needle) {
  return substr($haystack, -strlen($needle)) === $needle;
}
