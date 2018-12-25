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
    'description' => 'Generate CSV file for block content entities not used.',
    'options' => [],
    'arguments' => [
      'list' => 'Comma separated list of block_content IDs to skip.',
    ],
    // Bootstrap to DRUSH_BOOTSTAP_DRUPAL_SITE to pick the correct site.
    // Further bootstrap is done by the rebuild script.
    'bootstrap' => DRUSH_BOOTSTRAP_DRUPAL_FULL,
    'core' => ['8+'],
    'aliases' => ['gucb'],
  ];
  $items['remove-unused-content-blocks'] = [
    'description' => 'Remove block content entities not used.',
    'options' => [],
    'arguments' => [
      'list' => 'Comma separated list of block_content IDs.',
    ],
    // Bootstrap to DRUSH_BOOTSTRAP_DRUPAL_FULL to have full container.
    'bootstrap' => DRUSH_BOOTSTRAP_DRUPAL_FULL,
    'core' => ['8+'],
    'aliases' => ['rucb'],
  ];
  $items['remove-node-revisions'] = [
    'description' => 'Remove all revisions for node.',
    'options' => [],
    'arguments' => [],
    // Bootstrap to DRUSH_BOOTSTRAP_DRUPAL_FULL to have full container.
    'bootstrap' => DRUSH_BOOTSTRAP_DRUPAL_FULL,
    'core' => ['8+'],
    'aliases' => ['rnr'],
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
      return substr($v, 0, 5) === 'cache';
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

function drush_cibox_get_unused_content_blocks($list = '') {

  $ids_to_skip = [];
  if ($list) {
    $ids_to_skip = explode(',', $list);
  }
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
  $used_in_fields = array_merge($used_in_fields, $ids_to_skip);
  print_r(
    "\n\n\nTotal blocks to skip from removal: " . count(
      $used_in_fields
    ) . "\n\n\n"
  );

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

function drush_cibox_remove_unused_content_blocks($list = '') {
  if ($list) {
    $ids_to_remove = '20676,19781,19776,19756,19746,19731,19726,19241,19171,19141,18861,18831,18821,18811,18806,18801,18796,18776,18646,18591,18586,18541,18536,18531,18526,18521,18516,18511,18506,18501,18396,18136,18131,18126,18121,18116,18111,18106,18101,18096,18081,18071,17986,17836,17831,17826,17811,17806,17801,17796,17791,17786,17741,17671,17661,17656,17651,17646,17641,17636,17631,17626,17571,17526,17521,17506,17501,17496,17491,17486,17481,17476,17446,17396,17391,17386,17381,17376,17371,17366,17361,17351,17336,17326,17316,17311,17221,17216,17211,17206,17201,17196,17191,17186,17181,17156,17146,17136,17126,17121,17116,17111,17106,17101,17096,17091,17086,17081,17076,17071,17066,17061,17056,17051,17046,17036,16891,16886,16881,16871,16861,16856,16851,16846,16841,16836,16791,16766,16736,16731,16726,16721,16716,16711,16706,16701,16696,16691,16681,16616,16571,16551,16516,16506,16501,16491,16486,16476,16471,16446,16431,16421,16416,16411,16406,16401,16396,16391,16081,16066,15866,15861,15836,15791,15786,15781,15776,15771,15766,15761,15586,15541,15531,15486,15471,15456,15451,15446,15441,15436,15431,15426,15356,15331,15326,15311,15186,15171,15156,15116,15106,15101,15096,15091,15086,15081,15076,15071,15066,14976,14946,14941,14926,14896,14886,14881,14876,14871,14866,14861,14856,14786,14746,14716,14676,14671,14666,14661,14656,14646,14641,14636,14631,14626,14621,14611,14606,14591,14546,14536,14531,14521,14501,14496,14491,14486,14481,14476,14471,14466,14461,14456,14451,14446,14436,14431,14411,14406,14401,14396,14391,14386,14376,14366,14361,14356,14341,14336,14331,14326,14321,14316,14311,14136,14121,14061,14056,13926,13806,13801,13776,13711,13706,13696,13691,13686,13681,13676,13601,13196,13181,13176,13171,13166,13156,13146,13121,13116,13111,13106,12811,12806,12801,12786,12781,12776,12746,12741,12736,12731,12481,12466,12461,12436,12421,12406,12401,12396,12391,12386,12381,12376,12371,12366,12361,12356,12341,12336,12331,12326,12321,12316,12311,12306,12301,12296,12291,12286,12281,12276,12271,12266,12261,12256,12066,12061,12056,12046,11976,11971,11966,11941,11936,11931,11901,11896,11846,11841,11831,11826,11821,11816,11811,11806,11801,11796,11791,11786,11781,11776,11771,11766,11761,11756,11751,11746,11741,11641,11631,11626,11621,11616,11611,11606,11601,11596,11591,11586,11571,11566,11536,11526,11496,11491,11486,11481,11476,11471,11466,11456,11406,11371,11356,11206,11116,11046,11011,11006,11001,10991,10986,10981,10951,10946,10941,10926,10731,10721,10651,10551,10531,10521,10516,10496,10296,10281,10236,10221,10211,10201,10191,10166,10161,10151,10131,10071,10041,9981,9961,9941,9916,9866,9836,9801,9796,9786,9776,9775,9767,9766,9765,9745,9737,9716,9589,9588,9587,9586,9582,9581,9580,9579,9575,9574,9573,9572,9568,9567,9566,9565,9564,9546,9535,9528,9527,9492,9488,9486,9479,9477,9460,9459,9452,9447,9438,9437,9435,9434,9433,9432,9429,9426,9425,9419,9418,9401,9397,9392,9390,9379,9378,9356,9355,9344,9330,9319,9302,9269,9253,9249,9233,9219,9199,9198,9185,9184,9179,9165,9155,9140,9128,9116,9100,9099,9066,9065,9064,9063,9049,9035,9019,9017,9014,9010,8998,8997,8996,8995,8951,8949,8948,8947,8946,8945,8944,8943,8942,8941,8940,8939,8938,8937,8936,8935,8934,8932,8930,8917,8915,8901,8900,8899,8898,8897,8896,8895,8894,8893,8888,8886,8885,8884,8883,8882,8881,8880,8879,8878,8877,8876,8875,8874,8873,8872,8871,8864,8860,8859,8858,8857,8856,8855,8854,8853,8852,8851,8850,8849,8848,8847,8846,8845,8844,8843,8842,8841,8840,8839,8816,8815,8814,8783,8720,8719,8718,8717,8716,8715,8714,8713,8712,8711,8710,8709,8708,8707,8706,8705,8704,8703,8702,8701,8700,8699,8693,8689,8685,8645,8639,8607,8578,8517,8469,8468,8452,8436,8431,8426,8381,8336,160,158,69,68,66,65,63,61,50,6';
    $ids = explode(',', $list);
    $chunks = array_chunk($ids, 10);
    $cblocks_storage = Drupal::entityTypeManager()->getStorage('block_content');
    foreach ($chunks as $chunk) {
      print_r("Removing: " . implode(',', $chunk) . " blocks.\n");
      $cblocks_chunk = \Drupal\block_content\Entity\BlockContent::loadMultiple(
        $chunk
      );
      $cblocks_storage->delete($cblocks_chunk);
    }
  }
  else {
    print_r("Provide a comma separated list of entities to remove.");
  }

}

function drush_cibox_remove_node_revisions() {
  // All node ids.
  $nodes = db_query(
    'SELECT nid, vid FROM node n'
  )->fetchAllAssoc('nid');

  foreach ($nodes as $nid => $ndata) {

    $vids = \Drupal::entityTypeManager()->getStorage('node')->revisionIds(
      node_load($nid)
    );
    print_r("\nProcessing node: $nid \nRemoving revisions:");
    foreach ($vids as $vid) {
      if ($vid == $ndata->vid) {
        print_r(" ( skipping default: $vid ) ");
        continue;
      }
      try {
        \Drupal::entityTypeManager()->getStorage('node')->deleteRevision($vid);
        print_r(" $vid ");
      } catch (Exception $e) {
        // Latest revision will not be deleted but throws an error
        print_r("\nError: " . $e->getMessage());
      }
    }

  }
}
