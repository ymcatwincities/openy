<?php

namespace Drupal\acquia_connector;

use Drupal\acquia_connector\Helper\Storage;
use Drupal\Core\Archiver\ArchiveTar;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Url;
use Drupal\Core\DrupalKernel;
use GuzzleHttp\Exception\RequestException;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Class Migration.
 */
class Migration {

  /**
   * Check server for migration capabilities.
   *
   * @return array
   *   Array of environment capabilities or 'error' is set.
   */
  public function checkEnv() {
    $env = ['error' => FALSE];

    // Check available compression libs.
    if (function_exists('gzopen')) {
      $env['compression_ext'] = 'gz';
    }
    elseif (function_exists('bzopen')) {
      $env['compression_ext'] = 'bz2';
    }
    elseif (class_exists('ZipArchive')) {
      $env['compression_ext'] = 'zip';
    }
    else {
      $env['error'] = t('No compression libraries available');
    }
    return $env;
  }

  /**
   * Setup archive directory and internal migrate data struct.
   *
   * @param array $environment
   *   Environment to migrate to, from NSPI
   *   acquia_agent_cloud_migration_environments().
   *
   * @return array
   *   Migration array.
   */
  public function prepare($environment) {
    // Internal migration store is an array because objects cannot be stored
    // by Drupal's Batch API.
    $local_env = $this->checkEnv();
    if ($local_env['error'] !== FALSE) {
      return $local_env;
    }
    // Modify environment URL if SSL is available for use.
    if (in_array('ssl', stream_get_transports(), TRUE) && !defined('ACQUIA_DEVELOPMENT_NOSSL')) {
      $uri = parse_url($environment['url']);
      if (isset($uri['host'])) {
        $environment['url'] = $uri['host'];
      }
      $environment['url'] .= isset($uri['port']) ? ':' . $uri['port'] : '';
      $environment['url'] .= (isset($uri['path']) && isset($uri['host'])) ? $uri['path'] : '';
      $environment['url'] = 'https://' . $environment['url'];
    }
    $time = REQUEST_TIME;
    $date = gmdate('Ymd_his', $time);
    $migration = array(
      'error' => FALSE,
      'id' => uniqid() . '_' . $date,
      'date' => $date,
      'time' => $time,
      'compression_ext' => $local_env['compression_ext'],
      // Parameters used in transfer request.
      'request_params' => array(
        // Return URL on this site.
        'r' => Url::FromRoute('acquia_connector.settings', array(), array('absolute' => TRUE))->toString(),
        // For Acquia Hosting.
        'y' => 'sar',
        'stage' => $environment['stage'],
        'nonce' => $environment['nonce'],
      ),
      'env' => $environment,
      'no_data_tables' => array(),
    );

    // Set up local storage of archive.
    $this->destination($migration);

    return $migration;
  }

  /**
   * Ensure this response can work through migration.
   */
  public function processSetup() {
    if (!defined('OS_WINDOWS') && defined('PHP_OS') && in_array(PHP_OS, [
      'WINNT',
      'WIN32',
      'Windows',
    ])
    ) {
      // OS_WINDOWS constant used by Archive_Tar.
      define('OS_WINDOWS', TRUE);
    }
    // If not in 'safe mode', increase the maximum execution time:
    if (!ini_get('safe_mode') && strpos(ini_get('disable_functions'), 'set_time_limit') === FALSE && ini_get('max_execution_time') < 1200) {
      // @todo: Change to use config.
      set_time_limit(1200);
    }
    // Load any required include files.
    return $this->checkEnv();
  }

  /**
   * Create temporary directory and setup file for migration.
   */
  public function destination(&$migration) {
    $tmp_dir = \Drupal::service('file_system')->realpath(DrupalKernel::findSitePath(\Drupal::request()) . DIRECTORY_SEPARATOR . 'files') . DIRECTORY_SEPARATOR . 'acquia_migrate' . $migration['id'];
    if (!mkdir($tmp_dir) || !is_writable($tmp_dir)) {
      $migration['error'] = t('Cannot create temporary directory @dir to store site archive.', array('@dir' => $tmp_dir));
      return;
    }
    $migration['dir'] = $tmp_dir;
    $migration['file'] = $tmp_dir . DIRECTORY_SEPARATOR . 'archive-' . $migration['date'];
  }

