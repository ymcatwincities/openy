<?php

namespace Drupal\openy_gxp\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

use Drupal\migrate\MigrateExecutable;
use Drupal\migrate\MigrateMessage;

use GuzzleHttp\Client;

/**
 * Settings Form for gxp.
 */
class ImportForm extends FormBase {

  const FOLDER = 'gxp-import';

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'openy_gxp_import';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['selects']['button'] = [
      '#type' => 'submit',
      '#attributes' => [
        'class' => [
          'btn',
          'blue',
        ]
      ],
      '#value' => $this->t('Run GroupExPro Import'),
    ];

    return $form;

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = \Drupal::configFactory()->get('openy_gxp.settings');

    $batch = array(
      'title' => $this->t('Importing Programs from gxp'),
      'operations' => [
        // We will get Categories CSV file from Programs as ID's of categories
        // do not match.
        // Should pull in all locations and iterate through them to generate
        // separate CSV files and rum imports.
        array('Drupal\openy_gxp\Form\ImportForm::generateProgramsCSV', [$config, 202]),
//        array('Drupal\openy_gxp\Form\ImportForm::migrateCategories', [202]),
        array('Drupal\openy_gxp\Form\ImportForm::migrateOfferings', [202]),
      ],
      'finished' => 'Drupal\openy_gxp\Form\ImportForm::batchFinished',
    );

    batch_set($batch);
  }

  /**
   * Generate CSV files with programs.
   */
  public static function generateProgramsCSV($config, $location_id, &$context) {
    // Hardcoded URL for now for single location.
    $client = new Client(['base_uri' => 'https://www.groupexpro.com/gxp/api/']);

    $publicPath = \Drupal::service('file_system')->realpath('public://');
    $filenamePrograms = $publicPath . '/' . self::FOLDER . '/programs-' . $location_id . '.csv';
    if (file_exists($filenamePrograms)) {
      unlink($filenamePrograms);
    }
    if (!is_dir($publicPath . '/' . self::FOLDER)) {
      mkdir($publicPath . '/' . self::FOLDER);
    }
    
    $fp = fopen($filenamePrograms, 'w');

    $categories = [];

    $response = $client->request('GET', 'openy/view/36/' . $location_id);

    $programsResponse = json_decode((string) $response->getBody(), TRUE);

    foreach ($programsResponse as $row) {

      $startDate = (new \DateTime($row['start_date']))->format('Y-m-d');
      $endDate = (new \DateTime($row['end_date']))->format('Y-m-d');

      $newRow = [
        'class_id' => $row['class_id'],
        'category' => json_encode([
          'title' => $row['category'],
          'description' => $row['description'],
          'activity' => 119, // Hardcoded Activity as from Demo content. Need to move to configuration.
        ]),
        'location' => $row['location'],
        'title' => $row['title'],
        'studio' => $row['studio'],
        'instructor' => $row['instructor'],
        'exclusions' => isset($row['exclusions']) ? $row['exclusions'] : '',
        'times' => json_encode([
          'times' => [
            'start' => $row['patterns']['start_time'],
            'end' => $row['patterns']['end_time'],
          ],
          'days' => [$row['patterns']['day']],
          'start_date' => $startDate,
          'end_date' => $endDate,
        ]),
        // Add unique ID as class_id is not.
        'unique_id' => md5(serialize($row)),
      ];

      // Allow custom implementations to set up the mappings.
      \Drupal::moduleHandler()->alter('openy_gxp_programs_csv_row', $newRow);

      fputcsv($fp, $newRow);
    }

    fclose($fp);
  }

  /**
   * Finalize batch operations.
   */
  public static function batchFinished($success, $results, $operations) {
    // For some reason imported sessions (offerings) got status 13 instead of 1.
    // So update them manually or dive into the code and find the bug.
    $query = \Drupal::database()->update('node_field_data');
    $query->fields([
      'status' => '1',
    ]);
    $query->condition('status', 13);
    $query->execute();

    if ($success) {
      drupal_set_message(t('Great success!'));
    }
    else {
      $error_operation = reset($operations);
      drupal_set_message(t('An error occurred while processing @operation with arguments : @args', [ '@operation' => $error_operation[0], '@args' => print_r($error_operation[0], TRUE) ]));
    }
  }

  /**
   * Migrate categories.
   */
  public static function migrateCategories($location_id) {
    $migration = \Drupal::service('plugin.manager.migration')->createInstance('gxp_categories_import');

    $source = $migration->getSourceConfiguration();
    $publicPath = \Drupal::service('file_system')->realpath('public://');

    $source['path'] = $publicPath . '/' . self::FOLDER . '/categories-' . $location_id . '.csv';
    $migration->set('source', $source);

    $executable = new MigrateExecutable($migration, new MigrateMessage());
    $executable->import();
  }

  /**
   * Migrate offerings.
   */
  public static function migrateOfferings($location_id) {
    $migration = \Drupal::service('plugin.manager.migration')->createInstance('gxp_offerings_import');

    $source = $migration->getSourceConfiguration();
    $publicPath = \Drupal::service('file_system')->realpath('public://');

    $source['path'] = $publicPath . '/' . self::FOLDER . '/programs-' . $location_id . '.csv';
    $migration->set('source', $source);

    $executable = new MigrateExecutable($migration, new MigrateMessage());
    $executable->import();
  }

}
