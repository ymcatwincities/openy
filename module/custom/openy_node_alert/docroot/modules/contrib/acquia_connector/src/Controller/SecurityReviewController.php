<?php

namespace Drupal\acquia_connector\Controller;

use Drupal\views\Views;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Site\Settings;
use Drupal\Core\DrupalKernel;
use Drupal\field\Entity\FieldConfig;
use Drupal\Core\Session\AccountInterface;

/**
 * Class SecurityReviewController.
 *
 * @package Drupal\acquia_connector\Controller
 */
class SecurityReviewController extends ControllerBase {

  /**
   * Run some checks from the Security Review module.
   */
  public function runSecurityReview() {
    // Collect the checklist.
    $checklist = $this->securityReviewGetChecks();
    // Run only specific checks.
    $to_check = [
      'views_access',
      'temporary_files',
      'executable_php',
      'input_formats',
      'admin_permissions',
      'untrusted_php',
      'private_files',
      'upload_extensions',
    ];
    foreach ($checklist as $module => $checks) {
      foreach ($checks as $check_name => $args) {
        if (!in_array($check_name, $to_check)) {
          unset($checklist[$module][$check_name]);
        }
      }
      if (empty($checklist[$module])) {
        unset($checklist[$module]);
      }
    }
    $checklist_results = $this->securityReviewRun($checklist);
    foreach ($checklist_results as $module => $checks) {
      foreach ($checks as $check_name => $check) {
        // Unset data that does not need to be sent.
        if (is_null($check['result'])) {
          unset($checklist_results[$module][$check_name]);
        }
        else {
          unset($check['success']);
          unset($check['failure']);
          $checklist_results[$module][$check_name] = $check;
        }
      }
      if (empty($checklist_results[$module])) {
        unset($checklist_results[$module]);
      }
    }
    return $checklist_results;
  }

  /**
   * Function for running Security Review checklist and returning results.
   *
   * @param array $checklist
   *   Array of checks to run, indexed by module namespace.
   * @param bool $log
   *   Whether to log check processing using security_review_log.
   * @param bool $help
   *   Whether to load the help file and include in results.
   *
   * @return array
   *   Results from running checklist, indexed by module namespace.
   */
  private function securityReviewRun($checklist = NULL, $log = FALSE, $help = FALSE) {
    // @todo Use Security Review module if available.
    return $this->_securityReviewRun($checklist, $log);
  }

  /**
   * Private function the review and returns the full results.
   *
   * @param array $checklist
   *   Array of checks.
   * @param bool $log
   *   If TRUE logs result.
   *
   * @return array
   *   Result.
   */
  private function _securityReviewRun($checklist, $log = FALSE) {
    $results = array();
    foreach ($checklist as $module => $checks) {
      foreach ($checks as $check_name => $arguments) {
        $check_result = $this->_securityReviewRunCheck($module, $check_name, $arguments, $log);
        if (!empty($check_result)) {
          $results[$module][$check_name] = $check_result;
        }
      }
    }
    return $results;
  }

  /**
   * Run a single Security Review check.
   */
  private function _securityReviewRunCheck($module, $check_name, $check, $log, $store = FALSE) {
    $return = array('result' => NULL);
    if (isset($check['file'])) {
      // Handle Security Review defining checks for other modules.
      if (isset($check['module'])) {
        $module = $check['module'];
      }
      module_load_include('inc', $module, $check['file']);
    }
    $function = $check['callback'];
    if (method_exists($this, $function)) {

      $return = call_user_func(array(
        __NAMESPACE__ . '\SecurityReviewController',
        $function,
      ));

    }
    $check_result = array_merge($check, $return);
    $check_result['lastrun'] = REQUEST_TIME;

    // Do not log if result is NULL.
    if ($log && !is_null($return['result'])) {
      $variables = array('@name' => $check_result['title']);
      if ($check_result['result']) {
        $this->_securityReviewLog($module, $check_name, '@name check passed', $variables, WATCHDOG_INFO);
      }
      else {
        $this->_securityReviewLog($module, $check_name, '@name check failed', $variables, WATCHDOG_ERROR);
      }
    }
    return $check_result;
  }