  /**
   * Test migration setup and destination.
   *
   * @param array $migration
   *   Array of migration information.
   *
   * @return bool
   *   Whether migration can continue.
   *
   * @throws ConnectorException
   */
  public function testSetup(&$migration) {
    $url = $migration['env']['url'];

    try {
      $client = \Drupal::service('http_client_factory')->fromOptions([
        'headers' => ['User-Agent' => 'Acquia Migrate Client/1.x (Drupal ' . \Drupal::VERSION . ';)'],
        'http_errors' => FALSE,
        'allow_redirects' => FALSE,
      ]
      );

      $response = $client->get($url);
      if ($response->getStatusCode() != 400) {
        $migration['error'] = (string) t('Unable to connect to migration destination site (unexpected response code: @code), please contact Acquia Support.', array('@code' => $response->getStatusCode()));
        return FALSE;
      }
    }
    catch (RequestException $e) {
      throw new ConnectorException($e->getMessage(), $e->getCode());
    }

    return TRUE;
  }

  /**
   * Complete migration tasks.
   */
  public function complete(&$migration) {
    $storage = new Storage();
    $identifier = $storage->getIdentifier();
    $key = $storage->getKey();
    $client = \Drupal::service('acquia_connector.client');
    $body = array('identifier' => $identifier);
    if (isset($migration['redirect']) && is_array($migration['redirect']['data'])) {
      $body += $migration['redirect']['data'];
    }

    try {
      $data = $client->nspiCall('/agent-migrate-api/subscription/migration/complete', $body, $key);
    }
    catch (ConnectorException $e) {
      if ($e->getCustomMessage('code')) {
        acquia_connector_report_restapi_error($e->getCustomMessage('code'), $e->getCustomMessage());
        $migration['error'] = TRUE;
        return;
      }
      $migration['error'] = t("Server error, please submit again.");
      return;
    }

    // Response is in $data['result'].
    $result = $data['result'];
    if (!empty($result['success'])) {
      $migration['network_url'] = $result['network_url'];
    }
    else {
      $migration['error'] = $result['error'];
    }
    return $migration;
  }

  /**
   * Test migration.
   *
   * @param array $migration
   *   Migration array.
   * @param array $context
   *   Context.
   */
  public function batchTest($migration, &$context) {
    $this->processSetup();
    // Latest migration might be in $context.
    if (!empty($context['results']['migration'])) {
      $migration = $context['results']['migration'];
      \Drupal::state()->set('migrate.cloud', $migration);
    }
    // Check for error and abort if appropriate.
    if (empty($migration) || $migration['error'] !== FALSE) {
      $context['message'] = t('Encountered error, aborting migration.');
      return;
    }

    $this->testSetup($migration);

    // Store migration in results so it can be used by next operation.
    $context['results']['migration'] = $migration;
    $context['message'] = t('Testing migration capabilities');
  }

  /**
   * Backup database.
   *
   * @param array $migration
   *   Migration array.
   * @param array $context
   *   Context.
   */
  public function batchDb($migration, &$context) {
    $this->processSetup();
    // Latest migration might be in $context.
    if (!empty($context['results']['migration'])) {
      $migration = $context['results']['migration'];
      \Drupal::state()->set('migrate.cloud', $migration);
    }
    // Check for error and abort if appropriate.
    if (empty($migration) || $migration['error'] !== FALSE) {
      $context['message'] = t('Encountered error, aborting migration.');
      return;
    }

    $this->backupDbToFileMysql($migration);

    // Store migration in results so it can be used by next operation.
    $context['results']['migration'] = $migration;
    $context['message'] = t('Exported database. Archiving files.');
  }

  /**
   * Archive data.
   *
   * @param array $migration
   *   Migration array.
   * @param array $context
   *   Context.
   */
  public function batchTar($migration, &$context) {
    $this->processSetup();

    // Latest migration is in $context.
    if (!empty($context['results']['migration'])) {
      $migration = $context['results']['migration'];
      \Drupal::state()->set('migrate.cloud', $migration);
    }

    // Check for error and abort if appropriate.
    if (empty($migration) || $migration['error'] !== FALSE) {
      $context['message'] = t('Encountered error, aborting migration.');
      return;
    }

    $this->archiveSite($migration);

    // Store migration in results so it can be used by next operation.
    $context['results']['migration'] = $migration;
    $context['message'] = t('Created archive. Beginning transfer.');
  }

