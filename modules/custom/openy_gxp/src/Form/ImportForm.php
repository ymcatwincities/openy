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

    $operations = [];
    foreach (explode("\n", $config->get('locations')) as $row) {
      list($gxpLocationId, $locationName) = explode(',', $row);
      $gxpLocationId = (int) $gxpLocationId;
      $locationName = trim($locationName);
      $nids = \Drupal::entityQuery('node')
        ->condition('title', $locationName)
        ->execute();
      if (!empty($nids)) {
        $operations[] = ['Drupal\openy_gxp\Form\ImportForm::generateProgramsCSV', [$config->get('activity'), $config->get('client_id'), $gxpLocationId, reset($nids)]];
        $operations[] = ['Drupal\openy_gxp\Form\ImportForm::migrateOfferings', [$gxpLocationId]];
      }
      else {
        drupal_set_message(t('Unknown branch @branch. Please correct GroupExPro settings Location Mapping.', ['@branch' => $locationName]), 'error');
      }
    }

    $batch = array(
      'title' => $this->t('Importing Programs from gxp'),
      'operations' => $operations,
      'finished' => 'Drupal\openy_gxp\Form\ImportForm::batchFinished',
    );

    batch_set($batch);
  }

  /**
   * Generate CSV files with programs.
   */
  public static function generateProgramsCSV($activityId, $gxpClientId, $gxpLocationId, $locationId) {
    // Hardcoded URL for now for single location.
    $client = new Client(['base_uri' => 'https://www.groupexpro.com/gxp/api/']);

    $publicPath = \Drupal::service('file_system')->realpath('public://');
    $filenamePrograms = $publicPath . '/' . self::FOLDER . '/programs-' . $gxpLocationId . '.csv';
    if (file_exists($filenamePrograms)) {
      unlink($filenamePrograms);
    }
    if (!is_dir($publicPath . '/' . self::FOLDER)) {
      mkdir($publicPath . '/' . self::FOLDER);
    }
    
    $fp = fopen($filenamePrograms, 'w');

    $response = $client->request('GET', 'openy/view/' . $gxpClientId . '/' . $gxpLocationId);
    $programsResponse = json_decode((string) $response->getBody(), TRUE);

    if (empty($programsResponse)) {
      drupal_set_message(t('Something went wrong with GroupExPro API call. Received empty response. https://www.groupexpro.com/gxp/api/@client/@location',
        ['@client' => $gxpClientId, '@location' => $gxpLocationId]));
    }

    foreach ($programsResponse as $row) {
      $startDate = (new \DateTime($row['start_date']))->format('Y-m-d');
      $endDate = (new \DateTime($row['end_date']))->format('Y-m-d');

      $exclusions = [];
      if (isset($row['exclusions']) && !empty($exclusions_values = $row['exclusions'])) {
        foreach ($exclusions_values as $exclusion) {
          $exclusionStart = (new \DateTime($exclusion . '00:00:00'))->format('Y-m-d\TH:i:s');
          $exclusionEnd = (new \DateTime($exclusion . '24:00:00'))->format('Y-m-d\TH:i:s');
          $exclusions[] = [
            'value' => $exclusionStart,
            'end_value' => $exclusionEnd,
          ];
        }
      }

      $newRow = [
        'class_id' => $row['class_id'],
        'category' => json_encode([
          'title' => $row['category'],
          'description' => $row['description'],
          'activity' => $activityId,
        ]),
        'location' => $locationId,
        'title' => $row['title'],
        'studio' => $row['studio'],
        'instructor' => $row['instructor'],
        'exclusions' => json_encode($exclusions),
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
    $query->fields(['status' => '1']);
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
   * Migrate offerings.
   */
  public static function migrateOfferings($gxpLocationId) {
    $migration = \Drupal::service('plugin.manager.migration')->createInstance('gxp_offerings_import');

    $source = $migration->getSourceConfiguration();
    $publicPath = \Drupal::service('file_system')->realpath('public://');

    $filePath = $publicPath . '/' . self::FOLDER . '/programs-' . $gxpLocationId . '.csv';
    if (!file_exists($filePath)) {
      drupal_set_message(t('File @file does not exist. Please check script that builds CSV file for import.', ['@file' => $filePath]), 'error');
      return;
    }
    $source['path'] = $filePath;
    $migration->set('source', $source);

    $executable = new MigrateExecutable($migration, new MigrateMessage());
    $executable->import();
  }

}