  /**
   * Log results.
   *
   * @param string $module
   *   Module.
   * @param string $check_name
   *   Check name.
   * @param string $message
   *   Message.
   * @param array $variables
   *   Variables.
   * @param string $type
   *   Event type.
   *
   * @todo needs review.
   */
  private function _securityReviewLog($module, $check_name, $message, $variables, $type) {
    \Drupal::moduleHandler()
      ->invokeAll('acquia_spi_security_review_log', [
        $module,
        $check_name,
        $message,
        $variables,
        $type,
      ]);
  }

  /**
   * Helper function allows for collection of this file's security checks.
   */
  private function securityReviewGetChecks() {
    // Use Security Review's checks if available.
    if (\Drupal::moduleHandler()->moduleExists('security_review') && function_exists('security_review_security_checks')) {
      return \Drupal::moduleHandler()->invokeAll('security_checks');
    }
    else {
      return $this->securityReviewSecurityChecks();
    }
  }

  /**
   * Checks for acquia_spi_security_review_get_checks().
   *
   * @return array
   *   Result.
   */
  private function securityReviewSecurityChecks() {
    // @todo need review
    $checks['file_perms'] = array(
      'title' => t('File system permissions'),
      'callback' => 'acquia_spi_security_review_check_file_perms',
      'success' => t('Drupal installation files and directories (except required) are not writable by the server.'),
      'failure' => t('Some files and directories in your install are writable by the server.'),
    );
    $checks['input_formats'] = array(
      'title' => t('Text formats'),
      'callback' => 'checkInputFormats',
      'success' => t('Untrusted users are not allowed to input dangerous HTML tags.'),
      'failure' => t('Untrusted users are allowed to input dangerous HTML tags.'),
    );
    $checks['field'] = array(
    // @todo need review
      'title' => t('Content'),
      'callback' => 'acquia_spi_security_review_check_field',
      'success' => t('Dangerous tags were not found in any submitted content (fields).'),
      'failure' => t('Dangerous tags were found in submitted content (fields).'),
    );
    $checks['error_reporting'] = array(
    // @todo need review
      'title' => t('Error reporting'),
      'callback' => 'acquia_spi_security_review_check_error_reporting',
      'success' => t('Error reporting set to log only.'),
      'failure' => t('Errors are written to the screen.'),
    );
    $checks['private_files'] = array(
    // @todo need review
      'title' => t('Private files'),
      'callback' => 'acquia_spi_security_review_check_private_files',
      'success' => t('Private files directory is outside the web server root.'),
      'failure' => t('Private files is enabled but the specified directory is not secure outside the web server root.'),
    );
    // Checks dependent on dblog.
    if (\Drupal::moduleHandler()->moduleExists('dblog')) {
      $checks['query_errors'] = array(
      // @todo need review
        'title' => t('Database errors'),
        'callback' => 'acquia_spi_security_review_check_query_errors',
        'success' => t('Few query errors from the same IP.'),
        'failure' => t('Query errors from the same IP. These may be a SQL injection attack or an attempt at information disclosure.'),
      );

      $checks['failed_logins'] = array(
      // @todo need review
        'title' => t('Failed logins'),
        'callback' => 'acquia_spi_security_review_check_failed_logins',
        'success' => t('Few failed login attempts from the same IP.'),
        'failure' => t('Failed login attempts from the same IP. These may be a brute-force attack to gain access to your site.'),
      );
    }
    $checks['upload_extensions'] = array(
      'title' => t('Allowed upload extensions'),
      'callback' => 'checkUploadExtensions',
      'success' => t('Only safe extensions are allowed for uploaded files and images.'),
      'failure' => t('Unsafe file extensions are allowed in uploads.'),
    );
    $checks['admin_permissions'] = array(
      'title' => t('Drupal permissions'),
      'callback' => 'checkAdminPermissions',
      'success' => t('Untrusted roles do not have administrative or trusted Drupal permissions.'),
      'failure' => t('Untrusted roles have been granted administrative or trusted Drupal permissions.'),
    );
    // Check dependent on PHP filter being enabled.
    if (\Drupal::moduleHandler()->moduleExists('php')) {
      $checks['untrusted_php'] = array(
        'title' => t('PHP access'),
        'callback' => 'checkPhpFilter',
        'success' => t('Untrusted users do not have access to use the PHP input format.'),
        'failure' => t('Untrusted users have access to use the PHP input format.'),
      );
    }
    $checks['executable_php'] = array(
      'title' => t('Executable PHP'),
      'callback' => 'checkExecutablePhp',
      'success' => t('PHP files in the Drupal files directory cannot be executed.'),
      'failure' => t('PHP files in the Drupal files directory can be executed.'),
    );
    $checks['temporary_files'] = array(
      'title' => t('Temporary files'),
      'callback' => 'checkTemporaryFiles',
      'success' => t('No sensitive temporary files were found.'),
      'failure' => t('Sensitive temporary files were found on your files system.'),
    );
    if (\Drupal::moduleHandler()->moduleExists('views')) {
      $checks['views_access'] = array(
        'title' => t('Views access'),
        'callback' => 'checkViewsAccess',
        'success' => t('Views are access controlled.'),
        'failure' => t('There are Views that do not provide any access checks.'),
      );
    }

    return array('security_review' => $checks);
  }