  /**
   * Transmit data.
   *
   * @param array $migration
   *   Migration array.
   * @param array $context
   *   Context.
   */
  public function batchTransmit($migration, &$context) {
    $this->processSetup();

    // Latest migration is in $context.
    if (!empty($context['results']['migration'])) {
      $migration = $context['results']['migration'];
      \Drupal::state()->set('migrate.cloud', $migration);
    }

    // Check for error and abort if appropriate.
    if (empty($migration) || $migration['error'] !== FALSE) {
      $context['message'] = t('Encountered error, aborting migration.');
      $context['finished'] = 1;
      return;
    }

    // First call.
    if (empty($context['sandbox'])) {
      $context['sandbox']['position'] = 0;
      $size = filesize($migration['tar_file']);
      $context['sandbox']['size'] = $size;
      $migration['request_params']['file_size'] = $size;
      $migration['request_params']['hash'] = md5_file($migration['tar_file']);
      $migration['file_name'] = basename($migration['tar_file']);
    }

    // Set to 0.5 MB.
    $length = 1024 * 1024 / 2;
    $position = $this->transmitChunk($migration, $context['sandbox']['position'], $length);
    $context['sandbox']['position'] = $position;

    // Store migration in results so it can be used by next operation.
    $context['results']['migration'] = $migration;
    if ($context['sandbox']['position'] !== FALSE) {
      $context['message'] = t('Uploading archive. Transferred @pos of @size bytes.', array('@pos' => $context['sandbox']['position'], '@size' => $context['sandbox']['size']));
      $finished = $context['sandbox']['position'] / $context['sandbox']['size'];
      $context['finished'] = $finished;
    }
    else {
      $context['finished'] = 1;
    }
  }

  /**
   * Batch finished callback.
   *
   * @param bool $success
   *   Indicate that the batch API tasks were all completed successfully.
   * @param array $results
   *   An array of all the results that were updated in update_do_one().
   * @param array $operations
   *   A list of all the operations that had not been completed by the batch
   *   API.
   */
  public function batchFinished($success, $results, $operations) {
    $migration = !empty($results['migration']) ? $results['migration'] : FALSE;

    if ($success && $migration && $migration['error'] == FALSE) {
      // Inform Acquia Cloud of migration completion.
      $this->complete($migration);

      if ($migration['error'] != FALSE) {
        $message = t('There was an error checking for completed migration. @err<br/>See the @network for more information.', [
          '@err' => $migration['error'],
          '@network' => \Drupal::l(t('Network dashboard'), Url::fromUri('https://insight.acquia.com/')),
        ]);
        drupal_set_message($message);
      }
      else {
        $message = t('Migrate success. You can see import progress on the @network.', array(
          '@network' => \Drupal::l(t('Acquia Subscription'), Url::fromUri($migration['network_url'], array('external' => TRUE))),
        ));
        drupal_set_message($message);
      }

      // Cleanup migration.
      $this->cleanup($migration);
    }
    else {
      \Drupal::logger('acquia-migrate')->error('Migration error @m', array('@m' => var_export($migration, TRUE)));
      $message = t('There was an error during migration.');

      if ($migration && is_string($migration['error'])) {
        $message .= ' ' . $migration['error'];
      }

      drupal_set_message($message, 'error');
      // Cleanup anything left of migration.
      $this->cleanup($migration);
    }

    new RedirectResponse(\Drupal::url('acquia_connector.migrate'));
  }

  /**
   * Get list of folders to exclude.
   *
   * @param array $migration
   *   Migration array.
   *
   * @return array
   *   Array of folders to exclude.
   */
  public function exclude($migration) {
    $exclude = ['.', '..', '.git', '.svn', 'CVS', '.bzr'];

    // Exclude the migration directory.
    $exclude[] = basename($migration['dir']);

    if (!\Drupal::config('acquia_connector.settings')->get('migrate.files')) {
      $exclude[] = \Drupal::service('file_system')->realpath(DrupalKernel::findSitePath(\Drupal::request()) . DIRECTORY_SEPARATOR . 'files');
    }

    return $exclude;
  }

