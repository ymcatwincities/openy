<?php

namespace Drupal\acquia_connector\Controller;

use Drupal\acquia_connector\Helper\Storage;
use Drupal\Core\Database\Database;
use Drupal\Core\DrupalKernel;
use Drupal\Component\Utility\Unicode;
use Drupal\Core\Access\AccessResultAllowed;
use Drupal\Core\Access\AccessResultForbidden;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Drupal\Core\Routing\RouteMatch;
use Drupal\acquia_connector\Client;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Logger\RfcLogLevel;
use Drupal\user\Entity\Role;
use Drupal\Core\Site\Settings;
use Drupal\Component\Utility\Html;
use Drupal\Core\Url;
use Drupal\Core\Session\AccountInterface;

/**
 * Class SpiController.
 */
class SpiController extends ControllerBase {

  /**
   * The Acquia client.
   *
   * @var \Drupal\acquia_connector\Client
   */
  protected $client;

  /**
   * Constructs a \Drupal\system\ConfigFormBase object.
   *
   * @param Client $client
   *   Acquia Client.
   */
  public function __construct(Client $client) {
    $this->client = $client;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('acquia_connector.client')
    );
  }

  /**
   * Gather site profile information about this site.
   *
   * @param string $method
   *   Optional identifier for the method initiating request.
   *   Values could be 'cron' or 'menu callback' or 'drush'.
   *
   * @return array
   *   An associative array keyed by types of information.
   */
  public function get($method = '') {
    $config = \Drupal::configFactory()->getEditable('acquia_connector.settings');

    // Get the Drupal version.
    $drupal_version = $this->getVersionInfo();

    $stored = $this->dataStoreGet(array('platform'));
    if (!empty($stored['platform'])) {
      $platform = $stored['platform'];
    }
    else {
      $platform = $this->getPlatform();
    }

    $acquia_hosted = $this->checkAcquiaHosted();
    $environment = $this->config('acquia_connector.settings')->get('spi.site_environment');
    $env_detection_enabled = $this->config('acquia_connector.settings')->get('spi.env_detection_enabled');
    if ($acquia_hosted) {
      if ($environment != $_SERVER['AH_SITE_ENVIRONMENT']) {
        $config->set('spi.site_environment', $_SERVER['AH_SITE_ENVIRONMENT']);
        $environment = $_SERVER['AH_SITE_ENVIRONMENT'];
        if ($env_detection_enabled) {
          $config->set('spi.site_machine_name', $this->getAcquiaHostedMachineName());
        }
      }
    }
    else {
      if ($environment) {
        $config->set('spi.site_environment', NULL);
      }
      $environment = NULL;
    }

    if ($env_detection_enabled === NULL) {
      $config->set('spi.env_detection_enabled', TRUE);
    }

    $config->save();

    $spi = array(
    // Used in HMAC validation.
      'rpc_version'        => ACQUIA_SPI_DATA_VERSION,
    // Used in Fix it now feature.
      'spi_data_version'   => ACQUIA_SPI_DATA_VERSION,
      'site_key'           => sha1(\Drupal::service('private_key')->get()),
      'site_uuid'          => $this->config('acquia_connector.settings')->get('spi.site_uuid'),
      'env_changed_action' => $this->config('acquia_connector.settings')->get('spi.environment_changed_action'),
      'acquia_hosted'      => $acquia_hosted,
      'name'               => $this->config('acquia_connector.settings')->get('spi.site_name'),
      'machine_name'       => $this->config('acquia_connector.settings')->get('spi.site_machine_name'),
      'environment'        => $environment,
      'modules'            => $this->getModules(),
      'platform'           => $platform,
      'quantum'            => $this->getQuantum(),
      'system_status'      => $this->getSystemStatus(),
      'failed_logins'      => $this->config('acquia_connector.settings')->get('spi.send_watchdog') ? $this->getFailedLogins() : array(),
      '404s'               => $this->config('acquia_connector.settings')->get('spi.send_watchdog') ? $this->get404s() : array(),
      'watchdog_size'      => $this->getWatchdogSize(),
      'watchdog_data'      => $this->config('acquia_connector.settings')->get('spi.send_watchdog') ? $this->getWatchdogData() : array(),
      'last_nodes'         => $this->config('acquia_connector.settings')->get('spi.send_node_user') ? $this->getLastNodes() : array(),
      'last_users'         => $this->config('acquia_connector.settings')->get('spi.send_node_user') ? $this->getLastUsers() : array(),
      'extra_files'        => $this->checkFilesPresent(),
      'ssl_login'          => $this->checkLogin(),
      'distribution'       => isset($drupal_version['distribution']) ? $drupal_version['distribution'] : '',
      'base_version'       => $drupal_version['base_version'],
      'build_data'         => $drupal_version,
      'roles'              => Json::encode(user_roles()),
      'uid_0_present'      => $this->getUidZerroIsPresent(),
    );

    $scheme = parse_url($this->config('acquia_connector.settings')->get('spi.server'), PHP_URL_SCHEME);
    $via_ssl = (in_array('ssl', stream_get_transports(), TRUE) && $scheme == 'https') ? TRUE : FALSE;
    if ($this->config('acquia_connector.settings')->get('spi.ssl_override')) {
      $via_ssl = TRUE;
    }

    $additional_data = array();

    $security_review = new SecurityReviewController();
    $security_review_results = $security_review->runSecurityReview();

    // It's worth sending along node access control information even if there
    // are no modules implementing it - some alerts are simpler if we know we
    // don't have to worry about node access.
    // Check for node grants modules.
    $additional_data['node_grants_modules'] = \Drupal::moduleHandler()->getImplementations('node_grants');

    // Check for node access modules.
    $additional_data['node_access_modules'] = \Drupal::moduleHandler()->getImplementations('node_access');

    if (!empty($security_review_results)) {
      $additional_data['security_review'] = $security_review_results['security_review'];
    }

    // Collect all user-contributed custom tests that pass validation.
    $custom_tests_results = $this->testCollect();
    if (!empty($custom_tests_results)) {
      $additional_data['custom_tests'] = $custom_tests_results;
    }

    $spi_data = \Drupal::moduleHandler()->invokeAll('acquia_connector_spi_get');
    if (!empty($spi_data)) {
      foreach ($spi_data as $name => $data) {
        if (is_string($name) && is_array($data)) {
          $additional_data[$name] = $data;
        }
      }
    }

    include_once "core/includes/update.inc";
    $additional_data['pending_updates'] = (bool) update_get_update_list();

    if (!empty($additional_data)) {
      // JSON encode this additional data.
      $spi['additional_data'] = json_encode($additional_data);
    }

    if (!empty($method)) {
      $spi['send_method'] = $method;
    }

    if (!$via_ssl) {
      return $spi;
    }
    else {
      $variablesController = new VariablesController();
      // Values returned only over SSL.
      $spi_ssl = array(
        'system_vars' => $variablesController->getVariablesData(),
        'settings_ra' => $this->getSettingsPermissions(),
        'admin_count' => $this->config('acquia_connector.settings')->get('spi.admin_priv') ? $this->getAdminCount() : '',
        'admin_name' => $this->config('acquia_connector.settings')->get('spi.admin_priv') ? $this->getSuperName() : '',
      );

      return array_merge($spi, $spi_ssl);
    }
  }

  /**
   * Collects all user-contributed test results that pass validation.
   *
   * @return array $custom_data
   *   An associative array containing properly formatted user-contributed
   *   tests.
   */
  private function testCollect() {
    $custom_data = array();

    // Collect all custom data provided by hook_insight_custom_data().
    $collections = \Drupal::moduleHandler()->invokeAll('acquia_connector_spi_test');

    foreach ($collections as $test_name => $test_params) {
      $status = new TestStatusController();
      $result = $status->testValidate(array($test_name => $test_params));

      if ($result['result']) {
        $custom_data[$test_name] = $test_params;
      }
    }

    return $custom_data;
  }

  /**
   * Checks to see if SSL login is required.
   *
   * @return int
   *   1 if SSL login is required.
   */
  private function checkLogin() {
    $login_safe = 0;

    if (\Drupal::moduleHandler()->moduleExists('securelogin')) {
      $secureLoginConfig = $this->config('securelogin.settings')->get();
      if ($secureLoginConfig['all_forms']) {
        $forms_safe = TRUE;
      }
      else {
        // All the required forms should be enabled.
        $required_forms = array(
          'form_user_login_form',
          'form_user_form',
          'form_user_register_form',
          'form_user_pass_reset',
          'form_user_pass',
        );
        $forms_safe = TRUE;
        foreach ($required_forms as $form_variable) {
          if (!$secureLoginConfig[$form_variable]) {
            $forms_safe = FALSE;
            break;
          }
        }
      }
      // \Drupal::request()->isSecure() ($conf['https'] in D7) should be false
      // for expected behavior.
      if ($forms_safe && !\Drupal::request()->isSecure()) {
        $login_safe = 1;
      }
    }

    return $login_safe;
  }

  /**
   * Check to see if the unneeded release files with Drupal are removed.
   *
   * @return int
   *   1 if they are removed, 0 if they aren't.
   */
  private function checkFilesPresent() {
    $server = \Drupal::request()->server->all();
    $files_exist = FALSE;
    $files_to_remove = array('CHANGELOG.txt', 'COPYRIGHT.txt', 'INSTALL.mysql.txt', 'INSTALL.pgsql.txt', 'INSTALL.txt', 'LICENSE.txt',
      'MAINTAINERS.txt', 'README.txt', 'UPGRADE.txt', 'PRESSFLOW.txt', 'install.php',
    );

    foreach ($files_to_remove as $file) {
      $path = $server['DOCUMENT_ROOT'] . base_path() . $file;
      if (file_exists($path)) {
        $files_exist = TRUE;
      }
    }

    return $files_exist ? 1 : 0;
  }

  /**
   * Attempt to determine if this site is hosted with Acquia.
   *
   * @return bool
   *   TRUE if site is hosted with Acquia, otherwise FALSE.
   */
  public function checkAcquiaHosted() {
    return isset($_SERVER['AH_SITE_ENVIRONMENT'], $_SERVER['AH_SITE_NAME']);
  }

  /**
   * Generate the name for acquia hosted sites.
   *
   * @return string
   *   The Acquia Hosted name.
   */
  public function getAcquiaHostedName() {
    $subscription_name = $this->config('acquia_connector.settings')->get('subscription_name');

    if ($this->checkAcquiaHosted() && $subscription_name) {
      return $this->config('acquia_connector.settings')->get('subscription_name') . ': ' . $_SERVER['AH_SITE_ENVIRONMENT'];
    }
  }

  /**
   * Generate the machine name for acquia hosted sites.
   *
   * @return string
   *   The suggested Acquia Hosted machine name.
   */
  public function getAcquiaHostedMachineName() {
    $sub_data = $this->config('acquia_connector.settings')->get('subscription_data');

    if ($this->checkAcquiaHosted() && $sub_data) {
      $uuid = new StatusController();
      $sub_uuid = str_replace('-', '_', $uuid->getIdFromSub($sub_data));

      return $sub_uuid . '__' . $_SERVER['AH_SITE_NAME'] . '__' . uniqid();
    }
  }

  /**
   * Check if a site environment change has been detected.
   *
   * @return bool
   *   TRUE if change detected that needs to be addressed, otherwise FALSE.
   */
  public function checkEnvironmentChange() {
    $changes = $this->config('acquia_connector.settings')->get('spi.environment_changes');
    $change_action = $this->config('acquia_connector.settings')->get('spi.environment_changed_action');

    return !empty($changes) && empty($change_action);
  }

  /**
   * Get last 15 users created.
   *
   * Useful for determining if your site is compromised.
   *
   * @return array
   *   The details of last 15 users created.
   */
  private function getLastUsers() {
    $last_five_users = array();
    $result = db_select('users_field_data', 'u')
      ->fields('u', array('uid', 'name', 'mail', 'created'))
      ->condition('u.created', REQUEST_TIME - 3600, '>')
      ->orderBy('created', 'DESC')
      ->range(0, 15)
      ->execute();

    $count = 0;
    foreach ($result as $record) {
      $last_five_users[$count]['uid'] = $record->uid;
      $last_five_users[$count]['name'] = $record->name;
      $last_five_users[$count]['email'] = $record->mail;
      $last_five_users[$count]['created'] = $record->created;
      $count++;
    }

    return $last_five_users;
  }

  /**
   * Get last 15 nodes created.
   *
   * This can be useful to determine if you have some sort of spam on your site.
   *
   * @return array
   *   Array of the details of last 15 nodes created.
   */
  private function getLastNodes() {
    $last_five_nodes = array();
    if (\Drupal::moduleHandler()->moduleExists('node')) {
      $result = db_select('node_field_data', 'n')
        ->fields('n', array('title', 'type', 'nid', 'created', 'langcode'))
        ->condition('n.created', REQUEST_TIME - 3600, '>')
        ->orderBy('n.created', 'DESC')
        ->range(0, 15)
        ->execute();

      $count = 0;
      foreach ($result as $record) {
        $last_five_nodes[$count]['url'] = \Drupal::service('path.alias_manager')
          ->getAliasByPath('/node/' . $record->nid, $record->langcode);
        $last_five_nodes[$count]['title'] = $record->title;
        $last_five_nodes[$count]['type'] = $record->type;
        $last_five_nodes[$count]['created'] = $record->created;
        $count++;
      }
    }

    return $last_five_nodes;
  }

  /**
   * Get the latest (last hour) critical and emergency warnings from watchdog.
   *
   * These errors are 'severity' 0 and 2.
   *
   * @return array
   *   EMERGENCY and CRITICAL watchdog records for last hour.
   */
  private function getWatchdogData() {
    $wd = array();
    if (\Drupal::moduleHandler()->moduleExists('dblog')) {
      $result = db_select('watchdog', 'w')
        ->fields('w', array('wid', 'severity', 'type', 'message', 'timestamp'))
        ->condition('w.severity', array(RfcLogLevel::EMERGENCY, RfcLogLevel::CRITICAL), 'IN')
        ->condition('w.timestamp', REQUEST_TIME - 3600, '>')
        ->execute();

      while ($record = $result->fetchAssoc()) {
        $wd[$record['severity']] = $record;
      }
    }

    return $wd;
  }

  /**
   * Get the number of rows in watchdog.
   *
   * @return int
   *   Number of watchdog records.
   */
  private function getWatchdogSize() {
    if (\Drupal::moduleHandler()->moduleExists('dblog')) {
      return db_select('watchdog', 'w')->fields('w', array('wid'))->countQuery()->execute()->fetchField();
    }
  }

  /**
   * Grabs the last 404 errors in logs.
   *
   * Grabs the last 404 errors in logs, excluding the checks we run for drupal
   * files like README.
   *
   * @return array
   *   An array of the pages not found and some associated data.
   */
  private function get404s() {
    $data = array();
    $row = 0;

    if (\Drupal::moduleHandler()->moduleExists('dblog')) {
      $result = db_select('watchdog', 'w')
        ->fields('w', array('message', 'hostname', 'referer', 'timestamp'))
        ->condition('w.type', 'page not found', '=')
        ->condition('w.timestamp', REQUEST_TIME - 3600, '>')
        ->condition('w.message', array(
          "UPGRADE.txt",
          "MAINTAINERS.txt",
          "README.txt",
          "INSTALL.pgsql.txt",
          "INSTALL.txt",
          "LICENSE.txt",
          "INSTALL.mysql.txt",
          "COPYRIGHT.txt",
          "CHANGELOG.txt",
        ), 'NOT IN')
        ->orderBy('w.timestamp', 'DESC')
        ->range(0, 10)
        ->execute();

      foreach ($result as $record) {
        $data[$row]['message'] = $record->message;
        $data[$row]['hostname'] = $record->hostname;
        $data[$row]['referer'] = $record->referer;
        $data[$row]['timestamp'] = $record->timestamp;
        $row++;
      }
    }

    return $data;
  }

  /**
   * Get the information on failed logins in the last cron interval.
   *
   * @return array
   *   Array of last 10 failed logins.
   */
  private function getFailedLogins() {
    $last_logins = array();
    $cron_interval = $this->config('acquia_connector.settings')->get('spi.cron_interval');

    if (\Drupal::moduleHandler()->moduleExists('dblog')) {
      $result = db_select('watchdog', 'w')
        ->fields('w', array('message', 'variables', 'timestamp'))
        ->condition('w.message', 'login attempt failed%', 'LIKE')
        ->condition('w.timestamp', REQUEST_TIME - $cron_interval, '>')
        ->condition('w.message', array(
          "UPGRADE.txt",
          "MAINTAINERS.txt",
          "README.txt",
          "INSTALL.pgsql.txt",
          "INSTALL.txt",
          "LICENSE.txt",
          "INSTALL.mysql.txt",
          "COPYRIGHT.txt",
          "CHANGELOG.txt",
        ), 'NOT IN')
        ->orderBy('w.timestamp', 'DESC')
        ->range(0, 10)
        ->execute();

      foreach ($result as $record) {
        $variables = unserialize($record->variables);
        if (!empty($variables['%user'])) {
          $last_logins['failed'][$record->timestamp] = Html::escape($variables['%user']);
        }
      }
    }
    return $last_logins;
  }

  /**
   * This function is a trimmed version of Drupal's system_status function.
   *
   * @return array
   *   System status array.
   */
  private function getSystemStatus() {
    $data = array();

    $profile = drupal_get_profile();
    if ($profile != 'standard') {
      $info = system_get_info('module', $profile);
      $data['install_profile'] = array(
        'title' => 'Install profile',
        'value' => sprintf('%s (%s-%s)', $info['name'], $profile, $info['version']),
      );
    }
    $data['php'] = array(
      'title' => 'PHP',
      'value' => phpversion(),
    );
    $conf_dir = TRUE;
    $settings = TRUE;
    $dir = DrupalKernel::findSitePath(\Drupal::request(), TRUE);
    if (is_writable($dir) || is_writable($dir . '/settings.php')) {
      $value = 'Not protected';
      if (is_writable($dir)) {
        $conf_dir = FALSE;
      }
      elseif (is_writable($dir . '/settings.php')) {
        $settings = FALSE;
      }
    }
    else {
      $value = 'Protected';
    }
    $data['settings.php'] = array(
      'title' => 'Configuration file',
      'value' => $value,
      'conf_dir' => $conf_dir,
      'settings' => $settings,
    );
    $cron_last = \Drupal::state()->get('system.cron_last');
    if (!is_numeric($cron_last)) {
      $cron_last = \Drupal::state()->get('install_time', 0);
    }
    $data['cron'] = array(
      'title' => 'Cron maintenance tasks',
      'value' => sprintf('Last run %s ago', \Drupal::service('date.formatter')->formatInterval(REQUEST_TIME - $cron_last)),
      'cron_last' => $cron_last,
    );
    if (!empty(Settings::get('update_free_access'))) {
      $data['update access'] = array(
        'value' => 'Not protected',
        'protected' => FALSE,
      );
    }
    else {
      $data['update access'] = array(
        'value' => 'Protected',
        'protected' => TRUE,
      );
    }
    $data['update access']['title'] = 'Access to update.php';
    if (!\Drupal::moduleHandler()->moduleExists('update')) {
      $data['update status'] = array(
        'value' => 'Not enabled',
      );
    }
    else {
      $data['update status'] = array(
        'value' => 'Enabled',
      );
    }
    $data['update status']['title'] = 'Update notifications';
    return $data;
  }

  /**
   * Check the presence of UID 0 in the users table.
   *
   * @return bool
   *   Whether UID 0 is present.
   */
  private function getUidZerroIsPresent() {
    $count = db_query("SELECT uid FROM {users} WHERE uid = 0")->fetchAll();
    return (boolean) $count;
  }

  /**
   * The number of users who have admin-level user roles.
   *
   * @return int
   *   Count of admin users.
   */
  private function getAdminCount() {
    $roles_name = array();
    $get_roles = Role::loadMultiple();
    unset($get_roles[AccountInterface::ANONYMOUS_ROLE]);
    $permission = array('administer permissions', 'administer users');
    foreach ($permission as $value) {
      $filtered_roles = array_filter($get_roles, function ($role) use ($value) {
        return $role->hasPermission($value);
      });
      foreach ($filtered_roles as $role_name => $data) {
        $roles_name[] = $role_name;
      }
    }

    if (!empty($roles_name)) {
      $roles_name_unique = array_unique($roles_name);
      $query = db_select('user__roles', 'ur');
      $query->fields('ur', array('entity_id'));
      $query->condition('ur.bundle', 'user', '=');
      $query->condition('ur.deleted', '0', '=');
      $query->condition('ur.roles_target_id', $roles_name_unique, 'IN');
      $count = $query->countQuery()->execute()->fetchField();
    }

    return (isset($count) && is_numeric($count)) ? $count : NULL;
  }

  /**
   * Determine if the super user has a weak name.
   *
   * @return int
   *   1 if the super user has a weak name, 0 otherwise.
   */
  private function getSuperName() {
    $result = db_query("SELECT name FROM {users_field_data} WHERE uid = 1 AND (name LIKE '%admin%' OR name LIKE '%root%')")->fetchAll();
    return (int) $result;
  }

  /**
   * Determines if settings.php is read-only.
   *
   * @return bool
   *   TRUE if settings.php is read-only, FALSE otherwise.
   */
  private function getSettingsPermissions() {
    $settings_permissions_read_only = TRUE;
    // http://en.wikipedia.org/wiki/File_system_permissions.
    $writes = array('2', '3', '6', '7');
    $settings_file = './' . DrupalKernel::findSitePath(\Drupal::request(), TRUE) . '/settings.php';
    $permissions = Unicode::substr(sprintf('%o', fileperms($settings_file)), -4);

    foreach ($writes as $bit) {
      if (strpos($permissions, $bit)) {
        $settings_permissions_read_only = FALSE;
        break;
      }
    }

    return $settings_permissions_read_only;
  }

  /**
   * Determine if a path is a file type we care about for modificaitons.
   */
  private function isManifestType($path) {
    $extensions = array(
      'yml' => 1,
      'php' => 1,
      'php4' => 1,
      'php5' => 1,
      'module' => 1,
      'inc' => 1,
      'install' => 1,
      'test' => 1,
      'theme' => 1,
      'engine' => 1,
      'profile' => 1,
      'css' => 1,
      'js' => 1,
      'info' => 1,
      'sh' => 1,
      // SSL certificates.
      'pem' => 1,
      'pl' => 1,
      'pm' => 1,
    );
    $pathinfo = pathinfo($path);
    return isset($pathinfo['extension']) && isset($extensions[$pathinfo['extension']]);
  }

  /**
   * Calculate the sha1 hash for a path.
   *
   * @param string $path
   *   The name of the file or a directory.
   *
   * @return string
   *   base64 encoded sha1 hash. 'hash' is an empty string for directories.
   */
  private function hashPath($path = '') {
    $hash = '';
    if (file_exists($path)) {
      if (!is_dir($path)) {
        $string = file_get_contents($path);
        // Remove trailing whitespace.
        $string = rtrim($string);
        // Replace all line endings and CVS/svn Id tags.
        $string = preg_replace('/\$Id[^;<>{}\(\)\$]*\$/', 'x$' . 'Id$', $string);
        $string = preg_replace('/\r\n|\n|\r/', ' ', $string);
        $hash = base64_encode(pack("H*", sha1($string)));
      }
    }
    return $hash;
  }

  /**
   * Attempt to determine the version of Drupal being used.
   *
   * Note, there is better information on this in the common.inc file.
   *
   * @return array
   *   An array containing some detail about the version
   */
  private function getVersionInfo() {
    $server = \Drupal::request()->server->all();
    $ver = array();

    $ver['base_version'] = \Drupal::VERSION;
    $install_root = $server['DOCUMENT_ROOT'] . base_path();
    $ver['distribution']  = '';

    // Determine if this puppy is Acquia Drupal.
    acquia_connector_load_versions();

    if (IS_ACQUIA_DRUPAL) {
      $ver['distribution']   = 'Acquia Drupal';
      $ver['ad']['version']  = ACQUIA_DRUPAL_VERSION;
      $ver['ad']['series']   = ACQUIA_DRUPAL_SERIES;
      $ver['ad']['branch']   = ACQUIA_DRUPAL_BRANCH;
      $ver['ad']['revision'] = ACQUIA_DRUPAL_REVISION;
    }

    // @todo: Review all D8 distributions!
    // Determine if we are looking at Pressflow.
    if (defined('CACHE_EXTERNAL')) {
      $ver['distribution']  = 'Pressflow';
      $press_version_file = $install_root . './PRESSFLOW.txt';
      if (is_file($press_version_file)) {
        $ver['pr']['version'] = trim(file_get_contents($press_version_file));
      }
    }
    // Determine if this is Open Atrium.
    elseif (is_dir($install_root . '/profiles/openatrium')) {
      $ver['distribution']  = 'Open Atrium';
      $version_file = $install_root . 'profiles/openatrium/VERSION.txt';
      if (is_file($version_file)) {
        $ver['oa']['version'] = trim(file_get_contents($version_file));
      }
    }
    // Determine if this is Commons.
    elseif (is_dir($install_root . '/profiles/commons')) {
      $ver['distribution']  = 'Commons';
    }
    // Determine if this is COD.
    elseif (is_dir($install_root . '/profiles/cod')) {
      $ver['distribution']  = 'COD';
    }

    return $ver;
  }

  /**
   * Put SPI data in local storage.
   *
   * @param array $data
   *   Keyed array of data to store.
   * @param int $expire
   *   Expire time or null to use default of 1 day.
   */
  public function dataStoreSet($data, $expire = NULL) {
    if (is_null($expire)) {
      $expire = REQUEST_TIME + (60 * 60 * 24);
    }
    foreach ($data as $key => $value) {
      \Drupal::cache()->set('acquia.spi.' . $key, $value, $expire);
    }
  }

  /**
   * Get SPI data out of local storage.
   *
   * @param array $keys
   *   Array of keys to extract data for.
   *
   * @return array
   *   Stored data or false if no data is retrievable from storage.
   */
  public function dataStoreGet($keys) {
    $store = array();
    foreach ($keys as $key) {
      if ($cache = \Drupal::cache()->get('acquia.spi.' . $key)) {
        if (!empty($cache->data)) {
          $store[$key] = $cache->data;
        }
      }
    }
    return $store;
  }

  /**
   * Gather platform specific information.
   *
   * @return array
   *   An associative array keyed by a platform information type.
   */
  public static function getPlatform() {
    $server = \Drupal::request()->server;
    // Database detection depends on the structure starting with the database.
    $db_class = '\Drupal\Core\Database\Driver\\' . Database::getConnection()->driver() . '\Install\Tasks';
    $db_tasks = new $db_class();
    // Webserver detection is based on name being before the slash, and
    // version being after the slash.
    preg_match('!^([^/]+)(/.+)?$!', $server->get('SERVER_SOFTWARE'), $webserver);

    if (isset($webserver[1]) && stristr($webserver[1], 'Apache') && function_exists('apache_get_version')) {
      $webserver[2] = apache_get_version();
    }

    // Get some basic PHP vars.
    $php_quantum = array(
      'memory_limit' => ini_get('memory_limit'),
      'register_globals' => ini_get('register_globals'),
      'post_max_size' => ini_get('post_max_size'),
      'max_execution_time' => ini_get('max_execution_time'),
      'upload_max_filesize' => ini_get('upload_max_filesize'),
      'error_log' => ini_get('error_log'),
      'error_reporting' => ini_get('error_reporting'),
      'display_errors' => ini_get('display_errors'),
      'log_errors' => ini_get('log_errors'),
      'session.cookie_domain' => ini_get('session.cookie_domain'),
      'session.cookie_lifetime' => ini_get('session.cookie_lifetime'),
      'newrelic.appname' => ini_get('newrelic.appname'),
      'sapi' => php_sapi_name(),
    );

    $platform = array(
      'php'               => PHP_VERSION,
      'webserver_type'    => isset($webserver[1]) ? $webserver[1] : '',
      'webserver_version' => isset($webserver[2]) ? $webserver[2] : '',
      'php_extensions'    => get_loaded_extensions(),
      'php_quantum'       => $php_quantum,
      'database_type'     => (string) $db_tasks->name(),
      'database_version'  => Database::getConnection()->version(),
      'system_type'       => php_uname('s'),
      // php_uname() only accepts one character, so we need to concatenate
      // ourselves.
      'system_version'    => php_uname('r') . ' ' . php_uname('v') . ' ' . php_uname('m') . ' ' . php_uname('n'),
    );

    return $platform;
  }

  /**
   * Gather information about modules on the site.
   *
   * @return array
   *   An associative array keyed by filename of associative arrays with
   *   information on the modules.
   */
  private function getModules() {
    // @todo Only do a full rebuild of the module cache every 1 at the most
    $modules = system_rebuild_module_data();
    uasort($modules, 'system_sort_modules_by_info_name');

    $result = array();
    $keys_to_send = array('name', 'version', 'package', 'core', 'project');
    foreach ($modules as $module) {
      $info = array();
      $info['status'] = $module->status;
      foreach ($keys_to_send as $key) {
        $info[$key] = isset($module->info[$key]) ? $module->info[$key] : '';
      }
      $info['filename'] = $module->getPathname();
      if (empty($info['project']) && $module->origin == 'core') {
        $info['project'] = 'drupal';
      }

      $result[] = $info;
    }
    return $result;
  }

  /**
   * Gather information about nodes, users and comments.
   *
   * @return array
   *   An associative array.
   */
  private function getQuantum() {
    $quantum = array();

    if (\Drupal::moduleHandler()->moduleExists('node')) {
      // Get only published nodes.
      $quantum['nodes'] = db_select('node_field_data', 'n')->fields('n', array('nid'))->condition('n.status', NODE_PUBLISHED)->countQuery()->execute()->fetchField();
    }

    // Get only active users.
    $quantum['users'] = db_select('users_field_data', 'u')->fields('u', array('uid'))->condition('u.status', 1)->countQuery()->execute()->fetchField();

    if (\Drupal::moduleHandler()->moduleExists('comment')) {
      // Get only active comments.
      $quantum['comments'] = db_select('comment_field_data', 'c')->fields('c', array('cid'))->condition('c.status', 1)->countQuery()->execute()->fetchField();
    }

    return $quantum;
  }

  /**
   * Gather full SPI data and send to Acquia Network.
   *
   * @param string $method
   *   Optional identifier for the method initiating request.
   *   Values could be 'cron' or 'menu callback' or 'drush'.
   *
   * @return mixed
   *   FALSE if data is not sent or environment change detected,
   *   otherwise return NSPI response array.
   */
  public function sendFullSpi($method = '') {
    $spi = self::get($method);

    if ($this->checkEnvironmentChange()) {
      \Drupal::logger('acquia spi')->error('SPI data not sent, site environment change detected.');
      drupal_set_message(t('SPI data not sent, site environment change detected. Please <a href="@environment_change">indicate how you wish to proceed</a>.', array(
        '@environment_change' => Url::fromRoute('acquia_connector.environment_change')->toString(),
      )), 'error');
      return FALSE;
    }

    $storage = new Storage();
    $response = $this->client->sendNspi($storage->getIdentifier(), $storage->getKey(), $spi);

    if ($response === FALSE) {
      return FALSE;
    }

    $this->handleServerResponse($response);
    \Drupal::state()->set('acquia_connector.cron_last', REQUEST_TIME);

    return $response;
  }

  /**
   * Callback for sending SPI data.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Request.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
   *   Redirect to the destination or return HTTP_BAD_REQUEST|HTTP_OK response.
   */
  public function send(Request $request) {
    // Mark this page as being uncacheable.
    \Drupal::service('page_cache_kill_switch')->trigger();
    $method = ACQUIA_SPI_METHOD_CALLBACK;

    // Insight's set variable feature will pass method insight.
    if ($request->query->has('method') && ($request->query->get('method') === ACQUIA_SPI_METHOD_INSIGHT)) {
      $method = ACQUIA_SPI_METHOD_INSIGHT;
    }

    $response = $this->sendFullSpi($method);

    if ($request->get('destination')) {
      $this->spiProcessMessages($response);
      $route_match = RouteMatch::createFromRequest($request);
      return $this->redirect($route_match->getRouteName(), $route_match->getRawParameters()->all());
    }

    $headers = [
      'Expires' => 'Mon, 26 Jul 1997 05:00:00 GMT',
      'Content-Type' => 'text/plain',
      'Cache-Control' => 'no-cache',
      'Pragma' => 'no-cache',
    ];
    if (empty($response['body'])) {
      return new Response('', Response::HTTP_BAD_REQUEST, $headers);
    }

    return new Response('', Response::HTTP_OK, $headers);
  }

  /**
   * Parses and displays messages from the NSPI response.
   *
   * @param array $response
   *   Response array from NSPI.
   */
  public function spiProcessMessages($response) {
    if (empty($response['body'])) {
      drupal_set_message($this->t('Error sending SPI data. Consult the logs for more information.'), 'error');
      return;
    }

    $message_type = 'status';

    if (isset($response['body']['spi_data_received']) && $response['body']['spi_data_received'] === TRUE) {
      drupal_set_message($this->t('SPI data sent.'));
    }

    if (!empty($response['body']['nspi_messages'])) {
      drupal_set_message($this->t('Acquia Subscription returned the following messages. Further information may be in the logs.'));
      foreach ($response['body']['nspi_messages'] as $nspi_message) {
        if (!empty($response['body']['spi_error'])) {
          $message_type = $response['body']['spi_error'];
        }
        drupal_set_message(Html::escape($nspi_message), $message_type);
      }
    }

    if (!empty($response['body']['spi_environment_changes'])) {
      \Drupal::configFactory()
        ->getEditable('acquia_connector.settings')
        ->set('spi.environment_changes', Json::decode($response['body']['spi_environment_changes']))
        ->save();
    }
  }

  /**
   * Act on specific elements of SPI update server response.
   *
   * @param array $spi_response
   *   Array response from SpiController->send().
   */
  private function handleServerResponse($spi_response) {

    $config_set = \Drupal::configFactory()->getEditable('acquia_connector.settings');
    $changed_action = $this->config('acquia_connector.settings')->get('spi.environment_changed_action');
    $config_set->clear('spi.environment_changed_action')->save();
    $site_uuid = $this->config('acquia_connector.settings')->get('spi.site_uuid');

    // Set site_uuid if it changed or if it hasn't been previously captured.
    if (isset($spi_response['body']['site_uuid']) && (is_null($site_uuid) || $spi_response['body']['site_uuid'] != $site_uuid)) {
      $config_set->set('spi.site_uuid', $spi_response['body']['site_uuid'])->save();
    }

    // Wipe the site_uuid if it is set locally, but NSPI is trying to create a
    // new site.
    if (isset($spi_response['body']['site_uuid']) && empty($spi_response['body']['site_uuid']) && !is_null($site_uuid)) {
      $config_set->clear('spi.site_uuid')->save();
    }

    $spi_environment_changes = isset($spi_response['body']['spi_environment_changes']) ? Json::decode($spi_response['body']['spi_environment_changes']) : array();
    $site_blocked = array_key_exists('blocked', $spi_environment_changes) || !empty($spi_response['site_revoked']);

    // Address any actions taken based on a site environment change.
    if (!empty($changed_action) || $site_blocked) {
      if ($changed_action == 'create' && isset($spi_response['body']['site_uuid'])) {
        $config_set->set('spi.site_uuid', $spi_response['body']['site_uuid'])->save();
      }
      elseif (($changed_action == 'block' && array_key_exists('spi_error', $spi_response['body']) && empty($spi_response['body']['spi_error'])) || $site_blocked) {
        $config_set->set('spi.blocked', TRUE)->save();
      }
      elseif ($changed_action == 'unblock' && array_key_exists('spi_error', $spi_response['body']) && empty($spi_response['body']['spi_error'])) {
        $config_set->set('spi.blocked', FALSE)->save();
      }

      // If there were no errors, clear any pending actions.
      if (empty($spi_response['body']['spi_error'])) {
        $config_set->clear('spi.environment_changes')->save();
      }
    }

    // Check result for command to update SPI definition.
    $update = isset($spi_response['body']['update_spi_definition']) ? $spi_response['body']['update_spi_definition'] : FALSE;
    if ($update === TRUE) {
      $this->updateDefinition();
    }
    // Check for set_variables command.
    $set_variables = isset($spi_response['body']['set_variables']) ? $spi_response['body']['set_variables'] : FALSE;
    if ($set_variables !== FALSE) {
      $variablesController = new VariablesController();
      $variablesController->setVariables($set_variables);
    }
    // Log messages.
    $messages = isset($spi_response['body']['nspi_messages']) ? $spi_response['body']['nspi_messages'] : FALSE;
    if ($messages !== FALSE) {
      \Drupal::logger('acquia spi')->notice('SPI update server response messages: @messages', array('@messages' => implode(', ', $messages)));
    }
  }

  /**
   * Checks if NSPI server has an updated SPI data definition.
   *
   * If it does, then this function updates local copy of SPI definition data.
   *
   * @return bool
   *   True if SPI definition data has been updated.
   */
  private function updateDefinition() {
    $core_version = substr(\Drupal::VERSION, 0, 1);
    $spi_def_end_point = '/spi_def/get/' . $core_version;

    $response = $this->client->getDefinition($spi_def_end_point);

    if (!$response) {
      \Drupal::logger('acquia spi')->error('Failed to obtain latest SPI data definition.');
      return FALSE;
    }
    else {
      $response_data = $response;
      $expected_data_types = array(
        'drupal_version' => 'string',
        'timestamp' => 'string',
        'acquia_spi_variables' => 'array',
      );
      // Make sure that $response_data contains everything expected.
      foreach ($expected_data_types as $key => $values) {
        if (!array_key_exists($key, $response_data) || gettype($response_data[$key]) != $expected_data_types[$key]) {
          \Drupal::logger('acquia spi')
            ->error('Received SPI data definition does not match expected pattern while checking "@key". Received and expected data: @data', array(
              '@key' => $key,
              '@data' => var_export(array_merge(array('expected_data' => $expected_data_types), array('response_data' => $response_data)), TRUE),
            ));
          return FALSE;
        }
      }
      if ($response_data['drupal_version'] != $core_version) {
        \Drupal::logger('acquia spi')->notice('Received SPI data definition does not match with current version of your Drupal installation. Data received for Drupal @version', array('@version' => $response_data['drupal_version']));
        return FALSE;
      }
    }

    // NSPI response is in expected format.
    if ((int) $response_data['timestamp'] > (int) $this->config('acquia_connector.settings')->get('spi.def_timestamp')) {
      // Compare stored variable names to incoming and report on update.
      $old_vars = $this->config('acquia_connector.settings')->get('spi.def_vars');
      $new_vars = $response_data['acquia_spi_variables'];
      $new_optional_vars = 0;
      foreach ($new_vars as $new_var_name => $new_var) {
        // Count if received from NSPI optional variable is not present in old
        // local SPI definition or if it already was in old SPI definition, but
        // was not optional.
        if ($new_var['optional'] && !array_key_exists($new_var_name, $old_vars) ||
          $new_var['optional'] && isset($old_vars[$new_var_name]) && !$old_vars[$new_var_name]['optional']) {
          $new_optional_vars++;
        }
      }
      // Clean up waived vars that are not exposed by NSPI anymore.
      $waived_spi_def_vars = $this->config('acquia_connector.settings')->get('spi.def_waived_vars');
      $changed_bool = FALSE;
      foreach ($waived_spi_def_vars as $key => $waived_var) {
        if (!in_array($waived_var, $new_vars)) {
          unset($waived_spi_def_vars[$key]);
          $changed_bool = TRUE;
        }
      }

      $config = \Drupal::configFactory()->getEditable('acquia_connector.settings');
      if ($changed_bool) {
        $config->set('spi.def_waived_vars', $waived_spi_def_vars);
      }
      // Finally, save SPI definition data.
      if ($new_optional_vars > 0) {
        $config->set('spi.new_optional_data', 1);
      }
      $config->set('spi.def_timestamp', $response_data['timestamp']);
      $config->set('spi.def_vars', $response_data['acquia_spi_variables']);
      $config->save();
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Access callback check for SPI send independent call.
   */
  public function sendAccess() {
    $request = \Drupal::request();
    $storage = new Storage();
    $acquia_key = $storage->getKey();
    if (!empty($acquia_key) && $request->get('key')) {
      $key = sha1(\Drupal::service('private_key')->get());
      if ($key === $request->get('key')) {
        return AccessResultAllowed::allowed();
      }
    }
    return AccessResultForbidden::forbidden();
  }

}