  /**
   * Check for sensitive temporary files like settings.php~.
   *
   * @param int $last_check
   *   Timestamp.
   *
   * @return array
   *   Result.
   */
  private function checkTemporaryFiles($last_check = NULL) {
    $result = TRUE;
    $check_result_value = array();
    $files = array();
    $site_path = \Drupal::service('site.path');

    $dir = scandir(DRUPAL_ROOT . '/' . $site_path . '/');
    foreach ($dir as $file) {
      // Set full path to only files.
      if (!is_dir($file)) {
        $files[] = DRUPAL_ROOT . '/' . $site_path . '/' . $file;
      }
    }
    \Drupal::moduleHandler()->alter('security_review_temporary_files', $files);
    foreach ($files as $path) {
      $matches = array();
      if (file_exists($path) && preg_match('/.*(~|\.sw[op]|\.bak|\.orig|\.save)$/', $path, $matches) !== FALSE && !empty($matches)) {
        $result = FALSE;
        $check_result_value[] = $path;
      }
    }
    return array('result' => $result, 'value' => $check_result_value);
  }

  /**
   * Check views access.
   *
   * @param int $last_check
   *   Timestamp.
   *
   * @return array
   *   Result.
   */
  private function checkViewsAccess($last_check = NULL) {
    $result = TRUE;
    $check_result_value = array();
    // Need review.
    $views = Views::getEnabledViews();
    foreach ($views as $view) {
      $view_name = $view->get('originalId');
      $view_display = $view->get('display');
      // Access is set in display options of a display.
      foreach ($view_display as $display_name => $display) {
        if (isset($display['display_options']['access']) && $display['display_options']['access']['type'] == 'none') {
          $check_result_value[$view_name][] = $display_name;
        }
      }
    }
    if (!empty($check_result_value)) {
      $result = FALSE;
    }
    return array('result' => $result, 'value' => $check_result_value);
  }