  /**
   * Archive site.
   *
   * @param array $migration
   *   Migration array.
   */
  protected function archiveSite(&$migration) {
    $exclude = $this->exclude($migration);
    $files = $this->filesToBackup(DRUPAL_ROOT, $exclude);

    if (!empty($files) && isset($migration['file'])) {
      $this->validateArchiveFiles($migration, $files);
      if ($migration['error'] != FALSE) {
        return;
      }

      $dest_file = $migration['file'] . '.tar';
      if (!empty($migration['compression_ext'])) {
        $dest_file .= '.' . $migration['compression_ext'];
      }

      $gz = new ArchiveTar($dest_file, $migration['compression_ext'] ? $migration['compression_ext'] : NULL);
      if (!empty($migration['db_file'])) {
        // Add db file.
        try {
          $gz->addModify(array($migration['db_file']), '', $migration['dir'] . DIRECTORY_SEPARATOR);
        }
        catch (\Exception $e) {
          \Drupal::logger('acquia-migrate')->error('Failed to add file @file to the archive.', array(
            '@file' => array($migration['db_file'])
          ));
        }
      }
      // Remove Drupal root from the file paths, OS dependent.
      if (defined('OS_WINDOWS') && OS_WINDOWS) {
        $remove_dir = DRUPAL_ROOT . '\\';
      }
      else {
        $remove_dir = DRUPAL_ROOT . '/';
      }
      try {
        $gz->addModify($files, '', $remove_dir);
      }
      catch (\Exception $e) {
        \Drupal::logger('acquia-migrate')->error('Failed to add files to the archive.');
      }
      $migration['tar_file'] = $dest_file;
    }
    else {
      $migration['error'] = TRUE;
    }
  }

  /**
   * Run Acquia's site-uploader.php validation checks.
   */
  protected function validateArchiveFiles(&$migration, $files) {
    $output = implode("\n", $files);

    if (defined('OS_WINDOWS')) {
      $output = str_replace('\\', '/', $output);
    }

    $docroot = preg_quote(DRUPAL_ROOT . '/');

    // Count the number of sites dirs with settings.php files and files
    // directories.
    $count_settingsphp = preg_match_all('@^' . $docroot . 'sites/[^/\n]+/settings.php$@m', $output, $settings_phps);
    $count_filesdirs = preg_match_all('@^' . $docroot . 'sites/[^/\n]+/files/$@m', $output, $filesdirs);

    // Count the number of sql dumps in the root, plus in the docroot but
    // only if the docroot is a sub-dir (not empty or ./). Record all SQL
    // dumps into $sqls[0].
    $count_sqls = preg_match_all('@^' . $docroot . '[^/\n.][^/\n]*\.sql$@m', $output, $sqls);

    if (strlen($docroot) > 2) {
      $count_sqls += preg_match_all('@^(?:\./)?[^/\n.][^/\n]*\.sql$@m', $output, $docroot_sqls);
      $sqls[0] = array_merge($sqls[0], $docroot_sqls[0]);
    }

    // Allow simpletest SQL files.
    if (!empty($sqls[0])) {
      foreach ($sqls[0] as $key => $sql_file) {
        if (strpos($sql_file, 'simpletest') !== FALSE) {
          $count_sqls--;
          unset($sqls[0][$key]);
        }
      }
    }

    if (!in_array(DRUPAL_ROOT . DIRECTORY_SEPARATOR . 'index.php', $files)) {
      $migration['error'] = "The archive file will not be in the correct format: no index.php found in root or top-level directory.";
    }
    elseif ($count_settingsphp > 1) {
      $migration['error'] = "The archive file will not be in the correct format: it must have at most one sites directory containing settings.php, but the install has $count_settingsphp: " . implode(', ', $settings_phps[0]) . ". Remove unnecessary settings.php files and try again.";
    }
    elseif ($count_settingsphp == 0 && $count_filesdirs > 1) {
      $migration['error'] = "The archive file will not be in the correct format: no settings.php file and the install has more than one sites directory containing a files directory. Remove unnecessary files directories or consolidate and try again.";
    }
    elseif ($count_sqls > 0) {
      $migration['error'] = "The archive file will not be in the correct format: it contains $count_sqls extra SQL files: " . implode(', ', $sqls[0]) . ". Remove extra .sql files and try again.";
    }
  }

