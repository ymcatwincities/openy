<?php

namespace Drupal\openy_daxko2\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

use Drupal\migrate\MigrateExecutable;
use Drupal\migrate\MigrateMessage;

use GuzzleHttp\Client;

/**
 * Settings Form for daxko.
 */
class ImportForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'openy_daxko2_import';
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
      '#value' => $this->t('Run Daxko Import'),
    ];

    return $form;

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = \Drupal::configFactory()->get('openy_daxko2.settings');

    $batch = array(
      'title' => $this->t('Importing Programs from Daxko'),
      'operations' => [
        // We will get Categories CSV file from Programs as ID's of categories
        // do not match.
        array('Drupal\openy_daxko2\Form\ImportForm::generateProgramsCSV', [$config]),
        array('Drupal\openy_daxko2\Form\ImportForm::migrateCategories', []),
        array('Drupal\openy_daxko2\Form\ImportForm::migrateOfferings', []),
      ],
      'finished' => 'Drupal\openy_daxko2\Form\ImportForm::batchFinished',
    );

    batch_set($batch);
  }

  /**
   * Retrieve an access token from Daxko.
   */
  protected static function getAccessToken(&$context, $config) {
    if (isset($context['results']['access_token'])) {
      return $context['results']['access_token'];
    }
    $client = new Client(['base_uri' => $config->get('base_uri')]);
    $response = $client->request('POST', 'partners/oauth2/token',
    [
      'form_params' => [
        'client_id' => $config->get('user'),
        'client_secret' => $config->get('pass'),
        'grant_type' => 'client_credentials',
        'scope' => 'client:' . $config->get('client_id'),
      ],
      'headers' => [
        'Authorization' => "Bearer " . $config->get('referesh_token')
      ],
    ]);

    $context['results']['access_token'] = json_decode((string) $response->getBody())->access_token;

    return $context['results']['access_token'];
  }

  /**
   * Generate CSV files with programs.
   */
  public static function generateProgramsCSV($config, &$context) {
    $base_uri = $config->get('base_uri');

    $accessToken = self::getAccessToken($context, $config);

    $client = new Client(['base_uri' => $base_uri]);

    $after = TRUE;
    $i = 1;

    $filenamePrograms = '/tmp/programs.csv';
    unlink($filenamePrograms);
    $fp = fopen($filenamePrograms, 'w');

    $categories = [];

    while (!empty($after) && $i < 100) {
      $get = [];

      if (strlen($after) > 5) {
        $get = [ 'after' => $after ];
      }
      $response = $client->request('GET', 'programs/offerings/search',
        [
          'query' => $get,
          'headers' => [
            'Authorization' => "Bearer " . $accessToken
          ],
        ]);

      $programsResponse = json_decode((string) $response->getBody(), TRUE);

      foreach ($programsResponse['offerings'] as $row) {

        $newRow = [];
        foreach ($row as $key => $value) {
          if (in_array($key, [ 'highlights', 'score', 'type' ])) {
            continue;
          }

          switch ($key) {
            case 'start_date':
            case 'end_date':
              // We need to cut the date from the string: YYYY-MM-DD - 10 symbols.
              $value = substr($value, 0, 10);
              break;

            case 'locations':
              // We expect 'locations' to be overidden with hook.
              // @see openy_daxko2_example_openy_daxko2_categories_csv_row_alter().

              break;

            case 'program':
              array_pop($value);
              $value['description'] = $row['description'];

              $categories[$value['id']] = $value;

              $value = $value['id'];
              break;

            case 'days_offered':
              $weekdays = [];
              foreach ($value as $day) {
                $weekdays[] = $day['name'];
              }
              $value = implode(', ', $weekdays);
              break;

            case 'registration':
              $value = json_encode($value);
              break;

            case 'restrictions':
              if (isset($value['age'])) {
                $value = json_encode($value);
              }
              break;

            case 'times':
              $value = json_encode([
                'times' => $row['times'],
                'days' => $row['days_offered'],
                'start_date' => $row['start_date'],
                'end_date' => $row['end_date'],
              ]);
              break;
          }

          $newRow[$key] = $value;
        }

        $newRow['link'] = 'https://ops1.operations.daxko.com/Online/' . $config->get('client_id') . '/ProgramsV2/OfferingDetails.mvc?program_id=' . $row['program']['id'] . '&offering_id=' . $row['id'] . '&location_id=' . $row['locations'][0]['id'];
        $newRow['ageFrom'] = isset($row['restrictions']['age']) ? $row['restrictions']['age']['start'] : NULL;
        $newRow['ageTo'] = isset($row['restrictions']['age']) ? $row['restrictions']['age']['end'] : NULL;

        // Allow custom implementations to set up the mappings.
        \Drupal::moduleHandler()->alter('openy_daxko2_programs_csv_row', $newRow);

        fputcsv($fp, $newRow);
      }

      $after = '';
      if (isset($programsResponse['links'])) {
        $link = reset($programsResponse['links']);
        if (strpos($link['href'], 'after=')) {
          list(, $after) = explode('after=', $link['href']);
          $after = urldecode($after);
        }
      }

      $i++;
    }

    fclose($fp);

    // Save categories CSV file.
    $filenameCategories = '/tmp/categories.csv';
    unlink($filenameCategories);
    $fp = fopen($filenameCategories, 'w');
    foreach ($categories as $fields) {
      \Drupal::moduleHandler()->alter('openy_daxko2_categories_csv_row', $fields);

      fputcsv($fp, $fields);
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
  public static function migrateCategories() {
    self::runMigration('daxko_categories_import');
  }

  /**
   * Migrate offerings.
   */
  public static function migrateOfferings() {
    self::runMigration('daxko_offerings_import');
  }

  /**
   * Execute the migration by ID.
   */
  protected static function runMigration($migrationId) {
    $migration = \Drupal::service('plugin.manager.migration')->createInstance($migrationId);
    $executable = new MigrateExecutable($migration, new MigrateMessage());
    $executable->import();
  }

}