  /**
   * Check if PHP files written to the files directory can be executed.
   */
  private function checkExecutablePhp($last_check = NULL) {
    global $base_url;
    $result = TRUE;
    $check_result_value = array();

    $message = 'Security review test ' . date('Ymdhis');
    $content = "<?php\necho '" . $message . "';";
    $directory = Settings::get('file_public_path');
    if (empty($directory)) {
      $directory = DrupalKernel::findSitePath(\Drupal::request()) . DIRECTORY_SEPARATOR . 'files';
    }
    if (empty($directory)) {
      $directory = 'sites/default/files';
    }
    $file = '/security_review_test.php';
    if ($file_create = @fopen('./' . $directory . $file, 'w')) {
      fwrite($file_create, $content);
      fclose($file_create);
    }

    try {
      $response = \Drupal::httpClient()
        ->post($base_url . '/' . $directory . $file);
      if ($response->getStatusCode() == 200 && $response->getBody()
          ->read(100) === $message
      ) {
        $result = FALSE;
        $check_result_value[] = 'executable_php';
      }

    }
    catch (\Exception $e) {
      $response = $e->getResponse();
    }

    if (file_exists('./' . $directory . $file)) {
      @unlink('./' . $directory . $file);
    }
    // Check for presence of the .htaccess file and if the contents are correct.
    if (!file_exists($directory . '/.htaccess')) {
      $result = FALSE;
      $check_result_value[] = 'missing_htaccess';
    }
    elseif (!function_exists('file_htaccess_lines')) {
      $result = FALSE;
      $check_result_value[] = 'outdated_core';
    }
    else {
      $contents = file_get_contents($directory . '/.htaccess');
      // Text from includes/file.inc.
      $expected = file_htaccess_lines(FALSE);
      if ($contents !== $expected) {
        $result = FALSE;
        $check_result_value[] = 'incorrect_htaccess';
      }
      if (is_writable($directory . '/.htaccess')) {
        // Don't modify $result.
        $check_result_value[] = 'writable_htaccess';
      }
    }

    return array('result' => $result, 'value' => $check_result_value);
  }

  /**
   * Check upload extensions.
   *
   * @param int $last_check
   *   Last check.
   *
   * @return array
   *   Result.
   */
  private function checkUploadExtensions($last_check = NULL) {
    $check_result = TRUE;
    $check_result_value = array();
    $unsafe_extensions = $this->unsafeExtensions();
    $fields = FieldConfig::loadMultiple();
    foreach ($fields as $field) {
      $dependencies = $field->get('dependencies');
      if (isset($dependencies) && !empty($dependencies['module'])) {
        foreach ($dependencies['module'] as $module) {
          if ($module == 'image' || $module == 'file') {
            foreach ($unsafe_extensions as $unsafe_extension) {
              // Check instance file_extensions.
              if (strpos($field->getSetting('file_extensions'), $unsafe_extension) !== FALSE) {
                // Found an unsafe extension.
                $check_result_value[$field->getName()][$field->getTargetBundle()] = $unsafe_extension;
                $check_result = FALSE;
              }
            }
          }
        }
      }
    }
    return array('result' => $check_result, 'value' => $check_result_value);
  }

  /**
   * Check input formats fo unsafe tags.
   *
   * Check for formats that either do not have HTML filter that can be used by
   * untrusted users, or if they do check if unsafe tags are allowed.
   *
   * @return array
   *   Result.
   */
  private function checkInputFormats() {
    $result = TRUE;
    $formats = \Drupal::entityManager()
      ->getStorage('filter_format')
      ->loadByProperties(array('status' => TRUE));
    $check_result_value = array();

    // Check formats that are accessible by untrusted users.
    // $untrusted_roles = acquia_spi_security_review_untrusted_roles();
    $untrusted_roles = $this->untrustedRoles();
    $untrusted_roles = array_keys($untrusted_roles);
    foreach ($formats as $id => $format) {
      $format_roles = filter_get_roles_by_format($format);
      $intersect = array_intersect(array_keys($format_roles), $untrusted_roles);
      if (!empty($intersect)) {
        $filters = $formats[$id]->get('filters');
        // Check format for enabled HTML filter.
        if (in_array('filter_html', array_keys($filters)) && $filters['filter_html']['status'] == 1) {
          $filter = $filters['filter_html'];
          // Check for unsafe tags in allowed tags.
          $allowed_tags = $filter['settings']['allowed_html'];
          $unsafe_tags = $this->unsafeTags();
          foreach ($unsafe_tags as $tag) {
            if (strpos($allowed_tags, '<' . $tag . '>') !== FALSE) {
              // Found an unsafe tag.
              $check_result_value['tags'][$id] = $tag;
            }
          }
        }
        elseif (!in_array('filter_html_escape', array_keys($filters)) || !$filters['filter_html_escape']['status'] == 1) {
          // Format is usable by untrusted users but does not contain
          // the HTML Filter or the HTML escape.
          $check_result_value['formats'][$id] = $format;
        }
      }
    }

    if (!empty($check_result_value)) {
      $result = FALSE;
    }
    return array('result' => $result, 'value' => $check_result_value);
  }