  /**
   * Transmit chunk.
   *
   * @param array $migration
   *   Migration array.
   * @param int $position
   *   The offset.
   * @param int $length
   *   Number of bytes read.
   *
   * @return bool|int
   *   Current position of FALSE on EOF/transmit fail.
   */
  protected function transmitChunk(&$migration, $position, $length) {
    // Open file in binary mode.
    $handle = fopen($migration['tar_file'], 'rb');
    // Move to position in file.
    if ($position) {
      fseek($handle, $position);
    }
    $contents = fread($handle, $length);
    // Pass starting position.
    $migration['request_params']['position'] = $position;
    // Transfer contents.
    $result = $this->transmit($migration, $contents);

    // Set position to FALSE if the whole file has been read or if transmit
    // failed.
    if (feof($handle) || $result === FALSE) {
      $position = FALSE;
    }
    else {
      // Get current position.
      $position = ftell($handle);
    }
    fclose($handle);
    return $position;
  }

  /**
   * Perform POST of archive chunk to Acquia hosting environment URL.
   *
   * @param array $migration
   *   Migration array.
   * @param string $content
   *   Archived data.
   *
   * @return bool
   *   TRUE on success, FALSE otherwise.
   */
  protected function transmit(&$migration, $content) {
    $params = $migration['request_params'];
    $params['nonce'] = $migration['env']['nonce'];
    $params['t'] = time();
    $params[$migration['env']['stage']] = $this->getToken($params['t'], $params['r'], $migration['env']['secret']);

    $data = [];
    foreach ($params as $key => $value) {
      $data['multipart'][] = [
        'name' => $key,
        'contents' => (string) $value,
      ];
    }
    $url = $migration['env']['url'];
    $actual_uri = NULL;

    $config = [
      'allow_redirects' => [
        'max' => 0,
        'on_redirect' => function (RequestInterface $request, ResponseInterface $response, UriInterface $request_uri) use (&$actual_uri) {
          $actual_uri = (string) $request_uri;
        },
      ],
      'http_errors' => FALSE,
      'headers' => [
        'User-Agent' => 'Acquia Migrate Client/1.x (Drupal ' . \Drupal::VERSION . ';)',
      ],
    ];

    $data['multipart'][] = [
      'name' => 'files[u]',
      'contents' => $content,
      'filename' => $migration['file_name'],
    ];

    /** @var \GuzzleHttp\Client $client */
    $client = \Drupal::service('http_client_factory')->fromOptions($config);
    $response = $client->post($url, $data);

    try {
      $stream_size = $response->getBody()->getSize();
      $data = Json::decode($response->getBody()->read($stream_size));
    }
    catch (\Exception $e) {
      $data = $e->getMessage();
    }

    if ($response->getStatusCode() == 200) {
      if (!is_array($data)) {
        $migration['error'] = (string) t('Error occurred, please try again or consult the logs.');
        $migration['error_data'] = $data;
        return FALSE;
      }
      elseif (!empty($data['err'])) {
        $migration['error'] = $data['err'];
        $migration['error_data'] = $data;
        return FALSE;
      }
      else {
        // Validate signature.
        $response_signature = $data['sig'];
        unset($data['sig']);
        $sig = '';
        foreach ($data as $value) {
          $sig .= $value;
        }
        $signature = hash_hmac('sha256', $sig, $migration['env']['secret']);

        // Check if response is correct, if not stop migration.
        if ($signature != $response_signature) {
          $migration['error'] = (string) t('Signature from server is wrong');
          $migration['error_data'] = $data;
          return FALSE;
        }
      }
    }
    elseif ($response->getStatusCode() == 302) {
      // Final chunk, signature and any error is in Location URL.
      if (!($redirect_url = $response->getHeaderLine('location')) && $actual_uri && $actual_uri !== $url) {
        $redirect_url = $actual_uri;
      }
      $parsed = parse_url($redirect_url);
      parse_str($parsed['query'], $query);
      if (!empty($query['err'])) {
        $migration['error'] = $query['err'];
        $migration['error_data'] = $data;
        return FALSE;
      }
      else {
        $query_sig = $query['sig'];
        unset($query['sig']);

        $sig = '';
        foreach ($query as $v) {
          $sig .= $v;
        }
        $signature = hash_hmac('sha256', $sig, $migration['env']['secret']);

        if ($signature == $query_sig) {
          $query['sig'] = $query_sig;
          $migration['redirect'] = array(
            'url' => $redirect_url,
            'data' => $query,
          );
        }
        else {
          $migration['error'] = (string) t('Signature from server is wrong');
          $migration['error_data'] = $data;
          return FALSE;
        }
      }
    }
    else {
      $migration['error'] = (string) t('Transfer error');
      $migration['error_data'] = $data;
      return FALSE;
    }
  }

  /**
   * Get upload security token.
   *
   * @param int $now
   *   Timestamp.
   * @param string $return
   *   Message to be hashed.
   * @param string $secret
   *   Shared secret key used for generating the HMAC.
   *
   * @return string
   *   A string containing the calculated message digest as lowercase hexits.
   */
  public function getToken($now, $return, $secret) {
    return hash_hmac('sha256', $now . $return, $secret);
  }

  /**
   * Recursive function to find files to archive.
   *
   * @param string $directory
   *   Directory.
   * @param array $exclude
   *   Do not include these directories.
   *
   * @return array
   *   Files to archive
   */
  public function filesToBackup($directory, $exclude) {
    $array_items = array();
    if ($handle = opendir($directory)) {
      while (FALSE !== ($file = readdir($handle))) {
        if (!is_link($file) && !in_array($file, $exclude) && !in_array($directory . DIRECTORY_SEPARATOR . $file, $exclude)) {
          if (is_dir($directory . DIRECTORY_SEPARATOR . $file)) {
            // Do not include directories that cannot be executed to prevent
            // Archive_Tar error.
            if (@file_exists($directory . DIRECTORY_SEPARATOR . $file . DIRECTORY_SEPARATOR . '.')) {
              $array_items = array_merge($array_items, $this->filesToBackup($directory . DIRECTORY_SEPARATOR . $file, $exclude));
            }
          }
          elseif (is_readable($directory . DIRECTORY_SEPARATOR . $file)) {
            $file = $directory . DIRECTORY_SEPARATOR . $file;
            $array_items[] = preg_replace("/\/\//si", DIRECTORY_SEPARATOR, $file);
          }
        }
      }
      closedir($handle);
    }
    return $array_items;
  }

  /**
   * Remove database file created for migration.
   *
   * @param array $migration
   *   Migration array.
   */
  protected function cleanupDb(&$migration) {
    if (isset($migration['db_file'])) {
      \Drupal::service('file_system')->unlink($migration['db_file']);
      unset($migration['db_file']);
    }
  }

  /**
   * Remove files and directory created for migration.
   *
   * @param array $migration
   *   Migration array.
   */
  public function cleanup(&$migration) {
    if (isset($migration['db_file'])) {
      $this->cleanupDb($migration);
    }

    if (isset($migration['tar_file'])) {
      \Drupal::service('file_system')->unlink($migration['tar_file']);
      unset($migration['tar_file']);
    }

    if (isset($migration['dir'])) {
      if (is_dir($migration['dir']) && !@rmdir($migration['dir'])) {
        // Files leftover in directory, reconstruct names and remove.
        $db_file = $migration['file'] . '.sql';
        if (file_exists($db_file)) {
          \Drupal::service('file_system')->unlink($db_file);
        }
        $tar_file = $migration['file'] . '.tar';
        $tar_file .= !empty($migration['compression_ext']) ? '.' . $migration['compression_ext'] : '';
        if (file_exists($tar_file)) {
          \Drupal::service('file_system')->unlink($tar_file);
        }
        rmdir($migration['dir']);
      }

      unset($migration['dir']);
    }
    \Drupal::state()->set('migrate.cloud', $migration);
  }

  /**
   * Dump mysql database, modified from Backup & Migrate module by ronan.
   */