  /**
   * Look for admin permissions granted to untrusted roles.
   */
  private function checkAdminPermissions() {
    $result = TRUE;
    $check_result_value = array();
    $mapping_role = array('anonymous' => 1, 'authenticated' => 2);
    $untrusted_roles = $this->untrustedRoles();

    // Collect permissions marked as for trusted users only.
    $all_permissions = \Drupal::service('user.permissions')->getPermissions();
    $all_keys = array_keys($all_permissions);

    // Get permissions for untrusted roles.
    $untrusted_permissions = user_role_permissions(array_keys($untrusted_roles));
    foreach ($untrusted_permissions as $rid => $permissions) {
      $intersect = array_intersect($all_keys, $permissions);
      foreach ($intersect as $permission) {
        if (!empty($all_permissions[$permission]['restrict access'])) {
          $check_result_value[$mapping_role[$rid]][] = $permission;
        }
      }
    }

    if (!empty($check_result_value)) {
      $result = FALSE;
    }
    return array('result' => $result, 'value' => $check_result_value);
  }

  /**
   * Check if untrusted users can use PHP Filter format.
   *
   * @return array
   *   Result.
   */
  protected function checkPhpFilter() {
    $result = TRUE;
    $check_result_value = array();
    $formats = \Drupal::entityManager()
      ->getStorage('filter_format')
      ->loadByProperties(array('status' => TRUE));
    // Check formats that are accessible by untrusted users.
    $untrusted_roles = $this->untrustedRoles();
    $untrusted_roles = array_keys($untrusted_roles);
    foreach ($formats as $id => $format) {
      $format_roles = filter_get_roles_by_format($format);
      $intersect = array_intersect(array_keys($format_roles), $untrusted_roles);
      if (!empty($intersect)) {
        // Untrusted users can use this format.
        $filters = $formats[$id]->get('filters');
        // Check format for enabled PHP filter.
        if (in_array('php_code', array_keys($filters)) && $filters['php_code']['status'] == 1) {
          $result = FALSE;
          $check_result_value['formats'][$id] = $format;
        }
      }
    }

    return ['result' => $result, 'value' => $check_result_value];
  }

  /**
   * Helper function defines file extensions considered unsafe.
   */
  public function unsafeExtensions() {
    return [
      'swf',
      'exe',
      'html',
      'htm',
      'php',
      'phtml',
      'py',
      'js',
      'vb',
      'vbe',
      'vbs',
    ];
  }

  /**
   * Helper function defines HTML tags that are considered unsafe.
   *
   * Based on wysiwyg_filter_get_elements_blacklist().
   */
  public function unsafeTags() {
    return [
      'applet',
      'area',
      'audio',
      'base',
      'basefont',
      'body',
      'button',
      'comment',
      'embed',
      'eval',
      'form',
      'frame',
      'frameset',
      'head',
      'html',
      'iframe',
      'image',
      'img',
      'input',
      'isindex',
      'label',
      'link',
      'map',
      'math',
      'meta',
      'noframes',
      'noscript',
      'object',
      'optgroup',
      'option',
      'param',
      'script',
      'select',
      'style',
      'svg',
      'table',
      'td',
      'textarea',
      'title',
      'video',
      'vmlframe',
    ];
  }

  /**
   * Helper function for user-defined or default untrusted Drupal roles.
   *
   * @return array
   *   An associative array with the role id as the key and the role name as
   *   value.
   */
  public function untrustedRoles() {
    $defaults = $this->defaultUntrustedRoles();
    $roles = $defaults;
    return array_filter($roles);
  }

  /**
   * Helper function defines the default untrusted Drupal roles.
   */
  public function defaultUntrustedRoles() {
    $roles = array(AccountInterface::ANONYMOUS_ROLE => 'anonymous user');
    // Need set default value.
    $user_register = \Drupal::config('user.settings')->get('register');
    // If visitors are allowed to create accounts they are considered untrusted.
    if ($user_register != USER_REGISTER_ADMINISTRATORS_ONLY) {
      $roles[AccountInterface::AUTHENTICATED_ROLE] = 'authenticated user';
    }
    return $roles;
  }

}