  /**
   * Dump the database to the specified file.
   *
   * @param array $migration
   *   Migration array.
   */
  protected function backupDbToFileMysql(&$migration) {
    // Check migration file at first to avoid dumping db to a hidden file.
    if (!isset($migration['file'])) {
      $migration['error'] = TRUE;
      return;
    }

    $file = $migration['file'] . '.sql';
    $handle = fopen($file, 'w');
    $lines = 0;
    $exclude = array();
    $nodata = $migration['no_data_tables'];

    if ($handle) {
      $data = $this->getSqlFileHeaderMysql();
      fwrite($handle, $data);
      $alltables = $this->getTablesMysql();

      foreach ($alltables as $table) {
        if ($table['name'] && !isset($exclude[$table['name']])) {
          $data = $this->getTableStructureSqlMysql($table);
          fwrite($handle, $data);
          $lines++;
          if (!in_array($table['name'], $nodata)) {
            $lines += $this->dumpTableDataSqlToFile($handle, $table);
          }
        }
      }

      $data = $this->getSqlFileFooterMysql();
      fwrite($handle, $data);
      $stat = fstat($handle);
      fclose($handle);
      // Set migration details.
      $migration['db_size'] = $stat['size'];
      $migration['db_file'] = $file;
    }
    else {
      $migration['error'] = TRUE;
    }
  }

  /**
   * Get the sql for the structure of the given table.
   *
   * @param string $table
   *   Mysql table.
   *
   * @return string
   *   SQL for the table.
   */
  protected function getTableStructureSqlMysql($table) {
    $out = "";
    $result = db_query("SHOW CREATE TABLE `" . $table['name'] . "`", array(), array('fetch' => \PDO::FETCH_ASSOC));

    foreach ($result as $create) {
      // Lowercase the keys because between Drupal 7.12 and 7.13/14 the default
      // query behavior was changed.
      // See: http://drupal.org/node/1171866
      $create = array_change_key_case($create);
      $out .= "DROP TABLE IF EXISTS `" . $table['name'] . "`;\n";
      // Remove newlines and convert " to ` because PDO seems to convert those
      // for some reason.
      $out .= strtr($create['create table'], array("\n" => ' ', '"' => '`'));

      if ($table['auto_increment']) {
        $out .= " AUTO_INCREMENT=" . $table['auto_increment'];
      }

      $out .= ";\n";
    }

    return $out;
  }

  /**
   * Get the sql to insert the data for a given table.
   */
  protected function dumpTableDataSqlToFile($handle, $table) {
    $lines = 0;

    // Escape backslashes, PHP code, special chars.
    $search = array('\\', "'", "\x00", "\x0a", "\x0d", "\x1a");
    $replace = array('\\\\', "''", '\0', '\n', '\r', '\Z');
    $result = db_query("SELECT * FROM `" . $table['name'] . "`", array(), array('fetch' => \PDO::FETCH_ASSOC));

    foreach ($result as $row) {
      $items = array();

      foreach ($row as $value) {
        $items[] = is_null($value) ? "null" : "'" . str_replace($search, $replace, $value) . "'";
      }

      if ($items) {
        $data = "INSERT INTO `" . $table['name'] . "` VALUES (" . implode(",", $items) . ");\n";
        fwrite($handle, $data);
        $lines++;
      }
    }

    return $lines;
  }

  /**
   * Get a list of tables in the db.
   */
  protected function getTablesMysql() {
    $out = array();
    $tables = db_query("SHOW TABLE STATUS", array(), array('fetch' => \PDO::FETCH_ASSOC));

    foreach ($tables as $table) {
      $table = array_change_key_case($table);
      $out[$table['name']] = $table;
    }

    return $out;
  }

  /**
   * The header for the top of the sql dump file.
   *
   * These commands set the connection character encoding to help prevent
   * encoding conversion issues.
   *
   * @return string
   *   The header for the top of the sql dump file.
   */
  protected function getSqlFileHeaderMysql() {
    return "
/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE=NO_AUTO_VALUE_ON_ZERO */;

SET NAMES utf8;
";
  }

  /**
   * The footer of the sql dump file.
   */
  public function getSqlFileFooterMysql() {
    return "

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
";
  }

}
